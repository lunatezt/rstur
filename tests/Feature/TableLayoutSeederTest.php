<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Restaurant;
use App\Models\Table;
use App\Models\TableSection;
use Database\Seeders\TableLayoutSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TableLayoutSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_table_layout_seeder_replaces_old_tables_and_is_idempotent(): void
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
            'slug' => 'test-restaurant',
            'email' => 'restaurant@example.test',
            'phone' => '1234567890',
            'address' => 'Test address',
        ]);

        $oldSection = TableSection::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Old Section',
            'sort_order' => 1,
            'is_active' => true,
        ]);
        $oldTable = Table::create([
            'restaurant_id' => $restaurant->id,
            'section_id' => $oldSection->id,
            'name' => 'Old Table',
            'table_number' => 'OLD-1',
            'capacity' => 4,
            'table_type' => 'regular',
            'status' => 'available',
            'is_active' => true,
        ]);

        $seeder = new TableLayoutSeeder;
        $seeder->run();
        $seeder->run();

        $this->assertDatabaseMissing('tables', ['id' => $oldTable->id]);
        $this->assertSame(47, Table::where('restaurant_id', $restaurant->id)->where('is_active', true)->count());
        $this->assertSame(4, TableSection::where('restaurant_id', $restaurant->id)->where('is_active', true)->count());
        $this->assertDatabaseHas('tables', [
            'restaurant_id' => $restaurant->id,
            'name' => 'Room 100',
            'table_number' => 'Room 100',
            'table_type' => 'private',
        ]);
        $this->assertDatabaseHas('tables', [
            'restaurant_id' => $restaurant->id,
            'name' => 'T20',
            'table_type' => 'outdoor',
        ]);
    }
}
