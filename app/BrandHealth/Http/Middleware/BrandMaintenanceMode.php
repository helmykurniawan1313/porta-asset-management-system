<?php

namespace App\BrandHealth\Http\Middleware;

use App\BrandHealth\Models\BrandSetting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Brand Matrix's "Under Construction Mode", scoped to the Brand Health section
 * only — the rest of it-inventory keeps working. Super Admins pass through so
 * they can switch it back off; the public legal pages and Instagram OAuth
 * callback stay reachable (Meta requires them).
 */
class BrandMaintenanceMode
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! BrandSetting::isMaintenanceMode() || $request->user()?->hasRole('Super Admin')) {
            return $next($request);
        }

        $path = $request->path();
        if (in_array($path, ['brand-health/privacy-policy', 'brand-health/data-deletion'], true)
            || str_starts_with($path, 'brand-health/instagram/oauth')) {
            return $next($request);
        }

        return response()->view('brand-health.maintenance', [], 503);
    }
}
