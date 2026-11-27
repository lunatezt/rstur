<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $restaurantId = $request->user()->restaurant_id;
        $query = Product::where('restaurant_id', $restaurantId)
            ->with(['category', 'taxCategory', 'variants', 'addons'])
            ->orderBy('display_order')
            ->orderBy('name_en');

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->boolean('pos_only')) {
            $query->where('is_available', true)->where('is_active', true);
        }
        if ($request->has('is_veg')) {
            $query->where('is_veg', $request->boolean('is_veg'));
        }
        if ($request->has('search')) {
            $query->where(function ($searchQuery) use ($request) {
                $term = '%'.$request->search.'%';
                $searchQuery->where('name_en', 'like', $term)
                    ->orWhere('name_fa', 'like', $term)
                    ->orWhere('name', 'like', $term);
            });
        }
        if ($request->has('featured')) {
            $query->where('is_featured', true);
        }

        $perPage = $request->integer('per_page', 50);

        return response()->json(['products' => $query->paginate($perPage)]);
    }

    public function show(Request $request, Product $product)
    {
        abort_if($product->restaurant_id !== $request->user()->restaurant_id, 403);

        return response()->json(['product' => $product->load(['category', 'taxCategory', 'variants', 'addons', 'addonGroups.items', 'recipeIngredients.inventoryItem'])]);
    }

    public function store(Request $request)
    {
        $restaurantId = $request->user()->restaurant_id;
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'tax_category_id' => 'nullable|exists:tax_categories,id',
            'name_en' => 'required|string|max:255',
            'name_fa' => 'required|string|max:255',
            'short_description' => 'nullable|string',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:5120',
            'price' => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'mrp' => 'nullable|numeric|min:0',
            'sku' => 'nullable|string|max:100',
            'barcode' => 'nullable|string|max:100',
            'unit_type' => 'in:piece,plate,kg,gm,ml,litre,bowl,glass,pack,combo',
            'is_veg' => 'boolean',
            'is_available' => 'boolean',
            'is_featured' => 'boolean',
            'preparation_time_minutes' => 'integer|min:0',
            'track_inventory' => 'boolean',
            'discount_percentage' => 'numeric|min:0|max:100',
            'tags' => 'nullable|array',
            'allergens' => 'nullable|array',
            'variants' => 'nullable|array',
            'variants.*.name' => 'required_with:variants|string',
            'variants.*.price_adjustment' => 'required_with:variants|numeric',
            'variants.*.is_default' => 'boolean',
        ]);

        $validated['restaurant_id'] = $restaurantId;
        $validated['name'] = $validated['name_en'];
        $validated['slug'] = $this->uniqueSlug($restaurantId, Str::slug($validated['name_en']));
        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store("products/{$restaurantId}", 'public');
        } else {
            unset($validated['image']);
        }

        $variants = $validated['variants'] ?? [];
        unset($validated['variants']);

        $product = Product::create($validated);

        if (! empty($variants)) {
            $product->variants()->createMany($variants);
        }

        return response()->json(['product' => $product->load(['category', 'variants', 'addons'])], 201);
    }

    public function update(Request $request, Product $product)
    {
        abort_if($product->restaurant_id !== $request->user()->restaurant_id, 403);
        $validated = $request->validate([
            'category_id' => 'sometimes|exists:categories,id',
            'tax_category_id' => 'nullable|exists:tax_categories,id',
            'name_en' => 'sometimes|required|string|max:255',
            'name_fa' => 'sometimes|required|string|max:255',
            'price' => 'sometimes|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'is_veg' => 'boolean',
            'is_available' => 'boolean',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'image' => 'sometimes|nullable|image|max:5120',
            'short_description' => 'nullable|string',
            'description' => 'nullable|string',
            'preparation_time_minutes' => 'integer|min:0',
            'track_inventory' => 'boolean',
            'current_stock' => 'numeric|min:0',
            'discount_percentage' => 'numeric|min:0|max:100',
            'tags' => 'nullable|array',
        ]);

        if (isset($validated['name_en'])) {
            $validated['name'] = $validated['name_en'];
            $validated['slug'] = $this->uniqueSlug($product->restaurant_id, Str::slug($validated['name_en']), $product->id);
        }

        $oldImage = $product->image;
        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store("products/{$product->restaurant_id}", 'public');
        } elseif (array_key_exists('image', $validated)) {
            $validated['image'] = null;
        }

        $product->update($validated);
        if ($oldImage !== $product->image) {
            $this->deleteStoredImage($oldImage);
        }

        return response()->json(['product' => $product->load(['category', 'taxCategory', 'variants', 'addons'])]);
    }

    public function destroy(Request $request, Product $product)
    {
        abort_if($product->restaurant_id !== $request->user()->restaurant_id, 403);
        $image = $product->image;
        $product->delete();
        $this->deleteStoredImage($image);

        return response()->json(['message' => 'Product deleted.']);
    }

    public function toggleAvailability(Request $request, Product $product)
    {
        abort_if($product->restaurant_id !== $request->user()->restaurant_id, 403);
        $product->update(['is_available' => ! $product->is_available]);

        return response()->json(['product' => $product]);
    }

    private function uniqueSlug($restaurantId, $slug, $excludeId = null): string
    {
        $original = $slug;
        $count = 1;
        while (true) {
            $query = Product::where('restaurant_id', $restaurantId)->where('slug', $slug);
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }
            if (! $query->exists()) {
                break;
            }
            $slug = $original.'-'.$count++;
        }

        return $slug;
    }

    private function deleteStoredImage(?string $path): void
    {
        if ($path && Str::startsWith($path, 'products/')) {
            Storage::disk('public')->delete($path);
        }
    }
}
