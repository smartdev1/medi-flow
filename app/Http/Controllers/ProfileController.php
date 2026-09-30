<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();

        return view('profile.edit', [
            'user' => $user,
            'userName' => $user->name,
            // Un compte sans rôle n'a pas d'étiquette à afficher, mais il doit
            // pouvoir consulter son profil : l'absence de rôle n'est pas un
            // motif pour renvoyer une erreur 500.
            'userRole' => $user->role?->label() ?? 'Personnel',
            'userInitials' => $this->initialsFor($user->name),
        ]);
    }

    /**
     * Update the user's profile information.
     *
     * Changer d'adresse e-mail invalide la vérification : la nouvelle adresse
     * n'a pas été prouvée, la conserver comme « vérifiée » laisserait un
     * compte contrôler une adresse qui n'est pas la sienne.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->fill($request->validated());
        $user->save();

        if ($user->wasChanged('email')) {
            $user->email_verified_at = null;
            $user->save();
        }

        return Redirect::route('profile.edit')->with('success', 'Profil mis à jour avec succès.');
    }
}
