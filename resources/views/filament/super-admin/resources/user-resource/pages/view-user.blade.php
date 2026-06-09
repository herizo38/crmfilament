{{-- resources/views/filament/super-admin/resources/user-resource/pages/view-user.blade.php --}}
<x-filament-panels::page>

@push('styles')
<style>
/* ═══════════════════════════════════════════════════════════════
   VIEW USER — Panneau assignation prospects
════════════════════════════════════════════════════════════════ */
.vu-wrap * { box-sizing: border-box; }

/* ─── KPI cards ─── */
.vu-kpis {
    display: grid; grid-template-columns: repeat(6, 1fr); gap: 0.875rem;
    margin-bottom: 1.5rem;
}
@media (max-width: 1200px) { .vu-kpis { grid-template-columns: repeat(3, 1fr); } }
@media (max-width: 640px)  { .vu-kpis { grid-template-columns: repeat(2, 1fr); } }

.vu-kpi {
    background: white; border: 1px solid rgb(229 231 235);
    border-radius: 0.75rem; padding: 1rem 1.25rem;
    display: flex; flex-direction: column; gap: 0.25rem;
}
.dark .vu-kpi { background: rgb(31 41 55); border-color: rgb(55 65 81); }
.vu-kpi-label { font-size: 0.6875rem; text-transform: uppercase; font-weight: 600; color: rgb(107 114 128); letter-spacing: 0.05em; }
.vu-kpi-value { font-size: 1.75rem; font-weight: 800; line-height: 1; }
.vu-kpi-sub   { font-size: 0.75rem; color: rgb(107 114 128); }

/* ─── Section titre ─── */
.vu-section-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 1rem; padding-bottom: 0.75rem;
    border-bottom: 2px solid rgb(229 231 235);
}
.dark .vu-section-header { border-bottom-color: rgb(55 65 81); }
.vu-section-title { font-size: 1rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem; }
.vu-section-badge {
    display: inline-flex; align-items: center;
    padding: 0.125rem 0.625rem; border-radius: 9999px;
    font-size: 0.75rem; font-weight: 700;
    background: rgb(219 234 254); color: rgb(30 64 175);
}
.dark .vu-section-badge { background: rgb(23 37 84 / 0.4); color: rgb(147 197 253); }

/* ─── Tableau ─── */
.vu-table-wrap { border: 1px solid rgb(229 231 235); border-radius: 0.75rem; overflow: hidden; margin-bottom: 2rem; }
.dark .vu-table-wrap { border-color: rgb(55 65 81); }
.vu-table { width: 100%; border-collapse: collapse; }
.vu-th {
    background: rgb(249 250 251); padding: 0.625rem 1rem;
    font-size: 0.6875rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.05em; color: rgb(107 114 128); text-align: left;
    border-bottom: 1px solid rgb(229 231 235);
}
.dark .vu-th { background: rgb(31 41 55); border-bottom-color: rgb(55 65 81); }
.vu-td {
    padding: 0.75rem 1rem; font-size: 0.8125rem;
    border-bottom: 1px solid rgb(243 244 246); vertical-align: middle;
}
.dark .vu-td { border-bottom-color: rgb(31 41 55); }
.vu-tr:last-child .vu-td { border-bottom: none; }
.vu-tr:hover .vu-td { background: rgb(249 250 251); }
.dark .vu-tr:hover .vu-td { background: rgb(31 41 55 / 0.5); }

.vu-badge { display: inline-flex; align-items: center; padding: 0.125rem 0.5rem; border-radius: 0.25rem; font-size: 0.6875rem; font-weight: 700; }
.badge-rpc       { background: rgb(204 251 241); color: rgb(17 94 89); }
.badge-rp        { background: rgb(220 252 231); color: rgb(20 83 45); }
.badge-std_joint { background: rgb(219 234 254); color: rgb(30 64 175); }
.badge-ac        { background: rgb(243 244 246); color: rgb(55 65 81); border: 1px solid rgb(229 231 235); }
.badge-std_nr    { background: rgb(243 244 246); color: rgb(107 114 128); }
.badge-cse_nr    { background: rgb(255 237 213); color: rgb(154 52 18); }
.badge-ko        { background: rgb(254 226 226); color: rgb(153 27 27); }
.badge-qf        { background: rgb(220 252 231); color: rgb(20 83 45); border: 1px solid rgb(134 239 172); }

