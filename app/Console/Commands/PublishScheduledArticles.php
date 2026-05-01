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
            ->where('is_published', true)
            ->where('is_auto_post', true) // Only publish articles allowed for auto-posting
            ->where('notified_at', null)
            ->where(function ($query) {
                $query->whereNull('published_at')
                    ->orWhere('published_at', '<=', now('Asia/Bangkok'));
            })
            ->get();

        if ($articles->isEmpty()) {
            $this->info('No articles to publish at this time.');
            return;
        }

        foreach ($articles as $article) {
            try {
                \Illuminate\Support\Facades\DB::transaction(function () use ($article) {
                    // To prevent duplicate sends from parallel executions, we check again and update atomically
                    $updated = \Illuminate\Support\Facades\DB::table('articles')
                        ->where('id', $article->id)
                        ->whereNull('notified_at')
                        ->update(['notified_at' => now('Asia/Bangkok'), 'updated_at' => now('Asia/Bangkok')]);

                    if (!$updated) {
                        return; // Another process already handled this article
                    }

                    $this->info("Publishing article: {$article->title}");

                    // Post to Facebook
                    $fbRes = app(FacebookPagePoster::class)->postArticle($article);
                    
                    // Prepare Line message
                    $lineMessage = "📢 เผยแพร่บทความใหม่แล้ว!\n\n";
                    $lineMessage .= "หัวข้อ: {$article->title}\n";
                    $lineMessage .= "แชร์ไปที่ Facebook Page: " . ($fbRes['success'] ? "สำเร็จ ✅" : "ไม่สำเร็จ ❌") . "\n";
                    if (!$fbRes['success']) {
                        $lineMessage .= "สาเหตุ: " . ($fbRes['error'] ?? 'Unknown Error') . "\n";
                    }
                    $lineMessage .= "\n" . route('articles.show', ['slug' => $article->slug]);

                    // Send Line notification
                    app(LineNotifier::class)->queueText(
                        'article_published',
                        $lineMessage,
                        $article
                    );

                    $this->info("Successfully processed: {$article->title}");
                });
            } catch (\Throwable $e) {
                $this->error("Error publishing article {$article->id}: " . $e->getMessage());
                Log::error("Scheduled publish error for article {$article->id}: " . $e->getMessage());
            }
        }

        $this->info('Scheduled publishing completed.');
    }
}
