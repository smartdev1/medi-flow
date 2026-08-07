<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
    'padded' => true,
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
    'padded' => true,
]); ?>
<?php foreach (array_filter(([
    'padded' => true,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<div <?php echo e($attributes->merge(['class' => 'bg-white border border-line rounded-lg shadow-card ' . ($padded ? 'p-6' : '')])); ?>>
    <?php echo e($slot); ?>

</div>
<?php /**PATH C:\Users\arist\Documents\websites\moderne\resources\views/components/ui/card.blade.php ENDPATH**/ ?>