<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureSubscribed
{
    /**
     * Require a valid account subscription for students. Redirect to account subscription page if not subscribed.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();
        if ($user->role_id != 3) {
            return $next($request);
        }

        if (isAccountSubscribed()) {
            return $next($request);
        }

        if ($request->routeIs('accountSubscription') || $request->routeIs('accountSubscriptionPay')) {
            return $next($request);
        }

        return redirect()->route('accountSubscription');
    }
}
