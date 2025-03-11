<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = ['admin', 'user'];

        foreach ($roles as $role) {
            Role::create(['name' => $role]);
        }

        $user = User::factory()->create([
            'first_name' => 'Admin',
            'last_name' => '',
            'email' => 'admin@example.com',
        ]);

        $user->assignRole('admin');

        $this->command->info('Permissions seeded successfully!');
    }
}
