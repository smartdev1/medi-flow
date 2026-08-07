<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChambreController extends Controller
{
    public function index(): View
    {
        $rooms = Room::with('activeAdmissions.patient')->orderBy('ward')->orderBy('number')->get();

        $wards = $rooms->groupBy('ward')->map(fn ($wardRooms, $ward) => [
            'label' => $ward,
            'rooms' => $wardRooms->map(fn (Room $room) => [
                'number' => $room->number,
                'capacity' => $room->capacity,
                'maintenance' => $room->maintenance,
                'beds' => collect(range(1, $room->capacity))->map(function (int $bedNumber) use ($room) {
                    $admission = $room->activeAdmissions->firstWhere('bed_number', $bedNumber);

                    return [
                        'label' => "Lit {$bedNumber}",
                        'patient' => $admission ? [
                            'name' => $admission->patient->full_name,
                            'id' => $admission->patient->code,
                            'since' => $admission->admitted_at->isToday()
                                ? "Depuis {$admission->admitted_at->format('H:i')}"
                                : 'Depuis '.$admission->admitted_at->diffForHumans(null, true),
                        ] : null,
                    ];
                })->all(),
            ])->values()->all(),
        ])->values();

        $totalBeds = $rooms->sum('capacity');
        $occupiedBeds = $rooms->sum('occupied_count');

        $user = auth()->user();
        $userName = $user?->name ?? 'Utilisateur';
        $userRole = $user?->role?->label() ?? 'Personnel clinique';
        $userInitials = $user?->initials ?? $this->initialsFor($userName);

        $wardsList = Room::select('ward')->distinct()->orderBy('ward')->pluck('ward', 'ward')->toArray();
        
        // Créer les éléments de filtre
        $filterItems = [['label' => 'Tous les services', 'active' => true]];
        foreach ($wards as $ward) {
            $filterItems[] = ['label' => $ward['label'], 'active' => false];
        }

        return view('chambres.board', [
            'wards' => $wards,
            'totalBeds' => $totalBeds,
            'occupiedBeds' => $occupiedBeds,
            'availableBeds' => $totalBeds - $occupiedBeds,
            'occupancyRate' => $totalBeds > 0 ? round(($occupiedBeds / $totalBeds) * 100) : 0,
            'wardsList' => $wardsList,
            'filterItems' => $filterItems,
            'userName' => $userName,
            'userRole' => $userRole,
            'userInitials' => $userInitials,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'number' => 'required|string|max:255|unique:rooms,number',
            'ward' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'maintenance' => 'boolean',
        ]);

        Room::create([
            'number' => $validated['number'],
            'ward' => $validated['ward'],
            'capacity' => $validated['capacity'],
            'maintenance' => $validated['maintenance'] ?? false,
        ]);

        return redirect()->route('chambres.index')->with('success', 'Chambre ajoutée avec succès !');
    }

    public function toggleMaintenance(string $roomNumber)
    {
        $room = Room::where('number', $roomNumber)->firstOrFail();
        $room->update(['maintenance' => ! $room->maintenance]);

        $status = $room->maintenance ? 'en maintenance' : 'remise en service';
        return redirect()->route('chambres.index')->with('success', "Chambre {$room->number} {$status} avec succès !");
    }

    public function destroy(string $roomNumber)
    {
        $room = Room::where('number', $roomNumber)->firstOrFail();
        
        // Vérifier si la chambre a des admissions actives
        if ($room->activeAdmissions()->exists()) {
            return response()->json([
                'error' => 'Impossible de supprimer une chambre avec des patients hospitalisés.'
            ], 422);
        }
        
        $room->delete();
        return response()->json(['success' => true]);
    }
}
