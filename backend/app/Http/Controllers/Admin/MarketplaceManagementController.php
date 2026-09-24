<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class MarketplaceManagementController extends Controller
{
    /**
     * Display a listing of marketplace products with search, category & status filtering, and pagination.
     */
    /**
     * Build filtered query for products.
     */
    protected function buildFilteredQuery(Request $request)
    {
        $search = trim((string) $request->input('q'));
        $categoryId = $request->input('category_id');
        $status = $request->input('status');
        $condition = $request->input('condition');
        $isFeatured = $request->input('is_featured');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = Product::query();

        // Search Filter (Product ID, Title, Description, Brand, Location, Seller Name/Email/User ID)
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('id', $search)
                  ->orWhere('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('brand', 'like', "%{$search}%")
                  ->orWhere('location', 'like', "%{$search}%")
                  ->orWhereHas('member', function ($mq) use ($search) {
                      $mq->where('name', 'like', "%{$search}%")
                         ->orWhere('user_id', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        // Category Filter
        if (!empty($categoryId)) {
            $query->where('category_id', $categoryId);
        }

        // Status Filter
        if (!empty($status)) {
            $query->where('status', $status);
        }

        // Condition Filter
        if (!empty($condition)) {
            $query->where('condition', $condition);
        }

        // Featured Filter
        if ($isFeatured === 'yes') {
            $query->where('is_featured', true);
        } elseif ($isFeatured === 'no') {
            $query->where('is_featured', false);
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
            ->with(['member', 'category', 'media'])
            ->withCount(['savedProducts']);

        $products = $query->latest('created_at')
            ->paginate(15)
            ->withQueryString();

        // Metrics Summary
        $totalCount = Product::count();
        $activeCount = Product::where('status', 'available')->count();
        $featuredCount = Product::where('is_featured', true)->count();
        $totalSellersCount = Product::distinct('member_id')->count('member_id');

        $categories = Category::whereNull('parent_id')->get();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'products' => $products,
                'totalCount' => $totalCount,
                'activeCount' => $activeCount,
                'featuredCount' => $featuredCount,
                'totalSellersCount' => $totalSellersCount,
                'categories' => $categories,
            ]);
        }

        return view('admin.marketplace.index', compact(
            'products',
            'search',
            'categoryId',
            'status',
            'condition',
            'isFeatured',
            'dateFrom',
            'dateTo',
            'totalCount',
            'activeCount',
            'featuredCount',
            'totalSellersCount',
            'categories'
        ));
    }

    /**
     * Display detailed read-only inspection of a product.
     */
    public function show(Product $product)
    {
        $product->load([
            'member',
            'category',
            'subCategory',
            'media',
            'savedProducts'
        ])->loadCount(['savedProducts']);

        $reports = \App\Models\ReportedProduct::where('product_id', $product->id)->with('member')->get();
        $product->setRelation('reports', $reports);
        $product->setAttribute('reports_count', $reports->count());

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'product' => $product,
            ]);
        }

        return view('admin.marketplace.show', compact('product'));
    }

    /**
     * Update product status (e.g. available, hidden, sold).
     */
    public function updateStatus(Request $request, Product $product)
    {
        $status = $request->input('status', 'available');

        $product->update(['status' => $status]);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => "Product '{$product->title}' status updated to {$status}.",
            ]);
        }

        return redirect()->back()->with('success', "Product '{$product->title}' status updated to {$status}.");
    }

    /**
     * Toggle product featured status.
     */
    public function toggleFeatured(Request $request, Product $product)
    {
        $newFeatured = !$product->is_featured;
        $product->update(['is_featured' => $newFeatured]);

        $statusText = $newFeatured ? 'featured' : 'unfeatured';

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => "Product '{$product->title}' has been {$statusText}.",
                'is_featured' => $newFeatured,
            ]);
        }

        return redirect()->back()->with('success', "Product '{$product->title}' has been {$statusText}.");
    }

    /**
     * Delete a product.
     */
    public function destroy(Product $product)
    {
        $title = $product->title;

        $product->delete();

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => "Product '{$title}' has been deleted.",
            ]);
        }

        return redirect()->back()->with('success', "Product '{$title}' has been deleted.");
    }

    /**
     * Process bulk actions on selected products.
     */
    public function bulkAction(Request $request)
    {
        $request->validate([
            'action' => 'required|string|in:feature,unfeature,hide,available,delete',
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:products,id'
        ]);

        $action = $request->input('action');
        $ids = $request->input('ids');

        if ($action === 'feature') {
            Product::whereIn('id', $ids)->update(['is_featured' => true]);
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => true, 'message' => count($ids) . ' products marked featured.']);
            }
            return redirect()->back()->with('success', count($ids) . ' products marked featured.');
        } elseif ($action === 'unfeature') {
            Product::whereIn('id', $ids)->update(['is_featured' => false]);
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => true, 'message' => count($ids) . ' products unfeatured.']);
            }
            return redirect()->back()->with('success', count($ids) . ' products unfeatured.');
        } elseif ($action === 'hide') {
            Product::whereIn('id', $ids)->update(['status' => 'hidden']);
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => true, 'message' => count($ids) . ' products hidden.']);
            }
            return redirect()->back()->with('success', count($ids) . ' products hidden.');
        } elseif ($action === 'available') {
            Product::whereIn('id', $ids)->update(['status' => 'available']);
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => true, 'message' => count($ids) . ' products marked available.']);
            }
            return redirect()->back()->with('success', count($ids) . ' products marked available.');
        } elseif ($action === 'delete') {
            Product::whereIn('id', $ids)->delete();
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['success' => true, 'message' => count($ids) . ' products deleted.']);
            }
            return redirect()->back()->with('success', count($ids) . ' products deleted.');
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json(['success' => false, 'message' => 'Invalid bulk action.'], 422);
        }

        return redirect()->back()->with('error', 'Invalid bulk action.');
    }

    /**
     * Export products list to CSV stream.
     */
    public function export(Request $request)
    {
        $query = $this->buildFilteredQuery($request)
            ->with(['member', 'category'])
            ->withCount(['savedProducts', 'reports'])
            ->latest('created_at');

        $filename = 'marketplace_products_export_' . date('Y_m_d_His') . '.csv';

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
            fputcsv($file, ['Product ID', 'Title', 'Seller Name', 'Seller User ID', 'Category', 'Price', 'Condition', 'Status', 'Featured', 'Views', 'Wishlist Saves', 'Reports', 'Created Date']);

            $query->chunk(200, function ($products) use ($file) {
                foreach ($products as $p) {
                    fputcsv($file, [
                        $p->id,
                        $p->title,
                        $p->member?->name ?? 'N/A',
                        $p->member?->user_id ?? 'N/A',
                        $p->category?->name ?? 'Uncategorized',
                        $p->price,
                        ucfirst(str_replace('_', ' ', (string) $p->condition)),
                        ucfirst((string) $p->status),
                        $p->is_featured ? 'Yes' : 'No',
                        $p->views_count,
                        $p->saved_products_count,
                        $p->reports_count,
                        $p->created_at?->format('Y-m-d H:i:s'),
                    ]);
                }
            });

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
