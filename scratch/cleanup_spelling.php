<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Storage;
use App\Models\Article;

$disk = Storage::disk('public');

// 1. Rename folders in 'articles/2026/'
$years = ['2026'];
foreach ($years as $year) {
    $dir = "articles/$year";
    if ($disk->exists($dir)) {
        $subdirs = $disk->directories($dir);
        foreach ($subdirs as $subdir) {
            if (str_contains($subdir, 'goverment')) {
                $newSubdir = str_replace('goverment', 'government', $subdir);
                echo "Renaming folder $subdir to $newSubdir\n";
                
                $oldPath = $disk->path($subdir);
                $newPath = $disk->path($newSubdir);
                
                if (is_dir($oldPath)) {
                    // Check if new path already exists
                    if (is_dir($newPath)) {
                        // Move files instead
                        $files = scandir($oldPath);
                        foreach ($files as $file) {
                            if ($file === '.' || $file === '..') continue;
                            rename($oldPath . '/' . $file, $newPath . '/' . $file);
                        }
                        rmdir($oldPath);
                    } else {
                        rename($oldPath, $newPath);
                    }
                }
            }
        }
    }
}

// 2. Update Slugs in Database
echo "Updating slugs in database...\n";
$articles = Article::where('slug', 'like', '%goverment%')->get();
foreach ($articles as $article) {
    $oldSlug = $article->slug;
    $newSlug = str_replace('goverment', 'government', $oldSlug);
    echo "Updating slug $oldSlug to $newSlug for Article ID: {$article->id}\n";
    $article->slug = $newSlug;
    $article->save();
}

echo "Cleanup completed!\n";
