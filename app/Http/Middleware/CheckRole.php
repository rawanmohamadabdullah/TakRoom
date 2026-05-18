<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if ($user && $user->is_banned) {
            $user->tokens()->delete();
            return response()->json([
                'status' => 'error',
                'message' => 'You have been banned by the administration, you cannot browse the application.',
            ], 403);
        }

        if (!$user || !in_array($user->role, $roles)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized access. Required role: ' . implode(', ', $roles),
            ], 403);
        }

        return $next($request);
    }
}
