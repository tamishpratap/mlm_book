import { useState, useEffect, useCallback, useRef } from 'react';
import { useParams, Link, useSearchParams } from 'react-router-dom';
import {
  MessageSquare,
  Zap,
  Bell,
  ArrowLeft,
  Search,
  Star,
  Pin,
  Paperclip,
  Send,
  User,
  Plus,
  Trash2,
  X,
  FileText,
  HelpCircle,
  CheckCheck,
  Check,
  ExternalLink,
} from 'lucide-react';
import businessApi from '../../api/businessApi';
import BusinessNotificationModal from '../../components/business/BusinessNotificationModal';
import MemberAvatar from '../../components/common/MemberAvatar';
import { ModalPortal } from '../../components/common/ModalPortal';
import { useImageModeration } from '../../hooks/useImageModeration';
import { MODERATION_CONTEXTS, POLICY_ACTIONS } from '../../config/imageModerationPolicy';
import { ImageModerationScanModal } from '../../components/moderation/ImageModerationScanModal';

function getInitials(name) {
  if (!name) return 'U';
  const parts = name.trim().split(/\s+/);
  return parts.slice(0, 2).map((p) => p[0].toUpperCase()).join('') || 'U';
}

function formatMessageTime(dateString) {
  if (!dateString) return '';
  const d = new Date(dateString);
  return d.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

function formatRelativeTime(dateString) {
  if (!dateString) return '';
  const date = new Date(dateString);
  const now = new Date();
  const diffInSec = Math.floor((now - date) / 1000);

  if (diffInSec < 60) return 'Just now';
  if (diffInSec < 3600) return `${Math.floor(diffInSec / 60)}m ago`;
  if (diffInSec < 86400) return `${Math.floor(diffInSec / 3600)}h ago`;
  if (diffInSec < 604800) return `${Math.floor(diffInSec / 86400)}d ago`;
  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
}

export function BusinessInboxPage() {
  const { slug } = useParams();
  const [searchParams, setSearchParams] = useSearchParams();

  const [inboxData, setInboxData] = useState(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);

  // Active filters
  const activeFilter = searchParams.get('filter') || 'all';
  const [searchQuery, setSearchQuery] = useState(searchParams.get('q') || '');

  // Active conversation state
  const [selectedConvId, setSelectedConvId] = useState(null);
  const [activeConversation, setActiveConversation] = useState(null);
  const [messages, setMessages] = useState([]);
  const [isLoadingMessages, setIsLoadingMessages] = useState(false);

  // Composer state
  const [messageText, setMessageText] = useState('');
  const [attachmentFile, setAttachmentFile] = useState(null);
  const [isSending, setIsSending] = useState(false);
  const [sendError, setSendError] = useState(null);
  const fileInputRef = useRef(null);
  const messagesEndRef = useRef(null);

  const {
    scanImage,
    progress: modProgress,
    decision: modDecision,
    error: modError,
    cancel: cancelModeration,
    reset: resetModeration,
  } = useImageModeration({ context: MODERATION_CONTEXTS.MESSAGE_IMAGE });

  const [isScanModalOpen, setIsScanModalOpen] = useState(false);
  const [scanStatus, setScanStatus] = useState('IDLE');
  const [blockedItems, setBlockedItems] = useState([]);

  // Modals
  const [isQuickRepliesModalOpen, setIsQuickRepliesModalOpen] = useState(false);
  const [isNotificationsModalOpen, setIsNotificationsModalOpen] = useState(false);

  // Quick reply creation form state
  const [qrTitle, setQrTitle] = useState('');
  const [qrShortcut, setQrShortcut] = useState('');
  const [qrMessage, setQrMessage] = useState('');
  const [isSavingQr, setIsSavingQr] = useState(false);
  const [qrError, setQrError] = useState(null);

  // Fetch inbox conversations
  const fetchInbox = useCallback(() => {
    if (!slug) return Promise.resolve(null);
    const params = {
      filter: activeFilter,
      q: searchQuery.trim() || undefined,
    };
    return businessApi.getInbox(slug, params);
  }, [slug, activeFilter, searchQuery]);

  useEffect(() => {
    let isMounted = true;
    fetchInbox()
      .then((res) => {
        if (isMounted && res) {
          setInboxData(res);
          setError(null);
        }
      })
      .catch((err) => {
        if (isMounted) {
          setError(err.response?.data?.message || 'Failed to load Business Inbox.');
        }
      })
      .finally(() => {
        if (isMounted) setIsLoading(false);
      });

    return () => {
      isMounted = false;
    };
  }, [fetchInbox]);

  // Load specific conversation messages
  const loadConversation = useCallback(
    async (convId) => {
      setSelectedConvId(convId);
      setIsLoadingMessages(true);
      setSendError(null);

      try {
        const res = await businessApi.getConversation(slug, convId);
        if (res && res.success) {
          setActiveConversation(res.conversation);
          setMessages(res.messages || []);
          // Also refresh inbox list to update unread badge counts
          fetchInbox().then((inboxRes) => inboxRes && setInboxData(inboxRes));
        }
      } catch (err) {
        setSendError(err.response?.data?.message || 'Failed to load conversation.');
      } finally {
        setIsLoadingMessages(false);
      }
    },
    [slug, fetchInbox]
  );

  // Auto-scroll messages to bottom
  useEffect(() => {
    if (messagesEndRef.current) {
      messagesEndRef.current.scrollIntoView({ behavior: 'smooth' });
    }
  }, [messages]);

  const handleFilterChange = (filterName) => {
    const newParams = new URLSearchParams(searchParams);
    newParams.set('filter', filterName);
    setSearchParams(newParams);
  };

  const handleSearchSubmit = (e) => {
    e.preventDefault();
    const newParams = new URLSearchParams(searchParams);
    if (searchQuery.trim()) {
      newParams.set('q', searchQuery.trim());
    } else {
      newParams.delete('q');
    }
    setSearchParams(newParams);
  };

  const handleRemoveBlockedAttachment = () => {
    setAttachmentFile(null);
    if (fileInputRef.current) fileInputRef.current.value = '';
    setBlockedItems([]);
    setIsScanModalOpen(false);
    setScanStatus('IDLE');
    resetModeration();
  };

  const handleSendMessage = async (e) => {
    e.preventDefault();
    if (!selectedConvId || isSending) return;
    if (!messageText.trim() && !attachmentFile) return;

    setIsSending(true);
    setSendError(null);

    // Pre-flight Client-Side Moderation: Images only; PDFs, DOCs, and other files bypass moderation safely
    const isImageAttachment = attachmentFile && attachmentFile.type && attachmentFile.type.startsWith('image/');
    if (isImageAttachment) {
      setIsScanModalOpen(true);
      setScanStatus('SCANNING');
      setBlockedItems([]);

      try {
        const decision = await scanImage(attachmentFile);

        if (decision.action === POLICY_ACTIONS.BLOCK) {
          setScanStatus('BLOCKED');
          setBlockedItems([{ file: attachmentFile, fileName: attachmentFile.name, decision }]);
          setSendError(decision.userMessage);
          setIsSending(false);
          return;
        }

        if (decision.isSystemError) {
          setScanStatus('ERROR');
          setSendError(decision.userMessage);
          setIsSending(false);
          return;
        }

        setIsScanModalOpen(false);
        setScanStatus('IDLE');
      } catch {
        setScanStatus('ERROR');
        setSendError('Unable to verify image safety. Please try again.');
        setIsSending(false);
        return;
      }
    }

    const formData = new FormData();
    if (messageText.trim()) formData.append('message', messageText.trim());
    if (attachmentFile) formData.append('attachment', attachmentFile);

    try {
      const res = await businessApi.sendMessage(slug, selectedConvId, formData);
      if (res && res.success) {
        setMessageText('');
        setAttachmentFile(null);
        if (fileInputRef.current) fileInputRef.current.value = '';
        setMessages((prev) => [...prev, res.message]);
        // Update inbox list for latest message ordering
        fetchInbox().then((inboxRes) => inboxRes && setInboxData(inboxRes));
      }
    } catch (err) {
      setSendError(err.response?.data?.message || 'Failed to send message.');
    } finally {
      setIsSending(false);
    }
  };

  const handleToggleStar = async () => {
    if (!selectedConvId) return;
    try {
      const res = await businessApi.toggleStarConversation(slug, selectedConvId);
      if (res && res.success) {
        setActiveConversation((prev) => (prev ? { ...prev, is_starred: res.is_starred } : prev));
        fetchInbox().then((inboxRes) => inboxRes && setInboxData(inboxRes));
      }
    } catch {
      alert('Failed to update star state.');
    }
  };

  const handleTogglePin = async () => {
    if (!selectedConvId) return;
    try {
      const res = await businessApi.togglePinConversation(slug, selectedConvId);
      if (res && res.success) {
        setActiveConversation((prev) => (prev ? { ...prev, is_pinned: res.is_pinned } : prev));
        fetchInbox().then((inboxRes) => inboxRes && setInboxData(inboxRes));
      }
    } catch {
      alert('Failed to update pin state.');
    }
  };

  const handleUpdateStatus = async (status) => {
    if (!selectedConvId) return;
    try {
      const res = await businessApi.updateConversationStatus(slug, selectedConvId, status);
      if (res && res.success) {
        setActiveConversation((prev) => (prev ? { ...prev, status: res.status } : prev));
        fetchInbox().then((inboxRes) => inboxRes && setInboxData(inboxRes));
      }
    } catch {
      alert('Failed to update conversation status.');
    }
  };

  const handleMessageRequest = async (action) => {
    if (!selectedConvId) return;
    try {
      const res = await businessApi.handleMessageRequest(slug, selectedConvId, action);
      if (res && res.success) {
        alert(res.message);
        loadConversation(selectedConvId);
      }
    } catch {
      alert('Failed to process message request.');
    }
  };

  const handleSaveQuickReply = async (e) => {
    e.preventDefault();
    if (!qrTitle.trim() || !qrMessage.trim() || isSavingQr) return;

    setIsSavingQr(true);
    setQrError(null);

    try {
      await businessApi.storeQuickReply(slug, {
        title: qrTitle.trim(),
        shortcut: qrShortcut.trim() || undefined,
        message: qrMessage.trim(),
      });
      setQrTitle('');
      setQrShortcut('');
      setQrMessage('');
      fetchInbox().then((res) => res && setInboxData(res));
      setIsQuickRepliesModalOpen(false);
      alert('Quick Reply saved successfully!');
    } catch (err) {
      setQrError(err.response?.data?.message || 'Failed to save Quick Reply.');
    } finally {
      setIsSavingQr(false);
    }
  };

  const handleDeleteQuickReply = async (qrId) => {
    if (!window.confirm('Are you sure you want to delete this Quick Reply?')) return;
    try {
      await businessApi.deleteQuickReply(slug, qrId);
      fetchInbox().then((res) => res && setInboxData(res));
    } catch {
      alert('Failed to delete Quick Reply.');
    }
  };

  const handleApplyQuickReply = (text) => {
    if (!text) return;
    setMessageText((prev) => (prev ? `${prev} ${text}` : text));
  };

  const handleMarkNotificationsRead = async () => {
    try {
      await businessApi.markInboxNotificationsRead(slug);
      fetchInbox().then((res) => res && setInboxData(res));
    } catch {
      // Ignore
    }
  };

  if (isLoading) {
    return (
      <div style={{ textAlign: 'center', padding: '60px', color: 'var(--color-text-secondary)' }}>
        Loading Business Inbox...
      </div>
    );
  }

  if (error || !inboxData?.business_page) {
    return (
      <div className="card" style={{ maxWidth: '800px', margin: '40px auto', padding: '32px', textAlign: 'center' }}>
        <h2 style={{ fontSize: '18px', color: '#dc2626', marginBottom: '8px' }}>Business Inbox Access Restricted</h2>
        <p style={{ color: 'var(--color-text-secondary)', marginBottom: '20px' }}>
          {error || 'You do not have permission to view this Business Page Inbox.'}
        </p>
        <Link to={`/member/business-pages/${slug}`} className="member-button member-button--primary">
          Back to Business Page
        </Link>
      </div>
    );
  }

  const page = inboxData.business_page;
  const conversations = inboxData.conversations?.data || (Array.isArray(inboxData.conversations) ? inboxData.conversations : []);
  const quickReplies = inboxData.quick_replies || [];
  const notifications = inboxData.notifications || [];
  const unreadCount = Number(inboxData.unread_count ?? 0);
  const canReply = Boolean(inboxData.can_reply);
  const unreadNotificationsCount = notifications.filter((n) => !n.is_read).length;

  return (
    <div className="biz-page">
      {/* Header */}
      <header className="biz-header" style={{ marginBottom: '16px' }}>
        <div className="biz-header__info">
          <h1 style={{ display: 'flex', alignItems: 'center', gap: '8px', flexWrap: 'wrap' }}>
            <MessageSquare size={24} color="#4f7df3" aria-hidden="true" />
            <span>Business Inbox - {page.page_name}</span>
            {unreadCount > 0 && (
              <span
                className="biz-badge biz-badge--category"
                style={{ background: '#e53e3e', color: '#fff', verticalAlign: 'middle', marginLeft: '6px' }}
              >
                {unreadCount} New
              </span>
            )}
          </h1>
          <p>Manage customer conversations, saved quick replies, and business message notifications.</p>
        </div>
        <div className="biz-header__actions">
          <button
            type="button"
            className="member-button member-button--secondary"
            onClick={() => setIsQuickRepliesModalOpen(true)}
          >
            <Zap size={15} color="#f7b940" />
            <span>Quick Replies</span>
          </button>
          <button
            type="button"
            className="member-button member-button--secondary"
            onClick={() => {
              setIsNotificationsModalOpen(true);
              handleMarkNotificationsRead();
            }}
          >
            <Bell size={15} />
            <span>Notifications</span>
            {unreadNotificationsCount > 0 && (
              <span style={{ background: '#e53e3e', color: '#fff', borderRadius: '50%', padding: '2px 6px', fontSize: '11px' }}>
                {unreadNotificationsCount}
              </span>
            )}
          </button>
          <Link to={`/member/business-pages/${page.slug}`} className="member-button member-button--primary">
            <ArrowLeft size={15} />
            <span>Back to Profile</span>
          </Link>
        </div>
      </header>

      {/* Messenger Split 3-Column Layout */}
      <div
        style={{
          display: 'grid',
          gridTemplateColumns: 'minmax(280px, 320px) 1fr minmax(240px, 280px)',
          gap: '16px',
          minHeight: '620px',
          alignItems: 'start',
        }}
      >
        {/* Left Panel: Conversations List */}
        <div
          className="biz-info-card"
          style={{
            padding: '14px',
            display: 'flex',
            flexDirection: 'column',
            gap: '12px',
            height: '620px',
            overflowY: 'auto',
          }}
        >
          {/* Filter Tabs */}
          <div
            style={{
              display: 'flex',
              gap: '6px',
              overflowX: 'auto',
              paddingBottom: '4px',
              borderBottom: '1px solid #e7ecf4',
            }}
          >
            {['all', 'unread', 'starred', 'requests', 'archived', 'closed'].map((f) => (
              <button
                key={f}
                type="button"
                className={`biz-nav-tab ${activeFilter === f ? 'is-active' : ''}`}
                style={{
                  padding: '6px 10px',
                  fontSize: '12px',
                  background: activeFilter === f ? '#edf3ff' : 'transparent',
                  border: 'none',
                  borderRadius: '8px',
                  cursor: 'pointer',
                  fontWeight: activeFilter === f ? 700 : 500,
                  color: activeFilter === f ? '#4f7df3' : '#687386',
                  textTransform: 'capitalize',
                }}
                onClick={() => handleFilterChange(f)}
              >
                {f}
              </button>
            ))}
          </div>

          {/* Search Conversations Bar */}
          <form onSubmit={handleSearchSubmit}>
            <div style={{ position: 'relative' }}>
              <input
                type="text"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="biz-search-input"
                style={{ fontSize: '13px', padding: '8px 12px 8px 32px', width: '100%' }}
                placeholder="Search customer or message..."
              />
              <Search
                size={14}
                color="#98a2b3"
                style={{ position: 'absolute', left: '10px', top: '50%', transform: 'translateY(-50%)' }}
              />
            </div>
          </form>

          {/* Conversations Items List */}
          <div style={{ display: 'flex', flexDirection: 'column', gap: '8px', flex: 1 }}>
            {conversations.length === 0 ? (
              <div style={{ textAlign: 'center', padding: '40px 10px', color: '#98a2b3' }}>
                <MessageSquare size={28} style={{ margin: '0 auto 8px auto', opacity: 0.5 }} />
                <p style={{ fontSize: '13px', margin: 0 }}>No conversations in {activeFilter}.</p>
              </div>
            ) : (
              conversations.map((conv) => {
                const customer = conv.customer || {};
                const lastMsg = conv.latest_message || conv.latestMessage;
                const isSelected = selectedConvId === conv.id;
                const unread = conv.unread_count_for_business ?? 0;

                return (
                  <div
                    key={conv.id}
                    onClick={() => loadConversation(conv.id)}
                    style={{
                      padding: '10px 12px',
                      borderRadius: '12px',
                      border: isSelected ? '2px solid #4f7df3' : '1px solid #e7ecf4',
                      cursor: 'pointer',
                      transition: 'all 0.2s',
                      background: isSelected ? '#edf3ff' : unread > 0 ? '#f0f7ff' : '#fff',
                    }}
                  >
                    <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: '8px' }}>
                      <div style={{ display: 'flex', alignItems: 'center', gap: '10px', minWidth: 0 }}>
                        <MemberAvatar member={customer} size={36} />

                        <div style={{ minWidth: 0, flex: 1 }}>
                          <strong
                            style={{
                              fontSize: '13.5px',
                              color: '#1d2738',
                              display: 'block',
                              whiteSpace: 'nowrap',
                              overflow: 'hidden',
                              textOverflow: 'ellipsis',
                            }}
                          >
                            {customer.name || 'Customer'}
                          </strong>
                          <span
                            style={{
                              fontSize: '11.5px',
                              color: '#687386',
                              display: 'block',
                              whiteSpace: 'nowrap',
                              overflow: 'hidden',
                              textOverflow: 'ellipsis',
                            }}
                          >
                            {lastMsg
                              ? `${lastMsg.sender_type === 'business' ? 'You: ' : ''}${lastMsg.message || (lastMsg.attachment_path ? '📎 Attachment' : '')}`
                              : 'No messages yet'}
                          </span>
                        </div>
                      </div>

                      <div style={{ textAlign: 'right', flexShrink: 0 }}>
                        {unread > 0 && (
                          <span
                            style={{
                              background: '#4f7df3',
                              color: '#fff',
                              fontSize: '11px',
                              fontWeight: 700,
                              borderRadius: '50%',
                              padding: '2px 6px',
                              display: 'inline-block',
                            }}
                          >
                            {unread}
                          </span>
                        )}
                        <span style={{ fontSize: '10.5px', color: '#98a2b3', display: 'block', marginTop: '2px' }}>
                          {formatRelativeTime(conv.last_message_at)}
                        </span>
                        {conv.is_starred && <Star size={12} color="#f7b940" fill="#f7b940" style={{ marginTop: '2px' }} />}
                        {conv.is_pinned && <Pin size={12} color="#4f7df3" fill="#4f7df3" style={{ marginTop: '2px', marginLeft: '2px' }} />}
                      </div>
                    </div>
                  </div>
                );
              })
            )}
          </div>
        </div>

        {/* Middle Panel: Chat Thread Window */}
        <div
          className="biz-info-card"
          style={{
            padding: 0,
            display: 'flex',
            flexDirection: 'column',
            height: '620px',
            overflow: 'hidden',
            background: '#fff',
          }}
        >
          {/* Active Conversation Header */}
          <div
            style={{
              padding: '14px 18px',
              borderBottom: '1px solid #e7ecf4',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'space-between',
              background: '#fafbfc',
            }}
          >
            <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
              {activeConversation?.customer ? (
                <MemberAvatar member={activeConversation.customer} size={40} />
              ) : (
                <div
                  style={{
                    width: '40px',
                    height: '40px',
                    borderRadius: '50%',
                    background: '#edf3ff',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    color: '#4f7df3',
                  }}
                >
                  <User size={18} />
                </div>
              )}

              <div>
                <strong style={{ fontSize: '15px', color: '#1d2738', display: 'block' }}>
                  {activeConversation?.customer?.name || 'Select a Conversation'}
                </strong>
                <span style={{ fontSize: '12px', color: '#98a2b3' }}>
                  {activeConversation?.customer
                    ? `@${activeConversation.customer.user_id || 'customer'}`
                    : 'Click any conversation on the left to start chatting'}
                </span>
              </div>
            </div>

            {activeConversation && (
              <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                <button
                  type="button"
                  onClick={handleToggleStar}
                  style={{
                    background: 'transparent',
                    border: '1px solid #e7ecf4',
                    borderRadius: '8px',
                    padding: '6px 8px',
                    cursor: 'pointer',
                  }}
                  title={activeConversation.is_starred ? 'Unstar conversation' : 'Star conversation'}
                >
                  <Star
                    size={16}
                    color="#f7b940"
                    fill={activeConversation.is_starred ? '#f7b940' : 'none'}
                  />
                </button>
                <button
                  type="button"
                  onClick={handleTogglePin}
                  style={{
                    background: 'transparent',
                    border: '1px solid #e7ecf4',
                    borderRadius: '8px',
                    padding: '6px 8px',
                    cursor: 'pointer',
                  }}
                  title={activeConversation.is_pinned ? 'Unpin conversation' : 'Pin conversation'}
                >
                  <Pin
                    size={16}
                    color="#4f7df3"
                    fill={activeConversation.is_pinned ? '#4f7df3' : 'none'}
                  />
                </button>
                <select
                  value={activeConversation.status || 'active'}
                  onChange={(e) => handleUpdateStatus(e.target.value)}
                  className="biz-filter-select"
                  style={{ fontSize: '12px', padding: '4px 8px' }}
                >
                  <option value="active">Active</option>
                  <option value="archived">Archived</option>
                  <option value="closed">Closed</option>
                </select>
              </div>
            )}
          </div>

          {/* Messages Thread Scroll Box */}
          <div
            style={{
              flex: 1,
              padding: '18px',
              overflowY: 'auto',
              display: 'flex',
              flexDirection: 'column',
              gap: '12px',
              background: '#f8fafc',
            }}
          >
            {!activeConversation ? (
              <div style={{ margin: 'auto', textAlign: 'center', color: '#98a2b3' }}>
                <MessageSquare size={36} style={{ margin: '0 auto 8px auto', opacity: 0.4 }} />
                <h3 style={{ fontSize: '16px', color: '#1d2738', margin: '0 0 4px 0' }}>No Conversation Selected</h3>
                <p style={{ fontSize: '13px', margin: 0 }}>
                  Select a customer conversation from the list to view chat history and respond!
                </p>
              </div>
            ) : isLoadingMessages ? (
              <div style={{ margin: 'auto', textAlign: 'center', color: '#98a2b3', fontSize: '13px' }}>
                Loading conversation messages...
              </div>
            ) : messages.length === 0 ? (
              <div style={{ margin: 'auto', textAlign: 'center', color: '#98a2b3', fontSize: '13px' }}>
                No messages yet in this conversation. Send a greeting to start chatting!
              </div>
            ) : (
              messages.map((msg) => {
                const isBiz = msg.sender_type === 'business';
                return (
                  <div
                    key={msg.id}
                    style={{
                      alignSelf: isBiz ? 'flex-end' : 'flex-start',
                      maxWidth: '75%',
                      display: 'flex',
                      flexDirection: 'column',
                      gap: '4px',
                    }}
                  >
                    <div
                      style={{
                        padding: '10px 14px',
                        borderRadius: '14px',
                        background: isBiz ? 'linear-gradient(135deg, #4f7df3 0%, #8a2be2 100%)' : '#fff',
                        color: isBiz ? '#fff' : '#1d2738',
                        border: isBiz ? 'none' : '1px solid #e7ecf4',
                        fontSize: '13.5px',
                        boxShadow: '0 1px 3px rgba(0,0,0,0.05)',
                      }}
                    >
                      {msg.message && <p style={{ margin: 0, whiteSpace: 'pre-line' }}>{msg.message}</p>}

                      {msg.attachment_path && (
                        <div style={{ marginTop: msg.message ? '8px' : 0 }}>
                          {msg.attachment_type === 'image' ? (
                            <a
                              href={msg.attachment_path.startsWith('http') ? msg.attachment_path : `/${msg.attachment_path}`}
                              target="_blank"
                              rel="noreferrer"
                            >
                              <img
                                src={msg.attachment_path.startsWith('http') ? msg.attachment_path : `/${msg.attachment_path}`}
                                alt="Attachment"
                                style={{ maxWidth: '200px', maxHeight: '200px', borderRadius: '10px', display: 'block' }}
                              />
                            </a>
                          ) : (
                            <a
                              href={msg.attachment_path.startsWith('http') ? msg.attachment_path : `/${msg.attachment_path}`}
                              target="_blank"
                              rel="noreferrer"
                              style={{
                                display: 'flex',
                                alignItems: 'center',
                                gap: '6px',
                                color: isBiz ? '#fff' : '#4f7df3',
                                fontSize: '12px',
                                textDecoration: 'underline',
                              }}
                            >
                              <FileText size={14} />
                              <span>View Attached Document</span>
                            </a>
                          )}
                        </div>
                      )}

                      <div
                        style={{
                          fontSize: '10px',
                          opacity: 0.75,
                          display: 'flex',
                          alignItems: 'center',
                          justifyContent: 'flex-end',
                          gap: '4px',
                          marginTop: '4px',
                        }}
                      >
                        <span>{formatMessageTime(msg.created_at)}</span>
                        {isBiz && (
                          msg.is_read ? <CheckCheck size={12} color="#20c875" /> : <Check size={12} />
                        )}
                      </div>
                    </div>
                  </div>
                );
              })
            )}
            <div ref={messagesEndRef} />
          </div>

          {/* Message Request Bar (For non-follower requests) */}
          {activeConversation?.status === 'pending_request' && (
            <div
              style={{
                padding: '10px 18px',
                background: 'rgba(247, 185, 64, 0.12)',
                borderTop: '1px solid rgba(247, 185, 64, 0.3)',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'space-between',
              }}
            >
              <span style={{ fontSize: '13px', color: '#b7791f', fontWeight: 600, display: 'flex', alignItems: 'center', gap: '6px' }}>
                <HelpCircle size={15} />
                <span>Message Request from Non-Follower</span>
              </span>
              <div style={{ display: 'flex', gap: '8px' }}>
                <button
                  type="button"
                  className="member-button member-button--primary"
                  style={{ padding: '4px 12px', fontSize: '12px' }}
                  onClick={() => handleMessageRequest('accept')}
                >
                  Accept
                </button>
                <button
                  type="button"
                  className="member-button member-button--secondary"
                  style={{ padding: '4px 12px', fontSize: '12px' }}
                  onClick={() => handleMessageRequest('reject')}
                >
                  Ignore
                </button>
              </div>
            </div>
          )}

          {/* Message Composer Box */}
          <form
            onSubmit={handleSendMessage}
            style={{
              padding: '12px 18px',
              borderTop: '1px solid #e7ecf4',
              background: '#fff',
            }}
          >
            {sendError && (
              <div style={{ padding: '8px 12px', background: '#fee2e2', color: '#b91c1c', borderRadius: '8px', fontSize: '12px', marginBottom: '8px' }}>
                {sendError}
              </div>
            )}

            <div style={{ display: 'flex', flexDirection: 'column', gap: '8px' }}>
              <textarea
                value={messageText}
                onChange={(e) => setMessageText(e.target.value)}
                rows={2}
                className="biz-search-input"
                style={{ height: 'auto', padding: '10px', fontSize: '13.5px' }}
                placeholder={
                  !activeConversation
                    ? 'Select a conversation to reply...'
                    : !canReply
                    ? 'Only page admins or authorized team members can reply.'
                    : 'Type your response... (Press Enter or click Send)'
                }
                disabled={!activeConversation || !canReply}
              />

              <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: '10px', flexWrap: 'wrap' }}>
                <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                  {/* File Attachment Button */}
                  <label
                    style={{
                      cursor: activeConversation && canReply ? 'pointer' : 'not-allowed',
                      padding: '6px 8px',
                      borderRadius: '8px',
                      border: '1px solid #e7ecf4',
                      display: 'flex',
                      alignItems: 'center',
                      gap: '4px',
                      fontSize: '12px',
                      color: '#687386',
                      opacity: activeConversation && canReply ? 1 : 0.5,
                    }}
                    title="Attach photo or document"
                  >
                    <Paperclip size={14} />
                    <input
                      type="file"
                      ref={fileInputRef}
                      style={{ display: 'none' }}
                      disabled={!activeConversation || !canReply}
                      onChange={(e) => setAttachmentFile(e.target.files?.[0] || null)}
                    />
                    <span>{attachmentFile ? attachmentFile.name : 'Attach'}</span>
                  </label>

                  {attachmentFile && (
                    <button
                      type="button"
                      onClick={() => {
                        setAttachmentFile(null);
                        if (fileInputRef.current) fileInputRef.current.value = '';
                      }}
                      style={{ background: 'transparent', border: 'none', cursor: 'pointer' }}
                    >
                      <X size={14} color="#ef4444" />
                    </button>
                  )}

                  {/* Quick Reply Picker Dropdown */}
                  {quickReplies.length > 0 && (
                    <select
                      className="biz-filter-select"
                      style={{ fontSize: '12px', padding: '4px 8px' }}
                      disabled={!activeConversation || !canReply}
                      onChange={(e) => {
                        handleApplyQuickReply(e.target.value);
                        e.target.value = '';
                      }}
                    >
                      <option value="">Insert Quick Reply...</option>
                      {quickReplies.map((qr) => (
                        <option key={qr.id} value={qr.message}>
                          {qr.title}
                        </option>
                      ))}
                    </select>
                  )}
                </div>

                <button
                  type="submit"
                  className="member-button member-button--primary"
                  style={{ padding: '6px 16px', fontSize: '13px' }}
                  disabled={!activeConversation || !canReply || isSending || (!messageText.trim() && !attachmentFile)}
                >
                  <Send size={14} />
                  <span>{isSending ? 'Sending...' : 'Send'}</span>
                </button>
              </div>
            </div>
          </form>
        </div>

        {/* Right Panel: Customer Profile & Quick Replies Drawer */}
        <div style={{ display: 'flex', flexDirection: 'column', gap: '16px' }}>
          {/* Customer Summary Card */}
          <div className="biz-info-card" style={{ padding: '16px' }}>
            <h3 className="biz-info-card__title" style={{ fontSize: '14px', marginBottom: '12px', display: 'flex', alignItems: 'center', gap: '6px' }}>
              <User size={16} color="#4f7df3" />
              <span>Customer Details</span>
            </h3>

            {activeConversation?.customer ? (
              <div style={{ textAlign: 'center', padding: '10px 0' }}>
                <MemberAvatar member={activeConversation.customer} size={60} style={{ margin: '0 auto 8px auto' }} />
                <strong style={{ fontSize: '14.5px', color: '#1d2738', display: 'block' }}>
                  {activeConversation.customer.name}
                </strong>
                <span style={{ fontSize: '12px', color: '#98a2b3' }}>
                  @{activeConversation.customer.user_id || 'customer'}
                </span>

                <div
                  style={{
                    marginTop: '12px',
                    paddingTop: '12px',
                    borderTop: '1px solid #e7ecf4',
                    fontSize: '12px',
                    color: '#687386',
                    textAlign: 'left',
                  }}
                >
                  <p style={{ margin: '4px 0' }}>
                    <strong>Status:</strong> <span style={{ textTransform: 'capitalize' }}>{activeConversation.status}</span>
                  </p>
                  <p style={{ margin: '4px 0' }}>
                    <strong>Customer ID:</strong> #{activeConversation.customer.id}
                  </p>
                  <div style={{ marginTop: '10px' }}>
                    <Link
                      to={`/member/people/${activeConversation.customer.id}`}
                      className="member-button member-button--secondary"
                      style={{ fontSize: '11.5px', padding: '4px 10px', width: '100%', justifyContent: 'center' }}
                    >
                      <ExternalLink size={12} />
                      <span>View Full Profile</span>
                    </Link>
                  </div>
                </div>
              </div>
            ) : (
              <div style={{ textAlign: 'center', padding: '20px 0', color: '#98a2b3', fontSize: '13px', fontStyle: 'italic' }}>
                Select a conversation to view customer details.
              </div>
            )}
          </div>

          {/* Saved Quick Replies List Box */}
          <div className="biz-info-card" style={{ padding: '16px' }}>
            <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '10px' }}>
              <h3 className="biz-info-card__title" style={{ fontSize: '14px', margin: 0, display: 'flex', alignItems: 'center', gap: '6px' }}>
                <Zap size={16} color="#f7b940" />
                <span>Quick Replies</span>
              </h3>
              {canReply && (
                <button
                  type="button"
                  onClick={() => setIsQuickRepliesModalOpen(true)}
                  style={{
                    background: 'transparent',
                    border: 'none',
                    color: '#4f7df3',
                    fontSize: '12px',
                    fontWeight: 700,
                    cursor: 'pointer',
                    display: 'flex',
                    alignItems: 'center',
                    gap: '2px',
                  }}
                >
                  <Plus size={14} />
                  <span>Add</span>
                </button>
              )}
            </div>

            <div style={{ display: 'flex', flexDirection: 'column', gap: '8px', maxHeight: '220px', overflowY: 'auto' }}>
              {quickReplies.length === 0 ? (
                <p style={{ fontSize: '12px', color: '#98a2b3', fontStyle: 'italic', margin: 0 }}>
                  No quick replies created yet.
                </p>
              ) : (
                quickReplies.map((qr) => (
                  <div
                    key={qr.id}
                    style={{
                      padding: '8px 10px',
                      borderRadius: '8px',
                      background: '#f8fafc',
                      border: '1px solid #e7ecf4',
                      fontSize: '12px',
                      cursor: 'pointer',
                    }}
                    onClick={() => handleApplyQuickReply(qr.message)}
                  >
                    <strong style={{ color: '#1d2738', display: 'block' }}>{qr.title}</strong>
                    <span
                      style={{
                        color: '#687386',
                        display: 'block',
                        whiteSpace: 'nowrap',
                        overflow: 'hidden',
                        textOverflow: 'ellipsis',
                      }}
                    >
                      {qr.message}
                    </span>
                  </div>
                ))
              )}
            </div>
          </div>
        </div>
      </div>

      {/* Quick Replies Manager Modal */}
      {isQuickRepliesModalOpen && (
        <ModalPortal isOpen={isQuickRepliesModalOpen} onClose={() => setIsQuickRepliesModalOpen(false)}>
          <div
            className="biz-modal"
            role="dialog"
            aria-modal="true"
            aria-labelledby="quick-replies-modal-title"
            style={{
              background: '#fff',
              borderRadius: '20px',
              maxWidth: '520px',
              width: '100%',
              boxShadow: '0 20px 40px rgba(0,0,0,0.2)',
              overflow: 'hidden',
              maxHeight: '90vh',
              display: 'flex',
              flexDirection: 'column',
            }}
          >
            <div
              style={{
                padding: '18px 24px',
                borderBottom: '1px solid #e7ecf4',
                display: 'flex',
                alignItems: 'center',
                justifyContent: 'space-between',
              }}
            >
              <h3 style={{ fontSize: '17px', fontWeight: 700, margin: 0, display: 'flex', alignItems: 'center', gap: '8px' }}>
                <Zap size={18} color="#f7b940" />
                <span>Saved Quick Replies</span>
              </h3>
              <button
                type="button"
                className="icon-button"
                onClick={() => setIsQuickRepliesModalOpen(false)}
                style={{ background: 'transparent', border: 'none', cursor: 'pointer' }}
              >
                <X size={18} />
              </button>
            </div>

            <div style={{ padding: '20px 24px', overflowY: 'auto', flex: 1 }}>
              {/* Existing Quick Replies List */}
              <div style={{ marginBottom: '20px' }}>
                <h4 style={{ fontSize: '13px', fontWeight: 700, color: '#1d2738', marginBottom: '8px' }}>Existing Templates</h4>
                {quickReplies.length === 0 ? (
                  <p style={{ fontSize: '12.5px', color: '#98a2b3', fontStyle: 'italic' }}>No quick replies created yet.</p>
                ) : (
                  <div style={{ display: 'flex', flexDirection: 'column', gap: '8px' }}>
                    {quickReplies.map((qr) => (
                      <div
                        key={qr.id}
                        style={{
                          padding: '10px 12px',
                          borderRadius: '10px',
                          background: '#f8fafc',
                          border: '1px solid #e7ecf4',
                          display: 'flex',
                          alignItems: 'center',
                          justifyContent: 'space-between',
                          gap: '10px',
                        }}
                      >
                        <div>
                          <strong style={{ fontSize: '13px', color: '#1d2738', display: 'block' }}>
                            {qr.title} {qr.shortcut && <span style={{ color: '#4f7df3', fontWeight: 500 }}>({qr.shortcut})</span>}
                          </strong>
                          <p style={{ fontSize: '12px', color: '#687386', margin: '2px 0 0 0' }}>{qr.message}</p>
                        </div>
                        {canReply && (
                          <button
                            type="button"
                            onClick={() => handleDeleteQuickReply(qr.id)}
                            style={{ background: 'transparent', border: 'none', cursor: 'pointer', color: '#ef4444', padding: '4px' }}
                            title="Delete Quick Reply"
                          >
                            <Trash2 size={14} />
                          </button>
                        )}
                      </div>
                    ))}
                  </div>
                )}
              </div>

              {/* Add New Quick Reply Form */}
              {canReply && (
                <form onSubmit={handleSaveQuickReply} style={{ borderTop: '1px solid #e7ecf4', paddingTop: '16px', display: 'flex', flexDirection: 'column', gap: '12px' }}>
                  <h4 style={{ fontSize: '13px', fontWeight: 700, color: '#1d2738', margin: 0 }}>Add New Quick Reply</h4>

                  {qrError && (
                    <div style={{ padding: '8px 12px', background: '#fee2e2', color: '#b91c1c', borderRadius: '8px', fontSize: '12px' }}>
                      {qrError}
                    </div>
                  )}

                  <div className="form-group">
                    <label style={{ fontSize: '12.5px', fontWeight: 700, color: '#1d2738', marginBottom: '4px', display: 'block' }}>
                      Title / Label
                    </label>
                    <input
                      type="text"
                      value={qrTitle}
                      onChange={(e) => setQrTitle(e.target.value)}
                      placeholder="e.g. Greeting, Business Hours, Pricing FAQ..."
                      className="biz-search-input"
                      required
                      maxLength={255}
                    />
                  </div>

                  <div className="form-group">
                    <label style={{ fontSize: '12.5px', fontWeight: 700, color: '#1d2738', marginBottom: '4px', display: 'block' }}>
                      Shortcut (Optional)
                    </label>
                    <input
                      type="text"
                      value={qrShortcut}
                      onChange={(e) => setQrShortcut(e.target.value)}
                      placeholder="e.g. /greeting, /hours"
                      className="biz-search-input"
                      maxLength={50}
                    />
                  </div>

                  <div className="form-group">
                    <label style={{ fontSize: '12.5px', fontWeight: 700, color: '#1d2738', marginBottom: '4px', display: 'block' }}>
                      Message Template
                    </label>
                    <textarea
                      value={qrMessage}
                      onChange={(e) => setQrMessage(e.target.value)}
                      rows={3}
                      className="biz-search-input"
                      style={{ height: 'auto', padding: '8px' }}
                      placeholder="Type saved response text..."
                      required
                      maxLength={3000}
                    />
                  </div>

                  <div style={{ display: 'flex', gap: '10px', justifyContent: 'flex-end', marginTop: '6px' }}>
                    <button
                      type="button"
                      className="member-button member-button--secondary"
                      onClick={() => setIsQuickRepliesModalOpen(false)}
                    >
                      Close
                    </button>
                    <button
                      type="submit"
                      className="member-button member-button--primary"
                      disabled={isSavingQr || !qrTitle.trim() || !qrMessage.trim()}
                    >
                      <span>{isSavingQr ? 'Saving...' : 'Save Quick Reply'}</span>
                    </button>
                  </div>
                </form>
              )}
            </div>
          </div>
        </ModalPortal>
      )}

      {/* Notifications Modal */}
      <BusinessNotificationModal
        slug={slug}
        isOpen={isNotificationsModalOpen}
        onClose={() => setIsNotificationsModalOpen(false)}
        onNotificationsUpdated={() => {
          fetchInbox().then((res) => res && setInboxData(res));
        }}
      />

      {/* Pre-Flight Client-Side Image Moderation Modal */}
      <ImageModerationScanModal
        isOpen={isScanModalOpen}
        status={scanStatus}
        progress={modProgress}
        decision={modDecision}
        error={modError}
        blockedItems={blockedItems}
        onCancel={() => {
          cancelModeration();
          setIsScanModalOpen(false);
          setScanStatus('IDLE');
          setIsSending(false);
        }}
        onRetry={() => {
          handleSendMessage(new Event('submit'));
        }}
        onAcknowledge={handleRemoveBlockedAttachment}
      />
    </div>
  );
}

export default BusinessInboxPage;
