import React from 'react';
import { createRoot } from 'react-dom/client';
import { BrowserRouter } from 'react-router-dom';
import { ReplyItem } from './components/posts/ReplyItem';
import { CommentItem } from './components/posts/CommentItem';

const currentUser = {
  id: 10,
  name: 'Bob Replier',
  user_id: 'bob_replier_10',
  is_verified: true,
  profile_photo: 'uploads/profile/bob_photo_123.jpg',
};

const authorUser = {
  id: 5,
  name: 'Alice PostOwner',
  user_id: 'alice_owner_5',
  is_verified: true,
  profile_photo: 'uploads/profile/alice_photo_456.jpg',
};

const oauthUser = {
  id: 15,
  name: 'Charlie Google',
  user_id: 'charlie_15',
  is_verified: true,
  profile_photo: 'https://lh3.googleusercontent.com/a/charlie_avatar_789',
};

const noPhotoUser = {
  id: 20,
  name: 'David NoPhoto',
  user_id: 'david_20',
  is_verified: false,
  profile_photo: null,
};

const repliesList = [
  {
    id: 201,
    post_id: 1,
    parent_id: 101,
    member_id: 10,
    comment: 'Reply #1 with local uploaded profile photo',
    created_at: '2026-09-06T10:05:00.000000Z',
    member: currentUser,
    reactions: [],
    reactions_count: 0,
  },
  {
    id: 202,
    post_id: 1,
    parent_id: 101,
    member_id: 15,
    comment: 'Reply #2 with external Google OAuth photo',
    created_at: '2026-09-06T10:06:00.000000Z',
    member: oauthUser,
    reactions: [],
    reactions_count: 0,
  },
  {
    id: 203,
    post_id: 1,
    parent_id: 101,
    member_id: 20,
    comment: 'Reply #3 with no photo (initials fallback)',
    created_at: '2026-09-06T10:07:00.000000Z',
    member: noPhotoUser,
    reactions: [],
    reactions_count: 0,
  },
];

const commentWithReplies = {
  id: 101,
  post_id: 1,
  member_id: 5,
  comment: 'Root comment by Alice with uploaded photo',
  created_at: '2026-09-06T10:00:00.000000Z',
  parent_id: null,
  replies_count: 3,
  member: authorUser,
  reactions: [],
  reactions_count: 0,
  replies: repliesList,
};

function TestAvatarApp() {
  return (
    <BrowserRouter>
      <div style={{ padding: 24, maxWidth: 650, margin: '0 auto', background: '#fff' }}>
        <h1 id="test-title">Comment Reply Avatar Verification</h1>
        <div id="comment-container">
          <CommentItem
            comment={commentWithReplies}
            currentUser={currentUser}
            post={{ id: 1, member_id: 5 }}
          />
        </div>
      </div>
    </BrowserRouter>
  );
}

const root = createRoot(document.getElementById('root'));
root.render(<TestAvatarApp />);
