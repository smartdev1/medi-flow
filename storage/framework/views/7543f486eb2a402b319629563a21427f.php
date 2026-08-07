<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps([
    'label' => null,
    'name' => null,
    'options' => [],
    'placeholder' => null,
    'value' => null,
]) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps([
    'label' => null,
    'name' => null,
    'options' => [],
    'placeholder' => null,
    'value' => null,
]); ?>
<?php foreach (array_filter(([
    'label' => null,
    'name' => null,
    'options' => [],
    'placeholder' => null,
    'value' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<?php
    $current = old($name ?? '', $value);
?>

<div class="flex flex-col gap-1.5">
    <?php if($label): ?>
        <label <?php if($name): ?> for="<?php echo e($name); ?>" <?php endif; ?> class="text-sm font-medium text-ink"><?php echo e($label); ?></label>
    <?php endif; ?>
    <select
        <?php if($name): ?> name="<?php echo e($name); ?>" id="<?php echo e($name); ?>" <?php endif; ?>
        <?php echo e($attributes->merge(['class' => 'w-full rounded-md border border-line bg-white py-2.5 px-3 text-sm text-ink focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500'])); ?>

    >
        <?php if($placeholder): ?>
            <option value="" disabled <?php if(!$current): echo 'selected'; endif; ?>><?php echo e($placeholder); ?></option>
        <?php endif; ?>
        <?php $__currentLoopData = $options; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $optionValue => $optionLabel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($optionValue); ?>" <?php if((string) $current === (string) $optionValue): echo 'selected'; endif; ?>><?php echo e($optionLabel); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
</div>
<?php /**PATH C:\Users\arist\Documents\websites\moderne\resources\views/components/ui/select.blade.php ENDPATH**/ ?>