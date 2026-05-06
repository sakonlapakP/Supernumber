<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class ApiTokenAuth
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $this->resolveUserFromBearerToken($request->bearerToken());

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        Auth::setUser($user);
        $request->setUserResolver(fn (): User => $user);

        return $next($request);
    }

    private function resolveUserFromBearerToken(?string $bearerToken): ?User
    {
        if (! $bearerToken || ! str_contains($bearerToken, '|')) {
            return null;
        }

        [$tokenId, $plainTextToken] = explode('|', $bearerToken, 2);

        if (! ctype_digit($tokenId) || $plainTextToken === '') {
            return null;
        }

        $token = DB::table('personal_access_tokens')
            ->where('id', (int) $tokenId)
            ->first();

        if (! $token || ! hash_equals((string) $token->token, hash('sha256', $plainTextToken))) {
            return null;
        }

        if ($token->expires_at && Carbon::parse($token->expires_at)->isPast()) {
            return null;
        }

        if ($token->tokenable_type !== User::class) {
            return null;
        }

        $user = User::find($token->tokenable_id);

        if (! $user || ! $user->canAccessAdminPanel()) {
            return null;
        }

        DB::table('personal_access_tokens')
            ->where('id', $token->id)
            ->update([
                'last_used_at' => now(),
                'updated_at' => now(),
            ]);

        return $user;
    }
}
