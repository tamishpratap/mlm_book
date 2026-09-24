import { useState } from 'react';
import {
  Clock,
  Trash2,
  Image as ImageIcon,
  Video,
  Eye,
  Heart,
  Smile,
  PlayCircle,
} from 'lucide-react';
import { Button } from 'primereact/button';
import { Checkbox } from 'primereact/checkbox';
import { Paginator } from 'primereact/paginator';
import { StatusBadge } from '../../../components/common/StatusBadge';
import { EmptyState } from '../../../components/common/EmptyState';
import { ErrorState } from '../../../components/common/ErrorState';
import { LoadingSpinner } from '../../../components/common/LoadingSpinner';
import { StoryViewerModal } from './StoryViewerModal';

export function StoryTable({
  stories = [],
  loading = false,
  error = null,
  pagination = { page: 1, perPage: 15, total: 0 },
  onPageChange,
  selectedIds = [],
  onToggleSelectAll,
  onToggleSelectOne,
  onDeleteStory,
  onRetry,
  emptyTitle = 'No Stories Found',
  emptyMessage = 'No stories match your current search or filter criteria.',
}) {
  const [viewingStory, setViewingStory] = useState(null);

  return (
    <>
      <div className="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden">
        {loading ? (
          <div className="p-12 flex justify-center">
            <LoadingSpinner message="Loading stories..." />
          </div>
        ) : error ? (
          <div className="p-8">
            <ErrorState
              title="Failed to Load Stories"
              message={error}
              onRetry={onRetry}
            />
          </div>
        ) : stories.length === 0 ? (
          <div className="p-8">
            <EmptyState
              title={emptyTitle}
              message={emptyMessage}
              icon={Clock}
            />
          </div>
        ) : (
          <>
            <div className="overflow-x-auto">
              <table className="w-full text-left text-xs text-slate-700">
                <thead className="bg-slate-50 text-[11px] font-bold text-slate-500 uppercase border-b border-slate-200">
                  <tr>
                    <th className="p-3.5 w-10 text-center">
                      <Checkbox
                        checked={stories.length > 0 && selectedIds.length === stories.length}
                        onChange={(e) => onToggleSelectAll(e.checked)}
                      />
                    </th>
                    <th className="p-3.5">Author</th>
                    <th className="p-3.5">Story Caption</th>
                    <th className="p-3.5">Media</th>
                    <th className="p-3.5 text-center">Impressions</th>
                    <th className="p-3.5">Lifespan</th>
                    <th className="p-3.5">Status</th>
                    <th className="p-3.5 text-right">Actions</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {stories.map((story) => {
                    const isExpired = new Date(story.expires_at) <= new Date();
                    const authorName = story.member?.name || story.author?.name || 'Unknown Member';
                    const authorUserId = story.member?.user_id || story.author?.user_id || `ID: ${story.member_id || story.id}`;
                    const avatarUrl = story.member?.avatar_url || story.author?.avatar_url;

                    return (
                      <tr key={story.id} className="hover:bg-slate-50/80 transition-colors">
                        <td className="p-3.5 text-center">
                          <Checkbox
                            checked={selectedIds.includes(story.id)}
                            onChange={() => onToggleSelectOne(story.id)}
                          />
                        </td>
                        <td className="p-3.5">
                          <div className="flex items-center space-x-2.5">
                            <div className="w-8 h-8 rounded-full bg-blue-100 text-blue-700 font-bold text-xs flex items-center justify-center overflow-hidden shrink-0">
                              {avatarUrl ? (
                                <img
                                  src={avatarUrl}
                                  alt={authorName}
                                  className="w-full h-full object-cover"
                                />
                              ) : (
                                authorName.charAt(0).toUpperCase()
                              )}
                            </div>
                            <div>
                              <div className="font-semibold text-slate-900">
                                {authorName}
                              </div>
                              <div className="text-[10px] text-slate-400 font-mono">
                                {authorUserId}
                              </div>
                            </div>
                          </div>
                        </td>
                        <td className="p-3.5 max-w-xs">
                          <p className="line-clamp-2 text-slate-800 text-xs">
                            {story.caption || <span className="text-slate-400 italic">No caption</span>}
                          </p>
                        </td>
                        <td className="p-3.5">
                          {story.media_type === 'image' ? (
                            <span className="inline-flex items-center text-[10px] font-semibold bg-blue-50 text-blue-700 px-2 py-0.5 rounded-sm border border-blue-200">
                              <ImageIcon className="w-3 h-3 mr-1" /> Image
                            </span>
                          ) : story.media_type === 'video' ? (
                            <span className="inline-flex items-center text-[10px] font-semibold bg-purple-50 text-purple-700 px-2 py-0.5 rounded-sm border border-purple-200">
                              <Video className="w-3 h-3 mr-1" /> Video
                            </span>
                          ) : (
                            <span className="text-slate-400 text-[11px]">Text</span>
                          )}
                        </td>
                        <td className="p-3.5 text-center">
                          <div className="flex items-center justify-center space-x-2 text-[11px] text-slate-500">
                            <span className="flex items-center" title="Views">
                              <Eye className="w-3 h-3 text-blue-500 mr-0.5" /> {story.views_count ?? 0}
                            </span>
                            <span className="flex items-center" title="Likes">
                              <Heart className="w-3 h-3 text-red-500 mr-0.5" /> {story.likes_count ?? 0}
                            </span>
                            <span className="flex items-center" title="Reactions">
                              <Smile className="w-3 h-3 text-amber-500 mr-0.5" /> {story.reactions_count ?? 0}
                            </span>
                          </div>
                        </td>
                        <td className="p-3.5 text-[11px] text-slate-500">
                          <div>Created: {new Date(story.created_at).toLocaleDateString()}</div>
                          <div className={isExpired ? 'text-slate-400' : 'text-emerald-600 font-semibold'}>
                            Expires: {new Date(story.expires_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                          </div>
                        </td>
                        <td className="p-3.5">
                          <StatusBadge status={isExpired ? 'blocked' : 'active'} label={isExpired ? 'Expired' : 'Active'} />
                        </td>
                        <td className="p-3.5 text-right whitespace-nowrap">
                          <div className="inline-flex items-center justify-end gap-1.5 sm:gap-2">
                            <Button
                              icon={<PlayCircle className="w-3.5 h-3.5 text-blue-600" />}
                              onClick={() => setViewingStory(story)}
                              className="p-button-text p-button-secondary p-button-sm p-0 w-7 h-7"
                              tooltip="Preview Story"
                            />
                            <Button
                              icon={<Trash2 className="w-3.5 h-3.5 text-red-500" />}
                              onClick={() => onDeleteStory(story)}
                              className="p-button-text p-button-danger p-button-sm p-0 w-7 h-7"
                              tooltip="Delete Story"
                            />
                          </div>
                        </td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>

            {/* Paginator */}
            <div className="p-3 border-t border-slate-200 flex justify-between items-center text-xs text-slate-500">
              <span>Showing {stories.length} of {pagination.total} entries</span>
              <Paginator
                first={(pagination.page - 1) * pagination.perPage}
                rows={pagination.perPage}
                totalRecords={pagination.total}
                onPageChange={(e) => onPageChange(e.page + 1)}
                className="p-paginator-sm"
              />
            </div>
          </>
        )}
      </div>

      {/* Story Preview Modal */}
      <StoryViewerModal
        story={viewingStory}
        visible={Boolean(viewingStory)}
        onHide={() => setViewingStory(null)}
      />
    </>
  );
}

export default StoryTable;
