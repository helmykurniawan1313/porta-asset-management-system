<?php

namespace App\PerformanceReview\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\PerformanceReview\Http\Requests\SetPasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class PasswordSetupController extends Controller
{
    /**
     * Route is protected by the 'signed' middleware, so reaching this
     * method already guarantees a valid, unexpired signature.
     */
    public function show(Request $request, User $user): View
    {
        return view('performance-review.auth.set-password', [
            'user' => $user->only('id', 'name', 'email'),
            // The store route is also signed; re-sign a URL for POST (the
            // GET signature above isn't valid for the POST route) so the
            // form can submit without failing the signature check.
            'signedFormUrl' => \Illuminate\Support\Facades\URL::temporarySignedRoute(
                'password.setup.store',
                now()->addDays(7),
                ['user' => $user->getKey()]
            ),
        ]);
    }

    public function store(SetPasswordRequest $request, User $user): RedirectResponse
    {
        $user->forceFill([
            'password' => Hash::make($request->validated()['password']),
        ])->save();

        return Redirect::route('login')->with('status', 'Your password has been set. You may now log in.');
    }
}
