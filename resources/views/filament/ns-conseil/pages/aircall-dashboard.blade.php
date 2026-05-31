{{-- resources/views/filament/ns-conseil/pages/aircall-dashboard.blade.php --}}
<x-filament-panels::page>

    @if (!$this->connexionOk)
        <x-filament::section>
            <div class="flex items-center gap-3 text-danger-600">
                <x-heroicon-o-exclamation-triangle class="w-5 h-5" />
                <span class="font-medium">Connexion Aircall impossible.</span>
            </div>
        </x-filament::section>
    @endif

</x-filament-panels::page>