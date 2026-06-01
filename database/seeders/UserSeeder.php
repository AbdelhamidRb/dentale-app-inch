<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name'     => 'Dr. Test',
            'email'    => 'dentiste@test.local',
            'password' => 'password',
            'role'     => 'DENTIST',
        ]);

        User::create([
            'name'     => 'Assistant Test',
            'email'    => 'assistant@test.local',
            'password' => 'password',
            'role'     => 'ASSISTANT',
        ]);
    }
}
