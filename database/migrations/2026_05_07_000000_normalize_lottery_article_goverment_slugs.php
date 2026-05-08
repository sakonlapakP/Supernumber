<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('articles')) {
            return;
        }

        DB::table('articles')
            ->where('slug', 'like', 'thai-government-lottery-%')
            ->get()
            ->each(function ($article): void {
                $newSlug = Str::replace('thai-government', 'thai-goverment', (string) $article->slug);
                $updateData = [];

                if (
                    $newSlug !== $article->slug
                    && ! DB::table('articles')->where('slug', $newSlug)->where('id', '!=', $article->id)->exists()
                ) {
                    $updateData['slug'] = $newSlug;
                }

                foreach ([
                    'content',
                    'excerpt',
                    'cover_image_path',
                    'cover_image_square_path',
                    'cover_image_landscape_path',
                ] as $column) {
                    if (Schema::hasColumn('articles', $column) && is_string($article->{$column})) {
                        $newVal = Str::replace('thai-government', 'thai-goverment', $article->{$column});
                        if ($newVal !== $article->{$column}) {
                            $updateData[$column] = $newVal;
                        }
                    }
                }

                if (!empty($updateData)) {
                    DB::table('articles')->where('id', $article->id)->update($updateData);
                }
            });
    }

    public function down(): void
    {
        if (! Schema::hasTable('articles')) {
            return;
        }

        DB::table('articles')
            ->where('slug', 'like', 'thai-goverment-lottery-%')
            ->get()
            ->each(function ($article): void {
                $oldSlug = Str::replace('thai-goverment', 'thai-government', (string) $article->slug);
                $updateData = [];

                if (
                    $oldSlug !== $article->slug
                    && ! DB::table('articles')->where('slug', $oldSlug)->where('id', '!=', $article->id)->exists()
                ) {
                    $updateData['slug'] = $oldSlug;
                }

                foreach ([
                    'content',
                    'excerpt',
                    'cover_image_path',
                    'cover_image_square_path',
                    'cover_image_landscape_path',
                ] as $column) {
                    if (Schema::hasColumn('articles', $column) && is_string($article->{$column})) {
                        $oldVal = Str::replace('thai-goverment', 'thai-government', $article->{$column});
                        if ($oldVal !== $article->{$column}) {
                            $updateData[$column] = $oldVal;
                        }
                    }
                }

                if (!empty($updateData)) {
                    DB::table('articles')->where('id', $article->id)->update($updateData);
                }
            });
    }
};
