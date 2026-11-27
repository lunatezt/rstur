<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MenuLocalizationAndPhotosTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Restaurant $restaurant;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $organization = Organization::create([
            'name' => 'Test Group',
            'slug' => 'test-group',
            'email' => 'group@example.test',
        ]);
        $this->restaurant = Restaurant::create([
            'organization_id' => $organization->id,
            'name' => 'Test Restaurant',
            'code' => 'TEST',
            'slug' => 'test-restaurant',
            'email' => 'restaurant@example.test',
            'phone' => '1234567890',
            'address' => 'Test address',
        ]);

        $role = Role::create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Admin',
            'slug' => 'admin',
        ]);
        $permissions = collect(['menu.create', 'menu.edit', 'menu.delete', 'menu.categories'])
            ->map(fn (string $slug) => Permission::create([
                'module' => 'menu',
                'name' => $slug,
                'slug' => $slug,
            ]));
        $role->permissions()->sync($permissions->pluck('id'));

        $this->admin = User::create([
            'restaurant_id' => $this->restaurant->id,
            'role_id' => $role->id,
            'name' => 'Admin User',
            'email' => 'admin@example.test',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        $this->actingAs($this->admin, 'sanctum');

        $this->category = Category::create([
            'restaurant_id' => $this->restaurant->id,
            'name' => 'Starters',
            'name_en' => 'Starters',
            'name_fa' => 'پیش‌غذاها',
            'slug' => 'starters',
        ]);
    }

    public function test_categories_and_products_require_english_and_persian_names(): void
    {
        $this->postJson('/api/categories', ['name_en' => 'Mains'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name_fa');

        $this->postJson('/api/categories', [
            'name_en' => 'Main Course',
            'name_fa' => 'غذای اصلی',
        ])->assertCreated()
            ->assertJsonPath('category.name_en', 'Main Course')
            ->assertJsonPath('category.name_fa', 'غذای اصلی');

        $this->postJson('/api/products', [
            'category_id' => $this->category->id,
            'name_en' => 'Paneer Tikka',
            'price' => 12,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('name_fa');
    }

    public function test_product_photos_are_stored_and_default_photo_is_returned_when_missing(): void
    {
        Storage::fake('public');

        $response = $this->post('/api/products', [
            'category_id' => $this->category->id,
            'name_en' => 'Paneer Tikka',
            'name_fa' => 'پنیر تیکا',
            'price' => 12,
            'image' => $this->fakePhoto('paneer.jpg'),
        ], ['Accept' => 'application/json'])->assertCreated();

        $photoPath = $response->json('product.image');
        $this->assertStringStartsWith("products/{$this->restaurant->id}/", $photoPath);
        Storage::disk('public')->assertExists($photoPath);
        $this->assertStringStartsWith('/storage/', $response->json('product.image_url'));

        $withoutPhoto = $this->postJson('/api/products', [
            'category_id' => $this->category->id,
            'name_en' => 'Dal Tadka',
            'name_fa' => 'دال تادکا',
            'price' => 8,
        ])->assertCreated();

        $this->assertSame('/images/default-food.svg', $withoutPhoto->json('product.image_url'));
    }

    public function test_product_photo_can_be_replaced_and_removed(): void
    {
        Storage::fake('public');
        $product = Product::create([
            'restaurant_id' => $this->restaurant->id,
            'category_id' => $this->category->id,
            'name' => 'Paneer Tikka',
            'name_en' => 'Paneer Tikka',
            'name_fa' => 'پنیر تیکا',
            'slug' => 'paneer-tikka',
            'price' => 12,
        ]);
        $oldPhoto = $this->fakePhoto('old.jpg');
        $oldPath = $oldPhoto->store("products/{$this->restaurant->id}", 'public');
        $product->update(['image' => $oldPath]);

        $response = $this->post("/api/products/{$product->id}", [
            '_method' => 'PUT',
            'image' => $this->fakePhoto('new.jpg'),
        ], ['Accept' => 'application/json'])->assertOk();

        $newPath = $response->json('product.image');
        $this->assertNotSame($oldPath, $newPath);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($newPath);

        $this->post("/api/products/{$product->id}", [
            '_method' => 'PUT',
            'image' => '',
        ], ['Accept' => 'application/json'])->assertOk()
            ->assertJsonPath('product.image_url', '/images/default-food.svg');
        Storage::disk('public')->assertMissing($newPath);
    }

    private function fakePhoto(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            file_get_contents(base_path('screenshots/s1.JPG'))
        );
    }
}