.vu-btn-desassigner {
    padding: 0.25rem 0.625rem;
    background: rgb(254 242 242); color: rgb(153 27 27);
    border: 1px solid rgb(252 165 165); border-radius: 0.375rem;
    font-size: 0.6875rem; font-weight: 600; cursor: pointer; white-space: nowrap;
    transition: background 0.15s;
}
.vu-btn-desassigner:hover { background: rgb(254 226 226); }
.vu-nom-link { font-weight: 700; color: rgb(37 99 235); text-decoration: none; }
.vu-nom-link:hover { text-decoration: underline; }

/* ─── Modal overlay ─── */
.vu-assign-overlay {
    position: fixed; inset: 0; z-index: 50;
    background: rgb(0 0 0 / 0.45); backdrop-filter: blur(2px);
    display: flex; align-items: flex-end; justify-content: center;
}
@media (min-width: 768px) { .vu-assign-overlay { align-items: center; } }

.vu-assign-panel {
    background: white; border-radius: 1.25rem 1.25rem 0 0;
    width: 100%; max-height: 92vh;
    display: flex; flex-direction: column;
    box-shadow: 0 -20px 60px rgb(0 0 0 / 0.18);
}
.dark .vu-assign-panel { background: rgb(17 24 39); }
@media (min-width: 768px) {
    .vu-assign-panel { border-radius: 1.25rem; max-width: 980px; max-height: 86vh; }
}

.vu-handle { width: 3rem; height: 0.25rem; background: rgb(209 213 219); border-radius: 9999px; margin: 0.75rem auto; flex-shrink: 0; }
.dark .vu-handle { background: rgb(75 85 99); }

.vu-panel-header {
    display: flex; align-items: flex-start; justify-content: space-between;
    padding: 0 1.5rem 1rem; flex-shrink: 0;
    border-bottom: 1px solid rgb(229 231 235);
}
.dark .vu-panel-header { border-bottom-color: rgb(55 65 81); }
.vu-panel-title { font-size: 1.0625rem; font-weight: 800; }
.vu-panel-sub { font-size: 0.8125rem; color: rgb(107 114 128); margin-top: 0.25rem; }

.vu-close-btn {
    width: 2rem; height: 2rem; border-radius: 9999px;
    background: rgb(243 244 246); border: none; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    color: rgb(107 114 128); transition: background 0.15s; flex-shrink: 0;
}
.dark .vu-close-btn { background: rgb(55 65 71); color: rgb(156 163 175); }
.vu-close-btn:hover { background: rgb(229 231 235); }

/* Filtres */
.vu-filters {
    padding: 0.875rem 1.5rem; background: rgb(249 250 251);
    border-bottom: 1px solid rgb(229 231 235);
    display: flex; gap: 0.625rem; flex-wrap: wrap; align-items: center; flex-shrink: 0;
}
.dark .vu-filters { background: rgb(31 41 55); border-bottom-color: rgb(55 65 81); }
.vu-filter-input {
    padding: 0.375rem 0.75rem; border: 1px solid rgb(209 213 219);
    border-radius: 0.5rem; font-size: 0.8125rem; background: white;
    color: inherit; outline: none; min-width: 0; flex: 1;
}
.dark .vu-filter-input { background: rgb(17 24 39); border-color: rgb(55 65 81); }
.vu-filter-input:focus { border-color: rgb(59 130 246); box-shadow: 0 0 0 2px rgb(59 130 246 / 0.2); }
.vu-filter-select {
    padding: 0.375rem 0.75rem; border: 1px solid rgb(209 213 219);
    border-radius: 0.5rem; font-size: 0.8125rem;
    color: inherit; outline: none; cursor: pointer;
}
.dark .vu-filter-select { background: rgb(17 24 39); border-color: rgb(55 65 81); }

