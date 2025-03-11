<?php

namespace Database\Factories;

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
        return [
            'organizer_id' => \App\Models\Organizer::factory(),
            'title' => $this->faker->sentence,
            'image' => $this->faker->imageUrl(),
            'description' => $this->faker->paragraph,
            'type' => $this->faker->randomElement(['online', 'offline']),
//            'online_link' => $this->faker->url, only for online events
//            'note' => $this->faker->sentence, only for online events
//            'location' => $this->faker->address, only for offline events
            'start_date' => $this->faker->date(),
            'end_date' => $this->faker->date(),
            'start_time' => $this->faker->time(),
            'end_time' => $this->faker->time(),
        ];
    }
}
