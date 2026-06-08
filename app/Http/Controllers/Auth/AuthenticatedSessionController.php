<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $user = auth()->user();

        if ($user?->shouldLandInVerificationWorkspace() && ! $user->isSaasAdmin()) {
            return redirect()->intended('/verification');
        }

        if ($user?->canAccessPanel(app(\Filament\PanelRegistry::class)->get('saas'))) {
            return redirect()->intended('/saas');
        }

        if ($user?->canAccessPanel(app(\Filament\PanelRegistry::class)->get('admin'))) {
            return redirect()->intended('/verification');
        }

        if ($user?->canAccessPanel(app(\Filament\PanelRegistry::class)->get('clinic'))) {
            return redirect()->intended('/clinic');
        }

        return redirect('/login');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
