<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Community;
use App\Models\CommunityMember;
use App\Models\CommunityReport;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CommunityManagementController extends Controller
{
    /**
     * Display a listing of communities with search, visibility & status filtering, and pagination.
     */
    /**
     * Build filtered query for communities.
     */
    protected function buildFilteredQuery(Request $request)
    {
        $search = trim((string) $request->input('q'));
        $visibility = $request->input('visibility');
        $status = $request->input('status');
        $category = $request->input('category');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = Community::query();

        // Search Filter (Community ID, Name, Slug, Description, Owner Name/Email/User ID)
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('id', $search)
                  ->orWhere('community_id', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhereHas('owner', function ($oq) use ($search) {
                      $oq->where('name', 'like', "%{$search}%")
                         ->orWhere('user_id', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Visibility Filter
        if (!empty($visibility)) {
            $query->where('visibility', $visibility);
        }

        // Status Filter
        if (!empty($status)) {
            $query->where('status', $status);
        }

        // Category Filter
        if (!empty($category)) {
            $query->where('category', $category);
        }

        // Date Range Filter
        if (!empty($dateFrom)) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if (!empty($dateTo)) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        return $query;
    }

    public function index(Request $request)
    {
        $query = $this->buildFilteredQuery($request)
            ->with(['owner'])
            ->withCount(['acceptedMembers', 'pendingMembers', 'reports']);

        $communities = $query->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        // Metrics Summary
        $totalCount = Community::count();
        $activeCount = Community::where('status', 'active')->count();
        $privateCount = Community::where('visibility', 'private')->count();
        $totalMembersCount = CommunityMember::where('status', 'accepted')->count();

        $categories = Community::CATEGORIES;

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'communities' => $communities,
                'totalCount' => $totalCount,
                'activeCount' => $activeCount,
                'privateCount' => $privateCount,
                'totalMembersCount' => $totalMembersCount,
                'categories' => $categories,
            ]);
        }

        return view('admin.communities.index', compact(
            'communities',
            'search',
            'visibility',
            'status',
            'category',
            'dateFrom',
            'dateTo',
            'totalCount',
            'activeCount',
            'privateCount',
            'totalMembersCount',
            'categories'
        ));
    }

    /**
     * Display detailed read-only inspection of a community.
     */
    public function show(Community $community)
    {
        $community->load([
            'owner',
            'acceptedMembers.member',
            'pendingMembers.member',
            'reports.reporter',
            'reports.resolvedBy'
        ])->loadCount(['acceptedMembers', 'pendingMembers', 'reports']);

        // Load Moderators (owner, admin, moderator roles)
        $moderators = $community->members()
            ->whereIn('role', ['owner', 'admin', 'moderator'])
            ->where('status', 'accepted')
            ->with('member')
            ->get();

        // Load Recent Community Posts
        $posts = Post::where('community_id', $community->id)
            ->with(['member', 'originalPost.member'])
            ->withCount(['likes', 'comments'])
            ->latest()
            ->get();

        $postsCount = Post::where('community_id', $community->id)->count();

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'community' => $community,
                'moderators' => $moderators,
                'posts' => $posts,
                'postsCount' => $postsCount,
            ]);
        }

        return view('admin.communities.show', compact('community', 'moderators', 'posts', 'postsCount'));
    }

    /**
     * Show the form for editing the specified community.
     */
    public function edit(Community $community)
    {
        $community->load('owner');
        $categories = Community::CATEGORIES;
        $visibilities = Community::VISIBILITIES;

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'community' => $community,
                'categories' => $categories,
                'visibilities' => $visibilities,
            ]);
        }

        return view('admin.communities.edit', compact(
            'community',
            'categories',
            'visibilities'
        ));
    }

    /**
     * Update the specified community in storage.
     */
    public function update(Request $request, Community $community)
    {
        // Sanitize string inputs
        $name = trim((string) $request->input('name'));
        $slug = trim((string) $request->input('slug'));
        $description = trim((string) $request->input('description'));
        $rules = trim((string) $request->input('rules'));
        $tags = trim((string) $request->input('tags'));

        $request->merge([
            'name' => $name,
            'slug' => filled($slug) ? Str::slug($slug) : null,
            'description' => filled($description) ? $description : null,
            'rules' => filled($rules) ? $rules : null,
            'tags' => filled($tags) ? $tags : null,
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:100',
                'regex:/^[a-zA-Z0-9._-]+$/',
                Rule::unique('communities', 'slug')->ignore($community->id),
            ],
            'category' => ['required', 'string', Rule::in(array_values(array_unique(array_merge(Community::CATEGORIES, filled($community->category) ? [$community->category] : []))))],
            'visibility' => ['required', 'string', Rule::in(['public', 'private', 'invite_only', 'secret'])],
            'status' => ['required', 'string', Rule::in(['active', 'suspended'])],
            'join_approval_mode' => ['required', 'string', Rule::in(['auto', 'manual'])],
            'posting_permissions' => ['required', 'string', Rule::in(['everyone', 'members_only', 'moderators_admins', 'admins_only', 'owner_only'])],
            'description' => ['nullable', 'string', 'max:5000'],
            'rules' => ['nullable', 'string', 'max:5000'],
            'tags' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'cover_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_logo' => ['nullable', 'boolean'],
            'remove_cover' => ['nullable', 'boolean'],
        ], [
            'name.required' => 'Community name is required.',
            'name.min' => 'Community name must be at least 3 characters.',
            'category.required' => 'Please select a valid category.',
            'visibility.in' => 'Selected visibility is invalid.',
            'status.in' => 'Status must be active or suspended.',
            'logo.mimes' => 'Logo must be a JPG, JPEG, PNG, or WEBP image.',
            'logo.max' => 'Logo file size cannot exceed 5MB.',
            'cover_photo.mimes' => 'Cover banner must be a JPG, JPEG, PNG, or WEBP image.',
            'cover_photo.max' => 'Cover banner file size cannot exceed 5MB.',
        ]);

        $oldLogo = $community->logo;
        $oldCover = $community->cover_photo;

        // Handle Logo Removal / Replacement
        if ($request->boolean('remove_logo')) {
            $validated['logo'] = null;
        } elseif ($request->hasFile('logo')) {
            $logoPath = $this->storeFile($request->file('logo'), 'uploads/communities/logos');
            $validated['logo'] = $logoPath;
        }

        // Handle Cover Photo Removal / Replacement
        if ($request->boolean('remove_cover')) {
            $validated['cover_photo'] = null;
        } elseif ($request->hasFile('cover_photo')) {
            $coverPath = $this->storeFile($request->file('cover_photo'), 'uploads/communities/covers');
            $validated['cover_photo'] = $coverPath;
        }

        // Slug generation/preservation
        if (empty($validated['slug'])) {
            $baseSlug = Str::slug($validated['name']);
            $validated['slug'] = $baseSlug ?: 'community';
            if (Community::where('slug', $validated['slug'])->where('id', '!=', $community->id)->exists()) {
                $validated['slug'] = $validated['slug'] . '-' . Str::random(5);
            }
        }

        unset($validated['remove_logo'], $validated['remove_cover']);

        // Update ONLY this community record instance
        $community->update($validated);

        // ONLY delete old files after DB update succeeds
        if ($request->boolean('remove_logo') || ($request->hasFile('logo') && ! empty($validated['logo']))) {
            if ($oldLogo && File::exists(public_path($oldLogo))) {
                File::delete(public_path($oldLogo));
            }
        }
        if ($request->boolean('remove_cover') || ($request->hasFile('cover_photo') && ! empty($validated['cover_photo']))) {
            if ($oldCover && File::exists(public_path($oldCover))) {
                File::delete(public_path($oldCover));
            }
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => "Community '{$community->name}' has been updated successfully.",
                'community' => $community,
            ]);
        }

        return redirect()->route('admin.communities.show', $community)
            ->with('success', "Community '{$community->name}' has been updated successfully.");
    }

    /**
     * Store uploaded file safely into target directory.
     */
    private function storeFile($file, string $directory): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $fileName = Str::uuid() . '.' . $extension;
        $type = str_contains($directory, 'logo') ? 'logo' : 'cover';

        $storedPath = app(\App\Http\Controllers\ImageCompressionController::class)->compressAndStore(
            $file,
            $directory,
            $type,
            $fileName,
            'public_uploads'
        );

        return $storedPath ?: ($directory . '/' . $fileName);
    }

    /**
     * Update community status (e.g. active, suspended, hidden).
     */
    public function updateStatus(Request $request, Community $community)
    {
        $status = $request->input('status', 'active');

        $community->update(['status' => $status]);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => "Community '{$community->name}' status has been updated to {$status}.",
            ]);
        }

        return redirect()->back()->with('success', "Community '{$community->name}' status has been updated to {$status}.");
    }

    /**
     * Handle join request (Approve or Reject).
     */
    public function handleJoinRequest(Request $request, Community $community, CommunityMember $membership)
    {
        $action = $request->input('action', 'accept');

        if ($action === 'accept') {
            $membership->update([
                'status' => 'accepted',
                'joined_at' => now(),
            ]);

            $community->syncMemberCount();

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => true, 'message' => 'Join request approved.']);
            }

            return redirect()->back()->with('success', 'Join request approved.');
        } else {
            $membership->delete();

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => true, 'message' => 'Join request rejected.']);
            }

            return redirect()->back()->with('success', 'Join request rejected.');
        }
    }

    /**
     * Resolve a community report.
     */
    public function resolveReport(Request $request, CommunityReport $report)
    {
        $status = $request->input('status', 'resolved');

        $report->update([
            'status' => $status,
            'resolved_by_id' => auth()->id() ?? $report->community?->owner_id,
        ]);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => "Community Report #{$report->id} has been marked as {$status}.",
            ]);
        }

        return redirect()->back()->with('success', "Community Report #{$report->id} has been marked as {$status}.");
    }

    /**
     * Soft delete a community.
     */
    public function destroy(Community $community)
    {
        $name = $community->name;

        $community->delete();

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => "Community '{$name}' has been deleted.",
            ]);
        }

        return redirect()->route('admin.communities.index')->with('success', "Community '{$name}' has been deleted.");
    }

    /**
     * Process bulk actions on selected communities.
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|string|in:suspend,activate,delete',
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:communities,id'
        ]);

        $action = $request->input('action');
        $ids = $request->input('ids');

        if ($action === 'suspend') {
            Community::whereIn('id', $ids)->update(['status' => 'suspended']);
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => true, 'message' => count($ids) . ' communities have been suspended.']);
            }
            return redirect()->back()->with('success', count($ids) . ' communities have been suspended.');
        } elseif ($action === 'activate') {
            Community::whereIn('id', $ids)->update(['status' => 'active']);
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => true, 'message' => count($ids) . ' communities have been activated.']);
            }
            return redirect()->back()->with('success', count($ids) . ' communities have been activated.');
        } elseif ($action === 'delete') {
            Community::whereIn('id', $ids)->delete();
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => true, 'message' => count($ids) . ' communities have been deleted.']);
            }
            return redirect()->back()->with('success', count($ids) . ' communities have been deleted.');
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['success' => false, 'message' => 'Invalid bulk action.'], 422);
        }

        return redirect()->back()->with('error', 'Invalid bulk action.');
    }

    /**
     * Export communities list to CSV stream.
     */
    public function export(Request $request)
    {
        $query = $this->buildFilteredQuery($request)
            ->with(['owner'])
            ->withCount(['acceptedMembers', 'pendingMembers', 'reports'])
            ->latest('created_at');

        $filename = 'communities_export_' . date('Y_m_d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($query) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($file, ['Community ID', 'Name', 'Slug', 'Owner Name', 'Owner User ID', 'Category', 'Visibility', 'Status', 'Members', 'Posts', 'Pending Requests', 'Reports', 'Created Date']);

            $query->chunk(200, function ($communities) use ($file) {
                foreach ($communities as $c) {
                    fputcsv($file, [
                        $c->community_id ?: $c->id,
                        $c->name,
                        $c->slug,
                        $c->owner?->name ?? 'N/A',
                        $c->owner?->user_id ?? 'N/A',
                        $c->category,
                        ucfirst((string) $c->visibility),
                        ucfirst((string) $c->status),
                        $c->accepted_members_count,
                        $c->post_count,
                        $c->pending_members_count,
                        $c->reports_count,
                        $c->created_at?->format('Y-m-d H:i:s'),
                    ]);
                }
            });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
