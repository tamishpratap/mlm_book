<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\GroupInvitation;
use App\Models\GroupMember;
use App\Models\Member;
use App\Models\Post;
use App\Notifications\SystemNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GroupController extends Controller
{
    public function index(Request $request)
    {
        $member = auth('member')->user();

        // My Groups
        $myGroupIds = GroupMember::query()
            ->where('member_id', $member->id)
            ->where('status', 'accepted')
            ->pluck('group_id');

        $myGroups = Group::query()
            ->withCount(['acceptedMembers as members_count'])
            ->whereIn('id', $myGroupIds)
            ->latest('created_at')
            ->get();

        // Search & Suggested Groups Query
        $query = Group::query()
            ->withCount(['acceptedMembers as members_count'])
            ->whereNotIn('id', $myGroupIds)
            ->where('privacy', '!=', 'hidden');

        if ($request->filled('search')) {
            $search = '%'.$request->query('search').'%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('description', 'like', $search)
                    ->orWhere('category', 'like', $search);
            });
        }

        if ($request->filled('category')) {
            $query->where('category', $request->query('category'));
        }

        $suggestedGroups = $query->latest('created_at')->paginate(12)->withQueryString();

        $categories = [
            'Technology', 'Business', 'Education', 'Gaming', 'Sports',
            'Fitness', 'Travel', 'Music', 'Movies', 'Crypto',
            'Programming', 'Finance', 'Photography', 'General',
        ];

        return view('member.groups.index', compact('myGroups', 'suggestedGroups', 'categories'));
    }

    public function create()
    {
        $categories = [
            'Technology', 'Business', 'Education', 'Gaming', 'Sports',
            'Fitness', 'Travel', 'Music', 'Movies', 'Crypto',
            'Programming', 'Finance', 'Photography', 'General',
        ];
        return view('member.groups.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['required', 'string'],
            'privacy' => ['required', 'in:public,private,hidden'],
            'rules' => ['nullable', 'string'],
            'cover_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $member = auth('member')->user();
        $slug = Str::slug($validated['name']).'-'.Str::random(6);

        $coverPath = null;
        if ($request->hasFile('cover_photo')) {
            $coverPath = $this->storeFile($request->file('cover_photo'), 'uploads/groups/covers');
        }

        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $this->storeFile($request->file('logo'), 'uploads/groups/logos');
        }

        $group = Group::query()->create([
            'owner_id' => $member->id,
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'category' => $validated['category'],
            'privacy' => $validated['privacy'],
            'rules' => $validated['rules'] ?? null,
            'cover_photo' => $coverPath,
            'logo' => $logoPath,
        ]);

        // Add owner to group_members
        GroupMember::query()->create([
            'group_id' => $group->id,
            'member_id' => $member->id,
            'role' => 'owner',
            'status' => 'accepted',
            'joined_at' => now(),
        ]);

        return redirect()->route('member.groups.show', $group)->with('success', 'Community created successfully!');
    }

    public function show(Request $request, Group $group)
    {
        $member = auth('member')->user();
        $tab = $request->query('tab', 'feed');

        $group->loadCount(['acceptedMembers as members_count', 'posts as posts_count']);
        $userRole = $group->memberRole($member->id);
        $isMember = $group->isMember($member->id);
        $isPending = $group->isPending($member->id);

        // Fetch Posts if Tab is Feed
        $posts = collect();
        $announcements = collect();
        if ($tab === 'feed' && ($group->privacy === 'public' || $isMember)) {
            $announcements = Post::query()
                ->with(['member', 'likes', 'reactions'])
                ->withCount([
                    'likes', 'reactions', 'shares', 'savedPosts',
                    'comments' => fn ($cq) => $cq->whereNull('parent_id'),
                ])
                ->where('group_id', $group->id)
                ->where('is_announcement', true)
                ->latest('created_at')
                ->get();

            $posts = Post::query()
                ->with(['member', 'likes', 'reactions'])
                ->withCount([
                    'likes', 'reactions', 'shares', 'savedPosts',
                    'comments' => fn ($cq) => $cq->whereNull('parent_id'),
                ])
                ->where('group_id', $group->id)
                ->latest('is_pinned')
                ->latest('created_at')
                ->paginate(10);
        }

        // Fetch Members if Tab is Members
        $membersList = collect();
        if ($tab === 'members') {
            $membersList = GroupMember::query()
                ->with('member')
                ->where('group_id', $group->id)
                ->where('status', 'accepted')
                ->orderByRaw("FIELD(role, 'owner', 'admin', 'moderator', 'member')")
                ->paginate(20);
        }

        // Fetch Requests if Tab is Requests
        $pendingRequests = collect();
        if ($tab === 'requests' && $group->isAdmin($member->id)) {
            $pendingRequests = GroupMember::query()
                ->with('member')
                ->where('group_id', $group->id)
                ->where('status', 'pending')
                ->latest('created_at')
                ->get();
        }

        // Fetch Media if Tab is Media
        $mediaPosts = collect();
        if ($tab === 'media') {
            $mediaPosts = Post::query()
                ->where('group_id', $group->id)
                ->whereNotNull('media_path')
                ->latest('created_at')
                ->get();
        }

        return view('member.groups.show', compact(
            'group', 'tab', 'userRole', 'isMember', 'isPending',
            'posts', 'announcements', 'membersList', 'pendingRequests', 'mediaPosts'
        ));
    }

    public function edit(Group $group)
    {
        abort_unless($group->isOwner(auth('member')->id()), 403);
        $categories = [
            'Technology', 'Business', 'Education', 'Gaming', 'Sports',
            'Fitness', 'Travel', 'Music', 'Movies', 'Crypto',
            'Programming', 'Finance', 'Photography', 'General',
        ];
        return view('member.groups.edit', compact('group', 'categories'));
    }

    public function update(Request $request, Group $group)
    {
        abort_unless($group->isOwner(auth('member')->id()), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['required', 'string'],
            'privacy' => ['required', 'in:public,private,hidden'],
            'rules' => ['nullable', 'string'],
            'cover_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        if ($request->hasFile('cover_photo')) {
            $validated['cover_photo'] = $this->storeFile($request->file('cover_photo'), 'uploads/groups/covers');
        }

        if ($request->hasFile('logo')) {
            $validated['logo'] = $this->storeFile($request->file('logo'), 'uploads/groups/logos');
        }

        $group->update($validated);

        return redirect()->route('member.groups.show', $group)->with('success', 'Community settings updated successfully!');
    }

    public function destroy(Group $group)
    {
        abort_unless($group->isOwner(auth('member')->id()), 403);
        $group->delete();

        return redirect()->route('member.groups.index')->with('success', 'Community deleted.');
    }

    public function join(Request $request, Group $group)
    {
        $member = auth('member')->user();
        $existing = GroupMember::query()->where('group_id', $group->id)->where('member_id', $member->id)->first();

        if ($existing) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => true, 'status' => $existing->status]);
            }
            return back();
        }

        $status = $group->privacy === 'public' ? 'accepted' : 'pending';

        $gm = GroupMember::query()->create([
            'group_id' => $group->id,
            'member_id' => $member->id,
            'role' => 'member',
            'status' => $status,
            'joined_at' => now(),
        ]);

        if ($status === 'pending') {
            Member::query()->find($group->owner_id)?->notify(new SystemNotification(
                'Community join request',
                "{$member->name} requested to join {$group->name}.",
                route('member.groups.show', ['group' => $group, 'tab' => 'requests'])
            ));
        }

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'status' => $status,
                'message' => $status === 'accepted' ? 'Joined community!' : 'Join request sent!',
            ]);
        }

        return back()->with('success', $status === 'accepted' ? 'Joined community!' : 'Join request sent!');
    }

    public function leave(Group $group)
    {
        $member = auth('member')->user();
        abort_if($group->isOwner($member->id), 400, 'Community owner cannot leave community. Transfer ownership or delete community.');

        GroupMember::query()->where('group_id', $group->id)->where('member_id', $member->id)->delete();

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json(['success' => true, 'message' => 'Left community.']);
        }

        return back()->with('success', 'Left community.');
    }

    public function handleRequest(Request $request, Group $group, GroupMember $memberRecord)
    {
        abort_unless($group->isAdmin(auth('member')->id()), 403);
        $action = $request->input('action', 'approve');

        if ($action === 'approve') {
            $memberRecord->update(['status' => 'accepted']);
            $memberRecord->member?->notify(new SystemNotification(
                'Community request approved',
                "Your request to join {$group->name} was approved.",
                route('member.groups.show', $group)
            ));
            $msg = 'Request approved.';
        } else {
            $memberRecord->delete();
            $msg = 'Request rejected.';
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return back()->with('success', $msg);
    }

    public function invite(Request $request, Group $group)
    {
        $validated = $request->validate([
            'invited_id' => ['required', 'exists:members,id'],
        ]);

        $inviter = auth('member')->user();

        $invitation = GroupInvitation::query()->firstOrCreate(
            ['group_id' => $group->id, 'invited_id' => $validated['invited_id']],
            ['inviter_id' => $inviter->id, 'status' => 'pending']
        );

        Member::query()->find($validated['invited_id'])?->notify(new SystemNotification(
            'Community invitation',
            "{$inviter->name} invited you to join {$group->name}.",
            route('member.groups.show', $group)
        ));

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Invitation sent!']);
        }

        return back()->with('success', 'Invitation sent!');
    }

    public function updateRole(Request $request, Group $group, GroupMember $memberRecord)
    {
        abort_unless($group->isOwner(auth('member')->id()), 403);
        $newRole = $request->input('role', 'member');

        $memberRecord->update(['role' => $newRole]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Member role updated to '.ucfirst($newRole)]);
        }

        return back()->with('success', 'Member role updated.');
    }

    public function removeMember(Group $group, GroupMember $memberRecord)
    {
        abort_unless($group->isAdmin(auth('member')->id()), 403);
        abort_if($memberRecord->role === 'owner', 403);

        $memberRecord->delete();

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json(['success' => true, 'message' => 'Member removed from community.']);
        }

        return back()->with('success', 'Member removed from community.');
    }

    public function storePost(Request $request, Group $group)
    {
        $member = auth('member')->user();
        abort_unless($group->isMember($member->id), 403);

        if ($request->hasFile('media')) {
            $file = $request->file('media');
            $mime = (string) $file->getMimeType();
            $ext = strtolower($file->getClientOriginalExtension() ?: '');
            if (str_starts_with($mime, 'video/') || in_array($ext, ['mp4', 'mov', 'webm', 'm4v', 'avi', 'mkv'], true) || in_array($mime, ['video/mp4', 'video/webm', 'video/quicktime'], true)) {
                throw ValidationException::withMessages([
                    'media' => 'Videos can only be posted from a Business Page.',
                ]);
            }
        }

        $validated = $request->validate([
            'body' => ['required_without:media', 'nullable', 'string'],
            'media' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $mediaType = null;
        $mediaPath = null;

        if ($request->hasFile('media')) {
            $file = $request->file('media');
            $mediaType = 'image';
            $mediaPath = $this->storeFile($file, 'uploads/posts/images');
        }

        $post = Post::query()->create([
            'member_id' => $member->id,
            'group_id' => $group->id,
            'body' => $validated['body'] ?? null,
            'media_type' => $mediaType,
            'media_path' => $mediaPath,
        ]);

        return redirect()->route('member.groups.show', $group)->with('success', 'Post published to Community!');
    }

    public function toggleAnnouncement(Request $request, Group $group, Post $post)
    {
        abort_unless($group->isAdmin(auth('member')->id()), 403);
        abort_unless($post->group_id === $group->id, 400);

        $post->update(['is_announcement' => ! $post->is_announcement]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'is_announcement' => $post->is_announcement,
                'message' => $post->is_announcement ? 'Marked as Announcement' : 'Removed from Announcements',
            ]);
        }

        return back()->with('success', 'Announcement status updated.');
    }

    private function storeFile($file, string $directory): ?string
    {
        try {
            $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
            $filename = time().'_'.Str::random(10).'.'.$ext;
            $type = str_contains($directory, 'logo') ? 'logo' : (str_contains($directory, 'cover') ? 'cover' : 'post');

            return app(\App\Http\Controllers\ImageCompressionController::class)->compressAndStore(
                $file,
                $directory,
                $type,
                $filename,
                'public_uploads'
            );
        } catch (\Throwable) {
            return null;
        }
    }
}
