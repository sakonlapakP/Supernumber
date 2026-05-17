<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Services\FacebookPagePoster;
use App\Services\LineNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PublishScheduledArticles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'articles:publish-scheduled';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish scheduled articles and send notifications';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $articles = Article::query()
            ->where('is_auto_post', true)
            ->where('notified_at', null)
            ->where(function ($query) {
                // Case 1: Already marked as published, just needs notification/auto-posting
                $query->where(function ($q) {
                    $q->where('is_published', true)
                        ->where(function ($inner) {
                            $inner->whereNull('published_at')
                                ->orWhere('published_at', '<=', now('Asia/Bangkok'));
                        });
                })
                // Case 2: Still a Draft but scheduled time has reached -> Flip to Published
                ->orWhere(function ($q) {
                    $q->where('is_published', false)
                        ->whereNotNull('published_at')
                        ->where('published_at', '<=', now('Asia/Bangkok'));
                });
            })
            ->get();

        if ($articles->isEmpty()) {
            $this->info('No articles to publish at this time.');
            return;
        }

        foreach ($articles as $article) {
            try {
                // Step 1: Atomically claim this article — commit DB change before any external calls
                $updateData = [
                    'notified_at' => now('Asia/Bangkok'),
                    'updated_at' => now('Asia/Bangkok'),
                ];

                if (!$article->is_published) {
                    $updateData['is_published'] = true;
                }

                $updated = \Illuminate\Support\Facades\DB::table('articles')
                    ->where('id', $article->id)
                    ->whereNull('notified_at')
                    ->update($updateData);

                if (!$updated) {
                    continue; // Another process already handled this article
                }

                $article->refresh();

                $this->info("Publishing article: {$article->title}");

                // Step 2: External API calls after DB is committed — failures here won't un-publish the article
                $fbRes = app(FacebookPagePoster::class)->postArticle($article);

                $lineMessage = "📢 เผยแพร่บทความใหม่แล้ว!\n\n";
                $lineMessage .= "หัวข้อ: {$article->title}\n";
                $lineMessage .= "แชร์ไปที่ Facebook Page: " . ($fbRes['success'] ? "สำเร็จ ✅" : "ไม่สำเร็จ ❌") . "\n";
                if (!$fbRes['success']) {
                    $lineMessage .= "สาเหตุ: " . ($fbRes['error'] ?? 'Unknown Error') . "\n";
                }
                $lineMessage .= "\n" . route('articles.show', ['slug' => $article->slug]);

                app(LineNotifier::class)->queueText(
                    'article_published',
                    $lineMessage,
                    $article
                );

                $this->info("Successfully processed: {$article->title}");
            } catch (\Throwable $e) {
                $this->error("Error publishing article {$article->id}: " . $e->getMessage());
                Log::error("Scheduled publish error for article {$article->id}: " . $e->getMessage());
            }
        }

        $this->info('Scheduled publishing completed.');
    }
}
