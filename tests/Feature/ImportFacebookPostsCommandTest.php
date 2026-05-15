<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImportFacebookPostsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_facebook_import_command_imports_rows(): void
    {
        config()->set('services.facebook.page_id', '987654');
        config()->set('services.facebook.graph_access_token', 'command-token');
        config()->set('services.facebook.graph_api_version', 'v20.0');

        Http::fake([
            'https://graph.facebook.com/*' => Http::response([
                'data' => [
                    [
                        'id' => '987654_1',
                        'message' => 'command one',
                        'created_time' => '2025-01-05T08:00:00+0000',
                    ],
                    [
                        'id' => '987654_2',
                        'message' => 'command two',
                        'created_time' => '2025-01-06T08:00:00+0000',
                    ],
                ],
            ], 200),
        ]);

        $exitCode = Artisan::call('facebook:import-posts', [
            '--edge' => 'feed',
            '--until' => '2025-12-31',
            '--max-pages' => 1,
            '--limit' => 100,
        ]);

        $this->assertSame(0, $exitCode);
        $this->assertDatabaseCount('facebook_imported_posts', 2);
        $this->assertDatabaseHas('facebook_imported_posts', [
            'facebook_post_id' => '987654_1',
        ]);
        $this->assertDatabaseHas('facebook_imported_posts', [
            'facebook_post_id' => '987654_2',
        ]);
    }
}
