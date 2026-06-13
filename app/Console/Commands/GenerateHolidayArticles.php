<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Models\ArticlePlan;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GenerateHolidayArticles extends Command
{
    protected $signature = 'articles:generate-holidays {--force : Overwrite existing articles}';
    protected $description = 'Generate 20 holiday articles using Gemini API without phone numbers/lucky numbers';

    public function handle(): void
    {
        $apiKey = trim((string) config('services.gemini.api_key', env('GEMINI_API_KEY', '')));
        $model = trim((string) config('services.gemini.model', env('GEMINI_MODEL', 'gemini-2.5-flash')));
        $baseUrl = rtrim((string) config('services.gemini.base_url', env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com')), '/');

        if (empty($apiKey)) {
            $this->error('GEMINI_API_KEY is not configured in .env');
            return;
        }

        $author = User::whereIn('role', ['admin', 'manager'])->first() ?? User::first();
        $authorId = $author ? $author->id : null;

        $plansData = [
            '2026-01-01' => [
                'slug' => 'new-year-blessings-2569',
                'type' => 'blessing',
                'prompt' => 'เขียนคำอวยพรต้อนรับวันขึ้นปีใหม่ พ.ศ. 2569 (ค.ศ. 2026) โดยรวบรวมข้อความอวยพรอันอบอุ่นและซาบซึ้งสำหรับมอบให้ครอบครัว เพื่อน และคนรัก และแนบกลอนอวยพรวันขึ้นปีใหม่อันไพเราะลงไปด้วย ห้ามเขียนเรื่องตัวเลขมงคล เบอร์มงคล หรือเบอร์โทรศัพท์ใดๆ เด็ดขาด เขียนในสไตล์บทความทางการและเป็นสิริมงคล',
            ],
            '2026-01-09' => [
                'slug' => 'national-children-day-history',
                'type' => 'history',
                'prompt' => 'เล่าประวัติความเป็นมาและเรื่องราวความสำคัญของวันเด็กแห่งชาติของประเทศไทย จุดประสงค์ของการจัดงาน และการส่งเสริมเด็กและเยาวชน ห้ามเขียนเรื่องตัวเลขมงคล เบอร์มงคล หรือเบอร์โทรศัพท์เด็ดขาด',
            ],
            '2026-02-14' => [
                'slug' => 'valentine-day-story',
                'type' => 'history',
                'prompt' => 'เล่าประวัติของนักบุญวาเลนไทน์ (St. Valentine) และประวัติศาสตร์เรื่องเล่าความเป็นมาของวันแห่งความรักในระดับสากล ห้ามเขียนเรื่องตัวเลขมงคล เบอร์มงคล หรือเบอร์โทรศัพท์เด็ดขาด',
            ],
            '2026-02-21' => [
                'slug' => 'magha-bucha-day-history',
                'type' => 'history',
                'prompt' => 'เล่าประวัติความเป็นมาของวันมาฆบูชา เหตุการณ์สำคัญทางพระพุทธศาสนา (การจาตุรงคสันนิบาต และการแสดงโอวาทปาติโมกข์) รวมถึงข้อคิดหลักธรรมคำสอนในการดำเนินชีวิต ห้ามเขียนเรื่องตัวเลขมงคล เบอร์มงคล หรือเบอร์โทรศัพท์เด็ดขาด',
            ],
            '2026-04-06' => [
                'slug' => 'chakri-memorial-day-history',
                'type' => 'history',
                'prompt' => 'เล่าประวัติความสำคัญของวันจักรี พระราชกรณียกิจของพระบาทสมเด็จพระพุทธยอดฟ้าจุฬาโลกมหาราช (รัชกาลที่ 1) และการสถาปนากรุงเทพมหานครเป็นราชธานี ห้ามเขียนเรื่องตัวเลขมงคล เบอร์มงคล หรือเบอร์โทรศัพท์เด็ดขาด',
            ],
            '2026-04-13' => [
                'slug' => 'songkran-festival-legend',
                'type' => 'history',
                'prompt' => 'เล่าประเพณีวันสงกรานต์ปีใหม่ไทย ตำนานนางสงกรานต์ทั้งเจ็ด และเรื่องราวประเพณีการสรงน้ำพระ การรดน้ำดำหัวผู้ใหญ่ ห้ามเขียนเรื่องตัวเลขมงคล เบอร์มงคล หรือเบอร์โทรศัพท์เด็ดขาด',
            ],
            '2026-05-11' => [
                'slug' => 'royal-ploughing-ceremony-history',
                'type' => 'history',
                'prompt' => 'เล่าประวัติและความสำคัญของพระราชพิธีพืชมงคลจรดพระนังคัลแรกนาขวัญ ความเป็นมาของพิธี ความหมายของคำทำนายพระโค และการยกย่องวันชาวนาไทย ห้ามเขียนเรื่องตัวเลขมงคล เบอร์มงคล หรือเบอร์โทรศัพท์เด็ดขาด',
            ],
            '2026-05-31' => [
                'slug' => 'visakha-bucha-day-history',
                'type' => 'history',
                'prompt' => 'เล่าประวัติวันวิสาขบูชา วันสำคัญสากลของโลก (วันประสูติ ตรัสรู้ และปรินิพพานของพระพุทธเจ้า) และการปฏิบัติตนของพุทธศาสนิกชน ห้ามเขียนเรื่องตัวเลขมงคล เบอร์มงคล หรือเบอร์โทรศัพท์เด็ดขาด',
            ],
            '2026-06-26' => [
                'slug' => 'sunthorn-phu-day-biography',
                'type' => 'history',
                'prompt' => 'เล่าชีวประวัติของท่านสุนทรภู่ กวีเอกของไทยและกวีสำคัญของโลก ผลงานวรรณคดีชิ้นสำคัญ เช่น พระอภัยมณี สุดสาคร นิราศภูเขาทอง และข้อคิดสอนใจจากบทประพันธ์ ห้ามเขียนเรื่องตัวเลขมงคล เบอร์มงคล หรือเบอร์โทรศัพท์เด็ดขาด',
            ],
            '2026-07-28' => [
                'slug' => 'king-rama-10-tribute',
                'type' => 'history',
                'prompt' => 'เขียนบทความเฉลิมพระเกียรติเนื่องในวันเฉลิมพระชนมพรรษาพระบาทสมเด็จพระวชิรเกล้าเจ้าอยู่หัว (รัชกาลที่ 10) เล่าพระราชกรณียกิจที่สำคัญเพื่อประโยชน์สุขของปวงชนชาวไทย ห้ามเขียนเรื่องตัวเลขมงคล เบอร์มงคล หรือเบอร์โทรศัพท์เด็ดขาด',
            ],
            '2026-07-29' => [
                'slug' => 'asanha-bucha-day-history',
                'type' => 'history',
                'prompt' => 'เล่าประวัติและความสำคัญของวันอาสาฬหบูชา เหตุการณ์สำคัญทางพระพุทธศาสนา (การแสดงปฐมเทศนา ธัมมจักกัปปวัตตนสูตร และการมีพระรัตนตรัยครบองค์สาม) ห้ามเขียนเรื่องตัวเลขมงคล เบอร์มงคล หรือเบอร์โทรศัพท์เด็ดขาด',
            ],
            '2026-08-12' => [
                'slug' => 'national-mothers-day-blessings',
                'type' => 'blessing',
                'prompt' => 'เขียนบทอวยพรวันแม่แห่งชาติ (12 สิงหาคม) รวบรวมข้อความบอกรักแม่ คำอวยพรซาบซึ้งกินใจสำหรับมอบให้แม่ และกลอนวันแม่อันอบอุ่นเพื่อแสดงความกตัญญู ห้ามเขียนเรื่องตัวเลขมงคล เบอร์มงคล หรือเบอร์โทรศัพท์เด็ดขาด',
            ],
            '2026-08-15' => [
                'slug' => 'ganesha-chaturthi-legend',
                'type' => 'history',
                'prompt' => 'เล่าตำนานวันคเณศจตุรถี วันกำเนิดของพระพิฆเนศ มหาเทพแห่งปัญญาและความสำเร็จ และความเป็นมาของพิธีการบูชาเพื่อขจัดอุปสรรค ห้ามเขียนเรื่องตัวเลขมงคล เบอร์มงคล หรือเบอร์โทรศัพท์เด็ดขาด',
            ],
            '2026-09-25' => [
                'slug' => 'mid-autumn-moon-festival-legend',
                'type' => 'history',
                'prompt' => 'เล่าตำนานวันไหว้พระจันทร์ (เทศกาลกลางฤดูใบไม้ร่วง) เรื่องราวของนางฉางเอ๋อเหินสู่ดวงจันทร์ และความสำคัญของการไหว้พระจันทร์เพื่อความกลมเกลียวในครอบครัว ห้ามเขียนเรื่องตัวเลขมงคล เบอร์มงคล หรือเบอร์โทรศัพท์เด็ดขาด',
            ],
            '2026-10-20' => [
                'slug' => 'vegetarian-festival-history',
                'type' => 'history',
                'prompt' => 'เล่าประวัติความเป็นมาของเทศกาลกินเจ (ถือศีลกินผัก) ความหมายของธงเจ พิธีกรรม และคุณประโยชน์ของการชำระล้างจิตใจและร่างกาย ห้ามเขียนเรื่องตัวเลขมงคล เบอร์มงคล หรือเบอร์โทรศัพท์เด็ดขาด',
            ],
            '2026-10-23' => [
                'slug' => 'chulalongkorn-day-piya-maharaj',
                'type' => 'history',
                'prompt' => 'เล่าประวัติความสำคัญของวันปิยมหาราช พระราชกรณียกิจอันเป็นอเนกอนันต์ของพระบาทสมเด็จพระจุลจอมเกล้าเจ้าอยู่หัว (รัชกาลที่ 5) โดยเน้นเรื่องการเลิกทาสและการพัฒนาประเทศในด้านต่างๆ ห้ามเขียนเรื่องตัวเลขมงคล เบอร์มงคล หรือเบอร์โทรศัพท์เด็ดขาด',
            ],
            '2026-11-24' => [
                'slug' => 'loy-krathong-festival-legend',
                'type' => 'history',
                'prompt' => 'เล่าประวัติประเพณีลอยกระทง ประวัติความเป็นมาของพิธีขอขมาพระแม่คงคา ตำนานท้าวศรีจุฬาลักษณ์ (นางนพมาศ) และคุณค่าทางวัฒนธรรมไทย ห้ามเขียนเรื่องตัวเลขมงคล เบอร์มงคล หรือเบอร์โทรศัพท์เด็ดขาด',
            ],
            '2026-12-05' => [
                'slug' => 'national-fathers-day-blessings',
                'type' => 'blessing',
                'prompt' => 'เขียนบทอวยพรวันพ่อแห่งชาติ (5 ธันวาคม) รวบรวมข้อความบอกรักพ่อ คำอวยพรซาบซึ้งกินใจสำหรับมอบให้พ่อ และกลอนวันพ่ออันอบอุ่นเพื่อแสดงความกตัญญู ห้ามเขียนเรื่องตัวเลขมงคล เบอร์มงคล หรือเบอร์โทรศัพท์เด็ดขาด',
            ],
            '2026-12-10' => [
                'slug' => 'constitution-day-history-thailand',
                'type' => 'history',
                'prompt' => 'เล่าประวัติและความเป็นมาของวันรัฐธรรมนูญของประเทศไทย พระราชทานรัฐธรรมนูญฉบับแรกของพระบาทสมเด็จพระปกเกล้าเจ้าอยู่หัว (รัชกาลที่ 7) เพื่อส่งเสริมความรู้ความเข้าใจเรื่องระบอบประชาธิปไตย ห้ามเขียนเรื่องตัวเลขมงคล เบอร์มงคล หรือเบอร์โทรศัพท์เด็ดขาด',
            ],
            '2026-12-31' => [
                'slug' => 'new-year-eve-wishes-2569',
                'type' => 'blessing',
                'prompt' => 'เขียนคำอวยพรส่งท้ายปีเก่าต้อนรับปีใหม่ (พ.ศ. 2569 สู่ พ.ศ. 2570) โดยรวบรวมคำอวยพรความหมายดีๆ เพื่อความก้าวหน้า ความร่ำรวย ความสุข และสุขภาพแข็งแรงสำหรับเริ่มต้นปีใหม่ ห้ามเขียนเรื่องตัวเลขมงคล เบอร์มงคล หรือเบอร์โทรศัพท์เด็ดขาด',
            ],
        ];

        foreach ($plansData as $dateStr => $meta) {
            $slug = $meta['slug'];
            $promptText = $meta['prompt'] . "\nกรุณาตอบกลับเป็นรูปแบบ JSON เสมอตาม Schema ที่กำหนดไว้";

            // Check if plan exists for this date
            $plan = ArticlePlan::where('publish_date', $dateStr)->first();
            if (!$plan) {
                $this->info("Skipped date $dateStr (no plan row found in DB)");
                continue;
            }

            // Check if article already exists
            $existing = Article::where('slug', $slug)->first();
            if ($existing && !$this->option('force')) {
                $this->info("Article for slug '$slug' already exists. Use --force to overwrite. Linking plan...");
                $plan->update([
                    'article_id' => $existing->id,
                    'status' => ArticlePlan::STATUS_DONE
                ]);
                continue;
            }

            $this->info("Generating article for: {$plan->topic} ($dateStr)...");

            try {
                $response = Http::acceptJson()
                    ->timeout(90)
                    ->withQueryParameters(['key' => $apiKey])
                    ->post($baseUrl . "/v1beta/models/{$model}:generateContent", [
                        'contents' => [
                            [
                                'parts' => [
                                    ['text' => $promptText],
                                ],
                            ],
                        ],
                        'generationConfig' => [
                            'responseMimeType' => 'application/json',
                            'responseSchema' => [
                                'type' => 'OBJECT',
                                'properties' => [
                                    'title' => [
                                        'type' => 'STRING',
                                        'description' => 'หัวข้อบทความที่สั้นกระชับ สละสลวย น่าอ่าน และสอดคล้องกับหลัก SEO ความยาว 60-80 ตัวอักษร'
                                    ],
                                    'excerpt' => [
                                        'type' => 'STRING',
                                        'description' => 'คำเกริ่นย่อ หรือคำโปรยสั้นๆ 2-3 ประโยคเพื่อดึงดูดความสนใจบนการ์ดหน้ารวม'
                                    ],
                                    'content' => [
                                        'type' => 'STRING',
                                        'description' => 'เนื้อหาบทความแบบยาว ละเอียด โดยต้องจัดรูปแบบด้วย HTML tags เท่านั้น (เช่น <h2>, <p>, <ul>, <li>, <strong>, <em>, <ol>) ห้ามเขียนเรื่องตัวเลขนำโชคหรือเบอร์มงคลเด็ดขาด'
                                    ],
                                    'meta_description' => [
                                        'type' => 'STRING',
                                        'description' => 'คำอธิบายบทความสั้นๆ สำหรับแสดงบนหน้าค้นหาของ Google ความยาวไม่เกิน 150 ตัวอักษร'
                                    ],
                                    'keywords' => [
                                        'type' => 'STRING',
                                        'description' => 'คีย์เวิร์ดหลักของบทความ 3-5 คำ คั่นด้วยเครื่องหมายจุลภาค (,)'
                                    ],
                                    'lsi_keywords' => [
                                        'type' => 'STRING',
                                        'description' => 'คีย์เวิร์ดที่เกี่ยวข้อง (LSI) 3-5 คำ คั่นด้วยเครื่องหมายจุลภาค (,)'
                                    ]
                                ],
                                'required' => ['title', 'excerpt', 'content', 'meta_description', 'keywords', 'lsi_keywords'],
                            ],
                        ],
                    ]);

                if ($response->failed()) {
                    $this->error("API request failed for $dateStr: " . ($response->json('error.message') ?? $response->body()));
                    continue;
                }

                $result = $response->json();
                $contentStr = $result['choices'][0]['message']['content'] 
                    ?? $result['candidates'][0]['content']['parts'][0]['text'] 
                    ?? null;

                if (!$contentStr) {
                    $this->error("No content in response for $dateStr");
                    continue;
                }

                $data = json_decode($contentStr, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    if (preg_match('/\{.*\}/s', $contentStr, $matches)) {
                        $data = json_decode($matches[0], true);
                    }
                }

                if (!$data || json_last_error() !== JSON_ERROR_NONE) {
                    $this->error("Response for $dateStr is not valid JSON.");
                    continue;
                }

                // Default placeholder template paths
                $landscapePath = 'images/landscape article template.PNG';
                $squarePath = 'images/squae article template.PNG';

                $articleData = [
                    'title' => $data['title'],
                    'slug' => $slug,
                    'excerpt' => $data['excerpt'] ?: Str::limit(strip_tags($data['content']), 160),
                    'content' => $data['content'],
                    'cover_image_path' => $squarePath,
                    'cover_image_square_path' => $squarePath,
                    'cover_image_landscape_path' => $landscapePath,
                    'meta_description' => $data['meta_description'],
                    'keywords' => $data['keywords'],
                    'lsi_keywords' => $data['lsi_keywords'],
                    'is_published' => true,
                    'published_at' => Carbon::parse($dateStr . ' 09:00:00', 'Asia/Bangkok'),
                    'author_user_id' => $authorId,
                ];

                $article = Article::updateOrCreate(
                    ['slug' => $slug],
                    $articleData
                );

                // Update the plan with the created article ID and status Done
                $plan->update([
                    'article_id' => $article->id,
                    'status' => ArticlePlan::STATUS_DONE
                ]);

                $this->info("SUCCESS: Generated article '{$article->title}' (ID: {$article->id})");

            } catch (\Throwable $e) {
                $this->error("Error processing $dateStr: " . $e->getMessage());
            }
        }
    }
}
