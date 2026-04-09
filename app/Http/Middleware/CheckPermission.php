<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $permissionId): Response
    {
        $account = auth('api_account')->user();

        if (!$account) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if (!$account->role) {
            return response()->json(['message' => 'No role assigned'], 403);
        }

        $permissionIds = $account->role->permissions->pluck('id')->map(fn($id) => (int)$id)->toArray();
        $requiredIds = array_map('intval', explode('|', $permissionId));

        $allowed = array_intersect($requiredIds, $permissionIds);

        if (empty($allowed)) {
            return response()->json(['message' => 'Forbidden - No permission'], 403);
        }

        return $next($request);
    }
}
