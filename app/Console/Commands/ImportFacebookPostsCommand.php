<?php

namespace App\Console\Commands;

use App\Services\FacebookPostImporter;
use Illuminate\Console\Command;

class ImportFacebookPostsCommand extends Command
{
    protected $signature = 'facebook:import-posts
        {--node-id= : Facebook node id (default: services.facebook.page_id)}
        {--token= : Graph API access token (default: services.facebook.graph_access_token or services.facebook.page_access_token)}
        {--edge=feed : Edge to fetch (feed or posts)}
        {--since= : Fetch posts created on/after date (YYYY-MM-DD)}
        {--until= : Fetch posts created before date (YYYY-MM-DD)}
        {--limit=100 : Items per page (max 100)}
        {--max-pages=250 : Maximum pages to fetch}
        {--api-version= : Graph API version (default from config)}';

    protected $description = 'Import Facebook posts via Graph API pagination and store into local database';

    public function handle(FacebookPostImporter $importer): int
    {
        $nodeId = trim((string) ($this->option('node-id') ?: config('services.facebook.page_id', '')));
        $token = trim((string) ($this->option('token') ?: config('services.facebook.graph_access_token', config('services.facebook.page_access_token', ''))));

        if ($nodeId === '') {
            $this->error('Missing node id. Set --node-id or FB_PAGE_ID.');
            return self::FAILURE;
        }

        if ($token === '') {
            $this->error('Missing access token. Set --token or FB_GRAPH_ACCESS_TOKEN / FB_PAGE_ACCESS_TOKEN.');
            return self::FAILURE;
        }

        try {
            $result = $importer->import([
                'node_id' => $nodeId,
                'source_node_type' => 'page',
                'access_token' => $token,
                'edge' => (string) $this->option('edge'),
                'since' => (string) $this->option('since'),
                'until' => (string) $this->option('until'),
                'limit' => (int) $this->option('limit'),
                'max_pages' => (int) $this->option('max-pages'),
                'api_version' => (string) $this->option('api-version'),
            ]);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }

        $this->info('Facebook import completed.');
        $this->line('Pages fetched: ' . number_format((int) ($result['pages_fetched'] ?? 0)));
        $this->line('Rows received: ' . number_format((int) ($result['rows_received'] ?? 0)));
        $this->line('Inserted: ' . number_format((int) ($result['inserted'] ?? 0)));
        $this->line('Updated: ' . number_format((int) ($result['updated'] ?? 0)));
        $this->line('Skipped: ' . number_format((int) ($result['skipped'] ?? 0)));

        if (! empty($result['next_url'])) {
            $this->warn('Stopped before end of feed because max-pages limit was reached.');
        }

        return self::SUCCESS;
    }
}
