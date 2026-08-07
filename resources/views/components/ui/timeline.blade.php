@props(['steps' => []])

@php
    $dotClasses = [
        'done' => 'bg-success border-success text-white',
        'current' => 'bg-brand-500 border-brand-500 text-white',
        'pending' => 'bg-white border-line text-ink-muted',
    ];
@endphp

<ol class="relative">
    @foreach ($steps as $index => $step)
        @php $status = $step['status'] ?? 'pending'; @endphp
        <li class="relative flex gap-4 pb-8 last:pb-0">
            @if (!$loop->last)
                <span class="absolute left-[15px] top-8 h-full w-px bg-line"></span>
            @endif
            <span class="relative z-10 flex h-8 w-8 shrink-0 items-center justify-center rounded-full border-2 {{ $dotClasses[$status] ?? $dotClasses['pending'] }}">
                @if ($status === 'done')
                    <x-ui.icon name="check-circle" class="h-4 w-4" />
                @else
                    <x-ui.icon :name="$step['icon'] ?? 'clock'" class="h-4 w-4" />
                @endif
            </span>
            <div class="flex min-w-0 flex-1 flex-col gap-0.5 pt-1">
                <div class="flex flex-wrap items-baseline justify-between gap-x-3">
                    <p class="text-sm font-semibold {{ $status === 'pending' ? 'text-ink-muted' : 'text-ink' }}">{{ $step['label'] }}</p>
                    @if (!empty($step['time']))
                        <span class="font-mono text-xs text-ink-muted">{{ $step['time'] }}</span>
                    @endif
                </div>
                @if (!empty($step['meta']))
                    <p class="text-xs text-ink-muted">{{ $step['meta'] }}</p>
                @endif
            </div>
        </li>
    @endforeach
</ol>
