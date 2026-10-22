<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Popula a base de dados com os dados necessários através de `DatabaseSeeder`.
 */
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(SuperAdminSeeder::class);
    }
}
