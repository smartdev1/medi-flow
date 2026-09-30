<?php

namespace App\Http\Controllers;

use App\Models\Admission;
use App\Models\AdmissionRequest;
use App\Models\Exam;
use App\Models\Invoice;
use App\Models\Visit;
use Illuminate\Http\JsonResponse;

class BadgeController extends Controller
{
    public function counts(): JsonResponse
    {
        $counts = [
            'caisse' => Invoice::whereIn('status', [Invoice::STATUS_PENDING, Invoice::STATUS_AWAITING_PAYMENT])->whereDate('created_at', today())->count(),
            'attente' => Visit::whereNull('discharged_at')->whereDate('arrived_at', today())->where('status', Visit::STATUS_WAITING)->whereNull('vitals_taken_at')->count(),
            'medecin' => Visit::whereNull('discharged_at')->whereDate('arrived_at', today())->whereIn('status', [Visit::STATUS_WAITING, Visit::STATUS_RETURNED_FROM_OBSERVATION])->whereNotNull('vitals_taken_at')->count(),
            'laboratoire' => Exam::whereIn('status', [Exam::STATUS_AWAITING_SAMPLE, Exam::STATUS_IN_PROGRESS])->count(),
            'admission-requests' => AdmissionRequest::pending()->count(),
            'hospitalisation' => Admission::active()->where('status', Admission::STATUS_STABLE)->count(),
            'observation' => Admission::where('status', Admission::STATUS_OBSERVATION)->active()->count(),
            'sortie' => Visit::where('status', Visit::STATUS_CONSULTATION_CLOSED)->whereNull('discharged_at')->count(),
        ];

        return response()->json($counts);
    }
}
