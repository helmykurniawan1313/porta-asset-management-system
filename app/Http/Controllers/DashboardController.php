<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Personal landing page: the logged-in user's own profile summary,
     * pending review tasks, and assigned assets/software — replaces the old
     * "Overview" link that pointed at the IT inventory asset-count page.
     */
    public function index(Request $request)
    {
        $user = $request->user()->load(['department', 'devices', 'softwareAllocations.subscription']);

        $pendingReviews = $user->reviewAssignmentsAsReviewer()
            ->where('status', '!=', 'submitted')
            ->with(['reviewee:id,name', 'reviewCycle:id,name'])
            ->orderBy('created_at')
            ->get();

        return view('dashboard.index', [
            'user' => $user,
            'pendingReviews' => $pendingReviews,
        ]);
    }
}
