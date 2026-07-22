<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyKcaToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        $secret = env('KCA_BRIDGE_TOKEN');

        // Allow token in query parameter or request input as fallback for ease of development / testing
        if (!$token) {
            $token = $request->input('kca_token');
        }

        if (!$secret) {
            return response()->json([
                'error' => 'Integration misconfigured.',
                'message' => 'The system requires KCA_BRIDGE_TOKEN to be configured on the server environment.'
            ], 500);
        }

        if (!$token || $token !== $secret) {
            return response()->json([
                'error' => 'Unauthorized KCA connection.',
                'message' => 'Please provide a valid KCA_BRIDGE_TOKEN via Bearer Authentication.'
            ], 401);
        }

        // Check if writes are disabled (Read-Only Mode)
        if (!$request->isMethod('GET') && !filter_var(env('KCA_ALLOW_WRITES', false), FILTER_VALIDATE_BOOLEAN)) {
            return response()->json([
                'error' => 'Write operations disabled.',
                'message' => 'The KCA Integration is currently running in Read-Only mode. Write operations are disabled in server environment configurations.'
            ], 403);
        }

        return $next($request);
    }
}
