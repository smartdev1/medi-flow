{{--
    WHO Surgical Safety Checklist component.
    Expected variable: $surgery (App\Models\Surgery)
    Include with: @include("surgery.checklist.index", ["surgery" => $surgery])
--}}

@php
    $checklists = $surgery->checklists->keyBy("phase");
    $hasSignIn = isset($checklists["sign_in"]);
    $hasTimeOut = isset($checklists["time_out"]);
    $hasSignOut = isset($checklists["sign_out"]);
@endphp

<div class="space-y-4" x-data="{ activeTab: \"sign_in\" }">
    {{-- Tab navigation --}}
    <div class="flex border-b border-line" role="tablist">
        <button type="button"
                role="tab"
                :class="activeTab === \"sign_in\" ? \"border-brand-500 text-brand-700\" : \"border-transparent text-ink-muted hover:text-ink\""
                class="px-4 py-2 text-sm font-medium border-b-2 -mb-px focus:outline-none focus:ring-2 focus:ring-brand-500"
                @click="activeTab = \"sign_in\""
                :aria-selected="activeTab === \"sign_in\"">
            Sign In
            @if ($hasSignIn)
                <svg class="inline-block h-4 w-4 ml-1 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            @endif
        </button>
        <button type="button"
                role="tab"
                :class="activeTab === \"time_out\" ? \"border-brand-500 text-brand-700\" : \"border-transparent text-ink-muted hover:text-ink\""
                class="px-4 py-2 text-sm font-medium border-b-2 -mb-px focus:outline-none focus:ring-2 focus:ring-brand-500"
                @click="activeTab = \"time_out\""
                :aria-selected="activeTab === \"time_out\"">
            Time Out
            @if ($hasTimeOut)
                <svg class="inline-block h-4 w-4 ml-1 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            @endif
        </button>
        <button type="button"
                role="tab"
                :class="activeTab === \"sign_out\" ? \"border-brand-500 text-brand-700\" : \"border-transparent text-ink-muted hover:text-ink\""
                class="px-4 py-2 text-sm font-medium border-b-2 -mb-px focus:outline-none focus:ring-2 focus:ring-brand-500"
                @click="activeTab = \"sign_out\""
                :aria-selected="activeTab === \"sign_out\"">
            Sign Out
            @if ($hasSignOut)
                <svg class="inline-block h-4 w-4 ml-1 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            @endif
        </button>
    </div>

    {{-- Tab panels --}}
    <div class="space-y-4">

        {{-- SIGN IN --}}
        <div x-show="activeTab === \"sign_in\"" x-transition x-cloak>
            @include("surgery.checklist.phase", [
                "phase" => "sign_in",
                "title" => "Sign In (Avant l'anesthésie)",
                "items" => [
                    "identity_confirmed" => "Identité du patient confirmée",
                    "site_marked" => "Site opératoire marqué",
                    "anesthesia_check" => "Vérification anesthésie (matériel, médicaments)",
                    "allergies_verified" => "Allergies vérifiées",
                    "airway_risk" => "Risque voies aériennes évalué",
                    "blood_loss_risk" => "Risque hémorragie évalué",
                ],
                "checklist" => $checklists["sign_in"] ?? null,
                "surgery" => $surgery,
            ])
        </div>

        {{-- TIME OUT --}}
        <div x-show="activeTab === \"time_out\"" x-transition x-cloak>
            @include("surgery.checklist.phase", [
                "phase" => "time_out",
                "title" => "Time Out (Avant l'incision)",
                "items" => [
                    "team_intro" => "Présentation de l'équipe",
                    "patient_proc_site" => "Patient / Procédure / Site confirmés",
                    "antibiotics_given" => "Antibiotiques administrés (si indiqué)",
                    "imaging_displayed" => "Imagerie affichée / disponible",
                ],
                "checklist" => $checklists["time_out"] ?? null,
                "surgery" => $surgery,
            ])
        </div>

        {{-- SIGN OUT --}}
        <div x-show="activeTab === \"sign_out\"" x-transition x-cloak>
            @include("surgery.checklist.phase", [
                "phase" => "sign_out",
                "title" => "Sign Out (Avant sortie du bloc)",
                "items" => [
                    "procedure_confirmed" => "Nom de la procédure confirmé",
                    "instrument_count" => "Comptage instruments / compresses",
                    "specimen_labelled" => "Pièces opératoires étiquetées",
                    "equipment_issues" => "Problèmes matériel signalés",
                    "postop_plan" => "Plan post-opératoire communiqué",
                ],
                "checklist" => $checklists["sign_out"] ?? null,
                "surgery" => $surgery,
            ])
        </div>

    </div>
</div>
