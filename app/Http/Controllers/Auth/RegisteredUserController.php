<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Organization;
use App\Models\Clinic;
use App\Models\Location;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create()
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            /*
            |--------------------------------------------------------------------------
            | Owner Information
            |--------------------------------------------------------------------------
            */
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:20'],

            /*
            |--------------------------------------------------------------------------
            | Clinic Registration Information
            |--------------------------------------------------------------------------
            */
            'organization_name' => ['required', 'string', 'max:255'],
            'clinic_name' => ['required', 'string', 'max:255'],
            'location_name' => ['required', 'string', 'max:255'],

            /*
            |--------------------------------------------------------------------------
            | Password
            |--------------------------------------------------------------------------
            */
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        DB::beginTransaction();

        try {
            /*
            |--------------------------------------------------------------------------
            | Step 1 — Create Organization
            |--------------------------------------------------------------------------
            */

            $organization = Organization::create([
                'name' => $request->organization_name,
                'owner_name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'status' => true,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Step 2 — Create Clinic
            |--------------------------------------------------------------------------
            */

            $clinic = Clinic::create([
                'organization_id' => $organization->id,
                'clinic_name' => $request->clinic_name,
                'clinic_code' => 'CLN-' . strtoupper(substr(uniqid(), -6)),
                'timezone' => 'America/New_York',
                'status' => true,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Step 3 — Create First Location
            |--------------------------------------------------------------------------
            */

            $location = Location::create([
                'clinic_id' => $clinic->id,
                'location_name' => $request->location_name,
                'country' => 'USA',
                'status' => true,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Step 4 — Create Clinic Owner User
            |--------------------------------------------------------------------------
            */

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,

                'organization_id' => $organization->id,
                'clinic_id' => $clinic->id,
                'location_id' => $location->id,

                'created_by' => null,
                'status' => true,

                'password' => Hash::make($request->password),
            ]);

            /*
            |--------------------------------------------------------------------------
            | Step 5 — Assign Role
            |--------------------------------------------------------------------------
            */

            $user->assignRole('clinic_admin');

            /*
            |--------------------------------------------------------------------------
            | Step 6 — Commit Transaction
            |--------------------------------------------------------------------------
            */

            DB::commit();

            event(new Registered($user));

            Auth::login($user);

            /*
            |--------------------------------------------------------------------------
            | Step 7 — Redirect to Clinic Panel
            |--------------------------------------------------------------------------
            */

            return redirect('/clinic');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->withErrors([
                    'error' => 'Registration failed: ' . $e->getMessage(),
                ]);
        }
    }
}

// namespace App\Http\Controllers\Auth;

// use App\Http\Controllers\Controller;
// use App\Models\User;
// use Illuminate\Auth\Events\Registered;
// use Illuminate\Http\RedirectResponse;
// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Auth;
// use Illuminate\Support\Facades\Hash;
// use Illuminate\Validation\Rules;
// use Illuminate\Validation\ValidationException;
// use Illuminate\View\View;

// class RegisteredUserController extends Controller
// {
//     /**
//      * Display the registration view.
//      */
//     public function create(): View
//     {
//         return view('auth.register');
//     }

//     /**
//      * Handle an incoming registration request.
//      *
//      * @throws ValidationException
//      */
//     public function store(Request $request): RedirectResponse
//     {
//         $request->validate([
//             'name' => ['required', 'string', 'max:255'],
//             'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
//             'password' => ['required', 'confirmed', Rules\Password::defaults()],
//         ]);

//         $user = User::create([
//             'name' => $request->name,
//             'email' => $request->email,
//             'password' => Hash::make($request->password),
//         ]);

//         event(new Registered($user));

//         Auth::login($user);

//         return redirect(route('dashboard', absolute: false));
//     }
// }
