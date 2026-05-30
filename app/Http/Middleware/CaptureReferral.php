<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class CaptureReferral
{
    public function handle(Request $request, Closure $next): Response
    {
        $ref = $request->query('ref') ?? $request->query('referral');

        if ($ref) {
            $ref = strtoupper(trim((string) $ref));
            
            // Check if there is an approved sales rep with this referral code
            $sellerExists = User::query()
                ->where('role', User::ROLE_SALE)
                ->where('sale_status', User::SALE_STATUS_APPROVED)
                ->where('referral_code', $ref)
                ->exists();

            if ($sellerExists) {
                // Save in Session for active checkout process
                session(['captured_referral_code' => $ref]);

                // Queue Cookie for 30 days (43200 minutes) as fallback
                Cookie::queue('captured_referral_code', $ref, 43200);
            }
        }

        return $next($request);
    }
}
