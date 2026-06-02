{{-- resources/views/filament/allopro/pages/rapport-qualite-p7.blade.php --}}
<x-filament-panels::page>

    {{-- ── Sélecteur de période ── --}}
    <div class="flex items-center gap-4 mb-6">
        <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Période :</label>
        <input
            type="month"
            wire:model.live="periode"
            value="{{ $this->periode }}"
            class="rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-sm px-3 py-2 shadow-sm"
        />
        <span class="text-sm text-gray-500">
            Rapport qualité mensuel — CDC AlloPro P7
        </span>
    </div>

    {{-- ── KPI Tickets ── --}}
    @php
        $kpiTickets      = $this->getKpiTickets();
        $kpiNPS          = $this->getKpiNPS();
        $kpiReclam       = $this->getKpiReclamations();
        $kpiFactu        = $this->getKpiFacturation();
        $topArtisans     = $this->getTopArtisans();
        $artisansAlertes = $this->getArtisansAlertes();
        $evolutionNPS    = $this->getEvolutionNPS();

        $slaColor = fn($val, $cible) => $val >= $cible ? 'text-success-600' : 'text-danger-600';
    @endphp

    {{-- Section 1 : Activité Tickets --}}
    <div class="fi-section rounded-xl bg-white dark:bg-gray-900 shadow ring-1 ring-gray-950/5 dark:ring-white/10 p-6 mb-6">
        <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <x-heroicon-o-ticket class="w-5 h-5 text-primary-500" />
            Activité Tickets
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            @foreach([
                ['Tickets reçus',       $kpiTickets['total_reçus'],      null,  null],
                ['Clôturés',            $kpiTickets['clotures'],          null,  null],
                ['Taux décroché',       $kpiTickets['taux_decroché'].'%', 95,    $kpiTickets['taux_decroché']],
                ['SLA respecté',        $kpiTickets['sla_respecte'].'%',  85,    $kpiTickets['sla_respecte']],
                ['Délai moyen',         $kpiTickets['delai_moyen_min'].' min', null, null],
                ['Fiches complètes',    $kpiTickets['fiches_completes'].'%', 100, $kpiTickets['fiches_completes']],
                ['Conversion → RDV',   $kpiTickets['conversion_rdv'].'%', 80,   $kpiTickets['conversion_rdv']],
                ['En cours',           $kpiTickets['en_cours'],           null,  null],
            ] as [$label, $val, $cible, $raw])
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold {{ $cible !== null ? ($raw >= $cible ? 'text-success-600' : 'text-danger-600') : 'text-gray-900 dark:text-white' }}">
                        {{ $val }}
                    </div>
                    <div class="text-xs text-gray-500 mt-1">{{ $label }}</div>
                    @if($cible !== null)
                        <div class="text-xs {{ $raw >= $cible ? 'text-success-500' : 'text-danger-500' }} mt-1">
                            cible : ≥ {{ $cible }}%
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    {{-- Section 2 : NPS & Satisfaction --}}
    <div class="fi-section rounded-xl bg-white dark:bg-gray-900 shadow ring-1 ring-gray-950/5 dark:ring-white/10 p-6 mb-6">
        <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
            <x-heroicon-o-star class="w-5 h-5 text-warning-500" />
            Satisfaction P6 — NPS
            @if($kpiNPS['nps_moyen'])
                <span class="ml-auto text-2xl font-bold {{ $kpiNPS['nps_moyen'] >= 7.5 ? 'text-success-600' : 'text-danger-600' }}">
                    {{ $kpiNPS['nps_moyen'] }} / 10
                    <span class="text-sm font-normal text-gray-500">(cible ≥ 7,5)</span>
                </span>
            @endif
        </h3>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
            @foreach([
                ['Appels J+1',        $kpiNPS['total_appels'],           null,  null],
                ['Taux satisfaction', ($kpiNPS['taux_satisfaction'] ?? '—').'%', 75, $kpiNPS['taux_satisfaction'] ?? 0],
                ['Score NPS net',     ($kpiNPS['score_nps_net'] ?? '—'), null, null],
                ['Feedback transmis', ($kpiNPS['feedback_transmis'] ?? '—').'%', 100, $kpiNPS['feedback_transmis'] ?? 0],
            ] as [$label, $val, $cible, $raw])
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 text-center">
                    <div class="text-2xl font-bold {{ $cible !== null ? ($raw >= $cible ? 'text-success-600' : 'text-danger-600') : 'text-gray-900 dark:text-white' }}">
                        {{ $val }}
                    </div>
                    <div class="text-xs text-gray-500 mt-1">{{ $label }}</div>
                    @if($cible !== null)
                        <div class="text-xs {{ $raw >= $cible ? 'text-success-500' : 'text-danger-500' }} mt-1">
                            cible : ≥ {{ $cible }}%
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Répartition NPS --}}
        @if($kpiNPS['total_appels'] > 0)
            @php
                $total = $kpiNPS['total_appels'];
                $pProm = round($kpiNPS['promoteurs'] / $total * 100);
                $pPass = round($kpiNPS['passifs'] / $total * 100);
                $pDetr = round($kpiNPS['detracteurs'] / $total * 100);
            @endphp
            <div class="mt-2">
                <div class="flex rounded-full overflow-hidden h-4">
                    <div class="bg-success-500 transition-all" style="width: {{ $pProm }}%"
                         title="Promoteurs {{ $kpiNPS['promoteurs'] }} ({{ $pProm }}%)"></div>
                    <div class="bg-warning-400 transition-all" style="width: {{ $pPass }}%"
                         title="Passifs {{ $kpiNPS['passifs'] }} ({{ $pPass }}%)"></div>
                    <div class="bg-danger-500 transition-all" style="width: {{ $pDetr }}%"
                         title="Détracteurs {{ $kpiNPS['detracteurs'] }} ({{ $pDetr }}%)"></div>
                </div>
                <div class="flex gap-6 mt-2 text-xs text-gray-500">
                    <span><span class="inline-block w-3 h-3 rounded-full bg-success-500 mr-1"></span>Promoteurs {{ $kpiNPS['promoteurs'] }} ({{ $pProm }}%)</span>
                    <span><span class="inline-block w-3 h-3 rounded-full bg-warning-400 mr-1"></span>Passifs {{ $kpiNPS['passifs'] }} ({{ $pPass }}%)</span>
                    <span><span class="inline-block w-3 h-3 rounded-full bg-danger-500 mr-1"></span>Détracteurs {{ $kpiNPS['detracteurs'] }} ({{ $pDetr }}%)</span>
                </div>
            </div>
        @endif

        {{-- Évolution NPS 6 mois --}}
        @if(count($evolutionNPS) > 0)
            <div class="mt-4">
                <p class="text-xs text-gray-500 mb-2">Évolution NPS (6 derniers mois)</p>
                <div class="flex items-end gap-2 h-20">
                    @foreach($evolutionNPS as $point)
                        @php $h = $point['moyenne'] > 0 ? min(100, round($point['moyenne'] / 10 * 100)) : 5; @endphp
                        <div class="flex flex-col items-center flex-1">
                            <span class="text-xs text-gray-500">{{ number_format($point['moyenne'], 1) }}</span>
                            <div class="w-full rounded-t transition-all"
                                 style="height: {{ $h }}%; background: {{ $point['moyenne'] >= 7.5 ? '#16a34a' : ($point['moyenne'] >= 6 ? '#d97706' : '#dc2626') }}">
                            </div>
                            <span class="text-xs text-gray-400 mt-1">{{ \Carbon\Carbon::parse($point['mois'])->format('M') }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">

        {{-- Section 3 : Réclamations P8 --}}
        <div class="fi-section rounded-xl bg-white dark:bg-gray-900 shadow ring-1 ring-gray-950/5 dark:ring-white/10 p-6">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-danger-500" />
                Réclamations P8
            </h3>
            <div class="space-y-3">
                @foreach([
                    ['Ouvertes ce mois',      $kpiReclam['total_ouvertes'],    null],
                    ['Clôturées ce mois',     $kpiReclam['cloturees_mois'],    null],
                    ['Actives en cours',      $kpiReclam['actives'],           null],
                    ['🔴 En retard SLA',      $kpiReclam['en_retard'],         0],
                    ['En attente superviseur',$kpiReclam['a_valider'],          0],
                    ['Taux résolution SLA',   $kpiReclam['taux_resolution'].'%', null],
                    ['Délai moyen résolution',$kpiReclam['delai_moyen_jours'].' j', null],
                ] as [$label, $val, $alertSeuil])
                    <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-800 last:border-0">
                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ $label }}</span>
                        <span class="font-semibold {{ $alertSeuil !== null && (int)$val > $alertSeuil ? 'text-danger-600' : 'text-gray-900 dark:text-white' }}">
                            {{ $val }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Section 4 : Facturation --}}
        <div class="fi-section rounded-xl bg-white dark:bg-gray-900 shadow ring-1 ring-gray-950/5 dark:ring-white/10 p-6">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <x-heroicon-o-banknotes class="w-5 h-5 text-success-500" />
                Facturation
            </h3>
            <div class="space-y-3">
                @foreach([
                    ['Factures émises',       $kpiFactu['emises'],                      null],
                    ['Payées',                $kpiFactu['payees'],                      null],
                    ['🔴 En retard',           $kpiFactu['en_retard'],                   0],
                    ['Litigieuses',           $kpiFactu['litigieuses'],                 0],
                    ['CA encaissé (mois)',    number_format($kpiFactu['ca_encaisse'], 0, ',', ' ').' €', null],
                    ['Encours total',         number_format($kpiFactu['encours_total'], 0, ',', ' ').' €', null],
                    ['Taux recouvrement',     $kpiFactu['taux_recouvrement'].'%',       null],
                    ['Délai moyen paiement',  $kpiFactu['delai_moyen_paiement'].' j',   null],
                ] as [$label, $val, $alertSeuil])
                    <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-800 last:border-0">
                        <span class="text-sm text-gray-600 dark:text-gray-400">{{ $label }}</span>
                        <span class="font-semibold {{ $alertSeuil !== null && (int)filter_var($val, FILTER_SANITIZE_NUMBER_INT) > $alertSeuil ? 'text-danger-600' : 'text-gray-900 dark:text-white' }}">
                            {{ $val }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Section 5 : Classement artisans --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

        <div class="fi-section rounded-xl bg-white dark:bg-gray-900 shadow ring-1 ring-gray-950/5 dark:ring-white/10 p-6">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <x-heroicon-o-trophy class="w-5 h-5 text-warning-500" />
                Top Artisans — NPS
            </h3>
            @forelse($topArtisans as $i => $artisan)
                <div class="flex items-center gap-3 py-2 border-b border-gray-100 dark:border-gray-800 last:border-0">
                    <span class="text-lg font-bold text-gray-400 w-6">{{ $i + 1 }}</span>
                    <div class="flex-1">
                        <div class="font-medium text-sm text-gray-900 dark:text-white">
                            {{ $artisan->nom_complet }}
                        </div>
                        <div class="text-xs text-gray-400">
                            {{ $artisan->corps_de_metier?->label() }} — {{ $artisan->nb_rapports }} interventions
                        </div>
                    </div>
                    <span class="font-bold {{ $artisan->nps_moyen >= 8 ? 'text-success-600' : ($artisan->nps_moyen >= 6 ? 'text-warning-600' : 'text-danger-600') }}">
                        {{ number_format($artisan->nps_moyen, 1) }}/10
                    </span>
                </div>
            @empty
                <p class="text-sm text-gray-400 text-center py-4">Aucune donnée pour cette période</p>
            @endforelse
        </div>

        <div class="fi-section rounded-xl bg-white dark:bg-gray-900 shadow ring-1 ring-gray-950/5 dark:ring-white/10 p-6">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2">
                <x-heroicon-o-exclamation-circle class="w-5 h-5 text-danger-500" />
                Artisans en alerte qualité
            </h3>
            @forelse($artisansAlertes as $artisan)
                <div class="flex items-center gap-3 py-2 border-b border-gray-100 dark:border-gray-800 last:border-0">
                    <div class="flex-1">
                        <div class="font-medium text-sm text-gray-900 dark:text-white">
                            {{ $artisan->nom_complet }}
                        </div>
                        <div class="text-xs text-gray-400">
                            {{ $artisan->corps_de_metier?->label() }}
                            @if($artisan->nb_reclamations > 0)
                                — {{ $artisan->nb_reclamations }} réclamation(s)
                            @endif
                        </div>
                    </div>
                    <span class="font-bold text-danger-600">
                        @if($artisan->nps_moyen)
                            {{ number_format($artisan->nps_moyen, 1) }}/10
                        @else
                            —
                        @endif
                    </span>
                    <span class="text-xs px-2 py-1 rounded-full bg-danger-100 text-danger-700">⚠️ Plan d'action</span>
                </div>
            @empty
                <p class="text-sm text-success-600 text-center py-4">✅ Aucun artisan en alerte ce mois</p>
            @endforelse
        </div>
    </div>

</x-filament-panels::page>
