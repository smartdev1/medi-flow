<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
    'name' => 'patient_id',
    'label' => 'Patient',
    'options' => [],
    'placeholder' => 'Rechercher par CSM-XXXX ou par nom...',
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
    'name' => 'patient_id',
    'label' => 'Patient',
    'options' => [],
    'placeholder' => 'Rechercher par CSM-XXXX ou par nom...',
]); ?>
<?php foreach (array_filter(([
    'name' => 'patient_id',
    'label' => 'Patient',
    'options' => [],
    'placeholder' => 'Rechercher par CSM-XXXX ou par nom...',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<?php
    $normalized = collect($options)->map(function ($option) {
        if ($option instanceof \App\Models\Patient) {
            return [
                'id' => $option->id,
                'code' => $option->code,
                'name' => $option->full_name,
                'first_name' => $option->first_name,
                'last_name' => $option->last_name,
                'sex' => $option->sex,
                'birth_date' => optional($option->birth_date)->format('Y-m-d'),
                'phone' => $option->phone,
                'coverage' => $option->coverage,
            ];
        }

        return $option;
    })->values();

    $selectedId = old($name);
    $selected = $normalized->firstWhere('id', (int) $selectedId);
?>

<div
    x-data="{
        query: <?php echo \Illuminate\Support\Js::from($selected ? $selected['code'].' — '.$selected['name'] : '')->toHtml() ?>,
        open: false,
        selectedId: <?php echo \Illuminate\Support\Js::from($selectedId ? (int) $selectedId : null)->toHtml() ?>,
        options: <?php echo \Illuminate\Support\Js::from($normalized)->toHtml() ?>,
        get filtered() {
            const q = this.query.trim().toLowerCase();
            if (! q) return this.options;
            return this.options.filter(o => o.code.toLowerCase().includes(q) || o.name.toLowerCase().includes(q));
        },
        select(option) {
            this.selectedId = option.id;
            this.query = option.code + ' — ' + option.name;
            this.open = false;
            this.$dispatch('patient-selected', option);
        },
    }"
    class="relative flex flex-col gap-1.5"
    @click.outside="open = false"
>
    <?php if($label): ?>
        <label class="text-sm font-medium text-ink"><?php echo e($label); ?></label>
    <?php endif; ?>

    <input type="hidden" name="<?php echo e($name); ?>" :value="selectedId">

    <div class="relative">
        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-ink-muted">
            <?php if (isset($component)) { $__componentOriginal56804098dcf376a0e2227cb77b6cd00a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.icon','data' => ['name' => 'search','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('ui.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'search','class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $attributes = $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__attributesOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a)): ?>
<?php $component = $__componentOriginal56804098dcf376a0e2227cb77b6cd00a; ?>
<?php unset($__componentOriginal56804098dcf376a0e2227cb77b6cd00a); ?>
<?php endif; ?>
        </span>
        <input
            type="text"
            x-model="query"
            @focus="open = true"
            @input="open = true; if (selectedId !== null) { selectedId = null; $dispatch('patient-search-cleared'); }"
            placeholder="<?php echo e($placeholder); ?>"
            autocomplete="off"
            <?php echo e($attributes->merge(['class' => 'w-full rounded-md border border-line bg-white py-2.5 pl-10 pr-3 text-sm text-ink placeholder:text-ink-muted focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500'])); ?>

        >
    </div>

    <div
        x-show="open && filtered.length > 0"
        x-cloak
        class="absolute top-full z-20 mt-1 max-h-56 w-full overflow-y-auto rounded-md border border-line bg-white py-1 shadow-card-hover"
    >
        <template x-for="option in filtered" :key="option.id">
            <button
                type="button"
                @click="select(option)"
                class="flex w-full items-center justify-between gap-2 px-3 py-2 text-left text-sm hover:bg-surface-sunken"
            >
                <span class="font-medium text-ink" x-text="option.name"></span>
                <span class="font-mono text-xs text-ink-muted" x-text="option.code"></span>
            </button>
        </template>
    </div>

    <p
        x-show="open && query.trim() !== '' && filtered.length === 0"
        x-cloak
        class="absolute top-full z-20 mt-1 w-full rounded-md border border-line bg-white px-3 py-2 text-sm text-ink-muted shadow-card"
    >
        Aucun patient trouvé.
    </p>
</div>
<?php /**PATH C:\Users\arist\Documents\websites\moderne\resources\views/components/ui/patient-search.blade.php ENDPATH**/ ?>