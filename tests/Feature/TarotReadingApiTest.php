<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TarotReadingApiTest extends TestCase
{
    public function test_tarot_reading_endpoint_returns_ai_result(): void
    {
        config()->set('services.gemini.api_key', 'test-key');
        config()->set('services.gemini.model', 'gemini-2.5-flash');
        config()->set('services.gemini.base_url', 'https://generativelanguage.googleapis.com');

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'วันนี้พลังดี มีจังหวะให้ตัดสินใจอย่างมั่นใจ'],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->postJson('/api/tarot/reading', [
            'cards' => [
                [
                    'nameTh' => 'The Fool',
                    'nameEn' => 'The Fool',
                    'keywordsTh' => ['เริ่มต้น', 'อิสระ'],
                    'keywordsEn' => ['beginnings', 'freedom'],
                ],
                [
                    'nameTh' => 'The Sun',
                    'nameEn' => 'The Sun',
                    'keywordsTh' => ['ความสำเร็จ'],
                    'keywordsEn' => ['success'],
                ],
                [
                    'nameTh' => 'Ace of Cups',
                    'nameEn' => 'Ace of Cups',
                    'keywordsTh' => ['ความสุข'],
                    'keywordsEn' => ['joy'],
                ],
            ],
            'languageCode' => 'th',
            'type' => 'daily',
        ]);

        $response
            ->assertOk()
            ->assertJson([
                'result' => 'วันนี้พลังดี มีจังหวะให้ตัดสินใจอย่างมั่นใจ',
                'model' => 'gemini-2.5-flash',
            ]);
    }

    public function test_tarot_reading_endpoint_requires_card_name(): void
    {
        config()->set('services.gemini.api_key', 'test-key');

        $response = $this->postJson('/api/tarot/reading', [
            'cards' => [
                [
                    'keywordsTh' => ['เริ่มต้น'],
                ],
            ],
            'languageCode' => 'th',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonValidationErrors(['cards.0.nameTh']);
    }
}
