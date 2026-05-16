<?php

namespace Database\Factories;

use App\Models\Article;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    public function definition(): array
    {
        $title = fake()->sentence(6);

        return [
            'title' => $title,
            'slug' => Str::slug($title) . '-' . fake()->unique()->randomNumber(4),
            'excerpt' => fake()->paragraph(),
            'content' => '<p>' . fake()->paragraphs(3, true) . '</p>',
            'cover_image_path' => null,
            'cover_image_landscape_path' => null,
            'cover_image_square_path' => null,
            'image_guidelines' => null,
            'meta_description' => fake()->sentence(),
            'keywords' => null,
            'lsi_keywords' => null,
            'is_published' => true,
            'is_auto_post' => false,
            'is_line_broadcasted' => false,
            'published_at' => now()->subDays(rand(1, 30)),
            'notified_at' => null,
            'view_count' => 0,
            'author_user_id' => null,
        ];
    }
}
