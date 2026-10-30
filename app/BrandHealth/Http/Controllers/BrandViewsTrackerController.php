<?php

namespace App\BrandHealth\Http\Controllers;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Contracts\View\View as Response;

class BrandViewsTrackerController extends Controller
{
    public function index(Request $request): Response
    {
        return view('brand-health.views-tracker.index');
    }
}
