<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class StorageCleanupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'storage:cleanup {--force : Force the operation to run without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up redundant storage folders after consolidation to public/storage';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $oldStoragePath = storage_path('app/public');
        $newStoragePath = public_path('storage');

        $this->info("Consolidation Cleanup Utility");
        $this->line("Old Storage: {$oldStoragePath}");
        $this->line("New Storage: {$newStoragePath}");

        if (!File::exists($oldStoragePath)) {
            $this->warn("Old storage path does not exist. Nothing to clean up.");
            return;
        }

        if (!$this->option('force')) {
            if (!$this->confirm('This will delete the old storage/app/public directory. Have you verified everything is moved to public/storage?', false)) {
                $this->info('Cleanup aborted.');
                return;
            }
        }

        $this->info('Cleaning up redundant folders...');

        // 1. Delete the old storage/app/public directory entirely
        if (File::isDirectory($oldStoragePath)) {
            $this->comment("Deleting: {$oldStoragePath}");
            File::deleteDirectory($oldStoragePath);
            $this->info("✓ Old storage directory deleted.");
        }

        // 2. Cleanup empty directories in the new storage
        $this->cleanupEmptyDirs($newStoragePath);

        $this->info('Storage cleanup completed successfully.');
    }

    /**
     * Recursively delete empty directories
     */
    private function cleanupEmptyDirs($dir)
    {
        if (!File::isDirectory($dir)) return;

        $items = File::directories($dir);
        foreach ($items as $item) {
            $this->cleanupEmptyDirs($item);
        }

        // Re-check after cleaning sub-dirs
        if (count(File::allFiles($dir)) === 0 && count(File::directories($dir)) === 0) {
            // Avoid deleting the root storage or important folders
            $basename = basename($dir);
            if (!in_array($basename, ['storage', 'articles', 'tmp', 'temp_lottery', 'payment-slips'])) {
                $this->comment("Removing empty directory: {$dir}");
                File::deleteDirectory($dir);
            }
        }
    }
}
