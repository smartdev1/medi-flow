<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
    'items' => [],
    'label' => 'Filtrer',
    'icon' => 'filter',
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
    'items' => [],
    'label' => 'Filtrer',
    'icon' => 'filter',
]); ?>
<?php foreach (array_filter(([
    'items' => [],
    'label' => 'Filtrer',
    'icon' => 'filter',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<div <?php echo e($attributes->merge(['class' => 'flex flex-col gap-4 rounded-lg border border-line bg-white p-4 sm:flex-row sm:items-center'])); ?>

    x-data="{
        activeFilter: '<?php echo e(collect($items)->firstWhere('active', true)['label'] ?? ''); ?>',
        filterData(label) {
            this.activeFilter = label;
            
            // Get all filter buttons
            const buttons = this.$el.querySelectorAll('button');
            buttons.forEach(button => {
                if (button.textContent.trim() === label) {
                    button.classList.add('border-brand-500', 'bg-brand-50', 'text-brand-700');
                    button.classList.remove('border-line', 'text-ink-soft', 'hover:border-brand-300', 'hover:text-brand-700');
                } else {
                    button.classList.remove('border-brand-500', 'bg-brand-50', 'text-brand-700');
                    button.classList.add('border-line', 'text-ink-soft', 'hover:border-brand-300', 'hover:text-brand-700');
                }
            });
            
            // Dispatch event for parent components to handle
            this.$dispatch('filter-changed', { filter: label });
        }
    }">
    <div class="flex items-center gap-2 text-sm font-medium text-ink-soft">
        <?php if (isset($component)) { $__componentOriginal56804098dcf376a0e2227cb77b6cd00a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal56804098dcf376a0e2227cb77b6cd00a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.icon','data' => ['name' => $icon,'class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? (array) $attributes->getIterator() : [])); ?>
<?php $component->withName('ui.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag && $constructor = (new ReflectionClass(Illuminate\View\AnonymousComponent::class))->getConstructor()): ?>
<?php $attributes = $attributes->except(collect($constructor->getParameters())->map->getName()->all()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($icon),'class' => 'h-4 w-4']); ?>
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
        <?php echo e($label); ?>&nbsp;:
    </div>
    <div class="flex flex-wrap gap-2">
        <?php $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <button type="button"
                    @click="filterData('<?php echo e($item['label']); ?>')"
                    class="rounded-md border px-3 py-1.5 text-xs font-medium transition-colors
                           <?php echo e(($item['active'] ?? false) ? 'border-brand-500 bg-brand-50 text-brand-700' : 'border-line text-ink-soft hover:border-brand-300 hover:text-brand-700'); ?>">
                <?php echo e($item['label']); ?>

            </button>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
    <?php if(isset($trailing)): ?>
        <div class="flex items-center gap-2 text-sm text-ink-muted sm:ml-auto">
            <?php echo e($trailing); ?>

        </div>
    <?php endif; ?>
</div>
<?php /**PATH C:\Users\arist\Documents\websites\moderne\resources\views/components/ui/filter-pills.blade.php ENDPATH**/ ?>