<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Restaurant;
use App\Models\Table;
use App\Models\TableReservation;
use App\Models\TableSection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TableLayoutSeeder extends Seeder
{
    public function run(): void
    {
        $restaurant = Restaurant::query()->orderBy('id')->firstOrFail();

        DB::transaction(function () use ($restaurant): void {
            $sectionDefinitions = [
                'Floor 1' => 1,
                'Floor 2' => 2,
                'Rooms' => 3,
                'Balkon' => 4,
            ];

            $sections = [];
            foreach ($sectionDefinitions as $name => $sortOrder) {
                $sections[$name] = TableSection::updateOrCreate(
                    ['restaurant_id' => $restaurant->id, 'name' => $name],
                    ['sort_order' => $sortOrder, 'is_active' => true],
                );
            }

            TableSection::where('restaurant_id', $restaurant->id)
                ->whereNotIn('name', array_keys($sectionDefinitions))
                ->update(['is_active' => false]);

            $tables = [];
            foreach (range(1, 9) as $number) {
                $tables[] = ['number' => "T{$number}", 'section' => 'Floor 1', 'type' => 'regular'];
            }
            foreach (range(10, 17) as $number) {
                $tables[] = ['number' => "T{$number}", 'section' => 'Floor 2', 'type' => 'regular'];
            }
            foreach (range(100, 126) as $number) {
                $tables[] = ['number' => "Room {$number}", 'section' => 'Rooms', 'type' => 'private'];
            }
            foreach (range(18, 20) as $number) {
                $tables[] = ['number' => "T{$number}", 'section' => 'Balkon', 'type' => 'outdoor'];
            }

            $tableNumbers = array_column($tables, 'number');
            $referencedTableIds = Order::where('restaurant_id', $restaurant->id)
                ->whereNotNull('table_id')
                ->pluck('table_id')
                ->merge(
                    TableReservation::where('restaurant_id', $restaurant->id)
                        ->pluck('table_id')
                )
                ->unique();

            $oldTables = Table::where('restaurant_id', $restaurant->id)
                ->whereNotIn('table_number', $tableNumbers)
                ->get();

            foreach ($oldTables as $oldTable) {
                if ($referencedTableIds->contains($oldTable->id) || $oldTable->current_order_id !== null) {
                    $oldTable->update(['is_active' => false]);
                } else {
                    $oldTable->delete();
                }
            }

            foreach ($tables as $tableData) {
                $table = Table::firstOrNew([
                    'restaurant_id' => $restaurant->id,
                    'table_number' => $tableData['number'],
                ]);
                $table->fill([
                    'section_id' => $sections[$tableData['section']]->id,
                    'name' => $tableData['number'],
                    'capacity' => 4,
                    'minimum_capacity' => 1,
                    'table_type' => $tableData['type'],
                    'is_active' => true,
                ]);
                $table->save();
            }
        });

        $this->command?->info('Table layout seeded: Floor 1, Floor 2, Rooms, and Balkon.');
    }
}
