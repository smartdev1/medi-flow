<x-layout.app-shell title="Actes médicaux" active="acts" :user-name="$userName" :user-role="$userRole" :user-initials="$userInitials">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-ink">Actes médicaux</h1>
            <p class="mt-1 text-sm text-ink-muted">Référentiel de facturation de la clinique.</p>
        </div>
        @if ($canManage)
            <x-ui.slide-over title="Nouvel acte">
                <x-slot:trigger>
                    <x-ui.button>
                        <x-ui.icon name="plus" class="h-4 w-4" />
                        Nouvel acte
                    </x-ui.button>
                </x-slot:trigger>

                <form method="POST" action="{{ route('acts.store') }}" class="flex flex-col gap-4">
                    @csrf
                    <div>
                        <x-ui.field label="Code acte" name="code" placeholder="Ex : CONS-GEN" :value="old('code')" required />
                        <x-input-error :messages="$errors->get('code')" class="mt-1" />
                    </div>
                    <div>
                        <x-ui.field label="Libellé" name="label" placeholder="Ex : Consultation générale" :value="old('label')" required />
                        <x-input-error :messages="$errors->get('label')" class="mt-1" />
                    </div>
                    <div>
                        <x-ui.select label="Catégorie" name="category" placeholder="Sélectionner" :options="collect($categories)->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all()" :selected="old('category')" required />
                        <x-input-error :messages="$errors->get('category')" class="mt-1" />
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <x-ui.field label="Tarif de base (FCFA)" name="base_price" type="number" min="0" placeholder="5000" :value="old('base_price')" required />
                            <x-input-error :messages="$errors->get('base_price')" class="mt-1" />
                        </div>
                        <div>
                            <x-ui.field label="Tarif conventionné (FCFA)" name="insured_price" type="number" min="0" placeholder="3500" :value="old('insured_price')" />
                            <x-input-error :messages="$errors->get('insured_price')" class="mt-1" />
                        </div>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-ink">Praticiens habilités</label>
                        <select name="roles[]" multiple class="mt-1 w-full rounded-md border border-line bg-white py-2.5 px-3 text-sm text-ink focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                            @foreach ($roles as $role)
                                <option value="{{ $role->value }}">{{ $role->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mt-2 flex justify-end gap-2 border-t border-line pt-4">
                        <x-ui.button type="submit" class="w-full justify-center">Enregistrer l'acte</x-ui.button>
                    </div>
                </form>
            </x-ui.slide-over>
        @endif
    </div>

    <div x-data="actsManager()" x-init="init()" class="mt-6 space-y-8">
        @forelse ($actsByCategory as $category => $items)
            <x-ui.card>
                <div class="flex items-center justify-between border-b border-line pb-3">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-ink-muted">{{ \App\Enums\ActCategory::from($category)->label() }}</h2>
                    <span class="text-xs text-ink-muted">{{ $items->count() }} acte(s)</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="text-xs uppercase tracking-wide text-ink-muted">
                                <th class="px-3 py-2 font-medium">Code</th>
                                <th class="px-3 py-2 font-medium">Libellé</th>
                                <th class="px-3 py-2 font-medium">Base</th>
                                <th class="px-3 py-2 font-medium">Conventionné</th>
                                <th class="px-3 py-2 font-medium">Praticiens</th>
                                @if ($canManage)
                                    <th class="px-3 py-2 font-medium text-right">Actions</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($items as $act)
                                <tr class="hover:bg-surface-sunken">
                                    <td class="px-3 py-2.5 font-mono text-xs text-ink-muted">{{ $act->code }}</td>
                                    <td class="px-3 py-2.5 font-medium text-ink">{{ $act->label }}</td>
                                    <td class="px-3 py-2.5 text-ink-soft">{{ $act->formatted_base_price }}</td>
                                    <td class="px-3 py-2.5 text-ink-soft">{{ $act->formatted_insured_price }}</td>
                                    <td class="px-3 py-2.5">
                                        <div class="flex flex-wrap gap-1">
                                            @foreach ($act->rolesAsEnum() as $role)
                                                @if ($role)
                                                    <x-ui.badge tone="neutral">{{ $role->label() }}</x-ui.badge>
                                                @endif
                                            @endforeach
                                        </div>
                                    </td>
                                    @if ($canManage)
                                        <td class="px-3 py-2.5 text-right">
                                            <div class="flex justify-end gap-2">
                                                <button type="button" class="rounded-md border border-line px-2.5 py-1 text-xs font-medium text-ink-soft hover:border-brand-300 hover:text-brand-700"
                                                        @click="openEdit(@js($act))">Modifier</button>
                                                <form method="POST" action="{{ route('acts.destroy', $act) }}" onsubmit="return confirm('Supprimer cet acte ?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="rounded-md border border-line px-2.5 py-1 text-xs font-medium text-danger hover:border-danger hover:bg-danger-tint">Supprimer</button>
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
        @empty
            <x-ui.card class="py-12 text-center text-sm text-ink-muted">Aucun acte défini.</x-ui.card>
        @endforelse

        {{-- Slide-over d'édition --}}
        <template x-if="editing">
            <div class="fixed inset-0 z-50">
                <div class="absolute inset-0 bg-ink/40" @click="editing = null"></div>
                <div class="absolute right-0 top-0 flex h-full w-full max-w-md flex-col bg-white shadow-xl">
                    <div class="flex items-center justify-between border-b border-line px-6 py-4">
                        <h3 class="text-base font-semibold text-ink">Modifier l'acte</h3>
                        <button type="button" @click="editing = null" class="rounded-md p-1.5 text-ink-muted hover:bg-surface-muted">
                            <x-ui.icon name="x-circle" class="h-5 w-5" />
                        </button>
                    </div>
                    <form method="POST" x-bind:action="'{{ url('acts') }}/' + editing.id" class="flex flex-1 flex-col gap-4 overflow-y-auto px-6 py-4">
                        @csrf
                        @method('PUT')
                        <div>
                            <x-ui.field label="Code acte" name="code" x-model="form.code" required />
                        </div>
                        <div>
                            <x-ui.field label="Libellé" name="label" x-model="form.label" required />
                        </div>
                        <div>
                            <x-ui.select label="Catégorie" name="category" :options="collect($categories)->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all()" x-model="form.category" required />
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <x-ui.field label="Tarif de base (FCFA)" name="base_price" type="number" min="0" x-model="form.base_price" required />
                            </div>
                            <div>
                                <x-ui.field label="Tarif conventionné (FCFA)" name="insured_price" type="number" min="0" x-model="form.insured_price" />
                            </div>
                        </div>
                        <div>
                            <label class="text-sm font-medium text-ink">Praticiens habilités</label>
                            <select name="roles[]" multiple x-model="form.roles" class="mt-1 w-full rounded-md border border-line bg-white py-2.5 px-3 text-sm text-ink focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                                @foreach ($roles as $role)
                                    <option value="{{ $role->value }}">{{ $role->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mt-2 flex justify-end gap-2 border-t border-line pt-4">
                            <x-ui.button type="submit" class="w-full justify-center">Enregistrer</x-ui.button>
                        </div>
                    </form>
                </div>
            </div>
        </template>
    </div>

    <script>
        function actsManager() {
            return {
                editing: null,
                form: { code: '', label: '', category: '', base_price: 0, insured_price: '', roles: [] },
                init() {
                    @if ($editingAct)
                        this.openEdit(@json($editingAct));
                    @endif
                },
                openEdit(act) {
                    this.form = {
                        code: act.code,
                        label: act.label,
                        category: act.category,
                        base_price: act.base_price,
                        insured_price: act.insured_price ?? '',
                        roles: Array.isArray(act.roles) ? act.roles : [],
                    };
                    this.editing = act;
                    document.body.style.overflow = 'hidden';
                },
            };
        }
    </script>

</x-layout.app-shell>
