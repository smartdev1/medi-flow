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

        return view('insurance.index', [
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['name' => ['required', 'string', 'max:255', 'unique:insurance_companies,name']]);

        InsuranceCompany::create(['name' => $request->input('name')]);

        return redirect()->route('insurance.index')->with('success', 'Compagnie d’assurance enregistrée.');
    }

    public function update(Request $request, InsuranceCompany $insuranceCompany): RedirectResponse
    {
        $request->validate(['name' => ['required', 'string', 'max:255', 'unique:insurance_companies,name,'.$insuranceCompany->id]]);

        $insuranceCompany->update(['name' => $request->input('name')]);

        return redirect()->route('insurance.index')->with('success', 'Compagnie d’assurance mise à jour.');
    }

    public function destroy(InsuranceCompany $insuranceCompany): RedirectResponse
    {
        $insuranceCompany->delete();

        return redirect()->route('insurance.index')->with('success', 'Compagnie d’assurance supprimée.');
    }
}
