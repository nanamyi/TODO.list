<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Plan;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Pastikan plan Free ada
        $plan = Plan::firstOrCreate(
            ['name' => 'Free'],
            ['description' => 'Default free plan', 'price' => 0]
        );

        // Buat user default admin
        User::updateOrCreate(
            ['email' => 'rintan@gmail.com'],
            [
                'name' => 'Rintan',
                'password' => Hash::make('12345678'),
                'is_admin' => true,
                'plan_id' => $plan->id
            ]
        );
    }
}
