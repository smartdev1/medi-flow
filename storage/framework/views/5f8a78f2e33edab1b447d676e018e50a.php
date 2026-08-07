<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'button',
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'button',
]); ?>
<?php foreach (array_filter(([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'button',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<?php
    $variants = [
        'primary' => 'bg-brand-500 text-white border border-brand-500 hover:bg-brand-600 hover:border-brand-600 focus-visible:ring-brand-200',
        'secondary' => 'bg-white text-ink border border-line hover:bg-surface-muted focus-visible:ring-brand-200',
        'ghost' => 'bg-transparent text-ink-soft border border-transparent hover:bg-surface-muted focus-visible:ring-brand-200',
        'danger' => 'bg-white text-danger border border-danger/30 hover:bg-danger-tint focus-visible:ring-danger/30',
    ];

    $sizes = [
        'sm' => 'text-xs px-3 py-1.5 gap-1.5',
        'md' => 'text-sm px-4 py-2 gap-2',
        'lg' => 'text-sm px-5 py-2.5 gap-2',
    ];

    $classes = 'inline-flex items-center justify-center rounded-md font-medium transition-colors duration-100 focus:outline-none focus-visible:ring-2 disabled:opacity-50 disabled:pointer-events-none '
        . ($variants[$variant] ?? $variants['primary']) . ' ' . ($sizes[$size] ?? $sizes['md']);
?>

<?php if($href): ?>
    <a href="<?php echo e($href); ?>" <?php echo e($attributes->merge(['class' => $classes])); ?>>
        <?php echo e($slot); ?>

    </a>
<?php else: ?>
    <button type="<?php echo e($type); ?>" <?php echo e($attributes->merge(['class' => $classes])); ?>>
        <?php echo e($slot); ?>

    </button>
<?php endif; ?>
<?php /**PATH C:\Users\arist\Documents\websites\moderne\resources\views/components/ui/button.blade.php ENDPATH**/ ?>