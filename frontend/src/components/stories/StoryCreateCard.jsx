import { Plus } from 'lucide-react';
import { getAvatarUrl, prefixUrl } from '../../utils/assetHelper';

export function StoryCreateCard({ currentUser, onOpenCreate }) {
  const defaultFallback = prefixUrl('/member_assets/images/dashboard/image/story_1.png');
  const photoUrl = currentUser?.profile_photo
    ? getAvatarUrl(currentUser.profile_photo)
    : defaultFallback;

  return (
    <button
      className="story story--create"
      type="button"
      aria-haspopup="dialog"
      aria-controls="create-story-modal"
      onClick={onOpenCreate}
      aria-label="Create your story"
    >
      <img
        src={photoUrl}
        alt="Your story"
        onError={(e) => {
          if (e.currentTarget.src !== defaultFallback) {
            e.currentTarget.src = defaultFallback;
          }
        }}
      />
      <div className="story--create__shade" />
      <span className="story-add" aria-hidden="true">
        <Plus size={16} />
      </span>
      <strong>Create story</strong>
    </button>
  );
}

export default StoryCreateCard;
