<?php

namespace Tests\Feature;

use App\Models\Table;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicContactlessMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_load_a_tables_public_menu_without_staff_login(): void
    {
        $this->seed();
        $table = Table::where('name', 'T1')->firstOrFail();

        $response = $this->getJson("/api/public/contactless/tables/{$table->id}/menu");

        $response->assertOk()
            ->assertJsonPath('table.id', $table->id)
            ->assertJsonPath('restaurant.currency', 'AFS')
            ->assertJsonPath('categories.0.products.0.name_en', 'Chopan Kabab')
            ->assertJsonPath('categories.0.products.0.name_fa', 'چوپان کباب');

        $this->assertArrayNotHasKey('cost_price', $response->json('categories.0.products.0'));
    }

    public function test_inactive_tables_do_not_expose_a_public_menu(): void
    {
        $this->seed();
        $table = Table::where('name', 'T1')->firstOrFail();
        $table->update(['is_active' => false]);

        $this->getJson("/api/public/contactless/tables/{$table->id}/menu")
            ->assertNotFound();
    }
}
