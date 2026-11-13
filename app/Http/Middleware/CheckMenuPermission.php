<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMenuPermission
{
    public function handle(Request $request, Closure $next, string $menuCode, string $permission = 'can_view'): Response
    {
        if (!$request->user()) {
            return redirect()->route('login');
        }

        if (!$request->user()->hasPermission($menuCode, $permission)) {
            abort(403, 'You do not have permission to access this page.');
        }

        return $next($request);
    }
}

