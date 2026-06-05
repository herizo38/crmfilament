{{-- resources/views/filament/ns-conseil/pages/phoning-workflow.blade.php --}}
<x-filament-panels::page>

@push('styles')
<style>
/* ═══════════════════════════════════════════════════════════════════
   PHONING WORKFLOW — styles custom
   Utilise les variables CSS Filament pour compatibilité dark/light.
════════════════════════════════════════════════════════════════════ */

/* ─── Reset boîte ─── */
.pw-wrap * { box-sizing: border-box; }

/* ─── Layout global ─── */
.pw-wrap { padding: 0; }

/* ─── Barre de supervision ─── */
.pw-supervision {
    display: flex; align-items: center; gap: 0.75rem;
    padding: 0.5rem 1rem;
    background: rgb(239 246 255);
    border-bottom: 1px solid rgb(219 234 254);
    font-size: 0.8125rem; color: rgb(55 65 81);
}
.dark .pw-supervision {
    background: rgb(23 37 84 / 0.2);
    border-bottom-color: rgb(30 58 138 / 0.3);
    color: rgb(147 197 253);
}
.pw-supervision select {
    padding: 0.25rem 0.5rem;
    border: 1px solid rgb(191 219 254);
    border-radius: 0.375rem;
    font-size: 0.8125rem;
    background: white;
    color: rgb(30 64 175);
}
.dark .pw-supervision select {
    background: rgb(23 37 84 / 0.4);
    color: rgb(147 197 253);
    border-color: rgb(30 58 138);
}

