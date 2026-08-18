<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Location\Database\Seeders\LocationSeeder;
use Modules\Setting\Database\Seeders\SettingSeeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            RoleSeeder::class,
            AdminUserSeeder::class,
            LocationSeeder::class,
            SettingSeeder::class,
            // Self-guards against running in production (same pattern as
            // AdminUserSeeder) — safe to always call here.
            DemoDataSeeder::class,
        ]);
    }
}
