import { Link } from 'react-router-dom';
import { MessageSquare, Image, ArrowRight } from 'lucide-react';
import { EmptyState } from '../../../components/common/EmptyState';

export function RecentPostsFeed({ posts = [] }) {
  const hasPosts = posts && posts.length > 0;

  return (
    <div className="bg-white rounded-xl border border-slate-200 shadow-2xs overflow-hidden flex flex-col justify-between h-full">
      {/* Header */}
      <div className="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
        <h4 className="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center">
          <MessageSquare className="w-4 h-4 text-emerald-600 mr-2" /> Recent Community Posts
        </h4>
        <Link
          to="/admin/posts"
          className="text-xs font-semibold text-blue-600 hover:text-blue-700 inline-flex items-center"
        >
          All Posts <ArrowRight className="w-3.5 h-3.5 ml-1" />
        </Link>
      </div>

      {/* Body */}
      <div className="p-4 flex-1 flex flex-col justify-center">
        {!hasPosts ? (
          <EmptyState
            icon={MessageSquare}
            title="No Posts Published"
            description="Platform posts will appear here as members publish updates."
          />
        ) : (
          <div className="divide-y divide-slate-100">
            {posts.map((post, idx) => {
              const authorName = post.member?.name || post.author || 'Platform Member';
              const authorInitial = authorName.charAt(0).toUpperCase();

              return (
                <div key={post.id || idx} className="py-3 first:pt-0 last:pb-0 flex items-start space-x-3">
                  {post.member?.avatar_url || post.member?.profile_photo ? (
                    <img
                      src={post.member.avatar_url || post.member.profile_photo}
                      alt={authorName}
                      className="w-8 h-8 rounded-full object-cover ring-1 ring-slate-200 shrink-0 mt-0.5"
                      onError={(e) => {
                        e.target.style.display = 'none';
                      }}
                    />
                  ) : (
                    <div className="w-8 h-8 rounded-full bg-emerald-100 text-emerald-700 font-bold text-xs flex items-center justify-center shrink-0 mt-0.5">
                      {authorInitial}
                    </div>
                  )}

                  <div className="flex-1 min-w-0">
                    <div className="flex items-center justify-between">
                      <span className="text-xs font-bold text-slate-800 truncate">
                        {authorName}
                      </span>
                      <span className="text-[10px] text-slate-400 shrink-0">
                        {post.created_at_human || post.created || 'Recently'}
                      </span>
                    </div>

                    <p className="text-xs text-slate-600 line-clamp-1 mt-0.5">
                      {post.body || '[Media Attachment / Status Update]'}
                    </p>

                    {post.media_type && (
                      <div className="mt-1.5 flex items-center space-x-1.5">
                        <span className="inline-flex items-center text-[10px] font-semibold bg-sky-50 text-sky-700 px-2 py-0.5 rounded-sm border border-sky-200/60">
                          <Image className="w-2.5 h-2.5 mr-1" />
                          {post.media_type.charAt(0).toUpperCase() + post.media_type.slice(1)}
                        </span>
                      </div>
                    )}
                  </div>
                </div>
              );
            })}
          </div>
        )}
      </div>
    </div>
  );
}

export default RecentPostsFeed;
