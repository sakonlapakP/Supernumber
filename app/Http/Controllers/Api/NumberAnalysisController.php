<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PhoneNumberAnalyzer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public numerology endpoint — analyzes any phone number.
 * Powers the "เบอร์มงคล (Powered by Supernumber)" feature on partner sites (Phayakorn).
 */
class NumberAnalysisController extends Controller
{
    public function __invoke(Request $request, PhoneNumberAnalyzer $analyzer): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'regex:/^[0-9\-\s]{7,15}$/'],
        ]);

        $digits = preg_replace('/\D/', '', $data['phone']) ?? '';

        if (strlen($digits) < 7) {
            return response()->json([
                'message' => 'กรุณากรอกเบอร์โทรอย่างน้อย 7 หลัก',
            ], 422);
        }

        return response()->json($analyzer->analyze($digits));
    }
}
