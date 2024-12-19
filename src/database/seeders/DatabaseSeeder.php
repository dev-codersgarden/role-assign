<?php

namespace Codersgarden\RoleAssign\Database\Seeders;


use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(PermissionGroupSeeder::class);
        $this->call(PermissionSeeder::class);
         $this->call(RolesSeeder::class);
    }
}
