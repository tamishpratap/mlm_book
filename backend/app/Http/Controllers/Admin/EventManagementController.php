<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessPageCategory;
use App\Models\Event;
use App\Models\EventResponse;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EventManagementController extends Controller
{
    /**
     * Display a listing of events with search, timeframe & status filtering, and pagination.
     */
    /**
     * Build filtered query for events.
     */
    protected function buildFilteredQuery(Request $request)
    {
        $search = trim((string) $request->input('q'));
        $timeframe = $request->input('timeframe');
        $status = $request->input('status');
        $eventType = $request->input('event_type');
        $category = $request->input('category');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = Event::query();

        // Search Filter (Event ID, Title, Short Description, Description, Category, Location, Organizer Name/Email/User ID)
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('id', $search)
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('short_description', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%")
                  ->orWhere('location_city', 'like', "%{$search}%")
                  ->orWhere('location_country', 'like', "%{$search}%")
                  ->orWhereHas('organizer', function ($oq) use ($search) {
                      $oq->where('name', 'like', "%{$search}%")
                         ->orWhere('user_id', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Timeframe Filter
        $today = now()->format('Y-m-d');
        if ($timeframe === 'upcoming') {
            $query->where('start_date', '>', $today);
        } elseif ($timeframe === 'ongoing') {
            $query->where('start_date', '<=', $today)->where(function ($q) use ($today) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $today);
            });
        } elseif ($timeframe === 'completed') {
            $query->where('start_date', '<', $today)->whereNotNull('end_date')->where('end_date', '<', $today);
        }

        // Status Filter
        if (!empty($status)) {
            if (in_array(strtolower($status), ['active', 'published'], true)) {
                $query->whereIn('status', ['active', 'published']);
            } else {
                $query->where('status', $status);
            }
        }

        // Event Type Filter (offline/online)
        if (!empty($eventType)) {
            $query->where('event_type', $eventType);
        }

        // Category Filter
        if (!empty($category)) {
            $query->where('category', $category);
        }

        // Date Range Filter
        if (!empty($dateFrom)) {
            $query->whereDate('start_date', '>=', $dateFrom);
        }
        if (!empty($dateTo)) {
            $query->whereDate('start_date', '<=', $dateTo);
        }

        return $query;
    }

    public function index(Request $request)
    {
        $today = now()->format('Y-m-d');
        $timeframe = $request->input('timeframe');

        $query = $this->buildFilteredQuery($request)
            ->with(['organizer'])
            ->withCount(['responses', 'invitations']);

        $events = $query->latest('start_date')
            ->paginate(15)
            ->withQueryString();

        // Metrics Summary
        $totalCount = Event::count();
        $upcomingCount = Event::where('start_date', '>', $today)->count();
        $ongoingCount = Event::where('start_date', '<=', $today)->where(function ($q) use ($today) {
            $q->whereNull('end_date')->orWhere('end_date', '>=', $today);
        })->count();
        $totalResponsesCount = EventResponse::count();

        $categories = BusinessPageCategory::getActiveCategoryNames();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'events' => $events,
                'totalCount' => $totalCount,
                'upcomingCount' => $upcomingCount,
                'ongoingCount' => $ongoingCount,
                'totalResponsesCount' => $totalResponsesCount,
                'categories' => $categories,
            ]);
        }

        return view('admin.events.index', compact(
            'events',
            'search',
            'timeframe',
            'status',
            'eventType',
            'category',
            'dateFrom',
            'dateTo',
            'totalCount',
            'upcomingCount',
            'ongoingCount',
            'totalResponsesCount',
            'categories'
        ));
    }

    /**
     * Display detailed read-only inspection of an event.
     */
    public function show(Event $event)
    {
        $event->load([
            'organizer',
            'responses.member',
            'invitations',
            'posts.member'
        ])->loadCount(['responses', 'invitations']);

        $goingCount = $event->responses()->where('response', 'going')->count();
        $interestedCount = $event->responses()->where('response', 'interested')->count();

        $posts = Post::where('event_id', $event->id)
            ->with(['member'])
            ->withCount(['likes', 'comments'])
            ->latest()
            ->get();

        $postsCount = Post::where('event_id', $event->id)->count();

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'event' => $event,
                'goingCount' => $goingCount,
                'interestedCount' => $interestedCount,
                'posts' => $posts,
                'postsCount' => $postsCount,
            ]);
        }

        return view('admin.events.show', compact('event', 'goingCount', 'interestedCount', 'posts', 'postsCount'));
    }

    /**
     * Show the form for editing the specified event.
     */
    public function edit(Event $event)
    {
        $event->load('organizer');

        $categories = array_values(array_unique(array_merge(
            BusinessPageCategory::getActiveCategoryNames(),
            filled($event->category) ? [$event->category] : []
        )));

        $eventTypes = [
            'offline' => 'Offline / In-Person Venue',
            'online' => 'Online Meeting / Webinar',
        ];

        $privacies = [
            'public' => 'Public Event',
            'private' => 'Private Event',
            'friends_only' => 'Friends Only',
        ];

        $statuses = [
            'published' => 'Published',
            'cancelled' => 'Cancelled',
            'hidden' => 'Hidden',
        ];

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'event' => $event,
                'categories' => $categories,
                'eventTypes' => $eventTypes,
                'privacies' => $privacies,
                'statuses' => $statuses,
            ]);
        }

        return view('admin.events.edit', compact(
            'event',
            'categories',
            'eventTypes',
            'privacies',
            'statuses'
        ));
    }

    /**
     * Update the specified event in storage.
     */
    public function update(Request $request, Event $event)
    {
        $title = trim((string) $request->input('title'));
        $shortDescription = trim((string) $request->input('short_description'));
        $description = trim((string) $request->input('description'));
        $locationAddress = trim((string) $request->input('location_address'));
        $locationCity = trim((string) $request->input('location_city'));
        $locationState = trim((string) $request->input('location_state'));
        $locationCountry = trim((string) $request->input('location_country'));
        $googleMapsLink = trim((string) $request->input('google_maps_link'));
        $meetingLink = trim((string) $request->input('meeting_link'));
        $meetingPassword = trim((string) $request->input('meeting_password'));

        $request->merge([
            'title' => $title,
            'short_description' => filled($shortDescription) ? $shortDescription : null,
            'description' => filled($description) ? $description : null,
            'location_address' => filled($locationAddress) ? $locationAddress : null,
            'location_city' => filled($locationCity) ? $locationCity : null,
            'location_state' => filled($locationState) ? $locationState : null,
            'location_country' => filled($locationCountry) ? $locationCountry : null,
            'google_maps_link' => filled($googleMapsLink) ? $googleMapsLink : null,
            'meeting_link' => filled($meetingLink) ? $meetingLink : null,
            'meeting_password' => filled($meetingPassword) ? $meetingPassword : null,
        ]);

        $allowedCategories = array_values(array_unique(array_merge(
            BusinessPageCategory::getActiveCategoryNames(),
            filled($event->category) ? [$event->category] : []
        )));
        $categoryValidation = ['required', 'string', 'max:100'];
        if (!empty($allowedCategories)) {
            $categoryValidation[] = Rule::in($allowedCategories);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'min:3', 'max:255'],
            'category' => $categoryValidation,
            'event_type' => ['required', 'string', Rule::in(['offline', 'online'])],
            'privacy' => ['required', 'string', Rule::in(['public', 'private', 'friends_only'])],
            'status' => ['required', 'string', Rule::in(['published', 'cancelled', 'hidden'])],
            'start_date' => ['required', 'date'],
            'start_time' => ['nullable'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'end_time' => ['nullable'],
            'timezone' => ['nullable', 'string', 'max:100'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:10000'],
            'location_name' => ['nullable', 'string', 'max:255'],
            'location_address' => ['nullable', 'string', 'max:255'],
            'location_city' => ['nullable', 'string', 'max:100'],
            'location_state' => ['nullable', 'string', 'max:100'],
            'location_country' => ['nullable', 'string', 'max:100'],
            'google_maps_link' => ['nullable', 'url', 'max:500'],
            'meeting_link' => ['nullable', 'url', 'max:500'],
            'meeting_password' => ['nullable', 'string', 'max:100'],
            'max_attendees' => ['nullable', 'integer', 'min:1'],
            'is_featured' => ['nullable', 'boolean'],
            'banner' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'cover_photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
            'remove_banner' => ['nullable', 'boolean'],
            'remove_cover' => ['nullable', 'boolean'],
        ]);

        // Handle Banner / Cover removal & replacement
        $oldBanner = $event->banner;
        $oldCover = $event->cover_photo;

        if ($request->boolean('remove_banner') || $request->boolean('remove_cover')) {
            $validated['banner'] = null;
            $validated['cover_photo'] = null;
        } elseif ($request->hasFile('banner') || $request->hasFile('cover_photo')) {
            $file = $request->file('banner') ?: $request->file('cover_photo');
            $bannerPath = $this->storeFile($file, 'uploads/events/banners');
            $validated['banner'] = $bannerPath;
            $validated['cover_photo'] = $bannerPath;
        }

        unset($validated['remove_banner'], $validated['remove_cover']);

        // Update ONLY this event record instance
        $event->update($validated);

        // Safe media cleanup ONLY after DB update succeeds
        if ($request->boolean('remove_banner') || $request->boolean('remove_cover')) {
            if ($oldBanner && File::exists(public_path($oldBanner))) {
                File::delete(public_path($oldBanner));
            }
            if ($oldCover && File::exists(public_path($oldCover)) && $oldCover !== $oldBanner) {
                File::delete(public_path($oldCover));
            }
        } elseif ($request->hasFile('banner') || $request->hasFile('cover_photo')) {
            if ($oldBanner && File::exists(public_path($oldBanner))) {
                File::delete(public_path($oldBanner));
            }
            if ($oldCover && File::exists(public_path($oldCover)) && $oldCover !== $oldBanner) {
                File::delete(public_path($oldCover));
            }
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => "Event '{$event->title}' has been updated successfully.",
                'event' => $event,
            ]);
        }

        return redirect()->route('admin.events.show', $event)
            ->with('success', "Event '{$event->title}' has been updated successfully.");
    }

    /**
     * Store uploaded file safely into target directory.
     */
    private function storeFile($file, string $directory): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $fileName = Str::uuid() . '.' . $extension;

        try {
            $storedPath = app(\App\Http\Controllers\ImageCompressionController::class)->compressAndStore(
                $file,
                $directory,
                'cover',
                $fileName,
                'public_uploads'
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'banner' => ['Failed to process or store the event banner.'],
            ]);
        }

        if (!$storedPath) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'banner' => ['Failed to process or store the event banner.'],
            ]);
        }

        return $storedPath;
    }

    /**
     * Update event status (e.g. published/active, cancelled, hidden).
     */
    public function updateStatus(Request $request, Event $event)
    {
        $rawAction = strtolower(trim((string) ($request->input('action') ?: $request->input('status', 'published'))));

        $isActivation = in_array($rawAction, ['active', 'activate', 'published', 'publish'], true);
        $isCancellation = in_array($rawAction, ['cancelled', 'cancel'], true);
        $isHidden = in_array($rawAction, ['hidden', 'hide'], true);

        // State-Aware Guard: Already Active Event
        if ($isActivation) {
            if ($event->isActive()) {
                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This event is already active.',
                        'code' => 'EVENT_ALREADY_ACTIVE',
                        'event' => $event,
                    ], 409);
                }

                return redirect()->back()->with('warning', 'This event is already active.');
            }

            $event->update(['status' => Event::STATUS_PUBLISHED]);

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => true,
                    'message' => 'Event activated successfully.',
                    'event' => $event->fresh(),
                ]);
            }

            return redirect()->back()->with('success', 'Event activated successfully.');
        }

        // State-Aware Guard: Already Cancelled Event
        if ($isCancellation) {
            if ($event->status === Event::STATUS_CANCELLED) {
                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This event is already cancelled.',
                        'code' => 'EVENT_ALREADY_CANCELLED',
                        'event' => $event,
                    ], 409);
                }

                return redirect()->back()->with('warning', 'This event is already cancelled.');
            }

            $event->update(['status' => Event::STATUS_CANCELLED]);

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => true,
                    'message' => "Event '{$event->title}' has been cancelled.",
                    'event' => $event->fresh(),
                ]);
            }

            return redirect()->back()->with('success', "Event '{$event->title}' has been cancelled.");
        }

        // Other statuses (hidden, draft, etc.)
        $targetStatus = $isHidden ? Event::STATUS_HIDDEN : $rawAction;
        $event->update(['status' => $targetStatus]);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => "Event '{$event->title}' status updated to {$targetStatus}.",
                'event' => $event->fresh(),
            ]);
        }

        return redirect()->back()->with('success', "Event '{$event->title}' status updated to {$targetStatus}.");
    }

    /**
     * Delete an event.
     */
    public function destroy(Event $event)
    {
        $title = $event->title;

        $event->delete();

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => "Event '{$title}' has been deleted.",
            ]);
        }

        return redirect()->back()->with('success', "Event '{$title}' has been deleted.");
    }

    /**
     * Process bulk actions on selected events.
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|string|in:cancel,publish,activate,hide,delete',
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:events,id'
        ]);

        $action = strtolower(trim((string) $request->input('action')));
        $ids = $request->input('ids');

        if ($action === 'activate' || $action === 'publish') {
            $events = Event::whereIn('id', $ids)->get();
            $alreadyActiveEvents = $events->filter(fn (Event $e) => $e->isActive());
            $inactiveEvents = $events->reject(fn (Event $e) => $e->isActive());

            $alreadyActiveCount = $alreadyActiveEvents->count();
            $inactiveCount = $inactiveEvents->count();

            // Scenario 1: ALL selected events are already active
            if ($inactiveCount === 0 && $alreadyActiveCount > 0) {
                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'All selected events are already active.',
                        'code' => 'ALL_EVENTS_ALREADY_ACTIVE',
                        'already_active_count' => $alreadyActiveCount,
                        'activated_count' => 0,
                    ], 409);
                }

                return redirect()->back()->with('warning', 'All selected events are already active.');
            }

            // Activate eligible inactive events
            if ($inactiveCount > 0) {
                Event::whereIn('id', $inactiveEvents->pluck('id'))->update(['status' => Event::STATUS_PUBLISHED]);
            }

            // Scenario 2: SOME were active, SOME were inactive
            if ($alreadyActiveCount > 0 && $inactiveCount > 0) {
                $message = "{$inactiveCount} event" . ($inactiveCount > 1 ? 's' : '') . " activated. {$alreadyActiveCount} event" . ($alreadyActiveCount > 1 ? 's were' : ' was') . " already active.";

                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json([
                        'success' => true,
                        'message' => $message,
                        'code' => 'PARTIAL_EVENTS_ACTIVATED',
                        'already_active_count' => $alreadyActiveCount,
                        'activated_count' => $inactiveCount,
                    ]);
                }

                return redirect()->back()->with('success', $message);
            }

            // Scenario 3: NONE were active, all activated
            $message = "{$inactiveCount} event" . ($inactiveCount > 1 ? 's' : '') . " activated successfully.";

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'code' => 'EVENTS_ACTIVATED',
                    'already_active_count' => 0,
                    'activated_count' => $inactiveCount,
                ]);
            }

            return redirect()->back()->with('success', $message);
        }

        if ($action === 'cancel') {
            $events = Event::whereIn('id', $ids)->get();
            $alreadyCancelled = $events->where('status', Event::STATUS_CANCELLED);
            $inactiveEvents = $events->where('status', '!=', Event::STATUS_CANCELLED);

            if ($inactiveEvents->isEmpty()) {
                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'All selected events are already cancelled.',
                        'code' => 'ALL_EVENTS_ALREADY_CANCELLED',
                        'already_cancelled_count' => $alreadyCancelled->count(),
                        'cancelled_count' => 0,
                    ], 409);
                }

                return redirect()->back()->with('warning', 'All selected events are already cancelled.');
            }

            Event::whereIn('id', $inactiveEvents->pluck('id'))->update(['status' => Event::STATUS_CANCELLED]);
            $count = $inactiveEvents->count();
            $message = "{$count} event" . ($count > 1 ? 's' : '') . " cancelled.";

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'cancelled_count' => $count,
                ]);
            }

            return redirect()->back()->with('success', $message);
        }

        if ($action === 'hide') {
            Event::whereIn('id', $ids)->update(['status' => Event::STATUS_HIDDEN]);
            $count = count($ids);

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => true, 'message' => "{$count} event" . ($count > 1 ? 's' : '') . " hidden."]);
            }

            return redirect()->back()->with('success', "{$count} events hidden.");
        }

        if ($action === 'delete') {
            Event::whereIn('id', $ids)->delete();
            $count = count($ids);

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => true, 'message' => "{$count} event" . ($count > 1 ? 's' : '') . " deleted."]);
            }

            return redirect()->back()->with('success', "{$count} events deleted.");
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['success' => false, 'message' => 'Invalid bulk action.'], 422);
        }

        return redirect()->back()->with('error', 'Invalid bulk action.');
    }

    /**
     * Export events list to CSV stream.
     */
    public function export(Request $request)
    {
        $query = $this->buildFilteredQuery($request)
            ->with(['organizer'])
            ->withCount(['responses', 'invitations'])
            ->latest('start_date');

        $filename = 'events_export_' . date('Y_m_d_His') . '.csv';

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
            fputcsv($file, ['Event ID', 'Title', 'Organizer Name', 'Organizer User ID', 'Category', 'Event Type', 'Privacy', 'Start Date', 'Location / Link', 'RSVPs', 'Status', 'Created Date']);

            $query->chunk(200, function ($events) use ($file) {
                foreach ($events as $e) {
                    $location = $e->event_type === 'online' ? $e->meeting_link : implode(', ', array_filter([$e->location_city, $e->location_country]));

                    fputcsv($file, [
                        $e->id,
                        $e->title,
                        $e->organizer?->name ?? 'N/A',
                        $e->organizer?->user_id ?? 'N/A',
                        $e->category,
                        ucfirst((string) $e->event_type),
                        ucfirst((string) $e->privacy),
                        $e->start_date?->format('Y-m-d'),
                        $location ?: 'N/A',
                        $e->responses_count,
                        ucfirst((string) $e->status),
                        $e->created_at?->format('Y-m-d H:i:s'),
                    ]);
                }
            });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
