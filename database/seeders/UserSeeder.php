<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name'      => 'Sarah Mitchell',
                'email'     => 'manager@websiteexpert.co.uk',
                'password'  => bcrypt('Manager@WebsiteExpert2026!'),
                'is_active' => true,
                'locale'    => 'en',
                'role'      => 'manager',
            ],
            [
                'name'      => 'James Carter',
                'email'     => 'developer@websiteexpert.co.uk',
                'password'  => bcrypt('Dev@WebsiteExpert2026!'),
                'is_active' => true,
                'locale'    => 'en',
                'role'      => 'developer',
            ],
            [
                'name'      => 'Emily Thompson',
                'email'     => 'developer2@websiteexpert.co.uk',
                'password'  => bcrypt('Dev2@WebsiteExpert2026!'),
                'is_active' => true,
                'locale'    => 'en',
                'role'      => 'developer',
            ],
        ];

        foreach ($users as $data) {
            $role = $data['role'];
            unset($data['role']);

            $user = User::firstOrCreate(['email' => $data['email']], $data);
            $user->assignRole($role);
        }
    }
}
