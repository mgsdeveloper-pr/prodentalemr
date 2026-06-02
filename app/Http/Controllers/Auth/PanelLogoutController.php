<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PanelLogoutController
{
    public function __invoke(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->routeIs('admin.signout')) {
            return redirect('/verification/login');
        }

        return redirect('/login');
    }
}
