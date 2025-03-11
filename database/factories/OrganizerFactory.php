<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Organizer>
 */
class OrganizerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'name' => $this->faker->name,
            'bio' => $this->faker->paragraph,
            'profile_image' => $this->faker->imageUrl(),
            'facebook_url' => $this->faker->url,
            'twitter_url' => $this->faker->url,
            'website_url' => $this->faker->url,
        ];
    }
}
