<x-layout.app-shell title="Compagnies d’assurance" active="gestion" :user-name="$userName" :user-role="$userRole" :user-initials="$userInitials">

    <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-ink">Compagnies d’assurance</h1>
            <p class="mt-1 text-sm text-ink-muted">Gérez les assurances disponibles pour les patients.</p>
        </div>

    @if (auth()->user()?->isAdmin())
    <x-ui.slide-over title="Nouvelle assurance">
        <x-slot:trigger>
            <x-ui.button>
                <x-ui.icon name="plus" class="h-4 w-4" />
                Nouveau
            </x-ui.button>
        </x-slot:trigger>

        <form method="POST" action="{{ route('insurance.store') }}" class="flex flex-col gap-4">
            @csrf
            <div>
                <x-ui.field label="Nom de l’assurance" name="name" :value="old('name')" placeholder="Ex : SICAM" required autofocus />
                <x-input-error :messages="$errors->get('name')" class="mt-1" />
            </div>
            <div>
                <x-ui.field label="Taux de couverture (%)" name="rate" type="number" min="0" max="100" step="0.01" :value="old('rate', '100')" required />
                <x-input-error :messages="$errors->get('rate')" class="mt-1" />
            </div>
            <div class="mt-2 flex justify-end gap-2 border-t border-line pt-4">
                <x-ui.button type="submit" class="w-full justify-center">Enregistrer</x-ui.button>
            </div>
        </form>
    </x-ui.slide-over>
    @endif
    </div>

    <x-ui.card :padded="false" class="mt-6 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="border-b border-line bg-surface-sunken text-xs uppercase tracking-wide text-ink-muted">
                        <th class="px-5 py-3 font-semibold">Nom</th>
                        <th class="px-5 py-3 font-semibold text-right">Taux</th>
                        @if (auth()->user()?->isAdmin())
                        <th class="px-5 py-3 font-semibold text-right">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($insuranceCompanies as $company)
                        <tr class="hover:bg-surface-sunken">
                            <td class="px-5 py-3.5 font-medium text-ink">{{ $company->name }}</td>
                            <td class="px-5 py-3.5 text-right tabular-nums text-ink-soft">{{ $company->rate }} %</td>
                            @if (auth()->user()?->isAdmin())
                            <td class="px-5 py-3.5 text-right">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('insurance.index', ['edit_insurance_id' => $company->id]) }}" class="inline-flex items-center rounded-md border border-line px-3 py-1.5 text-sm font-semibold text-ink-soft hover:bg-surface-muted">
                                        Modifier
                                    </a>
                                    <form method="POST" action="{{ route('insurance.destroy', $company) }}">
                                        @csrf
                                        @method('DELETE')
                                        <x-ui.button variant="secondary" size="sm" type="submit">
                                            Supprimer
                                        </x-ui.button>
                                    </form>
                                </div>
                            </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>

    @php $editingCompany = $insuranceCompanies->firstWhere('id', request('edit_insurance_id')); @endphp

    @if ($editingCompany && auth()->user()?->isAdmin())
        <x-ui.card class="mt-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-ink">Modifier une assurance</h3>
                    <p class="mt-1 text-sm text-ink-muted">Mettez à jour le nom de la compagnie d’assurance.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('insurance.update', $editingCompany) }}" class="mt-4 flex flex-col gap-4 sm:flex-row sm:items-end">
                @csrf
                @method('PUT')
            <div class="flex-1">
                <x-ui.field label="Nom de l’assurance" name="name" :value="old('name', $editingCompany->name)" required />
                <x-input-error :messages="$errors->get('name')" class="mt-1" />
            </div>
            <div class="w-40">
                <x-ui.field label="Taux (%)" name="rate" type="number" min="0" max="100" step="0.01" :value="old('rate', $editingCompany->rate)" required />
                <x-input-error :messages="$errors->get('rate')" class="mt-1" />
            </div>
                <div class="flex gap-2">
                    <x-ui.button type="submit">Enregistrer</x-ui.button>
                    <a href="{{ route('insurance.index') }}" class="inline-flex items-center rounded-md border border-line px-3 py-2 text-sm font-semibold text-ink-soft hover:bg-surface-muted">
                        Annuler
                    </a>
                </div>
            </form>
        </x-ui.card>
    @endif
</x-layout.app-shell>
