<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $warehouse = Location::query()->firstOrCreate(
            ['type' => 'warehouse', 'name' => 'Namanve Warehouse'],
            ['parent_id' => null, 'status' => 'active'],
        );

        foreach (['A01', 'A02', 'A03'] as $zoneCode) {
            $zone = Location::query()->firstOrCreate(
                ['type' => 'zone', 'parent_id' => $warehouse->id, 'name' => 'Zone '.$zoneCode],
                ['status' => 'active'],
            );
            $rack = Location::query()->firstOrCreate(
                ['type' => 'rack', 'parent_id' => $zone->id, 'name' => 'Rack 01'],
                ['status' => 'active'],
            );

            for ($binNumber = 1; $binNumber <= 2; $binNumber++) {
                $binCode = $zoneCode.'-B'.str_pad((string) $binNumber, 2, '0', STR_PAD_LEFT);
                Location::query()->firstOrCreate(
                    ['type' => 'bin', 'code' => $binCode],
                    [
                        'parent_id' => $rack->id,
                        'name' => 'Bin '.str_pad((string) $binNumber, 2, '0', STR_PAD_LEFT),
                        'status' => 'active',
                    ],
                );
            }
        }
    }
}
