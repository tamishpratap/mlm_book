import { useState } from 'react';
import { Dialog } from 'primereact/dialog';
import { Button } from 'primereact/button';
import { InputTextarea } from 'primereact/inputtextarea';
import { InputSwitch } from 'primereact/inputswitch';
import { postsApi } from '../../../../api';
import { useToast } from '../../../../hooks/useToast';

function PostEditForm({ post, onHide, onPostUpdated }) {
  const { showSuccess, showError } = useToast();
  const [body, setBody] = useState(post?.body || '');
  const [removeMedia, setRemoveMedia] = useState(false);
  const [newMediaFile, setNewMediaFile] = useState(null);
  const [newMediaPreview, setNewMediaPreview] = useState(null);
  const [saving, setSaving] = useState(false);

  const handleMediaSelect = (e) => {
    const file = e.target.files?.[0];
    if (file) {
      setNewMediaFile(file);
      setNewMediaPreview(URL.createObjectURL(file));
      setRemoveMedia(false);
    }
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
      if (newMediaFile) files.media = newMediaFile;

      await postsApi.updatePost(post.id, payload, files);
      showSuccess(`Post #${post.id} updated successfully.`);
      onPostUpdated();
      onHide();
    } catch (err) {
      showError(err.message || 'Failed to update post.');
    } finally {
      setSaving(false);
    }
  };

  return (
    <form onSubmit={handleSave} className="space-y-4 pt-1">
      <div>
        <label className="block text-xs font-bold text-slate-800 mb-1" htmlFor="post-body">
          Post Content / Text
        </label>
        <InputTextarea
          id="post-body"
          value={body}
          onChange={(e) => setBody(e.target.value)}
          rows={4}
          placeholder="Enter post text..."
          className="w-full text-xs"
          disabled={saving}
        />
        <span className="text-[11px] text-slate-400 mt-1 block">
          Moderating or correcting inappropriate wording updates the post body.
        </span>
      </div>

      {/* Media attachment management */}
      <div className="border-t border-slate-100 pt-3 space-y-3">
        <label className="block text-xs font-bold text-slate-800">
          Media Attachment
        </label>

        {post.media_path && (
          <div className={`p-3 rounded-xl border transition-colors ${removeMedia ? 'bg-red-50/60 border-red-200' : 'bg-slate-50 border-slate-200'}`}>
            <div className="flex items-center justify-between mb-2">
              <span className="text-xs font-semibold text-slate-700">Current Media</span>
              <div className="flex items-center space-x-2">
                <span className="text-[11px] text-red-600 font-semibold">Remove Media</span>
                <InputSwitch
                  checked={removeMedia}
                  onChange={(e) => setRemoveMedia(e.value)}
                  disabled={saving}
                />
              </div>
            </div>
            <div className="bg-slate-950 rounded-lg overflow-hidden p-2 text-center">
              <img
                src={post.media_path}
                alt="Current Media"
                className={`max-h-32 mx-auto object-contain transition-opacity ${removeMedia ? 'opacity-30 grayscale' : 'opacity-100'}`}
              />
            </div>
          </div>
        )}

        <div>
          <label className="block text-[11px] font-semibold text-slate-600 mb-1">
            Replace Media / Upload New:
          </label>
          <input
            type="file"
            accept="image/jpeg,image/png,image/jpg,image/webp,video/mp4"
            onChange={handleMediaSelect}
            className="text-xs text-slate-600 file:mr-2 file:py-1 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
            disabled={saving}
          />
          {newMediaPreview && (
            <div className="mt-2 p-2 bg-emerald-50 rounded-lg border border-emerald-200 text-xs">
              <span className="font-bold text-emerald-800 block mb-1">New Media Selected</span>
              <img src={newMediaPreview} alt="Preview" className="max-h-32 rounded-md object-contain" />
            </div>
          )}
        </div>
      </div>

      <div className="flex items-center justify-end space-x-2 pt-3 border-t border-slate-100">
        <Button
          type="button"
          label="Cancel"
          size="small"
          onClick={onHide}
          disabled={saving}
          className="p-button-outlined p-button-secondary text-xs"
        />
        <Button
          type="submit"
          label={saving ? 'Saving...' : 'Save Changes'}
          icon={saving ? 'pi pi-spin pi-spinner' : 'pi pi-check'}
          size="small"
          loading={saving}
          className="p-button-primary text-xs"
        />
      </div>
    </form>
  );
}

export function PostEditModal({
  visible,
  onHide,
  post,
  onPostUpdated,
}) {
  if (!post) return null;

  return (
    <Dialog
      visible={visible}
      onHide={onHide}
      header={`Edit Post #${post.id}`}
      className="w-full max-w-lg"
      modal
    >
      <PostEditForm
        key={post.id}
        post={post}
        onHide={onHide}
        onPostUpdated={onPostUpdated}
      />
    </Dialog>
  );
}

export default PostEditModal;
