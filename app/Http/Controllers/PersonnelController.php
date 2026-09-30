<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDoctorRequest;
use App\Http\Requests\UpdateDoctorRequest;
use App\Models\OnCallShift;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PersonnelController extends Controller
{
    public function index(): View
    {
        $doctors = User::doctors()
            ->withCount(['onCallShifts as on_call_today' => fn ($query) => $query->whereDate('date', today())])
            ->orderBy('name')
            ->get();

        $onCallToday = $doctors->filter(fn (User $doctor) => $doctor->on_call_today > 0);

        $upcomingShifts = OnCallShift::with('user')
            ->where('date', '>=', today())
            ->orderBy('date')
            ->get()
            ->groupBy(fn (OnCallShift $shift) => $shift->date->format('Y-m-d'));

        $user = auth()->user();
        $userName = $user?->name ?? 'Utilisateur';
        $userRole = $user?->role?->label() ?? 'Personnel clinique';
        $userInitials = $user?->initials ?? $this->initialsFor($userName);

        return view('personnel.index', [
            'doctors' => $doctors,
            'onCallToday' => $onCallToday,
            'upcomingShifts' => $upcomingShifts,
            'userName' => $userName,
            'userRole' => $userRole,
            'userInitials' => $userInitials,
        ]);
    }

    public function store(StoreDoctorRequest $request): RedirectResponse
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

        return redirect()
            ->route('personnel.index')
            ->with('success', "Compte créé pour {$data['name']} — mot de passe temporaire : {$temporaryPassword}");
    }

    public function update(UpdateDoctorRequest $request, User $doctor): RedirectResponse
    {
        $doctor->update($request->validated());

        return redirect()
            ->route('personnel.index')
            ->with('success', "Informations de {$doctor->name} mises à jour.");
    }

    public function toggleActive(User $doctor): RedirectResponse
    {
        $doctor->update(['is_active' => ! $doctor->is_active]);

        $message = $doctor->is_active
            ? "{$doctor->name} peut de nouveau se connecter."
            : "{$doctor->name} a été désactivé(e) et ne peut plus se connecter.";

        return redirect()->route('personnel.index')->with('success', $message);
    }

    public function storeInsuranceCompany(StoreInsuranceCompanyRequest $request): RedirectResponse
    {
        $company = \App\Models\InsuranceCompany::create($request->validated());

        return redirect()->route('personnel.index')->with('success', "Compagnie d'assurance « {$company->name} » enregistrée.");
    }
}
