@if ($getRecord()->aircall_call_id && $getState())
    <div x-data="{ url: null, loading: false, error: false, open: false }" class="flex flex-col gap-1">
        <button x-on:click="
                    if (open) { open = false; url = null; return; }
                    loading = true;
                    fetch('/ns-conseil/aircall/recording/{{ $getRecord()->aircall_call_id }}')
                        .then(r => r.json())
                        .then(d => {
                            url = d.url;
                            loading = false;
                            open = true;
                            $nextTick(() => {
                                const audio = $el.parentElement.querySelector('audio');
                                if (audio) {
                                    audio.src = d.url;
                                    audio.load();
                                    audio.play();
                                }
                            });
                        })
                        .catch(() => { error = true; loading = false; })
                "
            class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-md bg-primary-50 text-primary-700 hover:bg-primary-100 transition w-fit">
            <x-heroicon-o-play-circle class="w-4 h-4" />
            <span x-text="loading ? 'Chargement...' : (open ? '⏹ Fermer' : '▶ Écouter')">▶ Écouter</span>
        </button>

        <div x-show="open" x-cloak>
            <audio controls preload="none" class="w-48 h-8">
                Votre navigateur ne supporte pas la lecture audio.
            </audio>
        </div>

        <span x-show="error" x-cloak class="text-xs text-danger-500">
            Enregistrement indisponible
        </span>
    </div>
@else
    <span class="text-gray-400 text-xs">—</span>
@endif