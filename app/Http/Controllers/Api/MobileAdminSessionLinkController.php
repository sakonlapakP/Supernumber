<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MobileAdminSessionLinkController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role, [
            User::ROLE_MANAGER,
            User::ROLE_ADMIN,
            User::ROLE_DOCUMENT_OFFICER,
        ], true)) {
            return response()->json(['message' => 'Unauthorized. Insufficient permissions.'], 403);
        }

        $data = $request->validate([
            'target' => ['required', 'string', Rule::in([
                'sales-documents-quick',
                'sales-documents',
                'saved-sales-documents',
            ])],
            'document_type' => ['nullable', 'string', Rule::in(['quotation', 'invoice'])],
        ]);

        $token = Str::random(48);

        Cache::put('mobile_admin_handoff:' . $token, [
            'user_id' => $user->id,
            'target' => $data['target'],
            'document_type' => $data['document_type'] ?? null,
        ], now()->addMinutes(5));

        return response()->json([
            'url' => route('admin.mobile-handoff', ['token' => $token]),
            'expires_in' => 300,
        ]);
    }
}
