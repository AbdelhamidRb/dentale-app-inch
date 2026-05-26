<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        // Compte dentiste (créé seulement s'il n'existe pas déjà)
        $email    = env('DENTIST_EMAIL', 'dentiste@cabinet.ma');
        $password = env('DENTIST_PASSWORD', 'ChangeMe2024!');
        $name     = env('DENTIST_NAME', 'Dr. Dentiste');

        User::firstOrCreate(
            ['email' => $email],
            [
                'name'     => $name,
                'password' => bcrypt($password),
                'role'     => 'DENTIST',
            ]
        );

        // Catalogue des actes de base
        $this->call(CatalogActSeeder::class);

        $this->command->info("Compte dentiste : {$email}");
        $this->command->info("Mot de passe    : {$password}");
        $this->command->info("CHANGEZ le mot de passe apres la premiere connexion !");
    }
}
