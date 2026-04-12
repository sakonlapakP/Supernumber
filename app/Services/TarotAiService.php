<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class TarotAiService
{
    /**
     * @param  array<int, array<string, mixed>>  $cards
     * @return array{result: string, model: string}
     */
    public function generateReading(
        array $cards,
        ?string $question = null,
        string $languageCode = 'th',
        ?string $type = null,
    ): array {
        $apiKey = trim((string) config('services.gemini.api_key', ''));
        $model = trim((string) config('services.gemini.model', 'gemini-2.5-flash'));
        $baseUrl = rtrim((string) config('services.gemini.base_url', 'https://generativelanguage.googleapis.com'), '/');
        $timeout = max(1, (int) config('services.gemini.timeout', 60));
        $languageCode = $languageCode === 'en' ? 'en' : 'th';
        $normalizedQuestion = trim((string) $question);
        $normalizedType = trim((string) $type);

        if ($apiKey === '') {
            throw new RuntimeException('Gemini API key is not configured.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $prompt = $this->buildPrompt(
            cards: $cards,
            question: $normalizedQuestion,
            languageCode: $languageCode,
            type: $normalizedType,
        );

        try {
            $response = Http::acceptJson()
                ->timeout($timeout)
                ->withQueryParameters(['key' => $apiKey])
                ->post($baseUrl . "/v1beta/models/{$model}:generateContent", [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt],
                            ],
                        ],
                    ],
                ]);
        } catch (Throwable $e) {
            Log::warning('Tarot AI request failed before receiving a response.', [
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException(
                $this->languageMessage(
                    $languageCode,
                    'Connection error with AI service. Please try again.',
                    'เกิดข้อผิดพลาดในการเชื่อมต่อกับ AI กรุณาลองใหม่อีกครั้ง'
                ),
                Response::HTTP_SERVICE_UNAVAILABLE,
                $e
            );
        }

        if (in_array($response->status(), [
            Response::HTTP_TOO_MANY_REQUESTS,
            Response::HTTP_SERVICE_UNAVAILABLE,
            Response::HTTP_GATEWAY_TIMEOUT,
        ], true)) {
            throw new RuntimeException(
                $this->languageMessage(
                    $languageCode,
                    'AI server is currently overloaded. Please try again in a moment.',
                    'ขออภัย ระบบ AI กำลังทำงานหนักในขณะนี้ กรุณาลองใหม่ในอีกสักครู่'
                ),
                Response::HTTP_SERVICE_UNAVAILABLE
            );
        }

        if (! $response->successful()) {
            $errorMessage = trim((string) data_get($response->json(), 'error.message', ''));

            Log::warning('Tarot AI upstream returned an error response.', [
                'status' => $response->status(),
                'message' => $errorMessage,
            ]);

            throw new RuntimeException(
                $errorMessage !== ''
                    ? $this->languageMessage(
                        $languageCode,
                        $errorMessage,
                        'เกิดข้อผิดพลาดจากบริการ AI: ' . $errorMessage
                    )
                    : $this->languageMessage(
                        $languageCode,
                        'Failed to generate AI reading.',
                        'ไม่สามารถสร้างคำทำนายจาก AI ได้'
                    ),
                Response::HTTP_BAD_GATEWAY
            );
        }

        $parts = data_get($response->json(), 'candidates.0.content.parts', []);
        $texts = collect(is_array($parts) ? $parts : [])
            ->pluck('text')
            ->filter(fn ($value) => is_string($value) && trim($value) !== '')
            ->map(fn (string $value) => trim($value));

        $result = trim($texts->implode("\n"));

        if ($result === '') {
            throw new RuntimeException(
                $this->languageMessage(
                    $languageCode,
                    'The AI returned an empty response.',
                    'AI ไม่ได้ส่งคำทำนายกลับมา'
                ),
                Response::HTTP_BAD_GATEWAY
            );
        }

        return [
            'result' => $result,
            'model' => $model,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $cards
     */
    private function buildPrompt(array $cards, string $question, string $languageCode, string $type): string
    {
        $responseLanguage = $languageCode === 'en' ? 'English' : 'Thai';
        $cardDescription = $this->buildCardDescription($cards, $languageCode);

        if ($type === 'daily') {
            return trim(<<<PROMPT
You are an expert Tarot Reader.
Cards:
{$cardDescription}

Question: "Daily Horoscope - How is my day today?"

Instruction:
1. Analyze the cards for a daily horoscope.
2. Provide the entire reading in exactly one concise and powerful line.
3. Do not use newlines, section headers, or bullet points.
4. Response Language: {$responseLanguage}.
5. Tone: Wise, encouraging, and punchy.
PROMPT);
        }

        if ($question !== '') {
            return trim(<<<PROMPT
You are an expert Tarot Reader.
Question: "{$question}"

Cards Spread:
{$cardDescription}

Instruction:
1. If the user's question is nonsensical, gibberish, or too unclear to interpret, do not analyze the cards. Instead, respond politely in {$responseLanguage} asking the user to rephrase the question more clearly.
2. Interpret the cards based on their positions relative to the question.
3. Do not explicitly mention labels like Position 1, Past, Present, Future, or Summary in the final response.
4. Weave the positional meanings into a natural narrative.
5. Response Language: {$responseLanguage}.
6. Tone: Polite, encouraging, and mysterious.

Structure:
- Start with a concise summary and advice.
- Then explain the narrative of the cards and how they connect to the user's situation.
PROMPT);
        }

        if (count($cards) === 4) {
            return trim(<<<PROMPT
You are an expert Tarot Reader.
Cards:
{$cardDescription}

Question: "How is my day today?"

Instruction:
1. Analyze the cards for a daily horoscope.
2. Do not explicitly mention labels like Position 1, Past, Present, Future, or Summary in the final response.
3. Response Language: {$responseLanguage}.
4. Start with a strong summary and advice for the day.
5. Then provide a natural, deeper narrative of what the cards indicate.
PROMPT);
        }

        return trim(<<<PROMPT
You are an expert Tarot Reader.
Cards:
{$cardDescription}

Question: "How is my day today?"

Instruction:
1. Analyze the cards for a daily horoscope.
2. Response Language: {$responseLanguage}.
3. Start with a concise summary and advice.
4. Then provide a deeper narrative without explicitly calling out position labels.
PROMPT);
    }

    /**
     * @param  array<int, array<string, mixed>>  $cards
     */
    private function buildCardDescription(array $cards, string $languageCode): string
    {
        $lines = [];

        foreach (array_values($cards) as $index => $card) {
            $nameTh = trim((string) ($card['nameTh'] ?? ''));
            $nameEn = trim((string) ($card['nameEn'] ?? ''));
            $keywords = $languageCode === 'en'
                ? $this->sanitizeStringList($card['keywordsEn'] ?? [])
                : $this->sanitizeStringList($card['keywordsTh'] ?? []);
            $name = $languageCode === 'en'
                ? ($nameEn !== '' ? $nameEn : $nameTh)
                : ($nameTh !== '' ? $nameTh : $nameEn);
            $keywordText = $keywords !== []
                ? ' (keywords: ' . implode(', ', $keywords) . ')'
                : '';

            if (count($cards) === 3) {
                $positions = [
                    'Position 1 (Past/Background)',
                    'Position 2 (Present/Situation)',
                    'Position 3 (Future/Outcome)',
                ];
                $lines[] = $positions[$index] . ': ' . $name . $keywordText;
                continue;
            }

            if (count($cards) === 4) {
                $positions = [
                    'Position 1 (Past/Background)',
                    'Position 2 (Present/Situation)',
                    'Position 3 (Future/Outcome)',
                    'Position 4 (Summary/Advice)',
                ];
                $lines[] = $positions[$index] . ': ' . $name . $keywordText;
                continue;
            }

            if (count($cards) === 10) {
                $positions = [
                    '1. Situation',
                    '2. Challenge',
                    '3. Focus',
                    '4. Recent Past',
                    '5. Possibilities',
                    '6. Near Future',
                    '7. Power (Self)',
                    '8. Environment',
                    '9. Hopes & Fears',
                    '10. Outcome',
                ];
                $lines[] = $positions[$index] . ': ' . $name . $keywordText;
                continue;
            }

            $lines[] = 'Card ' . ($index + 1) . ': ' . $name . $keywordText;
        }

        return implode("\n", $lines);
    }

    /**
     * @param  mixed  $value
     * @return array<int, string>
     */
    private function sanitizeStringList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_slice(array_filter(array_map(function ($item): string {
            return trim((string) $item);
        }, $value), fn (string $item): bool => $item !== ''), 0, 8));
    }

    private function languageMessage(string $languageCode, string $english, string $thai): string
    {
        return $languageCode === 'en' ? $english : $thai;
    }
}
