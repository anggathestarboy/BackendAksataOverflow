<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class IsModeratorMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user || (!$user->roles()->where('name', 'moderator')->first() && !$user->roles()->where('name', 'admin')->first())) {
            return response()->json(['message' => 'only moderator and admin can access this route'], 403);
        }
        return $next($request);
    }
}
