<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'id' => Str::uuid(),
                'name' => 'admin',
                'permissions' => json_encode([
                    'all' => true
                ]),
                'created_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'moderator',
                'permissions' => json_encode([
                    'delete_any_post' => true,
                    'close_any_post' => true,
                    'delete_any_comment' => true,
                    'manage_reports' => true,
                    'manage_tags' => true,
                    'manage_categories' => false,
                    'manage_roles' => false,
                    'ban_users' => false
                ]),
                'created_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'user',
                'permissions' => json_encode(null),
                'created_at' => now(),
            ]
        ];

        \App\Models\Role::insert($roles);
    }
}
