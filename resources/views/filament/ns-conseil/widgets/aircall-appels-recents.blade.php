<x-filament-widgets::widget>
    <x-filament::section heading="Historique des appels (Aircall)">

        {{-- Filtres --}}
        <div class="flex gap-2 mb-4">
            <button wire:click="setDirection('')"
                class="px-3 py-1 text-sm rounded-md {{ $filterDirection === '' ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700' }}">
                Tous
            </button>
            <button wire:click="setDirection('inbound')"
                class="px-3 py-1 text-sm rounded-md {{ $filterDirection === 'inbound' ? 'bg-success-600 text-white' : 'bg-gray-100 text-gray-700' }}">
                📥 Entrants
            </button>
            <button wire:click="setDirection('outbound')"
                class="px-3 py-1 text-sm rounded-md {{ $filterDirection === 'outbound' ? 'bg-primary-600 text-white' : 'bg-gray-100 text-gray-700' }}">
                📤 Sortants
            </button>
        </div>

        {{-- Tableau --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b text-left text-gray-500">
                        <th class="pb-2 pr-4">Date / Heure</th>
                        <th class="pb-2 pr-4">Direction</th>
                        <th class="pb-2 pr-4">Statut</th>
                        <th class="pb-2 pr-4">Durée</th>
                        <th class="pb-2 pr-4">Agent</th>
                        <th class="pb-2 pr-4">Numéro</th>
                        <th class="pb-2">Enregistrement</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($calls as $call)
                        @php
                            $direction = $call['direction'] ?? '';
                            $statut    = $call['status'] ?? '';
                            $duree     = $call['duration'] ?? 0;
                            $min       = floor($duree / 60);
                            $sec       = $duree % 60;
                            $dureeLabel = $min > 0 ? "{$min}min {$sec}s" : "{$sec}s";
                            $agent     = $call['user']['name'] ?? '—';
                            $numero    = $call['raw_digits'] ?? '—';
                            $date      = \Carbon\Carbon::createFromTimestamp($call['started_at'])->format('d/m/Y H:i');
                        @endphp
                        <tr class="border-b hover:bg-gray-50 dark:hover:bg-white/5">
                            <td class="py-2 pr-4 text-gray-700 dark:text-gray-300">{{ $date }}</td>
                            <td class="py-2 pr-4">
                                @if($direction === 'inbound')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-success-100 text-success-700">📥 Entrant</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-primary-100 text-primary-700">📤 Sortant</span>
                                @endif
                            </td>
                            <td class="py-2 pr-4">
                                @php
                                    $color = match($statut) {
                                        'answered', 'done' => 'success',
                                        'missed_customer', 'missed' => 'danger',
                                        'voicemail' => 'warning',
                                        default => 'gray',
                                    };
                                    $label = match($statut) {
                                        'answered', 'done' => 'Réalisé',
                                        'missed_customer', 'missed' => 'Manqué',
                                        'voicemail' => 'Messagerie',
                                        'abandoned' => 'Abandonné',
                                        default => $statut,
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-{{ $color }}-100 text-{{ $color }}-700">
                                    {{ $label }}
                                </span>
                            </td>
                            <td class="py-2 pr-4 text-gray-600 dark:text-gray-400">{{ $dureeLabel }}</td>
                            <td class="py-2 pr-4 text-gray-700 dark:text-gray-300">{{ $agent }}</td>
                            <td class="py-2 pr-4 text-gray-600 dark:text-gray-400 font-mono text-xs">{{ $numero }}</td>
                            <td class="py-2">
                                @if(!empty($call['recording']))
                                    <div x-data="{ open: false }" class="flex flex-col gap-1">
                                        <button
                                            x-on:click="
                                                if (open) { open = false; return; }
                                                open = true;
                                                $nextTick(() => {
                                                    const audio = $el.parentElement.querySelector('audio');
                                                    if (audio) {
                                                        audio.src = '{{ $call['recording'] }}';
                                                        audio.load();
                                                        audio.play();
                                                    }
                                                });
                                            "
                                            class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-md bg-primary-50 text-primary-700 hover:bg-primary-100 transition w-fit"
                                        >
                                            <x-heroicon-o-play-circle class="w-4 h-4" />
                                            <span x-text="open ? '⏹ Fermer' : '▶ Écouter'">▶ Écouter</span>
                                        </button>
                                        <div x-show="open" x-cloak>
                                            <audio controls preload="none" class="w-48 h-8"></audio>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-gray-400 text-xs">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-gray-400">Aucun appel</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="flex items-center justify-between mt-4 text-sm text-gray-500">
            <span>Page {{ $page }}</span>
            <div class="flex gap-2">
                <button wire:click="prevPage" @if($page <= 1) disabled @endif
                    class="px-3 py-1 rounded bg-gray-100 hover:bg-gray-200 disabled:opacity-50">
                    ← Précédent
                </button>
                <button wire:click="nextPage" @if(count($calls) < $perPage) disabled @endif
                    class="px-3 py-1 rounded bg-gray-100 hover:bg-gray-200 disabled:opacity-50">
                    Suivant →
                </button>
            </div>
        </div>

    </x-filament::section>
</x-filament-widgets::widget>