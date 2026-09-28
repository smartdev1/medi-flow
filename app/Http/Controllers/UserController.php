<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::query()
            ->orderBy('name')
            ->get();

        $editingUser = $users->firstWhere('id', request('edit_user_id'));

        return view('users.index', [
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $temporaryPassword = Str::password(12);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'password' => Hash::make($temporaryPassword),
            'email_verified_at' => now(),
        ]);

        return redirect()->route('users.index')->with('success', "Compte créé pour {$data['name']} — mot de passe temporaire : {$temporaryPassword}");
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();
        $user->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
        ]);

        return redirect()->route('users.index')->with('success', "Compte mis à jour pour {$user->name}.");
    }

    public function toggle(User $user): RedirectResponse
    {
        $user->update(['is_active' => ! $user->is_active]);

        $message = $user->is_active
            ? "{$user->name} peut de nouveau se connecter."
            : "{$user->name} a été désactivé(e) et ne peut plus se connecter.";

        return redirect()->route('users.index')->with('success', $message);
    }
}
