<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
    'initials',
    'tone' => 'brand',
    'size' => 'md',
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
    'initials',
    'tone' => 'brand',
    'size' => 'md',
]); ?>
<?php foreach (array_filter(([
    'initials',
    'tone' => 'brand',
    'size' => 'md',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<?php
    $tones = [
        'brand' => 'bg-brand-50 text-brand-700',
        'success' => 'bg-success-tint text-success',
        'warning' => 'bg-warning-tint text-warning',
        'danger' => 'bg-danger-tint text-danger',
        'neutral' => 'bg-surface-muted text-ink-soft',
    ];

    $sizes = [
        'sm' => 'h-8 w-8 text-xs',
        'md' => 'h-10 w-10 text-sm',
        'lg' => 'h-12 w-12 text-base',
    ];
?>

<span <?php echo e($attributes->merge(['class' => 'inline-flex shrink-0 items-center justify-center rounded-full font-semibold ' . ($tones[$tone] ?? $tones['brand']) . ' ' . ($sizes[$size] ?? $sizes['md'])])); ?>>
    <?php echo e($initials); ?>

</span>
<?php /**PATH C:\Users\arist\Documents\websites\moderne\resources\views/components/ui/avatar.blade.php ENDPATH**/ ?>