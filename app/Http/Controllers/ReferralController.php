<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReferralRequest;
use App\Models\Patient;
use App\Models\Referral;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class ReferralController extends Controller
{
    public function store(StoreReferralRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $patient = Patient::findOrFail($data['patient_id']);

        $patient->referrals()->create([
            'user_id' => Auth::id(),
            'service' => $data['service'],
            'reason' => $data['reason'],
            'status' => Referral::STATUS_PENDING,
            'requested_at' => now(),
        ]);

        return redirect()
            ->route('medecin.index')
            ->with('success', "{$patient->full_name} orienté(e) vers {$data['service']}.");
    }
}
