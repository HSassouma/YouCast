<x-app-layout width="read">
    <x-slot name="title">{{ $conversation->subject }} — YouCast</x-slot>

    <x-slot name="header">
        <x-page-header
            :title="$conversation->subject"
            subtitle="Ouverte le {{ $conversation->created_at->translatedFormat('d M Y') }}"
            backHref="{{ route('messages.index') }}"
            backLabel="Mes messages">
            <x-slot name="badge">
                <x-status-badge :status="$conversation->status" />
            </x-slot>
        </x-page-header>
    </x-slot>

    <div>
        <x-flash />

        <ol class="space-y-4">
            @forelse ($conversation->messages as $message)
                @php $mine = $message->user_id === auth()->id(); @endphp
                <li class="flex flex-col {{ $mine ? 'items-end' : 'items-start' }}">
                    <div class="max-w-[85%] rounded-2xl border px-4 py-3 sm:max-w-[75%] {{ $mine ? 'border-brand-100 bg-brand-50' : 'border-slate-200 bg-white' }}">
                        <p class="flex flex-wrap items-center gap-2 text-xs font-semibold {{ $mine ? 'text-brand-800' : 'text-slate-700' }}">
                            <span>{{ $mine ? 'Vous' : ($message->author?->name ?? 'Compte supprimé') }}</span>
                            @unless ($mine)
                                <span class="rounded-full bg-brand-50 px-1.5 py-0.5 text-[0.625rem] font-semibold uppercase tracking-wide text-brand-700">BDE</span>
                            @endunless
                            <time class="font-normal text-slate-500" datetime="{{ $message->created_at->toIso8601String() }}">
                                {{ $message->created_at->translatedFormat('d M à H:i') }}
                            </time>
                        </p>
                        <p class="mt-1.5 whitespace-pre-line break-words text-sm leading-relaxed {{ $mine ? 'text-brand-900' : 'text-slate-700' }}">
                            {{ $message->body }}
                        </p>
                    </div>
                </li>
            @empty
                <li class="rounded-2xl border border-dashed border-slate-300 bg-white px-5 py-8 text-center text-sm text-slate-500">
                    Aucun message dans cette conversation.
                </li>
            @endforelse
        </ol>

        <div class="mt-8">
            @can('reply', $conversation)
                <form method="POST" action="{{ route('messages.replies.store', $conversation) }}" class="panel p-5">
                    @csrf

                    <x-input-label for="body" value="Votre réponse" required />
                    <x-textarea-input id="body" name="body" rows="4" required maxlength="4000" class="mt-1.5">{{ old('body') }}</x-textarea-input>
                    <x-input-error :messages="$errors->get('body')" class="mt-2" />

                    <div class="mt-4 flex items-center justify-between gap-4">
                        <p class="text-xs text-slate-500">Visible uniquement par vous et le BDE.</p>
                        <x-primary-button icon="send">Envoyer</x-primary-button>
                    </div>
                </form>
            @else
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4">
                    <p class="flex items-center gap-2 text-sm font-medium text-slate-700">
                        <x-icon name="lock" :size="17" class="text-slate-400" />
                        Cette conversation est clôturée. Ouvrez un nouveau message si vous avez besoin d'aide.
                    </p>
                </div>
            @endcan
        </div>
    </div>
</x-app-layout>
