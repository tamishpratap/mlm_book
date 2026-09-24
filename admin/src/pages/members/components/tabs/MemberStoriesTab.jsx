import { useState } from 'react';
import { Eye, Trash2 } from 'lucide-react';
import { StatusBadge } from '../../../../components/common/StatusBadge';
import { EmptyState } from '../../../../components/common/EmptyState';
import { confirmHelper } from '../../../../utils/confirmHelper';
import { storiesApi } from '../../../../api';
import { useToast } from '../../../../hooks/useToast';
import { StoryViewModal } from '../modals/StoryViewModal';

export function MemberStoriesTab({
  stories = [],
  onRefresh,
}) {
  const { showSuccess, showError } = useToast();
  const [selectedStory, setSelectedStory] = useState(null);
  const [actionLoading, setActionLoading] = useState(false);

  const handleDeleteStory = (story) => {
    confirmHelper.confirmDelete({
      header: 'Delete Story',
      message: `Are you sure you want to delete story #${story.id}?`,
      onAccept: async () => {
        setActionLoading(true);
        try {
          await storiesApi.deleteStory(story.id);
          showSuccess(`Story #${story.id} removed.`);
          onRefresh();
        } catch (err) {
          showError(err.message || 'Failed to delete story.');
        } finally {
          setActionLoading(false);
        }
      },
    });
  };

  if (stories.length === 0) {
    return (
      <EmptyState
        title="No Stories Posted"
        description="This member has not posted any 24-hour stories."
      />
    );
  }

  return (
    <div className="pt-2">
      <StoryViewModal
        visible={Boolean(selectedStory)}
        onHide={() => setSelectedStory(null)}
        story={selectedStory}
      />

      <div className="overflow-x-auto border border-slate-200 rounded-xl bg-white">
        <table className="w-full text-left text-xs border-collapse">
          <thead>
            <tr className="bg-slate-50/80 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
              <th className="py-3 px-4">ID</th>
              <th className="py-3 px-4">Media Type</th>
              <th className="py-3 px-4">Caption</th>
              <th className="py-3 px-4">Expires At</th>
              <th className="py-3 px-4">Status</th>
              <th className="py-3 px-4">Created At</th>
              <th className="py-3 px-4 text-right">Actions</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-slate-100">
            {stories.map((story) => {
              const isExpired = Boolean(story.is_expired || (story.status === 'expired'));

              return (
                <tr key={story.id} className="hover:bg-slate-50/80 transition-colors">
                  <td className="py-3 px-4 font-mono font-bold text-slate-800 whitespace-nowrap">
                    <button
                      type="button"
                      onClick={() => setSelectedStory(story)}
                      className="hover:text-blue-600 hover:underline transition-colors font-mono font-bold"
                      title="View Story Inspection"
                    >
                      #{story.id}
                    </button>
                  </td>
                  <td className="py-3 px-4 whitespace-nowrap">
                    <span className="bg-sky-50 text-sky-700 font-semibold px-2 py-0.5 rounded-md text-[10px] uppercase">
                      {story.media_type || 'image'}
                    </span>
                  </td>
                  <td className="py-3 px-4 max-w-xs truncate text-slate-700">
                    <button
                      type="button"
                      onClick={() => setSelectedStory(story)}
                      className="text-left truncate hover:text-blue-600 hover:underline transition-colors block max-w-full"
                      title="View Story Inspection"
                    >
                      {story.caption || <span className="text-slate-400 italic">No caption provided</span>}
                    </button>
                  </td>
                  <td className="py-3 px-4 text-slate-500 whitespace-nowrap">
                    {story.expires_at || 'In 24 hours'}
                  </td>
                  <td className="py-3 px-4 whitespace-nowrap">
                    <StatusBadge
                      status={isExpired ? 'expired' : 'active'}
                      label={isExpired ? 'Expired' : 'Active'}
                    />
                  </td>
                  <td className="py-3 px-4 text-slate-500 whitespace-nowrap">
                    {story.created_at_human || 'Recently'}
                  </td>
                  <td className="py-3 px-4 text-right whitespace-nowrap">
                    <div className="inline-flex items-center justify-end gap-1.5 sm:gap-2">
                      <button
                        type="button"
                        onClick={() => setSelectedStory(story)}
                        className="p-1.5 rounded-md text-slate-500 hover:text-blue-600 hover:bg-blue-50 transition-colors"
                        title="View Story"
                        aria-label={`View Story #${story.id}`}
                      >
                        <Eye className="w-3.5 h-3.5" />
                      </button>

                      <button
                        type="button"
                        onClick={() => handleDeleteStory(story)}
                        disabled={actionLoading}
                        className="p-1.5 rounded-md text-slate-500 hover:text-red-600 hover:bg-red-50 transition-colors"
                        title="Delete Story"
                      >
                        <Trash2 className="w-3.5 h-3.5" />
                      </button>
                    </div>
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>
      </div>
    </div>
  );
}

export default MemberStoriesTab;
