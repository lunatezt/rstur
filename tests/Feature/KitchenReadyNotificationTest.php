<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\Role;
use App\Models\Table;
use App\Models\User;
use App\Notifications\KitchenReadyNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class KitchenReadyNotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $kitchen;

    private User $otherRestaurantAdmin;

    private OrderItem $firstItem;

    private OrderItem $secondItem;

    protected function setUp(): void
    {
        parent::setUp();

        $organization = Organization::create([
            'name' => 'Test Group',
            'slug' => 'test-group',
            'email' => 'group@example.test',
        ]);
        $restaurant = Restaurant::create([
            'organization_id' => $organization->id,
            'name' => 'Test Restaurant',
            'code' => 'TEST',
            'slug' => 'test-restaurant',
            'email' => 'restaurant@example.test',
            'phone' => '1234567890',
            'address' => 'Test address',
        ]);

        $permission = Permission::create([
            'module' => 'kitchen',
            'name' => 'Update Kitchen Status',
            'slug' => 'kitchen.update_status',
        ]);
        $adminRole = Role::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Admin',
            'slug' => 'admin',
        ]);
        $kitchenRole = Role::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Kitchen Staff',
            'slug' => 'kitchen_staff',
        ]);
        $kitchenRole->permissions()->sync([$permission->id]);

        $this->admin = $this->createUser($restaurant, $adminRole, 'admin', 'admin@example.test');
        $this->kitchen = $this->createUser($restaurant, $kitchenRole, 'kitchen', 'kitchen@example.test');

        $otherRestaurant = Restaurant::create([
            'organization_id' => $organization->id,
            'name' => 'Other Restaurant',
            'code' => 'OTHER',
            'slug' => 'other-restaurant',
            'email' => 'other@example.test',
            'phone' => '1234567891',
            'address' => 'Other address',
        ]);
        $otherAdminRole = Role::create([
            'restaurant_id' => $otherRestaurant->id,
            'name' => 'Admin',
            'slug' => 'admin',
        ]);
        $this->otherRestaurantAdmin = $this->createUser(
            $otherRestaurant,
            $otherAdminRole,
            'Other Admin',
            'other-admin@example.test'
        );

        $category = Category::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Kebabs',
            'name_en' => 'Kebabs',
            'name_fa' => 'کباب',
            'slug' => 'kebabs',
        ]);
        $product = Product::create([
            'restaurant_id' => $restaurant->id,
            'category_id' => $category->id,
            'name' => 'Chopan Kabab',
            'name_en' => 'Chopan Kabab',
            'name_fa' => 'چوپان کباب',
            'slug' => 'chopan-kabab',
            'price' => 100,
        ]);
        $table = Table::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Table 2',
            'table_number' => '2',
        ]);
        $order = Order::create([
            'restaurant_id' => $restaurant->id,
            'order_type' => 'dine_in',
            'table_id' => $table->id,
            'status' => 'preparing',
            'created_by' => $this->admin->id,
        ]);
        $this->firstItem = $this->createOrderItem($order, $product, 'preparing');
        $this->secondItem = $this->createOrderItem($order, $product, 'preparing');
    }

    public function test_item_and_order_ready_transitions_notify_only_same_restaurant_admins_once(): void
    {
        $this->actingAs($this->kitchen, 'sanctum')
            ->patchJson("/api/kds/items/{$this->firstItem->id}/status", ['status' => 'ready'])
            ->assertOk()
            ->assertJsonPath('order_status', 'preparing');

        $this->patchJson("/api/kds/items/{$this->firstItem->id}/status", ['status' => 'ready'])
            ->assertOk();

        $this->patchJson("/api/kds/items/{$this->secondItem->id}/status", ['status' => 'ready'])
            ->assertOk()
            ->assertJsonPath('order_status', 'ready');

        $notifications = $this->admin->notifications()->latest()->get();
        $this->assertCount(3, $notifications);
        $this->assertSame(2, $notifications->where('data.event', 'item_ready')->count());
        $this->assertSame(1, $notifications->where('data.event', 'order_ready')->count());
        $itemNotification = $notifications->first(fn ($notification) => $notification->data['event'] === 'item_ready');
        $this->assertSame('2', $itemNotification->data['table_number']);
        $this->assertSame('Chopan Kabab', $itemNotification->data['item_name_en']);
        $this->assertSame('چوپان کباب', $itemNotification->data['item_name_fa']);
        $this->assertSame([
            'name_en' => 'Chopan Kabab',
            'name_fa' => 'چوپان کباب',
        ], $itemNotification->data['meals'][0]);
        $orderNotification = $notifications->first(fn ($notification) => $notification->data['event'] === 'order_ready');
        $this->assertSame([
            'name_en' => 'Chopan Kabab',
            'name_fa' => 'چوپان کباب',
        ], $orderNotification->data['meals'][0]);
        $this->assertSame($this->kitchen->name, $itemNotification->data['ready_by']);
        $this->assertSame(0, $this->otherRestaurantAdmin->notifications()->count());
        $this->assertSame(0, $this->kitchen->notifications()->count());
    }

    public function test_kitchen_cannot_mark_a_whole_order_ready_directly(): void
    {
        $order = $this->firstItem->order;

        $this->actingAs($this->kitchen, 'sanctum')
            ->patchJson("/api/kds/orders/{$order->id}/status", ['status' => 'ready'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->assertSame('preparing', $order->fresh()->status);
        $this->assertSame(0, $this->admin->notifications()->count());
    }

    public function test_admin_can_list_and_mark_notifications_read_but_staff_cannot(): void
    {
        $this->admin->notify(new KitchenReadyNotification(['event' => 'item_ready']));
        $this->admin->notify(new KitchenReadyNotification(['event' => 'order_ready']));
        $otherAdmin = $this->createUser(
            $this->admin->restaurant,
            $this->admin->role,
            'Second Admin',
            'second-admin@example.test'
        );
        $otherAdmin->notify(new KitchenReadyNotification(['event' => 'order_ready']));

        $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonPath('unread_count', 2)
            ->assertJsonCount(2, 'notifications');

        $this->actingAs($this->kitchen, 'sanctum')
            ->getJson('/api/notifications')
            ->assertForbidden();

        $notificationId = $this->admin->notifications()->oldest()->first()->id;
        $this->actingAs($this->admin, 'sanctum')
            ->patchJson("/api/notifications/{$notificationId}/read")
            ->assertNoContent();
        $this->assertNotNull($this->admin->notifications()->find($notificationId)->read_at);

        $this->patchJson("/api/notifications/{$otherAdmin->notifications()->first()->id}/read")
            ->assertNotFound();

        $this->patchJson('/api/notifications/read-all')->assertNoContent();
        $this->assertSame(0, $this->admin->unreadNotifications()->count());
    }

    private function createUser(Restaurant $restaurant, Role $role, string $name, string $email): User
    {
        return User::create([
            'restaurant_id' => $restaurant->id,
            'role_id' => $role->id,
            'name' => $name,
            'email' => $email,
            'password' => Hash::make('password'),
            'pin_code' => '1234',
            'is_active' => true,
        ]);
    }

    private function createOrderItem(Order $order, Product $product, string $status): OrderItem
    {
        return OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_price' => $product->price,
            'quantity' => 1,
            'unit_price' => $product->price,
            'item_total' => $product->price,
            'item_status' => $status,
        ]);
    }
}
