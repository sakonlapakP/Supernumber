<?php

use App\Models\Article;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('articles')) {
            return;
        }

        Article::query()
            ->where('slug', 'like', 'thai-government-lottery-%')
            ->get()
            ->each(function (Article $article): void {
                $newSlug = Str::replace('thai-government', 'thai-goverment', (string) $article->slug);

                if (
                    $newSlug !== $article->slug
                    && ! Article::query()->where('slug', $newSlug)->whereKeyNot($article->getKey())->exists()
                ) {
                    $article->slug = $newSlug;
                }

                foreach ([
                    'content',
                    'excerpt',
                    'cover_image_path',
                    'cover_image_square_path',
                    'cover_image_landscape_path',
                ] as $column) {
                    if (Schema::hasColumn('articles', $column) && is_string($article->{$column})) {
                        $article->{$column} = Str::replace('thai-government', 'thai-goverment', $article->{$column});
                    }
                }

                $article->save();
            });
    }

    public function down(): void
    {
        if (! Schema::hasTable('articles')) {
            return;
        }

        Article::query()
            ->where('slug', 'like', 'thai-goverment-lottery-%')
            ->get()
            ->each(function (Article $article): void {
                $oldSlug = Str::replace('thai-goverment', 'thai-government', (string) $article->slug);

                if (
                    $oldSlug !== $article->slug
                    && ! Article::query()->where('slug', $oldSlug)->whereKeyNot($article->getKey())->exists()
                ) {
                    $article->slug = $oldSlug;
                }

                foreach ([
                    'content',
                    'excerpt',
                    'cover_image_path',
                    'cover_image_square_path',
                    'cover_image_landscape_path',
                ] as $column) {
                    if (Schema::hasColumn('articles', $column) && is_string($article->{$column})) {
                        $article->{$column} = Str::replace('thai-goverment', 'thai-government', $article->{$column});
                    }
                }

                $article->save();
            });
    }
};
