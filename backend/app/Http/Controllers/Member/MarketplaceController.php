<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductMedia;
use App\Models\ReportedProduct;
use App\Models\SavedProduct;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MarketplaceController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::query()->whereNull('parent_id')->orderBy('order')->get();

        $query = Product::query()
            ->with(['member', 'category', 'media'])
            ->where('status', 'available');

        // Filters
        if ($request->filled('category')) {
            $cat = Category::query()->where('slug', $request->query('category'))->first();
            if ($cat) {
                $query->where(function ($q) use ($cat) {
                    $q->where('category_id', $cat->id)->orWhere('sub_category_id', $cat->id);
                });
            }
        }

        if ($request->filled('search')) {
            $search = '%'.$request->query('search').'%';
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', $search)
                    ->orWhere('description', 'like', $search)
                    ->orWhere('location', 'like', $search)
                    ->orWhere('brand', 'like', $search)
                    ->orWhere('tags', 'like', $search);
            });
        }

        if ($request->filled('condition')) {
            $query->where('condition', $request->query('condition'));
        }

        if ($request->filled('min_price')) {
            $query->where('price', '>=', (float) $request->query('min_price'));
        }

        if ($request->filled('max_price')) {
            $query->where('price', '<=', (float) $request->query('max_price'));
        }

        // Sorting
        match ($request->query('sort')) {
            'price_low' => $query->orderBy('price', 'asc'),
            'price_high' => $query->orderBy('price', 'desc'),
            'oldest' => $query->orderBy('created_at', 'asc'),
            'most_viewed' => $query->orderBy('views_count', 'desc'),
            default => $query->orderBy('created_at', 'desc'),
        };

        $products = $query->paginate(20)->withQueryString();

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'products' => $products,
                'categories' => $categories,
                'total' => $products->total(),
            ]);
        }

        return view('member.marketplace.index', compact('categories', 'products'));
    }

    public function show(Request $request, Product $product)
    {
        $product->load(['member', 'category', 'subCategory', 'media']);
        $product->increment('views_count');

        $relatedProducts = Product::query()
            ->with(['member', 'media'])
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('status', 'available')
            ->take(4)
            ->get();

        $isSaved = auth('member')->check()
            ? SavedProduct::query()->where('member_id', auth('member')->id())->where('product_id', $product->id)->exists()
            : false;

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'product' => $product,
                'related_products' => $relatedProducts,
                'is_saved' => $isSaved,
                'is_owner' => auth('member')->check() && $product->member_id === auth('member')->id(),
            ]);
        }

        return view('member.marketplace.show', compact('product', 'relatedProducts'));
    }

    public function create(Request $request)
    {
        $categories = Category::query()->whereNull('parent_id')->with('children')->orderBy('order')->get();

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'categories' => $categories,
            ]);
        }

        return view('member.marketplace.create', compact('categories'));
    }

    public function store(Request $request)
    {
        if ($request->hasFile('videos') || $request->has('videos')) {
            throw ValidationException::withMessages([
                'videos' => 'Videos can only be posted from a Business Page.',
            ]);
        }

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                if ($file instanceof UploadedFile) {
                    $mime = (string) $file->getMimeType();
                    $ext = strtolower($file->getClientOriginalExtension() ?: '');
                    if (str_starts_with($mime, 'video/') || in_array($ext, ['mp4', 'mov', 'webm'], true)) {
                        throw ValidationException::withMessages([
                            'images' => 'Videos can only be posted from a Business Page.',
                        ]);
                    }
                }
            }
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'sub_category_id' => ['nullable', 'exists:categories,id'],
            'price' => ['required', 'numeric', 'min:0'],
            'condition' => ['required', 'in:new,like_new,good,fair'],
            'brand' => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:255'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'is_negotiable' => ['nullable', 'boolean'],
            'description' => ['required', 'string'],
            'images.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $member = auth('member')->user();
        $slug = Str::slug($validated['title']).'-'.Str::random(6);

        $uploadedMediaPaths = [];
        try {
            DB::beginTransaction();

            $product = Product::query()->create([
                'member_id' => $member->id,
                'category_id' => $validated['category_id'],
                'sub_category_id' => $validated['sub_category_id'] ?? null,
                'title' => $validated['title'],
                'slug' => $slug,
                'description' => $validated['description'],
                'price' => $validated['price'],
                'condition' => $validated['condition'],
                'brand' => $validated['brand'] ?? null,
                'location' => $validated['location'] ?? null,
                'quantity' => $validated['quantity'] ?? 1,
                'is_negotiable' => $request->boolean('is_negotiable'),
                'status' => 'available',
            ]);

            // Upload images
            if ($request->hasFile('images')) {
                foreach (array_slice($request->file('images'), 0, 10) as $index => $file) {
                    $path = $this->storeMediaFile($file, 'uploads/marketplace/images');
                    if ($path) {
                        $uploadedMediaPaths[] = $path;
                        ProductMedia::query()->create([
                            'product_id' => $product->id,
                            'media_type' => 'image',
                            'media_path' => $path,
                            'is_featured' => $index === 0,
                        ]);
                    }
                }
            }

            DB::commit();
        } catch (ValidationException $e) {
            DB::rollBack();
            foreach ($uploadedMediaPaths as $storedPath) {
                @unlink(public_path($storedPath));
            }
            throw $e;
        } catch (\Throwable $e) {
            DB::rollBack();
            foreach ($uploadedMediaPaths as $storedPath) {
                @unlink(public_path($storedPath));
            }
            throw $e;
        }

        $product->load(['category', 'media']);

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Product listed on Marketplace successfully!',
                'product' => $product,
            ]);
        }

        return redirect()->route('member.marketplace.show', $product)->with('success', 'Product listed on Marketplace successfully!');
    }

    public function edit(Request $request, Product $product)
    {
        abort_unless($product->member_id === auth('member')->id(), 403);
        $categories = Category::query()->whereNull('parent_id')->with('children')->orderBy('order')->get();
        $product->load(['category', 'subCategory', 'media']);

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'product' => $product,
                'categories' => $categories,
            ]);
        }

        return view('member.marketplace.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        abort_unless($product->member_id === auth('member')->id(), 403);

        if ($request->hasFile('videos') || $request->has('videos')) {
            throw ValidationException::withMessages([
                'videos' => 'Videos can only be posted from a Business Page.',
            ]);
        }

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                if ($file instanceof UploadedFile) {
                    $mime = (string) $file->getMimeType();
                    $ext = strtolower($file->getClientOriginalExtension() ?: '');
                    if (str_starts_with($mime, 'video/') || in_array($ext, ['mp4', 'mov', 'webm'], true)) {
                        throw ValidationException::withMessages([
                            'images' => 'Videos can only be posted from a Business Page.',
                        ]);
                    }
                }
            }
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'sub_category_id' => ['nullable', 'exists:categories,id'],
            'price' => ['required', 'numeric', 'min:0'],
            'condition' => ['required', 'in:new,like_new,good,fair'],
            'brand' => ['nullable', 'string', 'max:100'],
            'location' => ['nullable', 'string', 'max:255'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'is_negotiable' => ['nullable', 'boolean'],
            'description' => ['required', 'string'],
            'status' => ['required', 'in:available,sold,reserved,hidden'],
            'images.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $product->update($validated);

        // Upload any additional images
        if ($request->hasFile('images')) {
            $existingCount = $product->media()->where('media_type', 'image')->count();
            foreach (array_slice($request->file('images'), 0, max(0, 10 - $existingCount)) as $file) {
                $path = $this->storeMediaFile($file, 'uploads/marketplace/images');
                if ($path) {
                    ProductMedia::query()->create([
                        'product_id' => $product->id,
                        'media_type' => 'image',
                        'media_path' => $path,
                        'is_featured' => $existingCount === 0,
                    ]);
                    $existingCount++;
                }
            }
        }

        $product->load(['category', 'subCategory', 'media']);

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Listing updated successfully!',
                'product' => $product,
            ]);
        }

        return redirect()->route('member.marketplace.show', $product)->with('success', 'Listing updated successfully!');
    }

    public function destroy(Request $request, Product $product)
    {
        abort_unless($product->member_id === auth('member')->id(), 403);
        $product->delete();

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Product removed from Marketplace.',
            ]);
        }

        return redirect()->route('member.marketplace.my-products')->with('success', 'Product removed from Marketplace.');
    }

    public function myProducts(Request $request)
    {
        $member = auth('member')->user();
        $status = $request->query('status', 'available');

        $products = Product::query()
            ->with(['category', 'media'])
            ->where('member_id', $member->id)
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->latest('created_at')
            ->paginate(15);

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'products' => $products,
                'status' => $status,
                'total' => $products->total(),
            ]);
        }

        return view('member.marketplace.my_products', compact('products', 'status'));
    }

    public function saved(Request $request)
    {
        $member = auth('member')->user();
        $savedIds = SavedProduct::query()->where('member_id', $member->id)->pluck('product_id');
        $products = Product::query()->with(['member', 'category', 'media'])->whereIn('id', $savedIds)->paginate(15);

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'products' => $products,
                'total' => $products->total(),
            ]);
        }

        return view('member.marketplace.saved', compact('products'));
    }

    public function destroyMedia(Request $request, Product $product, ProductMedia $media)
    {
        abort_unless($product->member_id === auth('member')->id(), 403);
        abort_unless($media->product_id === $product->id, 404);

        $media->delete();

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => 'Media removed.',
            ]);
        }

        return back()->with('success', 'Media removed.');
    }

    public function toggleStatus(Request $request, Product $product)
    {
        abort_unless($product->member_id === auth('member')->id(), 403);

        $newStatus = $request->input('status', 'sold');
        $product->update(['status' => $newStatus]);

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'status' => $newStatus,
                'message' => 'Product status updated to '.ucfirst($newStatus),
            ]);
        }

        return back()->with('success', 'Status updated to '.ucfirst($newStatus));
    }

    public function toggleSave(Request $request, Product $product)
    {
        $member = auth('member')->user();
        $existing = SavedProduct::query()->where('member_id', $member->id)->where('product_id', $product->id)->first();

        if ($existing) {
            $existing->delete();
            $isSaved = false;
            $message = 'Product removed from saved items.';
        } else {
            SavedProduct::query()->create([
                'member_id' => $member->id,
                'product_id' => $product->id,
            ]);
            $isSaved = true;
            $message = 'Product saved successfully.';
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'is_saved' => $isSaved,
                'message' => $message,
            ]);
        }

        return back()->with('success', $message);
    }

    public function report(Request $request, Product $product)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $reportedProduct = ReportedProduct::query()->create([
            'member_id' => auth('member')->id(),
            'product_id' => $product->id,
            'reason' => $validated['reason'],
            'notes' => $validated['notes'] ?? null,
        ]);

        \App\Services\AdminNotificationService::notify(
            title: 'Product Listing Reported',
            message: sprintf('Product "%s" was reported for "%s".', $product->title, $validated['reason']),
            icon: 'alert-circle',
            sourceType: 'product_report',
            sourceId: (string) $reportedProduct->id,
            actionUrl: '/admin/reports/product/' . $reportedProduct->id,
            metadata: ['product_id' => $product->id, 'reason' => $validated['reason']]
        );

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Listing reported to admin.',
            ]);
        }

        return back()->with('success', 'Listing reported to admin.');
    }

    private function storeMediaFile($file, string $directory): ?string
    {
        try {
            $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
            $filename = time().'_'.Str::random(10).'.'.$ext;

            return app(\App\Http\Controllers\ImageCompressionController::class)->compressAndStore(
                $file,
                $directory,
                'marketplace',
                $filename,
                'public_uploads'
            );
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable) {
            return null;
        }
    }
}
