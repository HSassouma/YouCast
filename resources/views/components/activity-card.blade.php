@props(['activity', 'registered' => false])

@php
    $filled = $activity->active_registrations_count ?? $activity->filledCount();
    $month = $activity->starts_at?->translatedFormat('M');
    $day = $activity->starts_at?->format('d');
@endphp

<article class="yc-activity-card group p-5 sm:p-6">
    <div class="relative flex items-start gap-4">
        @if ($activity->starts_at)
            <div class="date-tile">
                <span class="date-tile-day">{{ $day }}</span>
                <span class="date-tile-month">{{ $month }}</span>
            </div>
        @else
            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700 ring-1 ring-inset ring-brand-100">
                <x-icon name="calendar" :size="21" />
            </span>
        @endif

        <div class="min-w-0 flex-1 pt-0.5">
            <p class="text-xs font-semibold text-brand-700">{{ $activity->type->label() }}</p>
            <h3 class="yc-break-anywhere mt-1.5 text-lg font-bold leading-snug tracking-tight text-slate-900">
                <a href="{{ route('activities.show', $activity) }}" class="hover:text-brand-700 focus-visible:text-brand-700">
                    {{ $activity->title }}
                </a>
            </h3>
        </div>
    </div>

    <p class="yc-activity-description mt-5 text-sm leading-relaxed text-slate-600">{{ $activity->description }}</p>

    <dl class="mt-5 space-y-2.5 border-t border-brand-50 pt-4 pb-5">
        @if ($activity->location)
            <div class="flex min-w-0 items-center gap-2 text-sm text-slate-600">
                <x-icon name="pin" :size="16" class="shrink-0 text-slate-400" />
                <dt class="sr-only">Lieu</dt>
                <dd class="min-w-0 break-words">{{ $activity->location }}</dd>
            </div>
        @endif
        <div class="flex min-w-0 items-center gap-2 text-sm text-slate-600">
            <x-icon name="clock" :size="16" class="shrink-0 text-slate-400" />
            <dt class="sr-only">Date limite d'inscription</dt>
            <dd class="break-words">
                @if ($activity->registration_deadline)
                    Inscription jusqu'au {{ $activity->registration_deadline->format('d M Y') }}
                @else
                    Inscription ouverte
                @endif
            </dd>
        </div>
        <div class="flex min-w-0 items-center gap-2 text-sm text-slate-600">
            <x-icon name="ticket" :size="16" class="shrink-0 text-slate-400" />
            <dt class="sr-only">Places</dt>
            <dd class="break-words">
                @if ($activity->capacity)
                    {{ $filled }} / {{ $activity->capacity }} places
                @else
                    Places illimitées
                @endif
            </dd>
        </div>
        @if ($activity->usesTeams())
            <div class="flex items-center gap-2 text-sm text-slate-600">
                <x-icon name="users" :size="16" class="shrink-0 text-slate-400" />
                <dt class="sr-only">Taille d'équipe</dt>
                <dd>Équipe de {{ $activity->min_team_size }} à {{ $activity->max_team_size }} membres</dd>
            </div>
        @endif
    </dl>

    <div class="relative mt-auto flex flex-wrap items-center justify-between gap-3 border-t border-brand-100/80 pt-4">
        @if ($registered)
            <span class="inline-flex items-center gap-1.5 rounded-full bg-success-50 px-2.5 py-1 text-xs font-semibold text-success-800 ring-1 ring-inset ring-success-200">
                <x-icon name="check-circle" :size="14" />
                Inscription validée
            </span>
        @else
            <x-status-badge :status="$activity->status" />
        @endif

        <a href="{{ route('activities.show', $activity) }}" class="inline-flex min-h-10 items-center gap-2 rounded-lg bg-brand-50 px-3 text-sm font-semibold text-brand-800 transition duration-200 hover:bg-brand-100">
            Découvrir
            <x-icon name="arrow-right" :size="16" class="transition-transform duration-200 group-hover:translate-x-0.5" />
        </a>
    </div>
</article>
