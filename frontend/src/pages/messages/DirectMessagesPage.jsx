import { useState, useEffect, useCallback, useRef } from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import {
  MessageSquare,
  Paperclip,
  Send,
  Search,
  X,
  FileText,
  ExternalLink,
  ArrowLeft,
} from 'lucide-react';
import directMessageApi from '../../api/directMessageApi';
import VerifiedBadge from '../../components/common/VerifiedBadge';
import MemberAvatar from '../../components/common/MemberAvatar';
import '../../styles/member-chat.css';


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

export function DirectMessagesPage() {
  const { memberId: routeMemberId } = useParams();
  const navigate = useNavigate();

  const [conversations, setConversations] = useState([]);
  const [activeMember, setActiveMember] = useState(null);
  const [messages, setMessages] = useState([]);
  const [unreadTotalCount, setUnreadTotalCount] = useState(0);

  const [searchQuery, setSearchQuery] = useState('');
  const [isLoading, setIsLoading] = useState(true);
  const [isLoadingMessages, setIsLoadingMessages] = useState(false);
  const [error, setError] = useState(null);

  // Composer state
  const [messageText, setMessageText] = useState('');
  const [attachmentFile, setAttachmentFile] = useState(null);
  const [isSending, setIsSending] = useState(false);
  const [sendError, setSendError] = useState(null);
  const fileInputRef = useRef(null);
  const messagesEndRef = useRef(null);



  // Fetch initial conversations and active partner
  const fetchMessagesData = useCallback(() => {
    return directMessageApi.getMessages(routeMemberId || null);
  }, [routeMemberId]);

  useEffect(() => {
    let isMounted = true;

    fetchMessagesData()
      .then((res) => {
        if (isMounted && res && res.success) {
          setConversations(res.conversations || []);
          setActiveMember(res.active_member || null);
          setMessages(res.messages || []);
          setUnreadTotalCount(res.unread_total_count ?? 0);
          setError(null);
        }
      })
      .catch((err) => {
        if (isMounted) {
          setError(err.response?.data?.message || 'Failed to load messages.');
        }
      })
      .finally(() => {
        if (isMounted) setIsLoading(false);
      });

    return () => {
      isMounted = false;
    };
  }, [fetchMessagesData]);

  // Select a conversation partner
  const handleSelectConversation = async (partner) => {
    if (!partner) return;
    setActiveMember(partner);
    setIsLoadingMessages(true);
    setSendError(null);
    navigate(`/member/messages/${partner.id}`, { replace: true });

    try {
      const res = await directMessageApi.fetchMessages(partner.id);
      if (res && res.success) {
        setMessages(res.messages || []);
        // Update unread count for this partner in conversations list
        setConversations((prev) =>
          prev.map((c) =>
            c.member.id === partner.id ? { ...c, unreadCount: 0 } : c
          )
        );
      }
    } catch (err) {
      setSendError(err.response?.data?.message || 'Failed to load conversation history.');
    } finally {
      setIsLoadingMessages(false);
    }
  };

  // Auto-scroll messages to bottom
  useEffect(() => {
    if (messagesEndRef.current) {
      messagesEndRef.current.scrollIntoView({ behavior: 'smooth' });
    }
  }, [messages]);

  // Send message
  const handleSendMessage = async (e) => {
    e.preventDefault();
    if (!activeMember || isSending) return;
    if (!messageText.trim() && !attachmentFile) return;

    setIsSending(true);
    setSendError(null);



    const formData = new FormData();
    if (messageText.trim()) formData.append('message', messageText.trim());
    if (attachmentFile) formData.append('attachment', attachmentFile);

    try {
      const res = await directMessageApi.sendMessage(activeMember.id, formData);
      if (res && res.success) {
        setMessageText('');
        setAttachmentFile(null);
        if (fileInputRef.current) fileInputRef.current.value = '';
        setMessages((prev) => [...prev, res.data]);

        // Update latest message in conversations list
        setConversations((prev) => {
          const exists = prev.find((c) => c.member.id === activeMember.id);
          if (exists) {
            return prev.map((c) =>
              c.member.id === activeMember.id
                ? {
                    ...c,
                    latestMessage: res.data,
                    lastActiveAt: new Date().toISOString(),
                  }
                : c
            );
          }
          return [
            {
              member: activeMember,
              latestMessage: res.data,
              unreadCount: 0,
              lastActiveAt: new Date().toISOString(),
            },
            ...prev,
          ];
        });
      }
    } catch (err) {
      setSendError(err.response?.data?.message || 'Failed to send message.');
    } finally {
      setIsSending(false);
    }
  };

  const filteredConversations = conversations.filter((c) => {
    if (!searchQuery.trim()) return true;
    const name = c.member?.name || '';
    const userId = c.member?.user_id || '';
    const query = searchQuery.toLowerCase();
    return name.toLowerCase().includes(query) || userId.toLowerCase().includes(query);
  });

  if (isLoading) {
    return (
      <div style={{ textAlign: 'center', padding: '60px', color: 'var(--color-text-secondary)' }}>
        Loading messages...
      </div>
    );
  }

  if (error) {
    return (
      <div className="card" style={{ maxWidth: '600px', margin: '40px auto', padding: '32px', textAlign: 'center' }}>
        <h2 style={{ fontSize: '18px', color: '#dc2626', marginBottom: '8px' }}>Unable to Load Messages</h2>
        <p style={{ color: 'var(--color-text-secondary)', marginBottom: '20px' }}>{error}</p>
        <button
          type="button"
          className="member-button member-button--primary"
          onClick={() => {
            setIsLoading(true);
            setError(null);
            fetchMessagesData()
              .then((res) => {
                if (res && res.success) {
                  setConversations(res.conversations || []);
                  setActiveMember(res.active_member || null);
                  setMessages(res.messages || []);
                  setUnreadTotalCount(res.unread_total_count ?? 0);
                }
              })
              .catch((err) => {
                setError(err.response?.data?.message || 'Failed to load messages.');
              })
              .finally(() => {
                setIsLoading(false);
              });
          }}
        >
          Try Again
        </button>
      </div>
    );
  }

  return (
    <div
      className={`messages-container ${activeMember ? 'has-active-chat' : 'no-active-chat'}`}
    >
      {/* Sidebar: Conversations List */}
      <aside className="messages-sidebar">
        <div className="messages-sidebar__header">
          <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '12px' }}>
            <h2 style={{ fontSize: '20px', fontWeight: 700, color: '#111827', margin: 0, display: 'flex', alignItems: 'center', gap: '8px' }}>
              <MessageSquare size={20} color="#4f46e5" />
              <span>Messages</span>
            </h2>
            {unreadTotalCount > 0 && (
              <span
                style={{
                  background: '#ef4444',
                  color: '#fff',
                  fontSize: '11px',
                  fontWeight: 700,
                  padding: '2px 8px',
                  borderRadius: '10px',
                }}
              >
                {unreadTotalCount} New
              </span>
            )}
          </div>

          {/* Search Box */}
          <div style={{ position: 'relative' }}>
            <input
              type="text"
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              placeholder="Search conversations..."
              className="chat-input"
              style={{
                width: '100%',
                fontSize: '13px',
                padding: '8px 12px 8px 34px',
                borderRadius: '10px',
                border: '1px solid #e5e7eb',
                background: '#f9fafb',
              }}
            />
            <Search
              size={15}
              color="#9ca3af"
              style={{ position: 'absolute', left: '10px', top: '50%', transform: 'translateY(-50%)' }}
            />
          </div>
        </div>

        {/* Conversations List */}
        <div
          className="messages-sidebar__list"
          style={{
            flex: 1,
            overflowY: 'auto',
            padding: '8px',
          }}
        >
          {filteredConversations.length === 0 ? (
            <div style={{ textAlign: 'center', padding: '40px 16px', color: '#9ca3af' }}>
              <MessageSquare size={32} style={{ margin: '0 auto 8px auto', opacity: 0.4 }} />
              <p style={{ fontSize: '13px', margin: 0 }}>
                {searchQuery ? 'No conversations matching search.' : 'No conversations yet. Connect with friends to start chatting!'}
              </p>
            </div>
          ) : (
            filteredConversations.map((conv) => {
              const partner = conv.member || {};
              const isSelected = activeMember?.id === partner.id;
              const unread = conv.unreadCount ?? 0;
              const lastMsg = conv.latestMessage;

              return (
                <div
                  key={partner.id}
                  className={`conversation-item ${isSelected ? 'is-active' : ''}`}
                  onClick={() => handleSelectConversation(partner)}
                  style={{
                    display: 'flex',
                    alignItems: 'center',
                    gap: '12px',
                    padding: '12px',
                    borderRadius: '12px',
                    textDecoration: 'none',
                    color: 'inherit',
                    cursor: 'pointer',
                    transition: 'background 0.15s ease',
                    marginBottom: '4px',
                    background: isSelected ? '#eef2ff' : unread > 0 ? '#f0f7ff' : '#ffffff',
                    border: isSelected ? '1px solid #c7d2fe' : '1px solid #f0f0f0',
                  }}
                >
                  <MemberAvatar member={partner} size={46} />

                  <div className="conversation-item__details" style={{ flex: 1, minWidth: 0 }}>
                    <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: '4px' }}>
                      <strong
                        className="conversation-item__name"
                        style={{
                          fontWeight: 600,
                          fontSize: '14.5px',
                          color: '#1f2937',
                          whiteSpace: 'nowrap',
                          overflow: 'hidden',
                          textOverflow: 'ellipsis',
                          display: 'inline-flex',
                          alignItems: 'center',
                        }}
                      >
                        <span>{partner.name || 'Member'}</span>
                        <VerifiedBadge member={partner} size={13} />
                      </strong>
                      <span style={{ fontSize: '10.5px', color: '#9ca3af', flexShrink: 0 }}>
                        {formatRelativeTime(conv.lastActiveAt)}
                      </span>
                    </div>

                    <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: '4px', marginTop: '2px' }}>
                      <span
                        className="conversation-item__preview"
                        style={{
                          fontSize: '12.5px',
                          color: unread > 0 ? '#1f2937' : '#6b7280',
                          fontWeight: unread > 0 ? 600 : 400,
                          whiteSpace: 'nowrap',
                          overflow: 'hidden',
                          textOverflow: 'ellipsis',
                        }}
                      >
                        {lastMsg
                          ? lastMsg.message || (lastMsg.attachment ? '📎 Attachment' : '')
                          : 'Start a conversation'}
                      </span>

                      {unread > 0 && (
                        <span
                          className="conversation-item__badge"
                          style={{
                            background: '#ef4444',
                            color: 'white',
                            fontSize: '11px',
                            fontWeight: 700,
                            padding: '2px 7px',
                            borderRadius: '10px',
                            flexShrink: 0,
                          }}
                        >
                          {unread}
                        </span>
                      )}
                    </div>
                  </div>
                </div>
              );
            })
          )}
        </div>
      </aside>

      {/* Main Panel: Chat Thread Window */}
      <main className="messages-main">
        {activeMember ? (
          <>
            {/* Active Header */}
            <div className="chat-header">
              <button
                type="button"
                className="chat-header__back"
                onClick={() => {
                  setActiveMember(null);
                  navigate('/member/messages', { replace: true });
                }}
                aria-label="Back to conversations"
                title="Back to conversations"
              >
                <ArrowLeft size={18} />
              </button>

              <div className="chat-header__user">
                <MemberAvatar member={activeMember} size={42} className="chat-header__avatar" />

                <div className="chat-header__info">
                  <strong style={{ display: 'inline-flex', alignItems: 'center', fontSize: '16px', color: '#111827' }}>
                    <span>{activeMember.name}</span>
                    <VerifiedBadge member={activeMember} size={16} />
                  </strong>
                  <small style={{ color: '#6b7280', fontSize: '12px', display: 'block' }}>
                    @{activeMember.user_id || 'member'}
                  </small>
                </div>
              </div>

              <div className="chat-header__actions chat-card-actions">
                <Link
                  to={`/member/people/${activeMember.id}`}
                  className="member-button member-button--secondary"
                  style={{ fontSize: '12px', padding: '6px 12px' }}
                >
                  <ExternalLink size={13} />
                  <span>View Profile</span>
                </Link>
              </div>
            </div>

            {/* Chat Body (Messages Scroll Area) */}
            <div className="chat-body">
              {isLoadingMessages ? (
                <div style={{ margin: 'auto', textAlign: 'center', color: '#9ca3af', fontSize: '13px' }}>
                  Loading chat history...
                </div>
              ) : messages.length === 0 ? (
                <div
                  className="chat-empty-thread"
                  style={{
                    display: 'flex',
                    flexDirection: 'column',
                    alignItems: 'center',
                    justifyContent: 'center',
                    padding: '40px 20px',
                    color: '#9ca3af',
                    textAlign: 'center',
                    margin: 'auto',
                  }}
                >
                  <MessageSquare size={36} style={{ marginBottom: '8px', opacity: 0.4 }} />
                  <p style={{ margin: 0, fontSize: '13.5px' }}>
                    No messages with {activeMember.name} yet. Send a message to start the conversation!
                  </p>
                </div>
              ) : (
                messages.map((msg) => {
                  const isMine = Boolean(msg.is_mine);
                  return (
                    <div
                      key={msg.id}
                      className={`chat-bubble-wrapper ${isMine ? 'chat-bubble-wrapper--mine' : 'chat-bubble-wrapper--other'}`}
                      style={{
                        display: 'flex',
                        alignItems: 'flex-end',
                        gap: '8px',
                        maxWidth: '75%',
                        alignSelf: isMine ? 'flex-end' : 'flex-start',
                        flexDirection: isMine ? 'row-reverse' : 'row',
                      }}
                    >
                      {!isMine && (
                        <MemberAvatar member={activeMember} size={28} className="chat-avatar" />
                      )}

                      <div
                        className="chat-bubble"
                        style={{
                          padding: '10px 14px',
                          borderRadius: '16px',
                          fontSize: '14px',
                          lineHeight: 1.45,
                          position: 'relative',
                          background: isMine ? '#4f46e5' : '#ffffff',
                          color: isMine ? '#ffffff' : '#1f2937',
                          borderBottomRightRadius: isMine ? '4px' : '16px',
                          borderBottomLeftRadius: isMine ? '16px' : '4px',
                          boxShadow: '0 1px 3px rgba(0, 0, 0, 0.05)',
                          border: isMine ? 'none' : '1px solid #e5e7eb',
                        }}
                      >
                        {msg.message && <p style={{ margin: 0, whiteSpace: 'pre-line' }}>{msg.message}</p>}

                        {msg.attachment && (
                          <div style={{ marginTop: msg.message ? '8px' : 0 }}>
                            {msg.attachment.match(/\.(jpeg|jpg|png|webp|gif)$/i) ? (
                              <a href={msg.attachment} target="_blank" rel="noreferrer">
                                <img
                                  src={msg.attachment}
                                  alt="Attachment"
                                  style={{ maxWidth: '200px', maxHeight: '200px', borderRadius: '8px', display: 'block' }}
                                />
                              </a>
                            ) : (
                              <a
                                href={msg.attachment}
                                target="_blank"
                                rel="noreferrer"
                                style={{
                                  display: 'flex',
                                  alignItems: 'center',
                                  gap: '6px',
                                  color: isMine ? '#ffffff' : '#4f46e5',
                                  fontSize: '12.5px',
                                  textDecoration: 'underline',
                                }}
                              >
                                <FileText size={14} />
                                <span>Attached File</span>
                              </a>
                            )}
                          </div>
                        )}

                        <span
                          className="chat-bubble__time"
                          style={{
                            display: 'block',
                            fontSize: '10px',
                            marginTop: '4px',
                            opacity: 0.75,
                            textAlign: 'right',
                          }}
                        >
                          {msg.time}
                        </span>
                      </div>
                    </div>
                  );
                })
              )}
              <div ref={messagesEndRef} />
            </div>

            {/* Composer Footer */}
            <form
              onSubmit={handleSendMessage}
              className="chat-footer"
            >
              {sendError && (
                <div style={{ position: 'absolute', bottom: '65px', left: '20px', right: '20px', padding: '6px 12px', background: '#fee2e2', color: '#b91c1c', borderRadius: '8px', fontSize: '12px' }}>
                  {sendError}
                </div>
              )}

              {/* Attachment File Input */}
              <label
                className="chat-file-btn"
                style={{
                  color: '#6b7280',
                  cursor: 'pointer',
                  padding: '8px',
                  borderRadius: '50%',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                }}
                title="Attach photo or file"
              >
                <Paperclip size={18} />
                <input
                  type="file"
                  ref={fileInputRef}
                  style={{ display: 'none' }}
                  onChange={(e) => setAttachmentFile(e.target.files?.[0] || null)}
                />
              </label>

              {attachmentFile && (
                <div
                  style={{
                    display: 'flex',
                    alignItems: 'center',
                    gap: '4px',
                    background: '#edf3ff',
                    padding: '4px 8px',
                    borderRadius: '8px',
                    fontSize: '12px',
                    color: '#4f46e5',
                    maxWidth: '150px',
                  }}
                >
                  <span style={{ overflow: 'hidden', textOverflow: 'ellipsis', whiteSpace: 'nowrap' }}>
                    {attachmentFile.name}
                  </span>
                  <button
                    type="button"
                    onClick={() => {
                      setAttachmentFile(null);
                      if (fileInputRef.current) fileInputRef.current.value = '';
                    }}
                    style={{ background: 'transparent', border: 'none', cursor: 'pointer', padding: 0 }}
                  >
                    <X size={13} color="#ef4444" />
                  </button>
                </div>
              )}

              <input
                type="text"
                value={messageText}
                onChange={(e) => setMessageText(e.target.value)}
                placeholder="Type a message..."
                className="chat-input"
                style={{
                  flex: 1,
                  border: '1px solid #d1d5db',
                  borderRadius: '20px',
                  padding: '10px 16px',
                  fontSize: '14px',
                  outline: 'none',
                }}
              />

              <button
                type="submit"
                className="chat-send-btn"
                style={{
                  background: '#4f46e5',
                  color: 'white',
                  border: 'none',
                  width: '40px',
                  height: '40px',
                  borderRadius: '50%',
                  display: 'flex',
                  alignItems: 'center',
                  justifyContent: 'center',
                  cursor: isSending || (!messageText.trim() && !attachmentFile) ? 'not-allowed' : 'pointer',
                  opacity: isSending || (!messageText.trim() && !attachmentFile) ? 0.6 : 1,
                }}
                disabled={isSending || (!messageText.trim() && !attachmentFile)}
              >
                <Send size={16} />
              </button>
            </form>
          </>
        ) : (
          <div
            className="chat-empty-state"
            style={{
              display: 'flex',
              flexDirection: 'column',
              alignItems: 'center',
              justifyContent: 'center',
              height: '100%',
              color: '#6b7280',
              textAlign: 'center',
              padding: '20px',
            }}
          >
            <MessageSquare size={48} style={{ marginBottom: '12px', opacity: 0.4, color: '#4f46e5' }} />
            <h3 style={{ fontSize: '18px', color: '#111827', margin: '0 0 6px 0' }}>Your Messages</h3>
            <p style={{ margin: 0, fontSize: '13.5px', maxWidth: '300px' }}>
              Select a conversation from the left to start chatting with your connections.
            </p>
          </div>
        )}
      </main>

    </div>
  );
}

export default DirectMessagesPage;
