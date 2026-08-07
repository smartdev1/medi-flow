<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
    'label' => null,
    'icon' => null,
    'type' => 'text',
    'name' => null,
    'placeholder' => null,
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
    'label' => null,
    'icon' => null,
    'type' => 'text',
    'name' => null,
    'placeholder' => null,
]); ?>
<?php foreach (array_filter(([
    'label' => null,
    'icon' => null,
    'type' => 'text',
    'name' => null,
    'placeholder' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<div class="flex flex-col gap-1.5">
    <?php if($label): ?>
        <label <?php if($name): ?> for="<?php echo e($name); ?>" <?php endif; ?> class="text-sm font-medium text-ink"><?php echo e($label); ?></label>
    <?php endif; ?>
    <div class="relative">
        <?php if($icon): ?>
            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-ink-muted">
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
            </span>
        <?php endif; ?>
        <input
            type="<?php echo e($type); ?>"
            <?php if($name): ?> name="<?php echo e($name); ?>" id="<?php echo e($name); ?>" <?php endif; ?>
            <?php if($placeholder): ?> placeholder="<?php echo e($placeholder); ?>" <?php endif; ?>
            <?php echo e($attributes->merge(['class' => 'w-full rounded-md border border-line bg-white py-2.5 text-sm text-ink placeholder:text-ink-muted focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 ' . ($icon ? 'pl-10 pr-3' : 'px-3')])); ?>

        />
    </div>
</div>
<?php /**PATH C:\Users\arist\Documents\websites\moderne\resources\views/components/ui/field.blade.php ENDPATH**/ ?>