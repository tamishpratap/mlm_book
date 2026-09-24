<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessPage;
use App\Models\BusinessPageCategory;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BusinessPageCategoryManagementController extends Controller
{
    /**
     * Display a listing of all Business Page categories.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->input('q'));
        $status = $request->input('status');

        $query = BusinessPageCategory::query();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        $categories = $query->orderBy('order', 'asc')
            ->orderBy('name', 'asc')
            ->paginate(20)
            ->withQueryString();

        // Calculate live Business Page usage count for each category
        $categoryPageCounts = BusinessPage::selectRaw('category, count(*) as total')
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->groupBy('category')
            ->pluck('total', 'category')
            ->toArray();

        // Metrics Summary
        $totalCount = BusinessPageCategory::count();
        $activeCount = BusinessPageCategory::where('is_active', true)->count();
        $inactiveCount = BusinessPageCategory::where('is_active', false)->count();
        $totalBusinessPages = BusinessPage::count();

        $allActiveCategories = BusinessPageCategory::where('is_active', true)->orderBy('name')->get();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'categories' => $categories,
                'categoryPageCounts' => $categoryPageCounts,
                'totalCategories' => $totalCount,
                'activeCategories' => $activeCount,
                'inactiveCategories' => $inactiveCount,
                'totalPagesLinked' => $totalBusinessPages,
                'allActiveCategories' => $allActiveCategories,
            ]);
        }

        return view('admin.business_pages.categories.index', compact(
            'categories',
            'categoryPageCounts',
            'search',
            'status',
            'totalCount',
            'activeCount',
            'inactiveCount',
            'totalBusinessPages',
            'allActiveCategories'
        ));
    }

    /**
     * Store a newly created Business Page category.
     */
    public function store(Request $request)
    {
        $name = trim((string) $request->input('name'));
        $description = trim((string) $request->input('description'));
        $order = (int) $request->input('order', 0);
        $isActive = $request->boolean('is_active', true);

        // Normalize whitespace and check for case-insensitive duplicates
        $existing = BusinessPageCategory::whereRaw('LOWER(TRIM(name)) = ?', [strtolower($name)])->first();
        if ($existing) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => "A category with the name '{$existing->name}' already exists.",
                    'errors' => [
                        'name' => ["A category with the name '{$existing->name}' already exists."],
                    ],
                ], 422);
            }
            return redirect()->back()
                ->withInput()
                ->withErrors(['name' => "A category with the name '{$existing->name}' already exists."]);
        }

        $request->merge([
            'name' => $name,
            'description' => filled($description) ? $description : null,
            'order' => $order,
            'is_active' => $isActive,
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $slug = BusinessPageCategory::generateUniqueSlug($validated['name']);

        $category = BusinessPageCategory::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'order' => $validated['order'] ?? 0,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => "Business Page category '{$category->name}' has been created successfully.",
                'category' => $category,
            ]);
        }

        return redirect()->route('admin.business-pages.categories.index')
            ->with('success', "Business Page category '{$category->name}' has been created successfully.");
    }

    /**
     * Update an existing Business Page category.
     */
    public function update(Request $request, BusinessPageCategory $category)
    {
        $name = trim((string) $request->input('name'));
        $description = trim((string) $request->input('description'));
        $order = (int) $request->input('order', $category->order);
        $isActive = $request->has('is_active') ? $request->boolean('is_active') : $category->is_active;

        // Check for case-insensitive duplicate (excluding current)
        $existing = BusinessPageCategory::where('id', '!=', $category->id)
            ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($name)])
            ->first();

        if ($existing) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => "Another category with the name '{$existing->name}' already exists.",
                    'errors' => [
                        'name' => ["Another category with the name '{$existing->name}' already exists."],
                    ],
                ], 422);
            }
            return redirect()->back()
                ->withInput()
                ->withErrors(['name' => "Another category with the name '{$existing->name}' already exists."]);
        }

        $request->merge([
            'name' => $name,
            'description' => filled($description) ? $description : null,
            'order' => $order,
            'is_active' => $isActive,
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $oldName = $category->name;
        $newName = $validated['name'];

        // If category name was renamed, update all existing Business Pages and Events to preserve synchronization
        if ($oldName !== $newName) {
            BusinessPage::where('category', $oldName)->update(['category' => $newName]);
            Event::where('category', $oldName)->update(['category' => $newName]);
            $category->slug = BusinessPageCategory::generateUniqueSlug($newName, $category->id);
        }

        $category->name = $newName;
        $category->description = $validated['description'] ?? null;
        $category->order = $validated['order'] ?? 0;
        $category->is_active = $validated['is_active'];
        $category->save();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => "Category '{$category->name}' has been updated.",
                'category' => $category,
            ]);
        }

        return redirect()->route('admin.business-pages.categories.index')
            ->with('success', "Category '{$category->name}' has been updated.");
    }

    /**
     * Toggle active/disabled status of a category.
     */
    public function toggleStatus(Request $request, BusinessPageCategory $category)
    {
        $category->is_active = ! $category->is_active;
        $category->save();

        $statusLabel = $category->is_active ? 'Active' : 'Disabled';

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => "Category '{$category->name}' is now {$statusLabel}.",
                'category' => $category,
            ]);
        }

        return redirect()->back()
            ->with('success', "Category '{$category->name}' is now {$statusLabel}.");
    }

    /**
     * Reassign all business pages and events from this category to another target category.
     */
    public function reassign(Request $request, BusinessPageCategory $category)
    {
        $validated = $request->validate([
            'target_category_id' => ['required', 'integer', 'exists:business_page_categories,id', 'different:category'],
        ], [
            'target_category_id.required' => 'Please select a destination category to reassign pages.',
            'target_category_id.different' => 'Destination category must be different from current category.',
        ]);

        $targetCategory = BusinessPageCategory::findOrFail($validated['target_category_id']);

        $affectedPagesCount = BusinessPage::where('category', $category->name)->update(['category' => $targetCategory->name]);
        $affectedEventsCount = Event::where('category', $category->name)->update(['category' => $targetCategory->name]);

        $usageParts = [];
        if ($affectedPagesCount > 0) {
            $usageParts[] = "{$affectedPagesCount} Business Page(s)";
        }
        if ($affectedEventsCount > 0) {
            $usageParts[] = "{$affectedEventsCount} Event(s)";
        }
        $reassignDesc = !empty($usageParts) ? implode(' and ', $usageParts) : '0 records';

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => "Successfully reassigned {$reassignDesc} from '{$category->name}' to '{$targetCategory->name}'.",
            ]);
        }

        return redirect()->back()
            ->with('success', "Successfully reassigned {$reassignDesc} from '{$category->name}' to '{$targetCategory->name}'.");
    }

    /**
     * Delete a category safely (blocked if in use by Business Pages or Events).
     */
    public function destroy(Request $request, BusinessPageCategory $category)
    {
        $pagesCount = BusinessPage::where('category', $category->name)->count();
        $eventsCount = Event::where('category', $category->name)->count();

        if ($pagesCount > 0 || $eventsCount > 0) {
            $usageParts = [];
            if ($pagesCount > 0) {
                $usageParts[] = "{$pagesCount} Business Page(s)";
            }
            if ($eventsCount > 0) {
                $usageParts[] = "{$eventsCount} Event(s)";
            }
            $usageDesc = implode(' and ', $usageParts);

            $message = "Category '{$category->name}' cannot be deleted because it is currently used by {$usageDesc}. Please reassign or disable the category instead.";

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 422);
            }
            return redirect()->back()
                ->with('error', $message);
        }

        $categoryName = $category->name;
        $category->delete();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => "Category '{$categoryName}' deleted successfully.",
            ]);
        }

        return redirect()->route('admin.business-pages.categories.index')
            ->with('success', "Category '{$categoryName}' deleted successfully.");
    }
}
