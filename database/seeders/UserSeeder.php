<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            'first_name' => 'Hoang Khan',
            'last_name' => 'Nguyen',
            'gender' => 'male',
            'email' => 'admin@gmail.com',
            'phone_number' => '0123456789',
            'password' => Hash::make('12345678'),
        ]);
    }
}
