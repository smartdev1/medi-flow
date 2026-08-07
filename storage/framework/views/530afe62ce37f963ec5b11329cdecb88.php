<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag; ?>
<?php foreach($attributes->onlyProps(['align' => 'right']) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $attributes = $attributes->exceptProps(['align' => 'right']); ?>
<?php foreach (array_filter((['align' => 'right']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
} ?>
<?php $__defined_vars = get_defined_vars(); ?>
<?php foreach ($attributes as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
} ?>
<?php unset($__defined_vars); ?>

<div x-data="{ open: false }" class="relative" @click.outside="open = false" @keydown.escape.window="open = false">
    <div @click="open = !open" class="cursor-pointer">
        <?php echo e($trigger); ?>

    </div>

    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        @click="open = false"
        class="absolute <?php echo e($align === 'right' ? 'right-0' : 'left-0'); ?> top-full z-30 mt-2 w-60 overflow-hidden rounded-md border border-line bg-white shadow-card-hover"
    >
        <?php echo e($slot); ?>

    </div>
</div>
<?php /**PATH C:\Users\arist\Documents\websites\moderne\resources\views/components/ui/dropdown.blade.php ENDPATH**/ ?>