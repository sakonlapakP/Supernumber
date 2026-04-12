<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TarotAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class TarotReadingController extends Controller
{
    public function __invoke(Request $request, TarotAiService $tarotAiService): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'cards' => ['required', 'array', 'min:1', 'max:10'],
            'cards.*' => ['required', 'array'],
            'cards.*.nameTh' => ['nullable', 'string', 'max:120'],
            'cards.*.nameEn' => ['nullable', 'string', 'max:120'],
            'cards.*.arcana' => ['nullable', 'string', 'max:40'],
            'cards.*.keywordsTh' => ['nullable', 'array', 'max:8'],
            'cards.*.keywordsTh.*' => ['string', 'max:60'],
            'cards.*.keywordsEn' => ['nullable', 'array', 'max:8'],
            'cards.*.keywordsEn.*' => ['string', 'max:60'],
            'question' => ['nullable', 'string', 'max:500'],
            'languageCode' => ['nullable', 'in:th,en'],
            'type' => ['nullable', 'string', 'max:50'],
        ]);

        $validator->after(function ($validator) use ($request): void {
            $cards = $request->input('cards', []);

            foreach ($cards as $index => $card) {
                $nameTh = trim((string) ($card['nameTh'] ?? ''));
                $nameEn = trim((string) ($card['nameEn'] ?? ''));

                if ($nameTh === '' && $nameEn === '') {
                    $validator->errors()->add(
                        "cards.$index.nameTh",
                        'Each card must include at least one name.'
                    );
                }
            }
        });

        $data = $validator->validate();

        try {
            $result = $tarotAiService->generateReading(
                cards: $data['cards'],
                question: $data['question'] ?? null,
                languageCode: $data['languageCode'] ?? 'th',
                type: $data['type'] ?? null,
            );

            return response()->json($result);
        } catch (Throwable $e) {
            report($e);

            $status = (int) $e->getCode();
            if ($status < 400 || $status > 599) {
                $status = Response::HTTP_INTERNAL_SERVER_ERROR;
            }

            return response()->json([
                'message' => $e->getMessage(),
            ], $status);
        }
    }
}
