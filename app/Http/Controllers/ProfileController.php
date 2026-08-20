<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        if ($reason = $this->accountDeletionBlockReason($user)) {
            return back()->withErrors([
                'password' => $reason,
            ], 'userDeletion');
        }

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    private function accountDeletionBlockReason(User $user): ?string
    {
        $responsibilities = [
            'saas_admin' => User::query()->where('status', true)->whereKeyNot($user->getKey()),
            'verification_admin' => User::query()->where('status', true)->whereKeyNot($user->getKey()),
            'dso_admin' => User::query()->where('status', true)->whereKeyNot($user->getKey())->where('dso_id', $user->dso_id),
            'clinic_admin' => User::query()->where('status', true)->whereKeyNot($user->getKey())
                ->when($user->organization_id, fn ($query) => $query->where('organization_id', $user->organization_id))
                ->when(! $user->organization_id, fn ($query) => $query->where('clinic_id', $user->clinic_id)),
        ];

        foreach ($responsibilities as $role => $possibleSuccessors) {
            if (! $user->hasRole($role)) {
                continue;
            }

            if (! $possibleSuccessors->role($role)->exists()) {
                return 'Transfer your administrator responsibility to another active user before deleting this account.';
            }
        }

        return null;
    }
}
