<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class ProductionSeeder extends Seeder
{
    public function run(): void
    {
        // Compte dentiste
        $dentistEmail = env('DENTIST_EMAIL', 'dentiste@cabinet.ma');
        $dentistPass  = env('DENTIST_PASSWORD', 'ChangeMe2024!');
        $dentistName  = env('DENTIST_NAME', 'Dr. Dentiste');

        User::firstOrCreate(
            ['email' => $dentistEmail],
            [
                'name'     => $dentistName,
                'password' => bcrypt($dentistPass),
                'role'     => 'DENTIST',
            ]
        );

        // Compte assistant
        $assistantEmail = env('ASSISTANT_EMAIL', 'assistant@cabinet.ma');
        $assistantPass  = env('ASSISTANT_PASSWORD', 'ChangeMe2024!');
        $assistantName  = env('ASSISTANT_NAME', 'Assistant');

        User::firstOrCreate(
            ['email' => $assistantEmail],
            [
                'name'     => $assistantName,
                'password' => bcrypt($assistantPass),
                'role'     => 'ASSISTANT',
            ]
        );

        // Catalogue des actes de base
        $this->call(CatalogActSeeder::class);

        $this->command->info("Dentiste  : {$dentistEmail} / {$dentistPass}");
        $this->command->info("Assistant : {$assistantEmail} / {$assistantPass}");
        $this->command->info("CHANGEZ les mots de passe apres la premiere connexion !");
    }
}
