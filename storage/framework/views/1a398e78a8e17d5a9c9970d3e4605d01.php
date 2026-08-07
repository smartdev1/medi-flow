<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
    'tone' => 'neutral',
    'dot' => true,
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
    'tone' => 'neutral',
    'dot' => true,
]); ?>
<?php foreach (array_filter(([
    'tone' => 'neutral',
    'dot' => true,
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
        'neutral' => 'bg-surface-muted text-ink-soft border-line',
        'brand' => 'bg-brand-50 text-brand-700 border-brand-200',
        'success' => 'bg-success-tint text-success border-success/20',
        'warning' => 'bg-warning-tint text-warning border-warning/20',
        'danger' => 'bg-danger-tint text-danger border-danger/20',
    ];

    $dots = [
        'neutral' => 'bg-ink-muted',
        'brand' => 'bg-brand-500',
        'success' => 'bg-success',
        'warning' => 'bg-warning',
        'danger' => 'bg-danger',
    ];
?>

<span <?php echo e($attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-md border px-2 py-1 text-xs font-medium leading-none ' . ($tones[$tone] ?? $tones['neutral'])])); ?>>
    <?php if($dot): ?>
        <span class="h-1.5 w-1.5 rounded-full <?php echo e($dots[$tone] ?? $dots['neutral']); ?>"></span>
    <?php endif; ?>
    <?php echo e($slot); ?>

</span>
<?php /**PATH C:\Users\arist\Documents\websites\moderne\resources\views/components/ui/badge.blade.php ENDPATH**/ ?>