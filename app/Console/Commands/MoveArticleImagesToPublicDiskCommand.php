<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class MoveArticleImagesToPublicDiskCommand extends Command
{
    protected $signature = 'articles:move-local-images-to-public
        {--dry-run : Show what would move without writing}';

    protected $description = 'Move misplaced article images from storage/app/articles to the configured public disk used by articles.';

    public function handle(): int
    {
        $sourceRoot = storage_path('app/articles');
        $dryRun = (bool) $this->option('dry-run');

        if (! File::isDirectory($sourceRoot)) {
            $this->info('No misplaced article images found at storage/app/articles.');
            return self::SUCCESS;
        }

        $disk = Storage::disk('public');
        $moved = 0;
        $skipped = 0;

        foreach (File::allFiles($sourceRoot) as $file) {
            $relativePath = 'articles/' . ltrim(str_replace('\\', '/', $file->getRelativePathname()), '/');

            if ($disk->exists($relativePath)) {
                $this->line("SKIP exists: {$relativePath}");
                $skipped++;
                continue;
            }

            $this->line("MOVE {$file->getPathname()} -> public disk: {$relativePath}");

            if (! $dryRun) {
                $disk->put($relativePath, File::get($file->getPathname()));
            }

            $moved++;
        }

        $this->info("Done. moved={$moved}, skipped={$skipped}, dry_run=" . ($dryRun ? 'true' : 'false'));

        return self::SUCCESS;
    }
}