/* Barre de sélection */
.vu-select-bar {
    display: flex; align-items: center; justify-content: space-between;
    padding: 0.625rem 1.5rem; border-bottom: 1px solid rgb(229 231 235); flex-shrink: 0;
}
.dark .vu-select-bar { border-bottom-color: rgb(55 65 81); }
.vu-select-info { font-size: 0.8125rem; color: rgb(107 114 128); }
.vu-select-count { font-weight: 700; color: rgb(37 99 235); }

.vu-btn-sm {
    padding: 0.25rem 0.75rem; border-radius: 0.375rem;
    font-size: 0.75rem; font-weight: 600; cursor: pointer; border: 1px solid;
    transition: background 0.15s;
}
.vu-btn-blue { background: rgb(219 234 254); color: rgb(30 64 175); border-color: rgb(147 197 253); }
.vu-btn-blue:hover { background: rgb(191 219 254); }
.vu-btn-gray { background: rgb(243 244 246); color: rgb(55 65 81); border-color: rgb(209 213 219); }
.vu-btn-gray:hover { background: rgb(229 231 235); }
.dark .vu-btn-gray { background: rgb(55 65 81); color: rgb(209 213 219); border-color: rgb(75 85 99); }

/* Liste prospects */
.vu-prospect-list { flex: 1; overflow-y: auto; }
.vu-prospect-item {
    display: flex; align-items: stretch;
    border-bottom: 1px solid rgb(243 244 246);
    cursor: pointer; transition: background 0.1s; user-select: none;
}
.dark .vu-prospect-item { border-bottom-color: rgb(31 41 55); }
.vu-prospect-item:hover { background: rgb(249 250 251); }
.dark .vu-prospect-item:hover { background: rgb(31 41 55); }
.vu-prospect-item.selected { background: rgb(239 246 255); }
.dark .vu-prospect-item.selected { background: rgb(23 37 84 / 0.25); }

.vu-color-bar { width: 4px; flex-shrink: 0; }
.bar-rpc       { background: rgb(20 184 166); }
.bar-rp        { background: rgb(34 197 94); }
.bar-std_joint { background: rgb(59 130 246); }
.bar-ac        { background: rgb(156 163 175); }
.bar-std_nr    { background: rgb(209 213 219); }
.bar-cse_nr    { background: rgb(249 115 22); }

.vu-checkbox-wrap { display: flex; align-items: center; justify-content: center; width: 3rem; flex-shrink: 0; }
.vu-checkbox {
    width: 1.125rem; height: 1.125rem; border-radius: 0.25rem;
    border: 2px solid rgb(209 213 219); background: white;
    display: flex; align-items: center; justify-content: center;
    transition: all 0.15s; flex-shrink: 0;
}
.dark .vu-checkbox { background: rgb(31 41 55); border-color: rgb(75 85 99); }
.vu-prospect-item.selected .vu-checkbox { background: rgb(37 99 235); border-color: rgb(37 99 235); }

