<?php

namespace App\Http\Controllers;

use App\Models\Surgery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class BlocController extends Controller
{
    public function index(Request $request): View
    {
        $date = $request->input("date", today()->toDateString());
        $status = $request->input("status");

        $query = Surgery::with(["patient", "surgeon", "anesthetist", "visit", "checklists"])
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
}
