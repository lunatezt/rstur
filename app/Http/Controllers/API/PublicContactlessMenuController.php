<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Table;
use Illuminate\Http\JsonResponse;

class PublicContactlessMenuController extends Controller
{
    public function show(Table $table): JsonResponse
    {
        $table->loadMissing('restaurant');
        abort_unless($table->is_active && $table->restaurant?->is_active, 404);

        $categories = Category::query()
            ->where('restaurant_id', $table->restaurant_id)
            ->where('is_active', true)
            ->where('display_in_online', true)
            ->with(['products' => function ($query) use ($table) {
                $query->where('restaurant_id', $table->restaurant_id)
                    ->where('is_active', true)
                    ->where('is_available', true)
                    ->orderBy('display_order')
                    ->select([
                        'id',
                        'category_id',
                        'name',
                        'name_en',
                        'name_fa',
                        'short_description',
                        'image',
                        'price',
                        'unit_type',
                        'is_veg',
                        'discount_percentage',
                        'discount_start_date',
                        'discount_end_date',
                    ]);
            }])
            ->orderBy('sort_order')
            ->get()
            ->filter(fn ($category) => $category->products->isNotEmpty())
            ->values()
            ->map(fn ($category) => [
                'id' => $category->id,
                'name_en' => $category->name_en ?: $category->name,
                'name_fa' => $category->name_fa,
                'products' => $category->products->map(fn ($product) => [
                    'id' => $product->id,
                    'name_en' => $product->name_en ?: $product->name,
                    'name_fa' => $product->name_fa,
                    'description' => $product->short_description,
                    'price' => $product->current_price,
                    'unit_type' => $product->unit_type,
                    'is_veg' => $product->is_veg,
                    'image_url' => $product->image_url,
                ])->values(),
            ]);

        return response()->json([
            'restaurant' => [
                'name' => $table->restaurant->name,
                'currency' => $table->restaurant->currency_symbol ?: 'AFS',
            ],
            'table' => [
                'id' => $table->id,
                'name' => $table->name,
            ],
            'categories' => $categories,
        ]);
    }
}