/* ─── Barre contact ─── */
.pw-contact-bar {
    display: flex; align-items: center; justify-content: space-between;
    padding: 0.875rem 1.25rem;
    background: white;
    border-bottom: 1px solid rgb(229 231 235);
}
.dark .pw-contact-bar {
    background: rgb(17 24 39);
    border-bottom-color: rgb(31 41 55);
}
.pw-contact-avatar {
    width: 2.25rem; height: 2.25rem;
    border-radius: 0.5rem;
    background: rgb(59 130 246);
    color: white; font-weight: 700; font-size: 0.875rem;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.pw-contact-name { font-size: 1rem; font-weight: 700; margin: 0; }
.pw-contact-sub  { font-size: 0.75rem; color: rgb(107 114 128); margin: 0; }
.pw-badge-ac {
    display: inline-flex; align-items: center;
    padding: 0.125rem 0.5rem;
    background: rgb(239 246 255); color: rgb(37 99 235);
    border: 1px solid rgb(191 219 254);
    border-radius: 0.25rem; font-size: 0.6875rem; font-weight: 700;
}
.pw-timer {
    text-align: right;
}
.pw-timer-label { font-size: 0.6875rem; color: rgb(107 114 128); text-transform: uppercase; }
.pw-timer-value { font-size: 1.25rem; font-family: monospace; font-weight: 700; letter-spacing: 0.05em; }
.pw-btn-demarrer {
    display: inline-flex; align-items: center; gap: 0.5rem;
    padding: 0.5rem 1.25rem;
    background: rgb(34 197 94);
    color: white; font-weight: 700; font-size: 0.875rem;
    border-radius: 0.5rem; border: none; cursor: pointer;
    white-space: nowrap;
    transition: background 0.15s;
}
.pw-btn-demarrer:hover { background: rgb(22 163 74); }
.pw-en-file {
    display: flex; flex-direction: column; align-items: center;
    background: rgb(249 250 251); border: 1px solid rgb(229 231 235);
    border-radius: 0.5rem; padding: 0.5rem 0.875rem;
    min-width: 3.5rem; text-align: center;
}
.dark .pw-en-file { background: rgb(31 41 55); border-color: rgb(55 65 81); }
.pw-en-file-num { font-size: 1.25rem; font-weight: 700; }
.pw-en-file-label { font-size: 0.625rem; text-transform: uppercase; color: rgb(107 114 128); }

/* ─── Layout principal 2 colonnes ─── */
.pw-body {
    display: grid;
    grid-template-columns: 1fr 280px;
    gap: 0;
    height: calc(100vh - 160px);
    overflow: hidden;
}
@media (max-width: 1024px) {
    .pw-body { grid-template-columns: 1fr; height: auto; overflow: visible; }
}

/* ─── Colonne gauche (script + infos) ─── */
.pw-left {
    display: flex; flex-direction: column;
    border-right: 1px solid rgb(229 231 235);
    overflow: hidden;
}
.dark .pw-left { border-right-color: rgb(31 41 55); }

/* ─── Onglets script ─── */
.pw-tabs {
    display: flex; border-bottom: 1px solid rgb(229 231 235);
    background: white; flex-shrink: 0;
}
.dark .pw-tabs {
    background: rgb(17 24 39);
    border-bottom-color: rgb(31 41 55);
}
.pw-tab {
    padding: 0.75rem 1.25rem;
    font-size: 0.875rem; font-weight: 500;
    color: rgb(107 114 128);
    border-bottom: 2px solid transparent;
    background: none; border-top: none; border-left: none; border-right: none;
    cursor: pointer; white-space: nowrap;
    transition: color 0.15s;
}
.pw-tab:hover { color: rgb(55 65 81); }
.dark .pw-tab:hover { color: rgb(209 213 219); }
.pw-tab-active {
    color: rgb(37 99 235) !important;
    border-bottom-color: rgb(37 99 235) !important;
}
.dark .pw-tab-active {
    color: rgb(96 165 250) !important;
    border-bottom-color: rgb(96 165 250) !important;
}

/* ─── Zone script ─── */
.pw-script-area {
    flex: 1; overflow-y: auto; padding: 1.25rem;
    background: rgb(249 250 251);
}
.dark .pw-script-area { background: rgb(17 24 39 / 0.5); }

.pw-script-empty {
    border: 1px solid rgb(191 219 254);
    border-left: 3px solid rgb(59 130 246);
    border-radius: 0.375rem;
    padding: 1rem 1.25rem;
    background: rgb(239 246 255);
    color: rgb(29 78 216); font-size: 0.875rem; font-style: italic;
}

/* Script text */
.pw-script-text {
    line-height: 1.8; font-size: 0.9375rem;
    color: rgb(31 41 55);
    white-space: pre-wrap;
}
.dark .pw-script-text { color: rgb(229 231 235); }
.pw-script-text strong { font-weight: 700; }

/* Conseil tip */
.pw-conseil {
    margin-top: 1rem;
    background: rgb(255 251 235);
    border-left: 4px solid rgb(251 191 36);
    border-radius: 0.375rem;
    padding: 0.75rem 1rem;
    font-size: 0.8125rem; color: rgb(146 64 14);
    display: flex; gap: 0.5rem; align-items: flex-start;
}
.dark .pw-conseil { background: rgb(120 53 15 / 0.15); color: rgb(253 186 116); }

/* Objections */
.pw-objection {
    border: 1px solid rgb(254 202 202);
    border-left: 3px solid rgb(239 68 68);
    border-radius: 0.375rem;
    padding: 0.75rem 1rem;
    margin-bottom: 0.75rem;
    background: white;
}
.dark .pw-objection { background: rgb(127 29 29 / 0.1); border-color: rgb(185 28 28); }
.pw-objection-q { font-weight: 600; font-size: 0.875rem; color: rgb(185 28 28); margin: 0 0 0.375rem; }
.pw-objection-r { font-size: 0.875rem; color: rgb(55 65 81); margin: 0; }
.dark .pw-objection-r { color: rgb(209 213 219); }

/* KPIs argumentaire */
.pw-kpis { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.75rem; margin-top: 1rem; }
.pw-kpi {
    background: white; border-radius: 0.5rem; padding: 0.875rem;
    text-align: center; border: 1px solid rgb(229 231 235);
}
.dark .pw-kpi { background: rgb(31 41 55); border-color: rgb(55 65 81); }
.pw-kpi-val { font-size: 1.375rem; font-weight: 700; }
.pw-kpi-lbl { font-size: 0.75rem; color: rgb(107 114 128); margin-top: 0.125rem; }

/* ─── Informations Prospect ─── */
.pw-infos {
    flex-shrink: 0;
    border-top: 2px solid rgb(229 231 235);
    background: white;
}
.dark .pw-infos { background: rgb(17 24 39); border-top-color: rgb(31 41 55); }
.pw-infos-header {
    display: flex; align-items: center; gap: 0.5rem;
    padding: 0.75rem 1.25rem;
    background: rgb(249 250 251);
    border-bottom: 1px solid rgb(229 231 235);
    cursor: pointer; user-select: none;
}
.dark .pw-infos-header { background: rgb(31 41 55); border-bottom-color: rgb(55 65 81); }
.pw-infos-title { font-size: 0.8125rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: rgb(239 68 68); }
.pw-infos-grid { padding: 1rem 1.25rem; display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.875rem 1.25rem; }
.pw-field-label { font-size: 0.6875rem; text-transform: uppercase; font-weight: 600; color: rgb(107 114 128); margin-bottom: 0.25rem; }
.pw-field-input {
    width: 100%; padding: 0.375rem 0.625rem;
    border: 1px solid rgb(209 213 219); border-radius: 0.375rem;
    font-size: 0.8125rem; background: white; color: inherit;
    outline: none;
}
.dark .pw-field-input { background: rgb(31 41 55); border-color: rgb(55 65 81); }
.pw-field-full { grid-column: span 2; }

/* Contacts identifiés */
.pw-contacts-section { padding: 0 1.25rem 1rem; }
.pw-contacts-label { font-size: 0.8125rem; font-weight: 600; margin-bottom: 0.75rem; color: rgb(55 65 81); }
.dark .pw-contacts-label { color: rgb(209 213 219); }
.pw-contacts-grid { display: grid; grid-template-columns: repeat(2, 1fr) 1fr 1fr; gap: 0.5rem 0.75rem; }

/* ─── Colonne droite (panel issue de l'appel) ─── */
.pw-right {
    display: flex; flex-direction: column;
    overflow-y: auto; background: white;
    padding: 1rem;
}
.dark .pw-right { background: rgb(17 24 39); }

/* Sans réponse stats */
.pw-nr-box {
    background: rgb(249 250 251); border-radius: 0.5rem;
    padding: 0.875rem 1rem; margin-bottom: 1.25rem;
    border: 1px solid rgb(229 231 235);
}
.dark .pw-nr-box { background: rgb(31 41 55); border-color: rgb(55 65 81); }
.pw-nr-title { font-size: 0.75rem; font-weight: 600; color: rgb(107 114 128); }
.pw-nr-subtitle { font-size: 0.6875rem; color: rgb(156 163 175); margin-left: 0.25rem; }
.pw-nr-count { font-size: 2rem; font-weight: 700; }
.pw-nr-tentatives { font-size: 0.875rem; color: rgb(107 114 128); }

/* Issue de l'appel */
.pw-issue-title { font-size: 0.875rem; font-weight: 700; margin-bottom: 0.875rem; }
.pw-issue-option {
    display: flex; align-items: center; justify-content: space-between;
    padding: 0.625rem 0.875rem; margin-bottom: 0.5rem;
    border-radius: 0.375rem; cursor: pointer;
    border: 1px solid rgb(229 231 235);
    transition: all 0.15s;
}
.dark .pw-issue-option { border-color: rgb(55 65 81); }
.pw-issue-option:hover { background: rgb(249 250 251); }
.dark .pw-issue-option:hover { background: rgb(31 41 55); }
.pw-issue-label { font-size: 0.875rem; font-weight: 600; }
.pw-issue-sub   { font-size: 0.75rem; color: rgb(107 114 128); }

/* Couleurs options actives */
.pw-opt-active-gray   { border-color: rgb(107 114 128) !important; background: rgb(249 250 251); }
.pw-opt-active-blue   { border-color: rgb(59 130 246)  !important; background: rgb(239 246 255); }
.pw-opt-active-orange { border-color: rgb(249 115 22)  !important; background: rgb(255 247 237); }
.pw-opt-active-green  { border-color: rgb(34 197 94)   !important; background: rgb(240 253 244); }
.pw-opt-active-teal   { border-color: rgb(20 184 166)  !important; background: rgb(240 253 250); }
.pw-opt-active-red    { border-color: rgb(239 68 68)   !important; background: rgb(254 242 242); }

.dark .pw-opt-active-gray   { background: rgb(17 24 39 / 0.5); }
.dark .pw-opt-active-blue   { background: rgb(23 37 84 / 0.3); }
.dark .pw-opt-active-orange { background: rgb(124 45 18 / 0.3); }
.dark .pw-opt-active-green  { background: rgb(5 46 22 / 0.3); }
.dark .pw-opt-active-teal   { background: rgb(19 78 74 / 0.3); }
.dark .pw-opt-active-red    { background: rgb(127 29 29 / 0.3); }

/* Indicateur couleur gauche */
.pw-opt-bar {
    width: 3px; height: 2rem; border-radius: 9999px; flex-shrink: 0; margin-right: 0.625rem;
}

/* ─── Compte rendu textarea ─── */
.pw-textarea {
    width: 100%; padding: 0.5rem 0.75rem;
    border: 1px solid rgb(229 231 235); border-radius: 0.5rem;
    font-size: 0.8125rem; background: rgb(249 250 251);
    color: inherit; resize: vertical; outline: none;
    margin-top: 1.25rem; transition: box-shadow 0.15s;
}
.pw-textarea:focus { box-shadow: 0 0 0 2px rgb(59 130 246 / 0.4); border-color: rgb(59 130 246); }
.dark .pw-textarea { background: rgb(31 41 55 / 0.5); border-color: rgb(55 65 81); }

/* ─── Boutons bas ─── */
.pw-actions { display: flex; gap: 0.75rem; margin-top: 1rem; }
.pw-btn-primary {
    flex: 1; padding: 0.625rem;
    background: rgb(34 197 94); color: white;
    font-weight: 700; font-size: 0.8125rem;
    border-radius: 0.5rem; border: none; cursor: pointer;
    transition: background 0.15s;
}
.pw-btn-primary:hover { background: rgb(22 163 74); }
.pw-btn-secondary {
    padding: 0.625rem 1rem;
    background: rgb(229 231 235); color: rgb(55 65 81);
    font-weight: 600; font-size: 0.8125rem;
    border-radius: 0.5rem; border: none; cursor: pointer;
    transition: background 0.15s;
}
.pw-btn-secondary:hover { background: rgb(209 213 219); }
.dark .pw-btn-secondary { background: rgb(55 65 81); color: rgb(209 213 219); }
.dark .pw-btn-secondary:hover { background: rgb(75 85 99); }
</style>
@endpush

@if($currentContact)
@php
    $info        = $this->getContactInfo();
    $variables   = $this->getVariablesScript();
    $contactName = trim(($info['prenom'] ?? '') . ' ' . ($info['nom'] ?? ''));
    $phoneNumber = $info['telephone'] ?? '—';
    $initials    = strtoupper(
        substr($info['prenom'] ?? ($info['nom'] ?? 'C'), 0, 1) .
        substr($info['nom']    ?? '?', 0, 1)
    );
    $statutLabel = $info['statut'] ?? 'AC';

    // Scripts pour l'onglet actif
    $scriptCourant = $this->getScriptCourant();

    $onglets = \App\Models\ScriptAppel::ONGLETS;

    // Correspondance statut → classes couleur option
    $optionColors = [
        'std_nr'    => 'gray',
        'std_joint' => 'blue',
        'cse_nr'    => 'orange',
        'rp'        => 'green',
        'rpc'       => 'teal',
        'ko'        => 'red',
    ];

    $options = [
        ['value' => 'std_nr',    'label' => 'STD-NR',    'sub' => 'Sans réponse',    'color' => 'gray'],
        ['value' => 'std_joint', 'label' => 'STD-Joint', 'sub' => 'Joint',           'color' => 'blue'],
        ['value' => 'cse_nr',    'label' => 'CSE-NR',    'sub' => 'CSE sans réponse','color' => 'orange'],
        ['value' => 'rp',        'label' => 'RP',         'sub' => 'Rappel planifié', 'color' => 'green'],
        ['value' => 'rpc',       'label' => 'RPC',        'sub' => 'RDV à planifier', 'color' => 'teal'],
        ['value' => 'ko',        'label' => 'KO',         'sub' => 'Refus / KO',      'color' => 'red'],
    ];

    $barColors = [
        'gray'   => 'background:rgb(107 114 128)',
        'blue'   => 'background:rgb(59 130 246)',
        'orange' => 'background:rgb(249 115 22)',
        'green'  => 'background:rgb(34 197 94)',
        'teal'   => 'background:rgb(20 184 166)',
        'red'    => 'background:rgb(239 68 68)',
    ];
@endphp

<div class="pw-wrap">

    {{-- ── Barre supervision ── --}}
    <div class="pw-supervision">
        <span>Supervision — file de :</span>
        <select>
            <option>{{ Auth::user()?->name ?? 'NS Conseil (98)' }}</option>
        </select>
        <span style="color:rgb(156 163 175); font-style:italic;">Mode lecture seule</span>
    </div>

    {{-- ── Barre contact ── --}}
    <div class="pw-contact-bar">
        <div style="display:flex; align-items:center; gap:0.875rem;">
            <div class="pw-contact-avatar">{{ $initials }}</div>
            <div>
                <div style="display:flex; align-items:center; gap:0.5rem;">
                    <h2 class="pw-contact-name">{{ Str::upper($contactName) ?: 'CONTACT SANS NOM' }}</h2>
                    <span class="pw-badge-ac">{{ $statutLabel }}</span>
                </div>
                <p class="pw-contact-sub">
                    @if(!empty($info['siret'])) #{{ $info['siret'] }} @endif
                    @if(!empty($info['ville'])) &nbsp;{{ $info['ville'] }} @elseif(!empty($info['adresse'])) &nbsp;{{ Str::limit($info['adresse'], 40) }} @endif
                </p>
            </div>
        </div>

        <div style="display:flex; align-items:center; gap:1.25rem;">
            <div class="pw-timer">
                <div class="pw-timer-label">Durée</div>
                <div class="pw-timer-value">00 : 00</div>
            </div>
            <button wire:click="callNow" class="pw-btn-demarrer">
                <svg style="width:1rem;height:1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                </svg>
                Démarrer l'appel
            </button>
            <div class="pw-en-file">
                <span class="pw-en-file-num">{{ $total - $completed }}</span>
                <span class="pw-en-file-label">EN FILE</span>
            </div>
        </div>
    </div>

    {{-- ── Corps principal ── --}}
    <div class="pw-body">

        {{-- ═══ COLONNE GAUCHE ═══ --}}
        <div class="pw-left">

            {{-- Onglets --}}
            <div class="pw-tabs">
                @foreach($onglets as $key => $label)
                    <button
                        wire:click="$set('activeScriptTab', '{{ $key }}')"
                        class="pw-tab {{ $activeScriptTab === $key ? 'pw-tab-active' : '' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            {{-- Zone script --}}
            <div class="pw-script-area">
                @if($scriptCourant)
                    {{-- ─ Script principal ─ --}}
                    @if($scriptCourant->onglet === 'objections' && $scriptCourant->objections)
                        {{-- Mode objections : liste de Q/R --}}
                        @foreach($scriptCourant->objections as $obj)
                            <div class="pw-objection">
                                <p class="pw-objection-q">"{{ $obj['question'] }}"</p>
                                <p class="pw-objection-r">→ {{ $obj['reponse'] }}</p>
                            </div>
                        @endforeach

                    @elseif($scriptCourant->onglet === 'argumentaire' && $scriptCourant->kpis)
                        {{-- Mode argumentaire : texte + KPIs --}}
                        @if($scriptCourant->contenu)
                            <div class="pw-script-text">{!! nl2br(e($scriptCourant->interpoler($variables))) !!}</div>
                        @endif
                        <div class="pw-kpis">
                            @foreach($scriptCourant->kpis as $kpi)
                                @php
                                    $kpiColors = [
                                        'purple' => 'color:rgb(147 51 234)',
                                        'blue'   => 'color:rgb(37 99 235)',
                                        'green'  => 'color:rgb(22 163 74)',
                                        'orange' => 'color:rgb(234 88 12)',
                                    ];
                                    $kpiColor = $kpiColors[$kpi['couleur'] ?? 'purple'] ?? $kpiColors['purple'];
                                @endphp
                                <div class="pw-kpi">
                                    <div class="pw-kpi-val" style="{{ $kpiColor }}">{{ $kpi['valeur'] }}</div>
                                    <div class="pw-kpi-lbl">{{ $kpi['label'] }}</div>
                                </div>
                            @endforeach
                        </div>

                    @else
                        {{-- Mode standard : texte --}}
                        @if($scriptCourant->contenu)
                            <div class="pw-script-text">{!! nl2br(e($scriptCourant->interpoler($variables))) !!}</div>
                        @endif
                    @endif

                    {{-- Conseil / tip --}}
                    @if($scriptCourant->interpolerConseil($variables))
                        <div class="pw-conseil">
                            <svg style="width:1rem;height:1rem;flex-shrink:0;margin-top:0.125rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                            </svg>
                            <span>{{ $scriptCourant->interpolerConseil($variables) }}</span>
                        </div>
                    @endif

                @else
                    {{-- Aucun script --}}
                    <div class="pw-script-empty">
                        Aucun script disponible pour ce type de campagne.
                        <a href="{{ route('filament.ns-conseil.resources.script-appels.create') }}"
                           style="margin-left:0.5rem; text-decoration:underline; font-weight:600;">
                            Créer un script →
                        </a>
                    </div>
                @endif
            </div>

            {{-- ── Informations Prospect ── --}}
            <div class="pw-infos">
                <div class="pw-infos-header">
                    <svg style="width:0.875rem;height:0.875rem;color:rgb(239 68 68);" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm0 4a1 1 0 011-1h12a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1V8z" clip-rule="evenodd"/>
                    </svg>
                    <span class="pw-infos-title">Informations Prospect</span>
                </div>
                <div class="pw-infos-grid">
                    <div class="pw-field-full">
                        <div class="pw-field-label">Téléphone principal</div>
                        <div style="display:flex; gap:0.5rem; align-items:center;">
                            <select style="padding:0.375rem 0.5rem; border:1px solid rgb(209 213 219); border-radius:0.375rem; font-size:0.8125rem; background:white; color:inherit; min-width:7rem;">
                                <option>France (+33)</option>
                            </select>
                            <input type="text" value="{{ $phoneNumber }}" readonly class="pw-field-input" style="flex:1;">
                        </div>
                    </div>
                    @if(!empty($info['telephone_alt']))
                    <div class="pw-field-full">
                        <div class="pw-field-label">Tél. secondaire</div>
                        <div style="display:flex; gap:0.5rem;">
                            <select style="padding:0.375rem 0.5rem; border:1px solid rgb(209 213 219); border-radius:0.375rem; font-size:0.8125rem; background:white; min-width:7rem;">
                                <option>France (+33)</option>
                            </select>
                            <input type="text" value="{{ $info['telephone_alt'] }}" readonly class="pw-field-input" style="flex:1;">
                        </div>
                    </div>
                    @endif
                    <div>
                        <div class="pw-field-label">Email</div>
                        <input type="text" value="{{ $info['email'] ?? '' }}" readonly class="pw-field-input">
                    </div>
                    <div>
                        <div class="pw-field-label">Entreprise / CSE</div>
                        <input type="text" value="{{ $info['nom'] ?? '' }}" readonly class="pw-field-input">
                    </div>
                    @if(!empty($info['adresse']))
                    <div class="pw-field-full">
                        <div class="pw-field-label">Adresse</div>
                        <input type="text" value="{{ $info['adresse'] }}" readonly class="pw-field-input">
                    </div>
                    @endif
                    @if(!empty($info['ville']))
                    <div>
                        <div class="pw-field-label">Ville</div>
                        <input type="text" value="{{ $info['ville'] }}" readonly class="pw-field-input">
                    </div>
                    @endif
                    @if(!empty($info['code_postal']))
                    <div>
                        <div class="pw-field-label">Code postal</div>
                        <input type="text" value="{{ $info['code_postal'] }}" readonly class="pw-field-input">
                    </div>
                    @endif

                    {{-- Champs RDV --}}
                    <div>
                        <div class="pw-field-label">Date RDV</div>
                        <input type="date" wire:model="rappel_date" class="pw-field-input" placeholder="jj/mm/aaaa">
                    </div>
                    <div>
                        <div class="pw-field-label">Heure RDV</div>
                        <input type="time" wire:model="rappel_heure" class="pw-field-input" placeholder="--:--">
                    </div>
                    <div class="pw-field-full">
                        <div class="pw-field-label">Lieu RDV</div>
                        <input type="text" class="pw-field-input" placeholder="Adresse du rendez-vous">
                    </div>
                </div>

                {{-- Contacts identifiés --}}
                @if(!empty($info['interlocuteur']) && $info['interlocuteur'] !== 'Non défini')
                <div class="pw-contacts-section">
                    <div class="pw-contacts-label">Contacts identifiés <span style="font-weight:400; font-size:0.75rem; color:rgb(107 114 128);">(si disponible lors de l'appel)</span></div>
                    <div style="border:1px solid rgb(229 231 235); border-radius:0.5rem; padding:0.75rem 1rem;">
                        <label style="display:flex; align-items:center; gap:0.5rem; margin-bottom:0.625rem; font-size:0.8125rem;">
                            <input type="checkbox" checked style="width:0.875rem;height:0.875rem;">
                            <span style="font-weight:600;">Dirigeant</span>
                        </label>
                        <div class="pw-contacts-grid">
                            <div>
                                <div class="pw-field-label">Prénom</div>
                                <input type="text" class="pw-field-input">
                            </div>
                            <div>
                                <div class="pw-field-label">Nom</div>
                                <input type="text" value="{{ $info['interlocuteur'] ?? '' }}" class="pw-field-input">
                            </div>
                            <div>
                                <div class="pw-field-label">Fonction</div>
                                <input type="text" class="pw-field-input">
                            </div>
                            <div>
                                <div class="pw-field-label">Téléphone</div>
                                <input type="text" value="{{ $phoneNumber }}" class="pw-field-input">
                            </div>
                            <div class="pw-field-full">
                                <div class="pw-field-label">Email</div>
                                <input type="text" value="{{ $info['email'] ?? '' }}" class="pw-field-input">
                            </div>
                        </div>
                    </div>
                </div>
                @endif

            </div>
        </div>{{-- /pw-left --}}

        {{-- ═══ COLONNE DROITE ═══ --}}
        <div class="pw-right">

            {{-- Sans réponse stats --}}
            <div class="pw-nr-box">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.375rem;">
                    <span class="pw-nr-title">Sans réponse (STD-NR)</span>
                    <span class="pw-nr-subtitle">08:00 - 18:00</span>
                </div>
                <div style="display:flex; align-items:baseline; gap:0.375rem;">
                    <span class="pw-nr-count">0</span>
                    <span class="pw-nr-tentatives">/ 3 tentatives</span>
                </div>
            </div>

            {{-- Issue de l'appel --}}
            <div class="pw-issue-title">Issue de l'appel</div>

            @foreach($options as $option)
                @php
                    $isActive  = $statut_resultat === $option['value'];
                    $activeClass = $isActive ? 'pw-opt-active-' . $option['color'] : '';
                @endphp
                <label
                    class="pw-issue-option {{ $activeClass }}"
                    style="cursor:pointer;">
                    <div style="display:flex; align-items:center; gap:0;">
                        <div class="pw-opt-bar" style="{{ $barColors[$option['color']] }}"></div>
                        <div>
                            <div class="pw-issue-label">{{ $option['label'] }}</div>
                            <div class="pw-issue-sub">{{ $option['sub'] }}</div>
                        </div>
                    </div>
                    <input
                        type="radio"
                        wire:model="statut_resultat"
                        value="{{ $option['value'] }}"
                        style="display:none;">
                    <svg style="width:1rem;height:1rem;color:rgb(156 163 175);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </label>
            @endforeach

            {{-- Compte rendu --}}
            <textarea
                wire:model="commentaires"
                rows="4"
                placeholder="Compte rendu d'appel : interlocuteur joint, objections, prochaine étape..."
                class="pw-textarea"></textarea>

            {{-- Actions --}}
            <div class="pw-actions">
                <button wire:click="submitResult" class="pw-btn-primary">
                    Enregistrer &amp; suivant
                </button>
                <button wire:click="skipCall" class="pw-btn-secondary">
                    Passer
                </button>
            </div>

        </div>{{-- /pw-right --}}

    </div>{{-- /pw-body --}}

</div>{{-- /pw-wrap --}}

@else
{{-- ── État vide ── --}}
<div style="display:flex; align-items:center; justify-content:center; min-height:60vh;">
    <div style="text-align:center;">
        <div style="width:5rem; height:5rem; border-radius:9999px; background:rgb(243 244 246); display:flex; align-items:center; justify-content:center; margin:0 auto 1rem auto;">
            <svg style="width:2.25rem;height:2.25rem;color:rgb(156 163 175);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
            </svg>
        </div>
        <h3 style="font-size:1.125rem; font-weight:700; margin:0 0 0.5rem;">Aucun contact à appeler</h3>
        <p style="color:rgb(107 114 128); margin:0 0 1.5rem;">Tous les contacts ont été traités ou aucun contact n'est disponible.</p>
        <button wire:click="$refresh"
                style="padding:0.5rem 1.5rem; background:rgb(37 99 235); color:white; border-radius:0.5rem; font-weight:600; border:none; cursor:pointer;">
            Rafraîchir
        </button>
    </div>
</div>
@endif

</x-filament-panels::page>
