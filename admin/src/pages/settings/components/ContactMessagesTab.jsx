import { useState, useEffect, useCallback, useMemo } from 'react';
import {
  Mail,
  Phone,
  Clock,
  Trash2,
  Eye,
  Send,
  CheckCircle2,
  Search,
  RefreshCw,
  MessageSquare,
  AlertCircle,
  ExternalLink
} from 'lucide-react';
import { Button } from 'primereact/button';
import { Dialog } from 'primereact/dialog';
import { InputText } from 'primereact/inputtext';
import { settingsApi } from '../../../api';
import { useToast } from '../../../hooks/useToast';

export function ContactMessagesTab() {
  const { showSuccess, showError } = useToast();
  const [messages, setMessages] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState('');
  const [activeMessage, setActiveMessage] = useState(null);
  const [detailVisible, setDetailVisible] = useState(false);
  const [actionLoading, setActionLoading] = useState(false);

  const fetchMessages = useCallback(async () => {
    setLoading(true);
    try {
      const res = await settingsApi.getContactMessages();
      setMessages(res?.messages || []);
    } catch (err) {
      showError(err.message || 'Failed to load contact messages.');
    } finally {
      setLoading(false);
    }
  }, [showError]);

  useEffect(() => {
    fetchMessages();
  }, [fetchMessages]);

  const filteredMessages = useMemo(() => {
    if (!searchTerm.trim()) return messages;
    const term = searchTerm.toLowerCase();
    return messages.filter(
      (m) =>
        m.name?.toLowerCase().includes(term) ||
        m.email?.toLowerCase().includes(term) ||
        m.phone?.toLowerCase().includes(term) ||
        m.subject?.toLowerCase().includes(term) ||
        m.message?.toLowerCase().includes(term)
    );
  }, [messages, searchTerm]);

  const handleDelete = async (id, e) => {
    if (e) e.stopPropagation();
    if (!window.confirm('Are you sure you want to delete this inquiry message?')) return;

    setActionLoading(true);
    try {
      await settingsApi.deleteContactMessage(id);
      showSuccess('Inquiry deleted successfully.');
      setMessages((prev) => prev.filter((m) => m.id !== id));
      if (activeMessage?.id === id) {
        setDetailVisible(false);
        setActiveMessage(null);
      }
    } catch (err) {
      showError(err.message || 'Failed to delete message.');
    } finally {
      setActionLoading(false);
    }
  };

  const handleUpdateStatus = async (id, status, e) => {
    if (e) e.stopPropagation();
    try {
      await settingsApi.updateContactMessageStatus(id, status);
      setMessages((prev) =>
        prev.map((m) => (m.id === id ? { ...m, status } : m))
      );
      if (activeMessage?.id === id) {
        setActiveMessage((prev) => ({ ...prev, status }));
      }
      showSuccess(`Status updated to ${status}.`);
    } catch (err) {
      showError(err.message || 'Failed to update status.');
    }
  };

  const openDetail = (msg) => {
    setActiveMessage(msg);
    setDetailVisible(true);
    if (msg.status === 'new') {
      handleUpdateStatus(msg.id, 'read');
    }
  };

  const formatDate = (isoStr) => {
    if (!isoStr) return '-';
    try {
      const d = new Date(isoStr);
      return d.toLocaleString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
      });
    } catch {
      return isoStr;
    }
  };

  return (
    <div className="space-y-5 pt-2">
      {/* Top Action Bar */}
      <div className="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
        <div className="flex items-center gap-2">
          <div className="relative flex-1 sm:w-72">
            <Search className="w-3.5 h-3.5 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
            <InputText
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              placeholder="Search sender, email, subject..."
              className="w-full text-xs pl-8"
            />
          </div>
          <span className="text-xs text-slate-500 font-medium whitespace-nowrap">
            {filteredMessages.length} inquiry{filteredMessages.length === 1 ? '' : 'ies'}
          </span>
        </div>

        <div className="flex items-center gap-2 self-end sm:self-auto">
          <Button
            type="button"
            icon={<RefreshCw className={`w-3.5 h-3.5 mr-1.5 ${loading ? 'animate-spin' : ''}`} />}
            label="Refresh"
            size="small"
            onClick={fetchMessages}
            disabled={loading}
            className="p-button-outlined p-button-secondary text-xs"
          />
        </div>
      </div>

      {/* Inquiries Table / List */}
      {loading && messages.length === 0 ? (
        <div className="py-12 text-center text-xs text-slate-400 flex flex-col items-center justify-center gap-2">
          <RefreshCw className="w-6 h-6 animate-spin text-blue-500" />
          <span>Loading received contact inquiries...</span>
        </div>
      ) : filteredMessages.length === 0 ? (
        <div className="py-12 text-center rounded-xl bg-slate-50 border border-dashed border-slate-200 p-8">
          <MessageSquare className="w-10 h-10 text-slate-300 mx-auto mb-2" />
          <h4 className="text-sm font-bold text-slate-700">No Contact Messages Found</h4>
          <p className="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
            {searchTerm
              ? 'No inquiries match your search filter.'
              : 'Messages submitted via the frontend "Send Us a Message" form will appear here in real time.'}
          </p>
        </div>
      ) : (
        <div className="overflow-x-auto rounded-xl border border-slate-200 bg-white">
          <table className="w-full text-left text-xs border-collapse">
            <thead>
              <tr className="bg-slate-50/80 border-b border-slate-200 text-slate-600 font-bold uppercase tracking-wider text-[11px]">
                <th className="py-3 px-4">Sender & Contact</th>
                <th className="py-3 px-4">Subject & Message Preview</th>
                <th className="py-3 px-4">Received At</th>
                <th className="py-3 px-4">Status</th>
                <th className="py-3 px-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100">
              {filteredMessages.map((item) => {
                const isNew = item.status === 'new' || !item.status;
                const isReplied = item.status === 'replied';

                return (
                  <tr
                    key={item.id}
                    onClick={() => openDetail(item)}
                    className="hover:bg-slate-50/80 cursor-pointer transition-colors"
                  >
                    <td className="py-3.5 px-4 align-top min-w-[200px]">
                      <div className="font-bold text-slate-800 flex items-center gap-1.5">
                        {isNew && (
                          <span className="w-2 h-2 rounded-full bg-blue-500 shrink-0" title="New Message" />
                        )}
                        <span>{item.name || 'Anonymous'}</span>
                      </div>
                      <div className="text-slate-500 text-[11px] flex items-center gap-1 mt-0.5">
                        <Mail className="w-3 h-3 text-slate-400 shrink-0" />
                        <span className="truncate max-w-[170px]">{item.email}</span>
                      </div>
                      {item.phone && (
                        <div className="text-slate-500 text-[11px] flex items-center gap-1 mt-0.5">
                          <Phone className="w-3 h-3 text-slate-400 shrink-0" />
                          <span>{item.phone}</span>
                        </div>
                      )}
                    </td>

                    <td className="py-3.5 px-4 align-top max-w-[320px]">
                      <div className="font-semibold text-slate-800 truncate" title={item.subject}>
                        {item.subject || '(No Subject)'}
                      </div>
                      <div className="text-slate-500 text-[11px] line-clamp-2 mt-1" title={item.message}>
                        {item.message}
                      </div>
                    </td>

                    <td className="py-3.5 px-4 align-top whitespace-nowrap text-slate-500 text-[11px]">
                      <div className="flex items-center gap-1">
                        <Clock className="w-3 h-3 text-slate-400 shrink-0" />
                        <span>{formatDate(item.created_at)}</span>
                      </div>
                      {item.ip && (
                        <span className="text-[10px] text-slate-400 mt-0.5 block">IP: {item.ip}</span>
                      )}
                    </td>

                    <td className="py-3.5 px-4 align-top whitespace-nowrap">
                      {isNew ? (
                        <span className="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                          ● New
                        </span>
                      ) : isReplied ? (
                        <span className="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                          ✓ Replied
                        </span>
                      ) : (
                        <span className="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-600 border border-slate-200">
                          Read
                        </span>
                      )}
                    </td>

                    <td className="py-3.5 px-4 align-top text-right whitespace-nowrap">
                      <div className="inline-flex items-center gap-1.5" onClick={(e) => e.stopPropagation()}>
                        <Button
                          type="button"
                          icon={<Eye className="w-3.5 h-3.5" />}
                          size="small"
                          text
                          rounded
                          severity="secondary"
                          title="View Full Message"
                          onClick={() => openDetail(item)}
                        />

                        <a
                          href={`mailto:${item.email}?subject=Re: ${encodeURIComponent(item.subject || 'Your Inquiry')}`}
                          className="p-button p-component p-button-icon-only p-button-text p-button-rounded p-button-info text-blue-600 hover:bg-blue-50"
                          title="Reply via Email"
                          onClick={() => handleUpdateStatus(item.id, 'replied')}
                        >
                          <Send className="w-3.5 h-3.5" />
                        </a>

                        <Button
                          type="button"
                          icon={<Trash2 className="w-3.5 h-3.5 text-rose-500" />}
                          size="small"
                          text
                          rounded
                          severity="danger"
                          title="Delete Message"
                          onClick={(e) => handleDelete(item.id, e)}
                        />
                      </div>
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>
        </div>
      )}

      {/* Detail Modal */}
      <Dialog
        header={
          <div className="flex items-center gap-2">
            <MessageSquare className="w-4 h-4 text-blue-600" />
            <span className="font-bold text-slate-800 text-sm">Contact Inquiry Details</span>
          </div>
        }
        visible={detailVisible}
        onHide={() => setDetailVisible(false)}
        className="w-full max-w-lg"
        footer={
          <div className="flex items-center justify-between gap-2 pt-2">
            <Button
              label="Delete"
              icon={<Trash2 className="w-3.5 h-3.5 mr-1.5" />}
              severity="danger"
              text
              size="small"
              onClick={() => activeMessage && handleDelete(activeMessage.id)}
              disabled={actionLoading}
            />

            <div className="flex items-center gap-2">
              {activeMessage?.status !== 'replied' ? (
                <Button
                  label="Mark as Replied"
                  icon={<CheckCircle2 className="w-3.5 h-3.5 mr-1.5" />}
                  size="small"
                  severity="secondary"
                  outlined
                  onClick={() => activeMessage && handleUpdateStatus(activeMessage.id, 'replied')}
                />
              ) : (
                <Button
                  label="Mark as New"
                  size="small"
                  severity="secondary"
                  outlined
                  onClick={() => activeMessage && handleUpdateStatus(activeMessage.id, 'new')}
                />
              )}

              <a
                href={`mailto:${activeMessage?.email}?subject=Re: ${encodeURIComponent(activeMessage?.subject || 'Your Inquiry')}`}
                target="_blank"
                rel="noreferrer"
                className="p-button p-component p-button-sm p-button-primary inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-lg bg-blue-600 text-white hover:bg-blue-700"
                onClick={() => activeMessage && handleUpdateStatus(activeMessage.id, 'replied')}
              >
                <Send className="w-3.5 h-3.5" />
                <span>Reply by Email</span>
              </a>
            </div>
          </div>
        }
      >
        {activeMessage && (
          <div className="space-y-4 pt-2 text-xs">
            {/* Sender Box */}
            <div className="bg-slate-50 p-3.5 rounded-xl border border-slate-200 grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div>
                <span className="text-[11px] font-bold text-slate-500 uppercase block">Sender Name</span>
                <span className="font-bold text-slate-800 text-sm mt-0.5 block">{activeMessage.name}</span>
              </div>

              <div>
                <span className="text-[11px] font-bold text-slate-500 uppercase block">Email Address</span>
                <a
                  href={`mailto:${activeMessage.email}`}
                  className="font-semibold text-blue-600 hover:underline text-xs mt-0.5 inline-flex items-center gap-1"
                >
                  <span>{activeMessage.email}</span>
                  <ExternalLink className="w-3 h-3" />
                </a>
              </div>

              <div>
                <span className="text-[11px] font-bold text-slate-500 uppercase block">Phone Number</span>
                <span className="font-medium text-slate-700 text-xs mt-0.5 block">
                  {activeMessage.phone || 'Not provided'}
                </span>
              </div>

              <div>
                <span className="text-[11px] font-bold text-slate-500 uppercase block">Submitted At</span>
                <span className="font-medium text-slate-700 text-xs mt-0.5 block">
                  {formatDate(activeMessage.created_at)}
                </span>
              </div>
            </div>

            {/* Subject */}
            <div>
              <span className="text-[11px] font-bold text-slate-500 uppercase block mb-1">Subject</span>
              <div className="p-2.5 rounded-lg bg-white border border-slate-200 font-semibold text-slate-800">
                {activeMessage.subject}
              </div>
            </div>

            {/* Full Message */}
            <div>
              <span className="text-[11px] font-bold text-slate-500 uppercase block mb-1">Message Content</span>
              <div className="p-3.5 rounded-xl bg-slate-50/80 border border-slate-200 text-slate-700 leading-relaxed whitespace-pre-wrap font-sans text-xs min-h-[100px]">
                {activeMessage.message}
              </div>
            </div>
          </div>
        )}
      </Dialog>
    </div>
  );
}

export default ContactMessagesTab;
