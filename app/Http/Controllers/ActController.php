<?php

namespace App\Http\Controllers;

use App\Enums\ActCategory;
use App\Enums\Role;
use App\Http\Requests\StoreActRequest;
use App\Http\Requests\UpdateActRequest;
use App\Models\Act;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ActController extends Controller
{
    public function index(): View
    {
        $acts = Act::orderBy('category')->orderBy('label')->get();
        $actsByCategory = $acts->groupBy('category');

        $editingAct = null;
        if (request()->filled('edit')) {
            $editingAct = $acts->firstWhere('id', request()->integer('edit'));
        }

        $canManage = auth()->user()?->isAdmin() ?? false;

        $user = Auth::user();
        $userName = $user?->name ?? 'Utilisateur';
        $userRole = $user?->role?->label() ?? 'Personnel clinique';
        $userInitials = $user?->initials ?? $this->initialsFor($userName);

        return view('acts.index', [
            'actsByCategory' => $actsByCategory,
            'categories' => ActCategory::cases(),
            'roles' => Role::cases(),
            'editingAct' => $editingAct,
            'canManage' => $canManage,
            'userName' => $userName,
            'userRole' => $userRole,
            'userInitials' => $userInitials,
        ]);
    }

    public function store(StoreActRequest $request)
    {
        Act::create($request->validated());

        return redirect()->route('acts.index')->with('success', 'Acte médical ajouté.');
    }

    public function update(UpdateActRequest $request, Act $act)
    {
        $act->update($request->validated());

        return redirect()->route('acts.index')->with('success', 'Acte médical mis à jour.');
    }

    public function destroy(Act $act)
    {
        $act->delete();

        return redirect()->route('acts.index')->with('success', 'Acte médical supprimé.');
    }
}
