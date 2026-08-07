<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOnCallShiftRequest;
use App\Models\OnCallShift;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;

class OnCallShiftController extends Controller
{
    public function store(StoreOnCallShiftRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $doctor = User::findOrFail($data['user_id']);

        $period = Carbon::parse($data['start_date'])->toPeriod(Carbon::parse($data['end_date']));

        foreach ($period as $date) {
            OnCallShift::firstOrCreate(['user_id' => $doctor->id, 'date' => $date->format('Y-m-d')]);
        }

        return redirect()
            ->route('personnel.index')
            ->with('success', "Garde programmée pour {$doctor->name}.");
    }

    public function destroy(OnCallShift $onCallShift): RedirectResponse
    {
        $onCallShift->delete();

        return redirect()->route('personnel.index')->with('success', 'Garde retirée du planning.');
    }
}
