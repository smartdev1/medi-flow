<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInsuranceCompanyRequest;
use App\Models\InsuranceCompany;
use Illuminate\Http\RedirectResponse;

class InsuranceCompanyController extends Controller
{
    public function store(StoreInsuranceCompanyRequest $request): RedirectResponse
    {
        InsuranceCompany::create($request->validated());

        return redirect()->route('personnel.index')->with('success', 'Compagnie d’assurance enregistrée.');
    }
}
