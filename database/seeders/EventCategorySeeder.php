<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EventCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Music', 'subcategories' => ['Rock', 'Classical']],
            ['name' => 'Sports', 'subcategories' => ['Basketball', 'Health Games']],
            ['name' => 'Travel', 'subcategories' => ['Business Travel', 'Adventure Travel']],
            ['name' => 'Festivals & Fairs', 'subcategories' => ['Music Festivals', 'Food Festivals']],
            ['name' => 'Arts & Craft', 'subcategories' => ['Performing Arts', 'Visual Arts']],
            ['name' => 'Exhibition', 'subcategories' => []],
            ['name' => 'Charity', 'subcategories' => []],
            ['name' => 'Workshop', 'subcategories' => []],
        ];

        foreach ($categories as $category) {
            $cat = \App\Models\Category::create([
                'name' => $category['name'],
                'slug' => \Illuminate\Support\Str::slug($category['name']),
            ]);

            foreach ($category['subcategories'] as $subcategory) {
                $cat->subcategories()->create([
                    'name' => $subcategory,
                    'slug' => \Illuminate\Support\Str::slug($subcategory),
                ]);
            }
        }

        $this->command->info('Event categories seeded successfully!');
    }
}
