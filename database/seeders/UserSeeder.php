<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name'     => 'Dr. Dupont',
            'email'    => 'hakim@gmail.com',
            'password' => 'hakim123',
            'role'     => 'DENTIST',
        ]);

        User::create([
            'name'     => 'Assistante Sara',
            'email'    => 'lina@gmail.com',
            'password' => 'lina123',
            'role'     => 'ASSISTANT',
        ]);
    }
}
