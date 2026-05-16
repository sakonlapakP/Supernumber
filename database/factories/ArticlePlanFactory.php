<?php

namespace Database\Factories;

use App\Models\ArticlePlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ArticlePlan>
 */
class ArticlePlanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'publish_date' => fake()->dateTimeBetween('now', '+3 months')->format('Y-m-d'),
            'publish_time' => '09:00',
            'type' => fake()->randomElement(['หวย/สำคัญ', 'Evergreen', 'วันสำคัญ']),
            'topic' => fake()->sentence(4),
            'is_lottery' => false,
            'status' => ArticlePlan::STATUS_TODO,
            'assigned_to' => null,
            'due_date' => null,
            'blocked_reason' => null,
            'notes' => null,
            'article_id' => null,
            'last_refreshed_at' => null,
            'refresh_status' => null,
        ];
    }
}
