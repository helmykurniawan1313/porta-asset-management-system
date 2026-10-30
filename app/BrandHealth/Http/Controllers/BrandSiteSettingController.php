<?php

namespace App\BrandHealth\Http\Controllers;

use App\Http\Controllers\Controller;

use App\BrandHealth\Models\BrandSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View as Response;

class BrandSiteSettingController extends Controller
{
    public function edit(): Response
    {
        return view('brand-health.settings.site', [
            'maintenanceMode' => BrandSetting::isMaintenanceMode(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'maintenance_mode' => ['required', 'boolean'],
        ]);

        BrandSetting::set(BrandSetting::MAINTENANCE_MODE, $data['maintenance_mode'] ? '1' : '0');

        return back();
    }
}
