<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessPage;
use App\Models\BusinessVerification;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BusinessPageManagementController extends Controller
{
    /**
     * Display a listing of business pages with search, verification & status filtering, and pagination.
     */
    /**
     * Build filtered query for business pages.
     */
    protected function buildFilteredQuery(Request $request)
    {
        $search = trim((string) $request->input('q'));
        $verificationStatus = $request->input('verification_status');
        $status = $request->input('status');
        $category = $request->input('category');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = BusinessPage::query();

        // Search Filter (Page ID, Page Name, Page Username, Owner Name/Email/User ID, Phone, Email)
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('id', $search)
                  ->orWhere('page_id', 'like', "%{$search}%")
                  ->orWhere('page_name', 'like', "%{$search}%")
                  ->orWhere('page_username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%")
                  ->orWhereHas('owner', function ($oq) use ($search) {
                      $oq->where('name', 'like', "%{$search}%")
                         ->orWhere('user_id', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Verification Status Filter
        if ($verificationStatus === 'verified') {
            $query->where('is_verified', true);
        } elseif ($verificationStatus === 'pending') {
            $query->where('is_verified', false)->whereHas('latestVerification', function ($vq) {
                $vq->where('status', 'pending');
            });
        } elseif ($verificationStatus === 'unverified') {
            $query->where('is_verified', false);
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
            ->with(['owner', 'latestVerification'])
            ->withCount(['acceptedFollowers', 'posts', 'reviews']);

        $businessPages = $query->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        // Metrics Summary
        $totalCount = BusinessPage::count();
        $verifiedCount = BusinessPage::where('is_verified', true)->count();
        $pendingVerificationCount = BusinessVerification::where('status', 'pending')->count();
        $totalFollowersCount = BusinessPage::all()->sum(function ($p) {
            return $p->acceptedFollowers()->count();
        });

        $categories = BusinessPage::categories();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'businessPages' => $businessPages,
                'totalCount' => $totalCount,
                'verifiedCount' => $verifiedCount,
                'pendingVerificationCount' => $pendingVerificationCount,
                'totalFollowersCount' => $totalFollowersCount,
                'categories' => $categories,
            ]);
        }

        return view('admin.business_pages.index', compact(
            'businessPages',
            'search',
            'verificationStatus',
            'status',
            'category',
            'dateFrom',
            'dateTo',
            'totalCount',
            'verifiedCount',
            'pendingVerificationCount',
            'totalFollowersCount',
            'categories'
        ));
    }

    /**
     * Display detailed read-only inspection of a business page, followers, and reviews.
     */
    public function show(BusinessPage $businessPage)
    {
        $businessPage->load([
            'owner',
            'verifications.member',
            'acceptedFollowers.member',
            'reviews.member',
            'teamMembers.member'
        ])->loadCount(['acceptedFollowers', 'posts', 'reviews']);

        // Load Recent Business Posts
        $posts = Post::where('business_page_id', $businessPage->id)
            ->with(['member', 'originalPost.member'])
            ->withCount(['likes', 'comments'])
            ->latest()
            ->take(15)
            ->get();

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'businessPage' => $businessPage,
                'posts' => $posts,
            ]);
        }

        return view('admin.business_pages.show', compact('businessPage', 'posts'));
    }

    /**
     * Show the form for editing the specified business page.
     */
    public function edit(BusinessPage $businessPage)
    {
        $businessPage->load('owner');
        $categories = BusinessPage::categories();
        if (filled($businessPage->category) && ! in_array($businessPage->category, $categories)) {
            $categories[] = $businessPage->category;
        }
        $visibilities = BusinessPage::VISIBILITIES;
        $countries = BusinessPage::COUNTRIES;
        $dialingCodes = BusinessPage::DIALING_CODES;

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'businessPage' => $businessPage,
                'categories' => $categories,
                'visibilities' => $visibilities,
                'countries' => $countries,
                'dialingCodes' => $dialingCodes,
            ]);
        }

        return view('admin.business_pages.edit', compact(
            'businessPage',
            'categories',
            'visibilities',
            'countries',
            'dialingCodes'
        ));
    }

    /**
     * Update the specified business page in storage.
     */
    public function update(Request $request, BusinessPage $businessPage)
    {
        $validated = $request->validate([
            'page_name' => ['required', 'string', 'min:3', 'max:255'],
            'page_username' => [
                'nullable',
                'string',
                'max:100',
                'regex:/^[a-zA-Z0-9._-]+$/',
                Rule::unique('business_pages', 'page_username')->ignore($businessPage->id),
            ],
            'category' => ['required', 'string', 'max:100'],
            'visibility' => ['required', 'string', Rule::in(BusinessPage::VISIBILITIES)],
            'description' => ['nullable', 'string', 'max:2000'],
            'website' => ['nullable', 'url', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'dialing_code' => ['nullable', 'string', 'max:10'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'zip' => ['nullable', 'string', 'max:20'],
            'remove_logo' => ['nullable', 'boolean'],
            'remove_cover' => ['nullable', 'boolean'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'cover_photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:10240'],
        ]);

        $oldLogo = $businessPage->profile_photo;
        $oldCover = $businessPage->cover_photo;

        // Handle profile photo
        if ($request->boolean('remove_logo')) {
            $validated['profile_photo'] = null;
        } elseif ($request->hasFile('logo')) {
            $logoPath = $this->storeFile($request->file('logo'), 'uploads/business_pages/logos');
            $validated['profile_photo'] = $logoPath;
        }

        // Handle cover photo
        if ($request->boolean('remove_cover')) {
            $validated['cover_photo'] = null;
        } elseif ($request->hasFile('cover_photo')) {
            $coverPath = $this->storeFile($request->file('cover_photo'), 'uploads/business_pages/covers');
            $validated['cover_photo'] = $coverPath;
        }

        unset($validated['remove_logo'], $validated['remove_cover']);

        // Update ONLY this business page record instance
        $businessPage->update($validated);

        // Safe media cleanup ONLY after DB update succeeds
        if ($request->boolean('remove_logo') || $request->hasFile('logo')) {
            if ($oldLogo && File::exists(public_path($oldLogo))) {
                File::delete(public_path($oldLogo));
            }
        }
        if ($request->boolean('remove_cover') || $request->hasFile('cover_photo')) {
            if ($oldCover && File::exists(public_path($oldCover))) {
                File::delete(public_path($oldCover));
            }
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => "Business Page '{$businessPage->page_name}' has been updated successfully.",
                'businessPage' => $businessPage,
            ]);
        }

        return redirect()->route('admin.business-pages.show', $businessPage)
            ->with('success', "Business Page '{$businessPage->page_name}' has been updated successfully.");
    }

    /**
     * Store uploaded file safely into target directory.
     */
    private function storeFile($file, string $directory): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $fileName = Str::uuid() . '.' . $extension;
        $type = str_contains($directory, 'logo') ? 'logo' : 'cover';

        try {
            $storedPath = app(\App\Http\Controllers\ImageCompressionController::class)->compressAndStore(
                $file,
                $directory,
                $type,
                $fileName,
                'public_uploads',
                \App\Services\ContentModeration\ContentModerationService::CONTEXT_BUSINESS_IMAGE
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'image' => ['Failed to process or store the business page image.'],
            ]);
        }

        if (!$storedPath) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'image' => ['Failed to process or store the business page image.'],
            ]);
        }

        return $storedPath;
    }

    /**
     * Update business page status (e.g. active, suspended, hidden).
     */
    public function updateStatus(Request $request, BusinessPage $businessPage)
    {
        $status = $request->input('status', 'active');

        $businessPage->update(['status' => $status]);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => "Business Page '{$businessPage->page_name}' status updated to {$status}.",
            ]);
        }

        return redirect()->back()->with('success', "Business Page '{$businessPage->page_name}' status updated to {$status}.");
    }

    /**
     * Handle verification application (Approve or Reject).
     */
    public function handleVerification(Request $request, BusinessPage $businessPage)
    {
        $action = $request->input('action', 'approve');
        $notes = $request->input('admin_notes');

        $verification = $businessPage->latestVerification;

        if ($action === 'approve') {
            $businessPage->update(['is_verified' => true]);

            if ($verification) {
                $verification->update([
                    'status' => 'approved',
                    'admin_notes' => $notes,
                    'reviewed_at' => now(),
                ]);
            }

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => true,
                    'message' => "Business Page '{$businessPage->page_name}' has been verified successfully.",
                ]);
            }

            return redirect()->back()->with('success', "Business Page '{$businessPage->page_name}' has been verified successfully.");
        } else {
            $businessPage->update(['is_verified' => false]);

            if ($verification) {
                $verification->update([
                    'status' => 'rejected',
                    'admin_notes' => $notes,
                    'reviewed_at' => now(),
                ]);
            }

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => true,
                    'message' => "Verification request for '{$businessPage->page_name}' has been rejected.",
                ]);
            }

            return redirect()->back()->with('success', "Verification request for '{$businessPage->page_name}' has been rejected.");
        }
    }

    /**
     * Soft delete a business page.
     */
    public function destroy(BusinessPage $businessPage)
    {
        $name = $businessPage->page_name;

        $businessPage->delete();

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => "Business Page '{$name}' has been deleted.",
            ]);
        }

        return redirect()->route('admin.business-pages.index')->with('success', "Business Page '{$name}' has been deleted.");
    }

    /**
     * Process bulk actions on selected business pages.
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|string|in:verify,unverify,suspend,activate,delete',
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:business_pages,id'
        ]);

        $action = $request->input('action');
        $ids = $request->input('ids');

        if ($action === 'verify') {
            BusinessPage::whereIn('id', $ids)->update(['is_verified' => true]);
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => true, 'message' => count($ids) . ' business pages verified.']);
            }
            return redirect()->back()->with('success', count($ids) . ' business pages verified.');
        } elseif ($action === 'unverify') {
            BusinessPage::whereIn('id', $ids)->update(['is_verified' => false]);
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => true, 'message' => count($ids) . ' business pages unverified.']);
            }
            return redirect()->back()->with('success', count($ids) . ' business pages unverified.');
        } elseif ($action === 'suspend') {
            BusinessPage::whereIn('id', $ids)->update(['status' => 'suspended']);
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => true, 'message' => count($ids) . ' business pages suspended.']);
            }
            return redirect()->back()->with('success', count($ids) . ' business pages suspended.');
        } elseif ($action === 'activate') {
            BusinessPage::whereIn('id', $ids)->update(['status' => 'active']);
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => true, 'message' => count($ids) . ' business pages activated.']);
            }
            return redirect()->back()->with('success', count($ids) . ' business pages activated.');
        } elseif ($action === 'delete') {
            BusinessPage::whereIn('id', $ids)->delete();
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => true, 'message' => count($ids) . ' business pages deleted.']);
            }
            return redirect()->back()->with('success', count($ids) . ' business pages deleted.');
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['success' => false, 'message' => 'Invalid bulk action.'], 422);
        }

        return redirect()->back()->with('error', 'Invalid bulk action.');
    }

    /**
     * Export business pages list to CSV stream.
     */
    public function export(Request $request)
    {
        $query = $this->buildFilteredQuery($request)
            ->with(['owner', 'latestVerification'])
            ->withCount(['acceptedFollowers', 'posts', 'reviews'])
            ->latest('created_at');

        $filename = 'business_pages_export_' . date('Y_m_d_His') . '.csv';

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
            fputcsv($file, ['Page ID', 'Business Name', 'Username', 'Owner Name', 'Owner User ID', 'Category', 'Email', 'Phone', 'Verified', 'Status', 'Followers', 'Posts', 'Reviews', 'Created Date']);

            $query->chunk(200, function ($pages) use ($file) {
                foreach ($pages as $p) {
                    fputcsv($file, [
                        $p->page_id ?: $p->id,
                        $p->page_name,
                        $p->page_username,
                        $p->owner?->name ?? 'N/A',
                        $p->owner?->user_id ?? 'N/A',
                        $p->category,
                        $p->email,
                        $p->phone,
                        $p->is_verified ? 'Yes' : 'No',
                        ucfirst((string) $p->status),
                        $p->accepted_followers_count,
                        $p->posts_count,
                        $p->reviews_count,
                        $p->created_at?->format('Y-m-d H:i:s'),
                    ]);
                }
            });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
