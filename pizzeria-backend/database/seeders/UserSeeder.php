<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        for ($i = 1; $i <= 100; $i++) {
            DB::table('users')->insert([
                'nombre' => "Usuario{$i}",
                'apellido_paterno' => "Paterno{$i}",
                'apellido_materno' => "Materno{$i}",
                'password' => Hash::make("Contrasena{$i}"),
                'salario' => 10000 + ($i * 10),
                'fecha_de_contrato' => date('Y-m-d', strtotime("2023-01-01 +{$i} days")),
                'duracion_de_contrato' => "{$i} dias",
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
