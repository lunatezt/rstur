<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleSectionAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_cashier_and_kitchen_roles_are_limited_to_their_sections(): void
    {
        $this->seed();

        $cashier = User::where('email', 'cashier@restaurantpos.com')->firstOrFail();
        $kitchen = User::where('email', 'kitchen@restaurantpos.com')->firstOrFail();
        $manager = Role::where('slug', 'manager')->firstOrFail();
        $admin = Role::where('slug', 'admin')->firstOrFail();

        $this->actingAs($cashier, 'sanctum')
            ->getJson('/api/dashboard')
            ->assertForbidden();
        $this->actingAs($cashier, 'sanctum')
            ->getJson('/api/orders')
            ->assertOk();
        $this->actingAs($kitchen, 'sanctum')
            ->getJson('/api/orders')
            ->assertForbidden();
        $this->actingAs($kitchen, 'sanctum')
            ->getJson('/api/kds')
            ->assertOk();

        $this->assertFalse($cashier->hasPermission('payments.process'));
        $this->assertFalse($manager->permissions()->where('slug', 'payments.process')->exists());
        $this->assertTrue($admin->permissions()->where('slug', 'payments.process')->exists());
    }
}
