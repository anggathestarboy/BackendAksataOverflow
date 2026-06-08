<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BadgeSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $badges = [
            // Bronze
            [
                'id'             => Str::uuid(),
                'name'           => 'Newcomer',
                'description'    => 'Berhasil membuat postingan pertama.',
                'icon_url'       => null,
                'tier'           => 'bronze',
                'condition_type' => 'posts_count',
                'condition_value' => 1,
                'created_at'     => $now,
            ],
            [
                'id'             => Str::uuid(),
                'name'           => 'Contributor',
                'description'    => 'Telah membuat 10 postingan.',
                'icon_url'       => null,
                'tier'           => 'bronze',
                'condition_type' => 'posts_count',
                'condition_value' => 10,
                'created_at'     => $now,
            ],
            [
                'id'             => Str::uuid(),
                'name'           => 'First Answer',
                'description'    => 'Jawaban pertama diterima oleh penanya.',
                'icon_url'       => null,
                'tier'           => 'bronze',
                'condition_type' => 'answers_accepted',
                'condition_value' => 1,
                'created_at'     => $now,
            ],

            // Silver
            [
                'id'             => Str::uuid(),
                'name'           => 'Rising Star',
                'description'    => 'Mengumpulkan 500 poin reputasi.',
                'icon_url'       => null,
                'tier'           => 'silver',
                'condition_type' => 'reputation_points',
                'condition_value' => 500,
                'created_at'     => $now,
            ],
            [
                'id'             => Str::uuid(),
                'name'           => 'Problem Solver',
                'description'    => 'Memiliki 25 jawaban yang diterima.',
                'icon_url'       => null,
                'tier'           => 'silver',
                'condition_type' => 'answers_accepted',
                'condition_value' => 25,
                'created_at'     => $now,
            ],
            [
                'id'             => Str::uuid(),
                'name'           => 'Prolific Writer',
                'description'    => 'Telah membuat 100 postingan.',
                'icon_url'       => null,
                'tier'           => 'silver',
                'condition_type' => 'posts_count',
                'condition_value' => 100,
                'created_at'     => $now,
            ],

            // Gold
            [
                'id'             => Str::uuid(),
                'name'           => 'Expert',
                'description'    => 'Mengumpulkan 5.000 poin reputasi.',
                'icon_url'       => null,
                'tier'           => 'gold',
                'condition_type' => 'reputation_points',
                'condition_value' => 5000,
                'created_at'     => $now,
            ],
            [
                'id'             => Str::uuid(),
                'name'           => 'Answer Guru',
                'description'    => 'Memiliki 100 jawaban yang diterima.',
                'icon_url'       => null,
                'tier'           => 'gold',
                'condition_type' => 'answers_accepted',
                'condition_value' => 100,
                'created_at'     => $now,
            ],

            // Platinum
            [
                'id'             => Str::uuid(),
                'name'           => 'Legendary',
                'description'    => 'Mengumpulkan 25.000 poin reputasi.',
                'icon_url'       => null,
                'tier'           => 'platinum',
                'condition_type' => 'reputation_points',
                'condition_value' => 25000,
                'created_at'     => $now,
            ],
            [
                'id'             => Str::uuid(),
                'name'           => 'Grand Master',
                'description'    => 'Memiliki 500 jawaban yang diterima.',
                'icon_url'       => null,
                'tier'           => 'platinum',
                'condition_type' => 'answers_accepted',
                'condition_value' => 500,
                'created_at'     => $now,
            ],
        ];

        DB::table('badges')->insert($badges);
    }
}