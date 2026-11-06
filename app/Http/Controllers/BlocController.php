<?php

namespace App\Http\Controllers;

use App\Models\Surgery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class BlocController extends Controller
{
    public function index(Request $request): View
    {
        $date = $request->input("date", today()->toDateString());
        $status = $request->input("status");

        $query = Surgery::with(["patient", "surgeon", "anesthetist", "visit"])
            ->where(function ($q) use ($date) {
                $q->whereDate("scheduled_at", $date)
                  ->orWhere(function ($q2) use ($date) {
                      $q2->whereNull("scheduled_at")
                         ->whereDate("created_at", $date);
                  });
            });

        if ($status) {
            $query->where("status", $status);
        }

        $surgeries = $query->orderBy("scheduled_at", "asc")->get();

        $user = Auth::user();
        $userName = $user?->name ?? "Utilisateur";
        $userRole = $user?->role?->label() ?? "Personnel clinique";
        $userInitials = $user?->initials ?? strtoupper(substr($userName, 0, 2));

        return view("bloc.dashboard", [
            "surgeries" => $surgeries,
            "date" => $date,
            "status" => $status,
            "userName" => $userName,
            "userRole" => $userRole,
            "userInitials" => $userInitials,
        ]);
    }

    public function refresh(Request $request): JsonResponse
    {
        $date = $request->input("date", today()->toDateString());
        $status = $request->input("status");

        $query = Surgery::with(["patient", "surgeon", "anesthetist", "visit"])
            ->where(function ($q) use ($date) {
                $q->whereDate("scheduled_at", $date)
                  ->orWhere(function ($q2) use ($date) {
                      $q2->whereNull("scheduled_at")
                         ->whereDate("created_at", $date);
                  });
            });

        if ($status) {
            $query->where("status", $status);
        }

        $surgeries = $query->orderBy("scheduled_at", "asc")->get();

        return response()->json([
            "surgeries" => $surgeries->map(fn (Surgery $s) => [
                "id" => $s->id,
                "scheduled_at" => $s->scheduled_at?->format("H:i"),
                "patient_name" => $s->patient?->full_name ?? "—",
                "patient_code" => $s->patient?->code ?? "",
                "referring_doctor_name" => $s->referring_doctor_name,
                "procedure_name" => $s->procedure_name,
                "cost" => $s->cost,
                "surgeon" => $s->surgeon?->name ?? "—",
                "anesthetist" => $s->anesthetist?->name ?? "—",
                "surgery_type" => $s->surgery_type,
                "status" => $s->status,
                "consent_signed_at" => $s->consent_signed_at?->format("d/m/Y H:i"),
                "consent_signed_by" => $s->consent_signed_by,
            ]),
            "in_progress_count" => $surgeries->where("status", Surgery::STATUS_IN_PROGRESS)->count(),
        ]);
    }
}