.vu-item-content { flex: 1; padding: 0.75rem 1rem 0.75rem 0; min-width: 0; }
.vu-item-top { display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 0.25rem; }
.vu-item-nom { font-size: 0.9375rem; font-weight: 700; }
.vu-item-mid { display: flex; gap: 0.75rem; flex-wrap: wrap; font-size: 0.75rem; color: rgb(107 114 128); }
.vu-item-sub { font-size: 0.6875rem; color: rgb(156 163 175); margin-top: 0.125rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.vu-engagement { display: flex; align-items: center; padding: 0 1rem; font-size: 0.875rem; flex-shrink: 0; }

/* Footer panel */
.vu-panel-footer {
    padding: 1rem 1.5rem; border-top: 1px solid rgb(229 231 235);
    display: flex; align-items: center; justify-content: space-between;
    flex-shrink: 0; gap: 1rem; flex-wrap: wrap;
}
.dark .vu-panel-footer { border-top-color: rgb(55 65 81); }
.vu-btn-assign {
    padding: 0.625rem 1.5rem; background: rgb(34 197 94); color: white;
    font-weight: 700; font-size: 0.875rem; border-radius: 0.5rem;
    border: none; cursor: pointer; transition: background 0.15s;
}
.vu-btn-assign:hover { background: rgb(22 163 74); }
.vu-btn-assign:disabled { opacity: 0.4; cursor: not-allowed; }
.vu-btn-cancel {
    padding: 0.625rem 1.25rem; background: rgb(243 244 246); color: rgb(55 65 81);
    font-weight: 600; font-size: 0.875rem; border-radius: 0.5rem;
    border: 1px solid rgb(229 231 235); cursor: pointer; transition: background 0.15s;
}
.vu-btn-cancel:hover { background: rgb(229 231 235); }
.dark .vu-btn-cancel { background: rgb(55 65 81); color: rgb(209 213 219); border-color: rgb(75 85 99); }

.vu-empty { padding: 3rem; text-align: center; color: rgb(156 163 175); }
.vu-empty-icon { font-size: 2.5rem; margin-bottom: 0.75rem; }
.vu-empty-text { font-size: 0.9375rem; font-weight: 600; color: rgb(107 114 128); }
.vu-empty-sub  { font-size: 0.8125rem; margin-top: 0.25rem; }

.vu-limit-bar { height: 0.25rem; border-radius: 9999px; background: rgb(229 231 235); overflow: hidden; flex: 1; max-width: 8rem; }
.vu-limit-fill { height: 100%; border-radius: 9999px; background: rgb(59 130 246); transition: width 0.3s; }
</style>
@endpush

{{-- ══════════════════════════════════════════════════════════
     Les variables viennent de getViewData() dans ViewUser.php
     $hasProspectRole, $kpis, $prospectsAssignes, $nbNonAssignes,
     $fieldLabel, $disponibles, $departementsDispos, $typesDispos
     sont injectées automatiquement par Filament dans la vue.
     Les propriétés Livewire ($showAssignPanel, $selectedProspectIds,
     $assignLimit, $filter*) restent accessibles via $this.
════════════════════════════════════════════════════════════ --}}

{{-- ── Infolist Filament standard ── --}}
{{ $this->infolist }}

{{-- ── Section Prospects ── --}}
@if($hasProspectRole)
<div class="vu-wrap" style="margin-top: 1.5rem;">

    {{-- KPI cards --}}
    <div class="vu-kpis">
        <div class="vu-kpi">
            <div class="vu-kpi-label">Total assignés</div>
            <div class="vu-kpi-value" style="color:rgb(37 99 235);">{{ $kpis['total'] }}</div>
            <div class="vu-kpi-sub">prospects</div>
        </div>
        <div class="vu-kpi">
            <div class="vu-kpi-label">Actifs</div>
            <div class="vu-kpi-value" style="color:rgb(59 130 246);">{{ $kpis['actifs'] }}</div>
            <div class="vu-kpi-sub">en cours</div>
        </div>
        <div class="vu-kpi">
            <div class="vu-kpi-label">RP + RPC</div>
            <div class="vu-kpi-value" style="color:rgb(22 163 74);">{{ $kpis['rp_rpc'] }}</div>
            <div class="vu-kpi-sub">réponses positives</div>
        </div>
        <div class="vu-kpi">
            <div class="vu-kpi-label">Qualifiés QF</div>
            <div class="vu-kpi-value" style="color:rgb(5 150 105);">{{ $kpis['qualifies'] }}</div>
            <div class="vu-kpi-sub">taux {{ $kpis['taux_qf'] }}%</div>
        </div>
        <div class="vu-kpi">
            <div class="vu-kpi-label">KO</div>
            <div class="vu-kpi-value" style="color:rgb(239 68 68);">{{ $kpis['ko'] }}</div>
            <div class="vu-kpi-sub">refus</div>
        </div>
        <div class="vu-kpi">
            <div class="vu-kpi-label">Rappels retard</div>
            <div class="vu-kpi-value" style="color:{{ $kpis['retards'] > 0 ? 'rgb(239 68 68)' : 'rgb(107 114 128)' }};">
                {{ $kpis['retards'] }}
            </div>
            <div class="vu-kpi-sub">en retard</div>
        </div>
    </div>

    {{-- Header section --}}
    <div class="vu-section-header">
        <div class="vu-section-title">
            <svg style="width:1.125rem;height:1.125rem;color:rgb(37 99 235);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
            </svg>
            Prospects assignés
            <span class="vu-section-badge">{{ count($prospectsAssignes) }}</span>
        </div>
        <div style="display:flex; gap:0.625rem; align-items:center;">
            @if($nbNonAssignes > 0)
                <span style="font-size:0.8125rem; color:rgb(107 114 128);">
                    {{ $nbNonAssignes }} sans {{ $fieldLabel }}
                </span>
            @endif
            <button
                wire:click="toggleAssignPanel"
                style="display:inline-flex; align-items:center; gap:0.375rem;
                       padding:0.5rem 1rem; background:rgb(37 99 235); color:white;
                       border-radius:0.5rem; border:none; font-size:0.8125rem;
                       font-weight:700; cursor:pointer;">
                <svg style="width:1rem;height:1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Assigner des prospects
            </button>
        </div>
    </div>

    {{-- Tableau prospects assignés --}}
    @if(count($prospectsAssignes) > 0)
        <div class="vu-table-wrap">
            <table class="vu-table">
                <thead>
                    <tr>
                        <th class="vu-th">Prospect</th>
                        <th class="vu-th">Statut</th>
                        <th class="vu-th">Téléphone</th>
                        <th class="vu-th">Localisation</th>
                        <th class="vu-th">Type</th>
                        <th class="vu-th">Engagement</th>
                        <th class="vu-th">Rappel</th>
                        <th class="vu-th"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($prospectsAssignes as $p)
                        <tr class="vu-tr">
                            <td class="vu-td">
                                <span class="vu-nom-link">{{ $p['nom'] }}</span>
                            </td>
                            <td class="vu-td">
                                <span class="vu-badge badge-{{ $p['statut'] }}">
                                    {{ $p['statut_label'] }}
                                </span>
                            </td>
                            <td class="vu-td" style="font-family:monospace;">
                                {{ $p['telephone'] ?? '—' }}
                            </td>
                            <td class="vu-td">
                                {{ collect([$p['ville'], $p['departement'] ? '('.$p['departement'].')' : null])->filter()->implode(' ') ?: '—' }}
                            </td>
                            <td class="vu-td" style="font-size:0.75rem; color:rgb(107 114 128);">
                                {{ ($p['type_pressenti'] ?? '') !== 'Non défini' ? ($p['type_pressenti'] ?? '—') : '—' }}
                            </td>
                            <td class="vu-td" style="text-align:center;">
                                {{ $p['taux_engagement'] }}
                            </td>
                            <td class="vu-td">
                                @if($p['rappel_at'])
                                    <span style="font-size:0.75rem; {{ $p['rappel_retard'] ? 'color:rgb(239 68 68); font-weight:700;' : 'color:rgb(107 114 128);' }}">
                                        {{ $p['rappel_at'] }}
                                        @if($p['rappel_retard']) ⚠ @endif
                                    </span>
                                @else
                                    <span style="color:rgb(209 213 219);">—</span>
                                @endif
                            </td>
                            <td class="vu-td">
                                <button
                                    wire:click="desassignerProspect({{ $p['id'] }})"
                                    wire:confirm="Désassigner ce prospect ?"
                                    class="vu-btn-desassigner">
                                    Désassigner
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="vu-empty" style="border:1px solid rgb(229 231 235); border-radius:0.75rem; margin-bottom:2rem;">
            <div class="vu-empty-icon">📭</div>
            <div class="vu-empty-text">Aucun prospect assigné</div>
            <div class="vu-empty-sub">Cliquez sur "Assigner des prospects" pour commencer.</div>
        </div>
    @endif

</div>
@endif

{{-- ════════════════════════════════════════════════════════════
     PANNEAU MODAL D'ASSIGNATION
     $this->showAssignPanel est la propriété Livewire directe
════════════════════════════════════════════════════════════ --}}
@if($this->showAssignPanel)
@php
    // Ces variables viennent de getViewData() — recalculées à chaque render
    $nbDisponibles  = count($disponibles);
    $nbSelectionnes = count($this->selectedProspectIds);
    $limitePct      = min(100, $this->assignLimit > 0
        ? round(($nbSelectionnes / $this->assignLimit) * 100)
        : 0);
@endphp

<div class="vu-assign-overlay" wire:click.self="closeAssignPanel">
    <div class="vu-assign-panel">
        <div class="vu-handle"></div>

        {{-- Header --}}
        <div class="vu-panel-header">
            <div>
                <div class="vu-panel-title">
                    Assigner des prospects à {{ $this->record->nom_complet }}
                </div>
                <div class="vu-panel-sub">
                    {{ $nbNonAssignes }} prospect(s) sans {{ $fieldLabel }} disponibles ·
                    Sélectionnez jusqu'à {{ $this->assignLimit }}
                </div>
            </div>
            <button wire:click="closeAssignPanel" class="vu-close-btn">
                <svg style="width:1rem;height:1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Filtres --}}
        <div class="vu-filters">
            <input
                type="text"
                wire:model.live.debounce.400ms="filterSearch"
                placeholder="🔍 Nom, ville, téléphone, SIRET..."
                class="vu-filter-input"
                style="min-width:200px;">

            <select wire:model.live="filterStatut" class="vu-filter-select">
                <option value="">Tous les statuts</option>
                <option value="ac">À contacter (AC)</option>
                <option value="std_nr">STD-NR</option>
                <option value="std_joint">STD-Joint</option>
                <option value="cse_nr">CSE-NR</option>
                <option value="rp">RP</option>
                <option value="rpc">RPC</option>
            </select>

            @if(count($departementsDispos) > 0)
                <select wire:model.live="filterDepartement" class="vu-filter-select">
                    <option value="">Tous les dép.</option>
                    @foreach($departementsDispos as $dep)
                        <option value="{{ $dep }}">{{ $dep }}</option>
                    @endforeach
                </select>
            @endif

            @if(count($typesDispos) > 0)
                <select wire:model.live="filterType" class="vu-filter-select">
                    <option value="">Tous les types</option>
                    @foreach($typesDispos as $type)
                        <option value="{{ $type }}">{{ $type }}</option>
                    @endforeach
                </select>
            @endif
        </div>

        {{-- Barre sélection --}}
        <div class="vu-select-bar">
            <div class="vu-select-info">
                <span class="vu-select-count">{{ $nbSelectionnes }}</span>
                / {{ $this->assignLimit }} sélectionné(s) ·
                {{ $nbDisponibles }} affiché(s)
            </div>
            <div style="display:flex; align-items:center; gap:0.75rem; flex-wrap:wrap;">
                <div class="vu-limit-bar">
                    <div class="vu-limit-fill"
                         style="width:{{ $limitePct }}%;
                                background:{{ $limitePct >= 100 ? 'rgb(239 68 68)' : 'rgb(59 130 246)' }};"></div>
                </div>
                <div style="display:flex; gap:0.5rem;">
                    <button wire:click="selectAll" class="vu-btn-sm vu-btn-blue">
                        Sélectionner {{ min($this->assignLimit, $nbDisponibles) }}
                    </button>
                    <button wire:click="clearSelection" class="vu-btn-sm vu-btn-gray">
                        Effacer
                    </button>
                </div>
            </div>
        </div>

        {{-- Liste --}}
        <div class="vu-prospect-list">
            @forelse($disponibles as $p)
                <div
                    wire:click="toggleProspect({{ $p['id'] }})"
                    class="vu-prospect-item {{ $p['selected'] ? 'selected' : '' }}"
                    wire:key="ap-{{ $p['id'] }}">

                    <div class="vu-checkbox-wrap">
                        <div class="vu-checkbox">
                            @if($p['selected'])
                                <svg style="width:0.75rem;height:0.75rem;color:white;" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                            @endif
                        </div>
                    </div>

                    <div class="vu-color-bar bar-{{ $p['statut'] }}"></div>

                    <div class="vu-item-content">
                        <div class="vu-item-top">
                            <span class="vu-item-nom">{{ $p['nom'] }}</span>
                            <span class="vu-badge badge-{{ $p['statut'] }}">{{ $p['statut_label'] }}</span>
                        </div>
                        <div class="vu-item-mid">
                            @if($p['telephone'])
                                <span>📞 {{ $p['telephone'] }}</span>
                            @endif
                            @if($p['ville'] || $p['departement'])
                                <span>📍 {{ $p['ville'] }}{{ $p['departement'] ? ' ('.$p['departement'].')' : '' }}</span>
                            @endif
                            @if(!empty($p['type_pressenti']) && $p['type_pressenti'] !== 'Non défini')
                                <span>🏢 {{ $p['type_pressenti'] }}</span>
                            @endif
                            @if(!empty($p['nb_salaries']))
                                <span>👥 {{ $p['nb_salaries'] }} sal.</span>
                            @endif
                        </div>
                        @if(!empty($p['interlocuteur']) || !empty($p['description']))
                            <div class="vu-item-sub">
                                {{ $p['interlocuteur'] ?? $p['description'] }}
                            </div>
                        @endif
                    </div>

                    <div class="vu-engagement">{{ $p['taux_engagement'] }}</div>
                </div>
            @empty
                <div class="vu-empty">
                    <div class="vu-empty-icon">
                        {{ ($this->filterSearch || $this->filterStatut || $this->filterDepartement || $this->filterType) ? '🔍' : '🎉' }}
                    </div>
                    <div class="vu-empty-text">
                        @if($this->filterSearch || $this->filterStatut || $this->filterDepartement || $this->filterType)
                            Aucun prospect ne correspond à vos filtres
                        @else
                            Tous les prospects sont déjà assignés !
                        @endif
                    </div>
                    @if($this->filterSearch || $this->filterStatut || $this->filterDepartement || $this->filterType)
                        <div class="vu-empty-sub">Modifiez ou supprimez vos filtres.</div>
                    @endif
                </div>
            @endforelse
        </div>

        {{-- Footer --}}
        <div class="vu-panel-footer">
            <div style="font-size:0.8125rem; color:rgb(107 114 128);">
                @if($nbSelectionnes > 0)
                    <span style="color:rgb(37 99 235); font-weight:700;">
                        {{ $nbSelectionnes }} prospect(s)
                    </span>
                    seront assignés à <strong>{{ $this->record->nom_complet }}</strong>
                    comme {{ $fieldLabel }}.
                @else
                    Sélectionnez des prospects dans la liste ci-dessus.
                @endif
            </div>
            <div style="display:flex; gap:0.75rem; align-items:center;">
                <button wire:click="closeAssignPanel" class="vu-btn-cancel">Annuler</button>
                <button
                    wire:click="assignerSelection"
                    class="vu-btn-assign"
                    @if($nbSelectionnes === 0) disabled @endif>
                    ✓ Assigner{{ $nbSelectionnes > 0 ? ' ' . $nbSelectionnes . ' prospect(s)' : '' }}
                </button>
            </div>
        </div>

    </div>
</div>
@endif

</x-filament-panels::page>
