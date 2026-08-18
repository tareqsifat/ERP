<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            AdminUserSeeder::class,
        ]);

        // Phase 9 (todo.md) adds a dedicated DemoDataSeeder here for the
        // full Order -> Booking -> Cutting -> Sewing -> Finished Goods ->
        // Shipment walkthrough chain, subcontract cycles, vouchers, and an
        // Employee + Salary cycle.
    }
}
