<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database idempotently using canonical modular seeders.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            BranchSeeder::class,
            UserSeeder::class,
            ProductCategorySeeder::class,
            KafeinaRealDataSeeder::class,
            ProductModifierSeeder::class,
            SystemSettingSeeder::class,
        ]);
    }
}
