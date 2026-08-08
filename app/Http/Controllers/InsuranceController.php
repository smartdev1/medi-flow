<?php

namespace App\Http\Controllers;

use App\Models\InsuranceCompany;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class InsuranceController extends Controller
{
    public function index(): View
    {
        $insuranceCompanies = InsuranceCompany::orderBy('name')->get();

        $user = auth()->user();
        $userName = $user?->name ?? 'Utilisateur';
        $userRole = $user?->role?->label() ?? 'Administrateur';
        $userInitials = $user?->initials ?? $this->initialsFor($userName);

        return view('insurance.index', [
            'insuranceCompanies' => $insuranceCompanies,
            'userName' => $userName,
            'userRole' => $userRole,
            'userInitials' => $userInitials,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:insurance_companies,name'],
        ]);

        InsuranceCompany::create($validated);

        return redirect()->route('insurance.index')
            ->with('success', "Assurance '{$validated['name']}' enregistrée avec succès.");
    }

    public function update(Request $request, InsuranceCompany $insuranceCompany): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:insurance_companies,name,' . $insuranceCompany->id],
        ]);

        $insuranceCompany->update($validated);

        return redirect()->route('insurance.index')
            ->with('success', "Assurance '{$validated['name']}' mise à jour avec succès.");
    }

    public function destroy(InsuranceCompany $insuranceCompany): RedirectResponse
    {
        $name = $insuranceCompany->name;
        $insuranceCompany->delete();

        return redirect()->route('insurance.index')
            ->with('success', "Assurance '{$name}' supprimée avec succès.");
    }
}
