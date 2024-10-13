<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        if (!Auth::user()->is_admin) {
            abort(403, 'Unauthorized action.');
        }

        $users = User::all();
        return view('users.index', compact('users'));
    }


    /**
     * Show the user's profile form.
     *
     * @return View
     */
    public function showProfile(): View
    {
        return view('profile', ['user' => Auth::user()]);
    }

    /**
     * Update the user's profile information.
     *
     * @param Request $request
     * @param User $user
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function updateProfile(Request $request, User $user): RedirectResponse
    {
        // Check if the authenticated user can update the profile
        $this->authorize('updateProfile', $user);

        $request->validate([
            'phone_number' => 'nullable|string|max:15',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|min:8|confirmed',
        ]);

        // Update only the allowed fields
        $user->email = $request->input('email');
        $user->phone_number = $request->input('phone_number');

        // If a password is being updated, hash it
        if ($request->filled('password')) {
            $user->password = Hash::make($request->input('password'));
        }

        // Prevent the user from changing their 'is_admin' status
        if ($request->has('is_admin')) {
            abort(403, 'You cannot change your admin status.');
        }

        $user->save();

        return redirect()->route('profile.show', $user->id)->with('success', 'Profile updated successfully.');
    }


    /**
     * Admin can update user role.
     *
     * @param Request $request
     * @param User $user
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function updateRole(Request $request, User $user): RedirectResponse
    {
        // Only admins can update roles
        $this->authorize('updateRole', $user);

        $request->validate([
            'is_admin' => 'required|boolean',
        ]);

        // Prevent admin from revoking their own admin role
        if ($user->id === auth()->id() && !$request->input('is_admin')) {
            return redirect()->route('users.index')
                ->with('error', 'You cannot revoke your own admin privileges.');
        }

        $user->is_admin = $request->input('is_admin');
        $user->save();

        return redirect()->route('users.index')->with('success', 'User role updated successfully.');
    }


}
