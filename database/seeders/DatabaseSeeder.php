<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PermissaoSeeder::class,
            PerfilSeeder::class,
            EmpresaSeeder::class,
            ProdutoSeeder::class,
            UserSeeder::class,
        ]);
    }
}
