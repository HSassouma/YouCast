@php
    $activity = $activity ?? null;
@endphp

<div class="space-y-6">
    <div>
        <x-input-label for="title" value="Titre" required />
        <x-text-input id="title" name="title" :value="old('title', $activity->title ?? '')" required maxlength="180" class="mt-1.5" />
        <x-input-error :messages="$errors->get('title')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="type" value="Type d'activité" required />
        <x-select-input id="type" name="type" class="mt-1.5">
            @foreach ($types as $type)
                <option value="{{ $type->value }}" @selected(old('type', $activity->type->value ?? '') === $type->value)>{{ $type->label() }}</option>
            @endforeach
        </x-select-input>
        <x-input-error :messages="$errors->get('type')" class="mt-2" />
    </div>

    <div>
        <x-input-label for="description" value="Description" required />
        <x-textarea-input id="description" name="description" rows="8" required maxlength="8000" class="mt-1.5">{{ old('description', $activity->description ?? '') }}</x-textarea-input>
        <p class="mt-1.5 text-xs text-slate-500">
            Expliquez le programme, le public visé et ce que les participants doivent préparer.
        </p>
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <x-input-label for="category" value="Catégorie" />
            <x-text-input id="category" name="category" :value="old('category', $activity->category ?? '')" maxlength="80" class="mt-1.5" />
            <x-input-error :messages="$errors->get('category')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="location" value="Lieu" />
            <x-text-input id="location" name="location" :value="old('location', $activity->location ?? '')" maxlength="160" class="mt-1.5" />
            <x-input-error :messages="$errors->get('location')" class="mt-2" />
        </div>
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <x-input-label for="starts_at" value="Date et heure de début" />
            <x-text-input id="starts_at" type="datetime-local" name="starts_at" :value="old('starts_at', $activity?->starts_at?->format('Y-m-d\TH:i'))" class="mt-1.5" />
            <x-input-error :messages="$errors->get('starts_at')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="ends_at" value="Date et heure de fin" />
            <x-text-input id="ends_at" type="datetime-local" name="ends_at" :value="old('ends_at', $activity?->ends_at?->format('Y-m-d\TH:i'))" class="mt-1.5" />
            <x-input-error :messages="$errors->get('ends_at')" class="mt-2" />
        </div>
    </div>

    <div class="grid gap-5 sm:grid-cols-2">
        <div>
            <x-input-label for="registration_opens_at" value="Ouverture des inscriptions" />
            <x-text-input id="registration_opens_at" type="datetime-local" name="registration_opens_at" :value="old('registration_opens_at', $activity?->registration_opens_at?->format('Y-m-d\TH:i'))" class="mt-1.5" />
            <x-input-error :messages="$errors->get('registration_opens_at')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="registration_deadline" value="Date limite d'inscription" />
            <x-text-input id="registration_deadline" type="datetime-local" name="registration_deadline" :value="old('registration_deadline', $activity?->registration_deadline?->format('Y-m-d\TH:i'))" class="mt-1.5" />
            <p class="mt-1.5 text-xs text-slate-500">Choisissez le jour du début de l'activité ou une date ultérieure.</p>
            <x-input-error :messages="$errors->get('registration_deadline')" class="mt-2" />
        </div>
    </div>

    <div class="grid gap-5 sm:grid-cols-3">
        <div>
            <x-input-label for="capacity" value="Capacité" />
            <x-text-input id="capacity" type="number" min="1" max="10000" name="capacity" :value="old('capacity', $activity->capacity ?? '')" class="mt-1.5" />
            <p class="mt-1.5 text-xs text-slate-500">Laisser vide pour un nombre illimité de places.</p>
            <x-input-error :messages="$errors->get('capacity')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="min_team_size" value="Équipe minimale" />
            <x-text-input id="min_team_size" type="number" min="1" max="50" name="min_team_size" :value="old('min_team_size', $activity->min_team_size ?? '')" class="mt-1.5" />
            <p class="mt-1.5 text-xs text-slate-500">Défis en équipe uniquement.</p>
            <x-input-error :messages="$errors->get('min_team_size')" class="mt-2" />
        </div>
        <div>
            <x-input-label for="max_team_size" value="Équipe maximale" />
            <x-text-input id="max_team_size" type="number" min="1" max="50" name="max_team_size" :value="old('max_team_size', $activity->max_team_size ?? '')" class="mt-1.5" />
            <x-input-error :messages="$errors->get('max_team_size')" class="mt-2" />
        </div>
    </div>

    <label class="flex items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
        <input type="checkbox" name="cancellation_allowed" value="1" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500" @checked(old('cancellation_allowed', $activity->cancellation_allowed ?? true))>
        <span class="text-sm font-medium text-slate-800">Les étudiants peuvent annuler leur inscription</span>
    </label>
</div>
