<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\TaxCategory;
use Database\Seeders\MenuSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_menu_seeder_creates_all_bilingual_categories_and_products_idempotently(): void
    {
        $organization = Organization::create([
            'name' => 'Test Group',
            'slug' => 'test-group',
            'email' => 'group@example.test',
        ]);
        $restaurant = Restaurant::create([
            'organization_id' => $organization->id,
            'name' => 'Test Restaurant',
            'code' => 'TEST',
            'slug' => 'restaurant-main',
            'email' => 'restaurant@example.test',
            'phone' => '1234567890',
            'address' => 'Test address',
        ]);
        TaxCategory::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'GST 5%',
            'tax_percentage' => 5,
            'cgst_percentage' => 2.5,
            'sgst_percentage' => 2.5,
            'is_active' => true,
        ]);

        $seeder = new MenuSeeder;
        $seeder->run();
        $seeder->run();

        $this->assertDatabaseHas('restaurants', [
            'id' => $restaurant->id,
            'currency_symbol' => 'AFS',
        ]);
        $this->assertSame(15, Category::where('restaurant_id', $restaurant->id)->count());
        $this->assertSame(130, Product::where('restaurant_id', $restaurant->id)->count());
        $this->assertSame(0, Product::where('restaurant_id', $restaurant->id)
            ->where(fn ($query) => $query->whereNull('name_en')->orWhereNull('name_fa'))
            ->count());
        $this->assertDatabaseHas('categories', [
            'restaurant_id' => $restaurant->id,
            'slug' => 'steak-kebab',
            'name_en' => 'Steak & Kebab',
            'name_fa' => 'استیک و کباب',
        ]);
        $this->assertDatabaseHas('products', [
            'restaurant_id' => $restaurant->id,
            'slug' => 'chopan-kabab',
            'name_en' => 'Chopan Kabab',
            'name_fa' => 'چوپان کباب',
            'price' => 450,
            'unit_type' => 'plate',
        ]);
    }
}
