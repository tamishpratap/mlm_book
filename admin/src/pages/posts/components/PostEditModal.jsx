import { useState, useEffect } from 'react';
import { Dialog } from 'primereact/dialog';
import { Button } from 'primereact/button';
import { InputTextarea } from 'primereact/inputtextarea';
import { InputSwitch } from 'primereact/inputswitch';
import {
  Upload,
  Trash2,
  Image as ImageIcon,
  Video,
  AlertCircle,
  Check,
  X,
  FileText,
  AlertTriangle,
} from 'lucide-react';
import { postsApi } from '../../../api';
import { useToast } from '../../../hooks/useToast';
import { confirmHelper } from '../../../utils/confirmHelper';

export function PostEditModal({
  visible,
  onHide,
  post,
  onPostUpdated,
}) {
  const { showSuccess, showError } = useToast();
  const [body, setBody] = useState('');
  const [removeMedia, setRemoveMedia] = useState(false);
  const [newMediaFile, setNewMediaFile] = useState(null);
  const [newMediaPreview, setNewMediaPreview] = useState(null);
  const [newMediaType, setNewMediaType] = useState(null);
  const [saving, setSaving] = useState(false);
  const [actionLoading, setActionLoading] = useState(false);

  useEffect(() => {
    if (post) {
      setBody(post.body || '');
      setRemoveMedia(false);
      setNewMediaFile(null);
      setNewMediaPreview(null);
      setNewMediaType(null);
    }
  }, [post, visible]);

  if (!post) return null;

  const currentMediaUrl = post.media_url || post.media_path;
  const isCurrentVideo = post.media_type === 'video';

  const handleMediaSelect = (e) => {
    const file = e.target.files?.[0];
    if (file) {
      // Validate file size (max 25MB)
      if (file.size > 25 * 1024 * 1024) {
        showError('Selected file exceeds maximum allowed upload size (25MB).');
        return;
      }

      const isVideo = file.type.startsWith('video/');
      setNewMediaFile(file);
      setNewMediaType(isVideo ? 'video' : 'image');
      setNewMediaPreview(URL.createObjectURL(file));
      setRemoveMedia(false);
    }
  };

  const handleClearNewMedia = () => {
    if (newMediaPreview) {
      URL.revokeObjectURL(newMediaPreview);
    }
    setNewMediaFile(null);
    setNewMediaPreview(null);
    setNewMediaType(null);
  };

  const handleDirectRemoveMedia = () => {
    confirmHelper.confirm({
      header: 'Remove Media Attachment',
      message: `Are you sure you want to remove the media attachment from Post #${post.id}? The post text and comments will be preserved.`,
      icon: 'pi pi-trash text-red-500',
      acceptClassName: 'p-button-danger text-xs',
      onAccept: async () => {
        setActionLoading(true);
        try {
          await postsApi.removeMedia(post.id);
          showSuccess(`Media removed from Post #${post.id}.`);
          onPostUpdated?.();
          onHide();
        } catch (err) {
          showError(err.message || 'Failed to remove media.');
        } finally {
          setActionLoading(false);
        }
      },
    });
  };

  const handleSave = async (e) => {
    e.preventDefault();
    setSaving(true);
    try {
      const payload = {
        body,
        remove_media: removeMedia ? 1 : 0,
      };

      const files = {};
      if (newMediaFile) {
        files.media = newMediaFile;
      }

      await postsApi.updatePost(post.id, payload, files);
      showSuccess(`Post #${post.id} updated successfully.`);
      onPostUpdated?.();
      onHide();
    } catch (err) {
      showError(err.message || 'Failed to update post.');
    } finally {
      setSaving(false);
    }
  };

  return (
    <Dialog
      visible={visible}
      onHide={onHide}
      header={
        <div className="flex items-center space-x-2">
          <FileText className="w-4 h-4 text-blue-600" />
          <span className="font-bold text-slate-800 text-sm">
            Edit Post #{post.id}
          </span>
        </div>
      }
      className="w-full max-w-xl"
      modal
    >
      <form onSubmit={handleSave} className="space-y-4 pt-2 text-xs">
        {/* Author Meta Strip */}
        <div className="p-3 bg-slate-50 rounded-xl border border-slate-200/80 flex items-center justify-between">
          <div>
            <span className="text-slate-500">Author:</span>{' '}
            <strong className="text-slate-800 font-semibold">{post.member?.name || 'Unknown'}</strong>
            <span className="text-slate-400 font-mono ml-2">({post.member?.user_id || 'ID: ' + post.member_id})</span>
          </div>
          <span className="text-slate-400">
            {post.created_at ? new Date(post.created_at).toLocaleDateString() : ''}
          </span>
        </div>

        {/* Post Body Editor */}
        <div>
          <label className="block font-bold text-slate-700 mb-1.5" htmlFor="post-body-input">
            Post Body / Caption
          </label>
          <InputTextarea
            id="post-body-input"
            value={body}
            onChange={(e) => setBody(e.target.value)}
            rows={4}
            placeholder="Type post text or moderation correction..."
            className="w-full text-xs"
            disabled={saving || actionLoading}
          />
          <span className="text-[11px] text-slate-400 mt-1 block">
            Author information, reactions, comments, and relationships will be preserved.
          </span>
        </div>

        {/* Media Management Section */}
        <div className="border-t border-slate-200 pt-3 space-y-3">
          <div className="flex items-center justify-between">
            <span className="font-bold text-slate-800 flex items-center">
              <ImageIcon className="w-4 h-4 mr-1 text-blue-600" />
              Media Attachment Management
            </span>
            {post.media_type && (
              <span className="text-[10px] font-semibold bg-slate-100 text-slate-600 px-2 py-0.5 rounded-full uppercase">
                {post.media_type}
              </span>
            )}
          </div>

          {/* Existing Media Card */}
          {currentMediaUrl && (
            <div className={`p-3.5 rounded-xl border transition-colors ${removeMedia ? 'bg-red-50/60 border-red-200' : 'bg-slate-50 border-slate-200'}`}>
              <div className="flex items-center justify-between mb-2">
                <span className="text-xs font-semibold text-slate-700 flex items-center">
                  {isCurrentVideo ? <Video className="w-3.5 h-3.5 mr-1 text-purple-600" /> : <ImageIcon className="w-3.5 h-3.5 mr-1 text-blue-600" />}
                  Current Attachment
                </span>
                <div className="flex items-center space-x-3">
                  <div className="flex items-center space-x-2">
                    <span className="text-[11px] text-red-600 font-semibold">Remove Media</span>
                    <InputSwitch
                      checked={removeMedia}
                      onChange={(e) => setRemoveMedia(e.value)}
                      disabled={saving || actionLoading || Boolean(newMediaFile)}
                    />
                  </div>
                  <Button
                    type="button"
                    label="Delete Now"
                    icon={<Trash2 className="w-3 h-3 mr-1 text-red-500" />}
                    size="small"
                    outlined
                    severity="danger"
                    onClick={handleDirectRemoveMedia}
                    loading={actionLoading}
                    disabled={saving}
                    className="text-[10px] py-1 px-2"
                  />
                </div>
              </div>

              <div className="bg-slate-950 rounded-lg overflow-hidden p-2 text-center">
                {isCurrentVideo ? (
                  <video
                    src={currentMediaUrl}
                    controls
                    className={`max-h-40 mx-auto rounded-md transition-opacity ${removeMedia ? 'opacity-25 grayscale' : 'opacity-100'}`}
                  />
                ) : (
                  <>
                    <img
                      src={currentMediaUrl}
                      alt="Current Post Media"
                      onError={(e) => {
                        e.target.style.display = 'none';
                        e.target.nextSibling?.classList.remove('hidden');
                      }}
                      className={`max-h-40 mx-auto object-contain rounded-md transition-opacity ${removeMedia ? 'opacity-25 grayscale' : 'opacity-100'}`}
                    />
                    <div className="hidden py-4 text-center text-slate-400 text-xs">
                      <ImageIcon className="w-6 h-6 text-slate-500 mx-auto mb-1 opacity-60" />
                      <span>Attachment file ({post.media_path}) unavailable on storage</span>
                    </div>
                  </>
                )}
              </div>
              {removeMedia && (
                <div className="mt-2 flex items-center text-red-600 text-[11px] font-semibold">
                  <AlertCircle className="w-3.5 h-3.5 mr-1" />
                  Media file will be removed from disk and unattached upon saving.
                </div>
              )}
            </div>
          )}

          {/* Upload / Replace Media */}
          <div className="space-y-2">
            <label className="block text-[11px] font-semibold text-slate-700">
              {currentMediaUrl ? 'Replace with New Media File:' : 'Upload Media File:'}
            </label>
            <input
              type="file"
              accept="image/jpeg,image/png,image/jpg,image/webp,image/gif,video/mp4,video/quicktime,video/x-msvideo"
              onChange={handleMediaSelect}
              className="text-xs text-slate-600 file:mr-2 file:py-1 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 w-full"
              disabled={saving || actionLoading}
            />

            {/* New Media Live Preview */}
            {newMediaPreview && (
              <div className="p-3 bg-emerald-50 rounded-xl border border-emerald-200 space-y-2">
                <div className="flex items-center justify-between">
                  <span className="font-bold text-emerald-800 flex items-center text-xs">
                    <Check className="w-3.5 h-3.5 mr-1 text-emerald-600" />
                    New {newMediaType === 'video' ? 'Video' : 'Image'} Selected: {newMediaFile?.name}
                  </span>
                  <button
                    type="button"
                    onClick={handleClearNewMedia}
                    className="text-slate-400 hover:text-red-600 p-1 rounded transition-colors"
                    title="Remove selected replacement file"
                  >
                    <X className="w-3.5 h-3.5" />
                  </button>
                </div>
                <div className="bg-slate-950 rounded-lg overflow-hidden p-2 text-center">
                  {newMediaType === 'video' ? (
                    <video
                      src={newMediaPreview}
                      controls
                      className="max-h-40 mx-auto rounded-md"
                    />
                  ) : (
                    <img
                      src={newMediaPreview}
                      alt="Replacement Preview"
                      className="max-h-40 mx-auto object-contain rounded-md"
                    />
                  )}
                </div>
              </div>
            )}
          </div>
        </div>

        {/* Dialog Actions */}
        <div className="flex flex-wrap items-center justify-end gap-2.5 sm:gap-3 pt-3 border-t border-slate-200">
          <Button
            type="button"
            label="Cancel"
            size="small"
            onClick={onHide}
            disabled={saving || actionLoading}
            className="p-button-outlined p-button-secondary text-xs"
          />
          <Button
            type="submit"
            label={saving ? 'Saving Changes...' : 'Save Post Changes'}
            icon={saving ? 'pi pi-spin pi-spinner' : <Check className="w-3.5 h-3.5 mr-1" />}
            size="small"
            loading={saving}
            disabled={actionLoading}
            className="p-button-primary text-xs"
          />
        </div>
      </form>
    </Dialog>
  );
}

export default PostEditModal;
