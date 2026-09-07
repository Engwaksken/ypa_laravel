<?php

namespace App\Http\Middleware;

use App\Services\PermissionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Abort with 403 unless the authenticated user holds the permission.
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (!$user || !app(PermissionService::class)->can($permission, $user)) {
            abort(403, 'Access denied.');
        }

        return $next($request);
    }
}