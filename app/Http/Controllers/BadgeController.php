<?php

namespace App\Http\Controllers;

use App\Models\Admission;
use App\Models\AdmissionRequest;
use App\Models\Exam;
use App\Models\Invoice;
use App\Models\Surgery;
use App\Models\Visit;
use App\Support\DoctorQueue;
use Illuminate\Http\JsonResponse;

class BadgeController extends Controller
{
    public function counts(): JsonResponse
    {
        $counts = [
            'caisse' => Invoice::whereIn('status', [Invoice::STATUS_PENDING, Invoice::STATUS_AWAITING_PAYMENT])->whereDate('created_at', today())->count(),
            'attente' => Visit::whereNull('discharged_at')->whereDate('arrived_at', today())->where('status', Visit::STATUS_WAITING)->whereNull('vitals_taken_at')->count(),
            'medecin' => DoctorQueue::count(),
            'bloc' => Surgery::where('status', Surgery::STATUS_IN_PROGRESS)->count(),
            'laboratoire' => Exam::pendingInLaboratory()->count(),
            'admission-requests' => AdmissionRequest::pending()->count(),
            'hospitalisation' => Admission::active()->where('status', Admission::STATUS_STABLE)->count(),
            'observation' => Admission::where('status', Admission::STATUS_OBSERVATION)->active()->count(),
            'sortie' => Visit::where('status', Visit::STATUS_CONSULTATION_CLOSED)->whereNull('discharged_at')->count(),
        ];

        return response()->json($counts);
    }
}
