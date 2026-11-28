<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPinChangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_change_their_pin_after_confirming_the_current_pin(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@restaurantpos.com')->firstOrFail();

        $this->actingAs($admin, 'sanctum')
            ->putJson('/api/auth/pin', [
                'current_pin' => '1234',
                'pin_code' => '2468',
                'pin_code_confirmation' => '2468',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'PIN updated successfully.');

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'pin_code' => '2468',
        ]);
    }

    public function test_admin_pin_change_rejects_an_incorrect_current_pin(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@restaurantpos.com')->firstOrFail();

        $this->actingAs($admin, 'sanctum')
            ->putJson('/api/auth/pin', [
                'current_pin' => '9999',
                'pin_code' => '2468',
                'pin_code_confirmation' => '2468',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('current_pin');

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'pin_code' => '1234',
        ]);
    }

    public function test_non_admin_cannot_change_a_pin_through_the_admin_pin_endpoint(): void
    {
        $this->seed();
        $cashier = User::where('email', 'cashier@restaurantpos.com')->firstOrFail();

        $this->actingAs($cashier, 'sanctum')
            ->putJson('/api/auth/pin', [
                'current_pin' => '5678',
                'pin_code' => '2468',
                'pin_code_confirmation' => '2468',
            ])
            ->assertForbidden();
    }
}
