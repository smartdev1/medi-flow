<?php

namespace App\Http\Controllers;

use App\Models\InsuranceCompany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InsuranceController extends Controller
{
    public function index(): View
    {
        $insuranceCompanies = InsuranceCompany::query()
            ->orderBy('name')
            ->get();

        $user = auth()->user();
        $userName = $user?->name ?? 'Utilisateur';
        $userRole = $user?->role?->label() ?? 'Personnel clinique';
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
        $data = $this->validated($request);

        InsuranceCompany::create($data);

        return redirect()->route('insurance.index')->with('success', 'Compagnie d’assurance enregistrée.');
    }

    public function update(Request $request, InsuranceCompany $insuranceCompany): RedirectResponse
    {
        $data = $this->validated($request, $insuranceCompany->id);

        $insuranceCompany->update($data);

        return redirect()->route('insurance.index')->with('success', 'Compagnie d’assurance mise à jour.');
    }

    /**
     * Le taux est aussi important que le nom : c'est lui qui détermine le
     * ticket modérateur des factures de tous les patients de cette assurance.
     * Une compagnie enregistrée à 0 % ferait payer l'intégralité des actes à
     * des patients qui ne doivent rien, sans aucun signe visible.
     */
    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:insurance_companies,name'.($ignoreId ? ','.$ignoreId : '')],
            'rate' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);
    }

    public function destroy(InsuranceCompany $insuranceCompany): RedirectResponse
    {
        $insuranceCompany->delete();

        return redirect()->route('insurance.index')->with('success', 'Compagnie d’assurance supprimée.');
    }
}
