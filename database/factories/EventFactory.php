<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Event>
 */
class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Determine if the event is online or offline
        $isOnline = $this->faker->boolean();

        // Initialize the optional fields
        $onlineLink = null;
        $note = null;
        $location = null;

        // Set the optional fields based on the event type
        if ($isOnline) {
            $onlineLink = $this->faker->url;
            $note = $this->faker->sentence;
        } else {
            $location = $this->faker->address;
        }

        // Get a random category and its subcategory
        $category = Category::inRandomOrder()->first();
        $subCategory = $category->subcategories()->inRandomOrder()->first();

        $start_date = $this->faker->dateTimeBetween('now', '+1 year');

        return [
            'organizer_id' => \App\Models\Organizer::factory(),
            'category_id' => $category->id,
            'sub_category_id' => $subCategory ? $subCategory->id : null,
            'title' => $this->faker->sentence,
            'image' => 'image-1@2x.jpg',
            'description' => $this->faker->paragraphs(3, true),
            'is_online' => $isOnline,
            'online_link' => $onlineLink,
            'note' => $note,
            'location' => $location,
            'start_date' => $start_date,
            'end_date' => $this->faker->dateTimeBetween($start_date, '+1 year'),
        ];
    }
}
