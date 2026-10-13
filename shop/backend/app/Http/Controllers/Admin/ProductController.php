<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Product;
use App\Services\ImageService;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductController extends Controller
{
    /**
     * List brands for product form and catalog filters.
     */
    public function brands(): JsonResponse
    {
        $brands = Brand::orderBy('name')->get(['id', 'name', 'slug', 'logo']);

        return response()->json([
            'data' => $brands,
        ]);
    }

    /**
     * List products with admin search, filtering, sorting, and eager loading.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::query()
            ->with(['category', 'brand', 'primaryImage'])
            ->withCount('variants');

        if ($request->filled('search')) {
            $term = trim((string) $request->input('search'));
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('sku', 'like', "%{$term}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('category_id')) {
            $catId = (int) $request->input('category_id');
            $query->where(function ($q) use ($catId) {
                $q->where('category_id', $catId)
                    ->orWhereHas('categories', fn ($c) => $c->where('categories.id', $catId));
            });
        }

        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->input('brand_id'));
        }

        if ($request->input('stock_filter') === 'low') {
            $query->where('track_stock', true)
                ->whereColumn('stock_qty', '<=', 'low_stock_threshold');
        } elseif ($request->input('stock_filter') === 'out') {
            $query->where('track_stock', true)
                ->where('stock_qty', '<=', 0);
        }

        if ($request->filled('is_featured')) {
            $query->where('is_featured', filter_var($request->input('is_featured'), FILTER_VALIDATE_BOOLEAN));
        }

        $allowedSorts = ['id', 'name', 'base_price', 'stock_qty', 'status'];
        $sortBy = in_array($request->input('sort_by'), $allowedSorts, true) ? $request->input('sort_by') : 'id';
        $sortDir = strtolower((string) $request->input('sort_dir')) === 'asc' ? 'asc' : 'desc';

        $perPage = (int) $request->input('per_page', 25);
        $products = $query->orderBy($sortBy, $sortDir)->paginate($perPage);

        return response()->json($products);
    }

    /**
     * Quick toggle active status or featured flag from the admin product list.
     */
    public function quickToggle(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'nullable|in:draft,active,published,archived',
            'is_featured' => 'nullable|boolean',
        ]);

        if (array_key_exists('status', $validated) && $validated['status'] !== null) {
            $product->status = $validated['status'];
        }

        if (array_key_exists('is_featured', $validated) && $validated['is_featured'] !== null) {
            $product->is_featured = (bool) $validated['is_featured'];
        }

        $product->save();
        Cache::forget('storefront_catalog_filters');

        return response()->json([
            'message' => 'Product updated.',
            'data' => $product->fresh(['category', 'brand', 'primaryImage']),
        ]);
    }

    /**
     * Create a new product with full variants, images, sections, and specs.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'slug' => 'nullable|string|max:220|unique:products,slug',
            'sku' => 'nullable|string|max:100|unique:products,sku',
            'brand_id' => 'nullable|exists:brands,id',
            'category_id' => 'nullable|exists:categories,id',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id',
            'tags' => 'nullable|array',
            'status' => 'nullable|in:draft,active,published,archived',
            'is_featured' => 'nullable|boolean',
            'base_price' => 'required|numeric|min:0',
            'compare_at_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'track_stock' => 'nullable|boolean',
            'stock_qty' => 'nullable|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'allow_backorder' => 'nullable|boolean',
            'short_description' => 'nullable|string|max:1000',
            'description' => 'nullable|string',
            'weight' => 'nullable|numeric|min:0',
            'length' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'tax_class' => 'nullable|string|max:50',
            'video_url' => 'nullable|string|max:500',
            'video_path' => 'nullable|string|max:500',
            'video_poster' => 'nullable|string|max:500',
            'seo_title' => 'nullable|string|max:150',
            'seo_description' => 'nullable|string|max:500',
            'seo_social_image' => 'nullable|string|max:500',
            'related_products_json' => 'nullable|array',
            'frequently_bought_together_json' => 'nullable|array',
            'options' => 'nullable|array',
            'variants' => 'nullable|array',
            'images' => 'nullable|array',
            'sections' => 'nullable|array',
            'specs' => 'nullable|array',
        ]);

        $product = ProductService::saveProduct($validated);

        return response()->json([
            'message' => 'Product created successfully.',
            'data' => $product->load(['category', 'categories', 'brand', 'images', 'variants.optionValues', 'optionTypes.values', 'detailsSections', 'specs']),
        ], 201);
    }

    /**
     * Show full single product for editing, including order counts per variant.
     */
    public function show(Product $product): JsonResponse
    {
        $product->load([
            'category',
            'categories',
            'brand',
            'tags',
            'images',
            'optionTypes.values',
            'variants' => fn ($q) => $q->with(['optionValues', 'image'])->withCount('orderItems as orders_count'),
            'detailsSections',
            'specs',
        ]);

        return response()->json([
            'data' => $product,
        ]);
    }

    /**
     * Update an existing product.
     */
    public function update(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:200',
            'slug' => "sometimes|nullable|string|max:220|unique:products,slug,{$product->id}",
            'sku' => "sometimes|nullable|string|max:100|unique:products,sku,{$product->id}",
            'brand_id' => 'nullable|exists:brands,id',
            'category_id' => 'nullable|exists:categories,id',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id',
            'tags' => 'nullable|array',
            'status' => 'nullable|in:draft,active,published,archived',
            'is_featured' => 'nullable|boolean',
            'base_price' => 'sometimes|required|numeric|min:0',
            'compare_at_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'track_stock' => 'nullable|boolean',
            'stock_qty' => 'nullable|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'allow_backorder' => 'nullable|boolean',
            'short_description' => 'nullable|string|max:1000',
            'description' => 'nullable|string',
            'weight' => 'nullable|numeric|min:0',
            'length' => 'nullable|numeric|min:0',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'tax_class' => 'nullable|string|max:50',
            'video_url' => 'nullable|string|max:500',
            'video_path' => 'nullable|string|max:500',
            'video_poster' => 'nullable|string|max:500',
            'seo_title' => 'nullable|string|max:150',
            'seo_description' => 'nullable|string|max:500',
            'seo_social_image' => 'nullable|string|max:500',
            'related_products_json' => 'nullable|array',
            'frequently_bought_together_json' => 'nullable|array',
            'options' => 'nullable|array',
            'variants' => 'nullable|array',
            'images' => 'nullable|array',
            'sections' => 'nullable|array',
            'specs' => 'nullable|array',
        ]);

        $updatedProduct = ProductService::saveProduct($validated, $product);

        return response()->json([
            'message' => 'Product updated successfully.',
            'data' => $updatedProduct->load([
                'category',
                'categories',
                'brand',
                'images',
                'variants' => fn ($q) => $q->with(['optionValues', 'image'])->withCount('orderItems as orders_count'),
                'optionTypes.values',
                'detailsSections',
                'specs',
            ]),
        ]);
    }

    /**
     * Soft delete a product.
     */
    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully.',
        ]);
    }

    /**
     * Duplicate a product.
     */
    public function duplicate(Product $product): JsonResponse
    {
        $clone = ProductService::duplicate($product);

        return response()->json([
            'message' => 'Product duplicated successfully.',
            'data' => $clone->load(['category', 'brand', 'images', 'variants']),
        ], 201);
    }

    /**
     * Bulk actions for multiple products (publish, archive, delete, change category).
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:products,id',
            'action' => 'required|in:publish,archive,delete,change_category',
            'category_id' => 'required_if:action,change_category|nullable|exists:categories,id',
        ]);

        $ids = $request->input('ids');
        $action = $request->input('action');

        switch ($action) {
            case 'publish':
                Product::whereIn('id', $ids)->update(['status' => 'active']);
                break;
            case 'archive':
                Product::whereIn('id', $ids)->update(['status' => 'archived']);
                break;
            case 'delete':
                Product::whereIn('id', $ids)->delete();
                break;
            case 'change_category':
                Product::whereIn('id', $ids)->update(['category_id' => $request->input('category_id')]);
                break;
        }

        return response()->json([
            'message' => 'Bulk action executed successfully.',
        ]);
    }

    /**
     * Bulk edit variants tool.
     */
    public function bulkEditVariants(Request $request, Product $product): JsonResponse
    {
        $request->validate([
            'action' => 'required|in:set_price,increase_price_percent,set_stock',
            'value' => 'required|numeric',
        ]);

        ProductService::bulkEditVariants($product, $request->input('action'), $request->input('value'));

        return response()->json([
            'message' => 'Variants updated successfully.',
            'data' => $product->variants()->with('optionValues')->get(),
        ]);
    }

    /**
     * Upload and optimize product image to 3 responsive WebP sizes.
     */
    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|file|mimes:jpeg,png,jpg,webp,svg|max:5120',
        ]);

        $file = $request->file('image');
        $paths = ImageService::storeProductImage($file);

        return response()->json([
            'path' => $paths['path'],
            'thumb_path' => $paths['thumb_path'],
            'medium_path' => $paths['medium_path'],
            'large_path' => $paths['large_path'],
            'url' => asset('storage/'.$paths['large_path']),
            'thumb_url' => asset('storage/'.$paths['thumb_path']),
            'medium_url' => asset('storage/'.$paths['medium_path']),
            'large_url' => asset('storage/'.$paths['large_path']),
        ]);
    }

    /**
     * Upload an MP4 video for product card hover and product gallery.
     */
    public function uploadVideo(Request $request): JsonResponse
    {
        $request->validate([
            'video' => 'required|file|mimetypes:video/mp4,video/quicktime,video/webm|max:51200',
        ]);

        $file = $request->file('video');
        $ext = strtolower($file->getClientOriginalExtension());
        $filename = 'video_'.uniqid().'.'.$ext;
        $path = $file->storeAs('products/videos', $filename, 'public');

        return response()->json([
            'path' => $path,
            'url' => asset('storage/'.$path),
        ]);
    }

    /**
     * Export products catalog to CSV.
     */
    public function exportCsv(): StreamedResponse
    {
        $fileName = 'products_export_'.date('Y-m-d').'.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Name', 'SKU', 'Slug', 'Price', 'Compare Price', 'Stock Qty', 'Status', 'Category', 'Brand']);

            Product::with(['category', 'brand'])->chunk(100, function ($products) use ($handle) {
                foreach ($products as $p) {
                    fputcsv($handle, [
                        $p->id,
                        $p->name,
                        $p->sku,
                        $p->slug,
                        $p->base_price,
                        $p->compare_at_price,
                        $p->stock_qty,
                        $p->status,
                        $p->category?->name,
                        $p->brand?->name,
                    ]);
                }
            });

            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv']);
    }

    /**
     * Import products from CSV file.
     */
    public function importCsv(Request $request): JsonResponse
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $file = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');
        $header = fgetcsv($handle);

        $importedCount = 0;
        while (($row = fgetcsv($handle)) !== false) {
            if (empty($row[0]) && empty($row[1])) {
                continue;
            }

            $name = $row[1] ?? 'Imported Product';
            $sku = ! empty($row[2]) ? $row[2] : ('SKU-'.strtoupper(Str::random(8)));
            $price = ! empty($row[4]) ? (float) $row[4] : 10.0;
            $stock = ! empty($row[6]) ? (int) $row[6] : 10;

            Product::updateOrCreate(
                ['sku' => $sku],
                [
                    'name' => $name,
                    'slug' => Str::slug($name).'-'.uniqid(),
                    'base_price' => $price,
                    'stock_qty' => $stock,
                    'status' => 'active',
                ]
            );
            $importedCount++;
        }

        fclose($handle);

        return response()->json([
            'message' => "Successfully imported {$importedCount} products.",
        ]);
    }
}
