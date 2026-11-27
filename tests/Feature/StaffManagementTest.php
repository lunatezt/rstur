<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Permission;
use App\Models\Restaurant;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Role $cashierRole;

    private Role $kitchenRole;

    private Role $adminRole;

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
            'module' => 'staff',
            'name' => 'Manage Staff',
            'slug' => 'staff.manage',
        ]);
        $this->adminRole = Role::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Admin',
            'slug' => 'admin',
        ]);
        $this->adminRole->permissions()->sync([$permission->id]);
        $this->cashierRole = Role::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Cashier',
            'slug' => 'cashier',
        ]);
        $this->kitchenRole = Role::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Kitchen Staff',
            'slug' => 'kitchen_staff',
        ]);
        $this->admin = User::create([
            'restaurant_id' => $restaurant->id,
            'role_id' => $this->adminRole->id,
            'name' => 'Admin User',
            'email' => 'admin@example.test',
            'password' => Hash::make('password'),
            'pin_code' => '1234',
            'is_active' => true,
        ]);

        $this->actingAs($this->admin, 'sanctum');
        $this->assertTrue($this->admin->is_active);
        $this->assertTrue($this->admin->hasPermission('staff.manage'));
    }

    public function test_admin_can_create_update_and_delete_cashier_or_kitchen_staff(): void
    {
        $created = $this->postJson('/api/staff', [
            'name' => 'New Cashier',
            'role_id' => $this->cashierRole->id,
            'pin_code' => '0427',
        ])->assertCreated()
            ->assertJsonPath('staff.name', 'New Cashier')
            ->assertJsonPath('staff.is_active', true)
            ->assertJsonPath('staff.role.slug', 'cashier');

        $staffId = $created->json('staff.id');
        $this->assertDatabaseHas('users', [
            'id' => $staffId,
            'restaurant_id' => $this->admin->restaurant_id,
            'pin_code' => '0427',
        ]);

        $this->putJson("/api/staff/{$staffId}", [
            'name' => 'Kitchen Member',
            'role_id' => $this->kitchenRole->id,
            'pin_code' => '5678',
            'is_active' => false,
        ])->assertOk()
            ->assertJsonPath('staff.role.slug', 'kitchen_staff');

        $this->assertDatabaseHas('users', [
            'id' => $staffId,
            'name' => 'Kitchen Member',
            'pin_code' => '5678',
            'is_active' => false,
        ]);

        $this->deleteJson("/api/staff/{$staffId}")->assertOk();
        $this->assertDatabaseMissing('users', ['id' => $staffId]);
    }

    public function test_staff_management_rejects_admin_roles_and_duplicate_pins(): void
    {
        $this->postJson('/api/staff', [
            'name' => 'Invalid Role',
            'role_id' => $this->adminRole->id,
            'pin_code' => '5678',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('role_id');

        $this->postJson('/api/staff', [
            'name' => 'Duplicate PIN',
            'role_id' => $this->cashierRole->id,
            'pin_code' => '1234',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('pin_code');
    }

    public function test_admin_cannot_delete_their_own_account(): void
    {
        $this->deleteJson("/api/staff/{$this->admin->id}")
            ->assertUnprocessable();

        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
    }
}
