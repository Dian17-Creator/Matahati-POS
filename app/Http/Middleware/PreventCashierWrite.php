<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PreventCashierWrite
{
    /**
     * Route names that a Cashier user is still allowed to submit write requests to.
     *
     * @var array<int, string>
     */
    private const ALLOWED_WRITE_ROUTES = [
        'logout',
        'switch-outlet',
        'profile',
        'profile.update',
        'profile.password',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (! $user || ! method_exists($user, 'isCashierLevel') || ! $user->isCashierLevel()) {
            return $next($request);
        }

        $routeName = (string) ($request->route()?->getName() ?? '');

        if (in_array($routeName, self::ALLOWED_WRITE_ROUTES, true)) {
            return $next($request);
        }

        $isReadMethod = in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true);
        $isFormPage = str_ends_with($routeName, '.create') || str_ends_with($routeName, '.edit');

        if (! $isReadMethod || $isFormPage) {
            $message = 'Akses ditolak. Level Kasir hanya memiliki akses lihat data (Read-Only).';

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 403);
            }

            abort(403, $message);
        }

        return $next($request);
    }
}
