<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\Article;

$disk = Storage::disk('public');

// 1. Move everything from 'article/' to 'articles/'
if ($disk->exists('article')) {
    echo "Moving contents from 'article/' to 'articles/'...\n";
    if (!$disk->exists('articles')) {
        $disk->makeDirectory('articles');
    }
    
    $directories = $disk->directories('article');
    foreach ($directories as $dir) {
        $newDir = str_replace('article/', 'articles/', $dir);
        echo "Moving $dir to $newDir\n";
        // Recursively move directories is not directly supported by Storage::move in some disks
        // We'll use rename on the physical path if possible
        $oldPath = $disk->path($dir);
        $newPath = $disk->path($newDir);
        
        $parentDir = dirname($newPath);
        if (!is_dir($parentDir)) {
            mkdir($parentDir, 0755, true);
        }
        
        if (is_dir($oldPath)) {
            rename($oldPath, $newPath);
        }
    }
}

// 2. Handle files that were in just '{year}/'
for ($year = 2024; $year <= 2027; $year++) {
    if ($disk->exists((string)$year)) {
        echo "Moving files from '$year/' to 'articles/$year/'...\n";
        $files = $disk->files((string)$year);
        $targetDir = "articles/$year";
        if (!$disk->exists($targetDir)) {
            $disk->makeDirectory($targetDir);
        }
        foreach ($files as $file) {
            $filename = basename($file);
            $newFile = "$targetDir/$filename";
            echo "Moving $file to $newFile\n";
            $disk->move($file, $newFile);
        }
    }
}

// 3. Update Database Records
echo "Updating database records...\n";
$articles = Article::all();
foreach ($articles as $article) {
    $updated = false;
    
    $paths = ['cover_image_path', 'cover_image_square_path', 'cover_image_landscape_path'];
    foreach ($paths as $pathField) {
        $oldValue = $article->$pathField;
        if ($oldValue && str_starts_with($oldValue, 'article/')) {
            $article->$pathField = str_replace('article/', 'articles/', $oldValue);
            $updated = true;
        } elseif ($oldValue && preg_match('/^202[0-9]\//', $oldValue)) {
            // Path was just '2026/filename.png'
            $article->$pathField = 'articles/' . $oldValue;
            $updated = true;
        }
    }
    
    // Update content links
    $content = $article->content;
    if (str_contains($content, 'article/')) {
        $article->content = str_replace('article/', 'articles/', $content);
        $updated = true;
    }
    // Also handle cases where images were in '{year}/'
    if (preg_match('/src="\/storage\/202[0-9]\//', $content)) {
        $article->content = preg_replace('/src="\/storage\/(202[0-9]\/)/', 'src="/storage/articles/$1', $content);
        $updated = true;
    }

    if ($updated) {
        echo "Updated Article ID: {$article->id}\n";
        $article->save();
    }
}

echo "Migration completed successfully!\n";
