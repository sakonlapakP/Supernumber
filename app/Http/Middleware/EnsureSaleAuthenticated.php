<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSaleAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! session('sale_authenticated')) {
            return redirect()->route('sale.login')->with('error', 'กรุณาเข้าสู่ระบบ');
        }

        $userId = session('sale_user_id');
        $user   = is_numeric($userId) ? User::find((int) $userId) : null;

        if (! $user || ! $user->isSale()) {
            session()->forget(['sale_authenticated', 'sale_user_id']);

            return redirect()->route('sale.login')->with('error', 'กรุณาเข้าสู่ระบบ');
        }

        $request->attributes->set('sale_user', $user);

        return $next($request);
    }
}
