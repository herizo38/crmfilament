{{-- resources/views/filament/ns-conseil/pages/phoning-workflow.blade.php --}}
@php
    // Codes déclenchant le bloc rappel/RDV — défini tout en haut car
    // référencé dans le <script> de @push('styles') ci-dessous, qui est
    // évalué avant le bloc @php principal plus bas dans le fichier.
    $rappelCodes = ['rdv', 'rapl_elu', 'rapl_std', 'cse_ni', 'rp', 'rpc', 'bloc'];
    $maxTentatives = app(\App\Services\Crm\CrmSettingsService::class)->get('prospection.max_standard_attempts', 3);
    $tentativesActuelles = $this->getTentativesAppel();
@endphp
<x-filament-panels::page>

    @push('styles')
        <style>
            /* ═══════════════════════════════════════════════════════════════════
       PHONING WORKFLOW — v2 — styles custom
    ════════════════════════════════════════════════════════════════════ */
            .pw-wrap * {
                box-sizing: border-box;
            }

            .pw-wrap {
                padding: 0;
            }

            /* ─── Barre de supervision ─── */
            .pw-supervision {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                flex-wrap: wrap;
                padding: 0.5rem 1rem;
                background: rgb(239 246 255);
                border-bottom: 1px solid rgb(219 234 254);
                font-size: 0.8125rem;
                color: rgb(55 65 81);
            }

            .dark .pw-supervision {
                background: rgb(23 37 84 / 0.2);
                border-bottom-color: rgb(30 58 138 / 0.3);
                color: rgb(147 197 253);
            }

            .pw-sup-avatar {
                width: 1.75rem;
                height: 1.75rem;
                border-radius: 0.375rem;
                background: rgb(37 99 235);
                color: white;
                font-weight: 700;
                font-size: 0.6875rem;
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: pointer;
                transition: background 0.15s;
            }

            .pw-sup-avatar:hover {
                background: rgb(29 78 216);
            }

            .pw-sup-avatar.active {
                background: rgb(37 99 235);
                box-shadow: 0 0 0 2px white, 0 0 0 4px rgb(37 99 235);
            }

            .pw-sup-self {
                background: rgb(22 163 74);
            }

            .pw-sup-self:hover {
                background: rgb(15 118 65);
            }

            .pw-sup-divider {
                width: 1px;
                height: 1.25rem;
                background: rgb(191 219 254);
                margin: 0 0.25rem;
            }

            /* ─── Barre contact ─── */
            .pw-contact-bar {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 0.875rem 1.25rem;
                background: white;
                border-bottom: 1px solid rgb(229 231 235);
                flex-wrap: wrap;
                gap: 0.75rem;
            }

            .dark .pw-contact-bar {
                background: rgb(17 24 39);
                border-bottom-color: rgb(31 41 55);
            }

            .pw-contact-avatar {
                width: 2.5rem;
                height: 2.5rem;
                border-radius: 0.625rem;
                background: rgb(59 130 246);
                color: white;
                font-weight: 700;
                font-size: 1rem;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
            }

            .pw-contact-name {
                font-size: 1.0625rem;
                font-weight: 800;
                margin: 0;
                letter-spacing: -0.01em;
            }

            .pw-contact-sub {
                font-size: 0.75rem;
                color: rgb(107 114 128);
                margin: 0;
                display: flex;
                align-items: center;
                gap: 0.375rem;
                flex-wrap: wrap;
            }

            .pw-badge {
                display: inline-flex;
                align-items: center;
                padding: 0.125rem 0.5rem;
                border-radius: 0.25rem;
                font-size: 0.6875rem;
                font-weight: 700;
            }

            .pw-badge-rpc {
                background: rgb(204 251 241);
                color: rgb(17 94 89);
            }

            .pw-badge-rp {
                background: rgb(220 252 231);
                color: rgb(20 83 45);
            }

            .pw-badge-std_joint {
                background: rgb(219 234 254);
                color: rgb(30 64 175);
            }

            .pw-badge-ac {
                background: rgb(243 244 246);
                color: rgb(55 65 81);
                border: 1px solid rgb(229 231 235);
            }

            .pw-badge-std_nr {
                background: rgb(243 244 246);
                color: rgb(107 114 128);
            }

            .pw-badge-cse_nr {
                background: rgb(255 237 213);
                color: rgb(154 52 18);
            }

            .pw-badge-ko {
                background: rgb(254 226 226);
                color: rgb(153 27 27);
            }

            .pw-badge-info {
                background: rgb(219 234 254);
                color: rgb(30 64 175);
            }

            .pw-badge-gray {
                background: rgb(243 244 246);
                color: rgb(55 65 81);
            }

            .pw-timer {
                text-align: right;
            }

            .pw-timer-label {
                font-size: 0.6875rem;
                color: rgb(107 114 128);
                text-transform: uppercase;
            }

            .pw-timer-value {
                font-size: 1.25rem;
                font-family: monospace;
                font-weight: 700;
                letter-spacing: 0.05em;
            }

            .pw-btn-call {
                display: inline-flex;
                align-items: center;
                gap: 0.5rem;
                padding: 0.5rem 1.375rem;
                background: rgb(34 197 94);
                color: white;
                font-weight: 700;
                font-size: 0.875rem;
                border-radius: 0.5rem;
                border: none;
                cursor: pointer;
                white-space: nowrap;
                transition: background 0.15s;
                box-shadow: 0 1px 3px rgb(0 0 0 / 0.1);
            }

            .pw-btn-call:hover {
                background: rgb(22 163 74);
            }

            .pw-btn-call-alt {
                display: inline-flex;
                align-items: center;
                gap: 0.375rem;
                padding: 0.5rem 0.875rem;
                background: rgb(243 244 246);
                color: rgb(55 65 81);
                font-weight: 600;
                font-size: 0.8125rem;
                border-radius: 0.5rem;
                border: 1px solid rgb(229 231 235);
                cursor: pointer;
                transition: background 0.15s;
            }

            .pw-btn-call-alt:hover {
                background: rgb(229 231 235);
            }

            .pw-en-file {
                display: flex;
                flex-direction: column;
                align-items: center;
                background: rgb(249 250 251);
                border: 1px solid rgb(229 231 235);
                border-radius: 0.5rem;
                padding: 0.5rem 0.875rem;
                min-width: 3.5rem;
                text-align: center;
            }

            .dark .pw-en-file {
                background: rgb(31 41 55);
                border-color: rgb(55 65 81);
            }

            .pw-en-file-num {
                font-size: 1.25rem;
                font-weight: 700;
            }

            .pw-en-file-label {
                font-size: 0.625rem;
                text-transform: uppercase;
                color: rgb(107 114 128);
            }

            /* ─── Layout 2 colonnes ─── */
            .pw-body {
                display: grid;
                grid-template-columns: 1fr 300px;
                gap: 0;
                height: calc(100vh - 168px);
                overflow: hidden;
            }

            @media (max-width: 1024px) {
                .pw-body {
                    grid-template-columns: 1fr;
                    height: auto;
                    overflow: visible;
                }
            }

            /* ─── Colonne gauche ─── */
            .pw-left {
                display: flex;
                flex-direction: column;
                border-right: 1px solid rgb(229 231 235);
                overflow: hidden;
            }

            .dark .pw-left {
                border-right-color: rgb(31 41 55);
            }

            /* ─── Onglets ─── */
            .pw-tabs {
                display: flex;
                border-bottom: 1px solid rgb(229 231 235);
                background: white;
                flex-shrink: 0;
                overflow-x: auto;
            }

            .dark .pw-tabs {
                background: rgb(17 24 39);
                border-bottom-color: rgb(31 41 55);
            }

            .pw-tab {
                padding: 0.75rem 1.125rem;
                font-size: 0.8125rem;
                font-weight: 500;
                color: rgb(107 114 128);
                border-bottom: 2px solid transparent;
                background: none;
                border-top: none;
                border-left: none;
                border-right: none;
                cursor: pointer;
                white-space: nowrap;
                transition: color 0.15s;
            }

            .pw-tab:hover {
                color: rgb(55 65 81);
            }

            .dark .pw-tab:hover {
                color: rgb(209 213 219);
            }

            .pw-tab.active {
                color: rgb(37 99 235) !important;
                border-bottom-color: rgb(37 99 235) !important;
            }

            .dark .pw-tab.active {
                color: rgb(96 165 250) !important;
                border-bottom-color: rgb(96 165 250) !important;
            }

            /* ─── Zone script ─── */
            .pw-script-area {
                flex: 1;
                overflow-y: auto;
                padding: 1.25rem;
                background: #e5e7eb;
            }

            .dark .pw-script-area {
                background: rgb(17 24 39 / 0.5);
            }

            .pw-script-empty {
                border: 1px solid rgb(191 219 254);
                border-left: 3px solid rgb(59 130 246);
                border-radius: 0.375rem;
                padding: 1rem 1.25rem;
                background: rgb(239 246 255);
                color: rgb(29 78 216);
                font-size: 0.875rem;
                font-style: italic;
            }

            .pw-script-text {
                line-height: 1.8;
                font-size: 0.9375rem;
                color: rgb(31 41 55);
                white-space: pre-wrap;
            }

            .dark .pw-script-text {
                color: rgb(229 231 235);
            }

            .pw-conseil {
                margin-top: 1rem;
                background: rgb(255 251 235);
                border-left: 4px solid rgb(251 191 36);
                border-radius: 0.375rem;
                padding: 0.75rem 1rem;
                font-size: 0.8125rem;
                color: rgb(146 64 14);
                display: flex;
                gap: 0.5rem;
                align-items: flex-start;
            }

            .dark .pw-conseil {
                background: rgb(120 53 15 / 0.15);
                color: rgb(253 186 116);
            }

            .pw-objection {
                border: 1px solid rgb(254 202 202);
                border-left: 3px solid rgb(239 68 68);
                border-radius: 0.375rem;
                padding: 0.75rem 1rem;
                margin-bottom: 0.75rem;
                background: white;
            }

            .dark .pw-objection {
                background: rgb(127 29 29 / 0.1);
            }

            .pw-objection-q {
                font-weight: 600;
                font-size: 0.875rem;
                color: rgb(185 28 28);
                margin: 0 0 0.375rem;
            }

            .pw-objection-r {
                font-size: 0.875rem;
                color: rgb(55 65 81);
                margin: 0;
            }

            .dark .pw-objection-r {
                color: rgb(209 213 219);
            }

            .pw-kpis {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 0.75rem;
                margin-top: 1rem;
            }

            .pw-kpi {
                background: white;
                border-radius: 0.5rem;
                padding: 0.875rem;
                text-align: center;
                border: 1px solid rgb(229 231 235);
            }

            .dark .pw-kpi {
                background: rgb(31 41 55);
                border-color: rgb(55 65 81);
            }

            .pw-kpi-val {
                font-size: 1.375rem;
                font-weight: 700;
            }

            .pw-kpi-lbl {
                font-size: 0.75rem;
                color: rgb(107 114 128);
                margin-top: 0.125rem;
            }

            /* ─── Panneau infos prospect (bas gauche) ─── */
            .pw-infos {
                flex-shrink: 0;
                border-top: 2px solid rgb(229 231 235);
                background: white;
                max-height: 55%;
                overflow-y: auto;
            }

            .dark .pw-infos {
                background: rgb(17 24 39);
                border-top-color: rgb(31 41 55);
            }

            .pw-infos-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 0.625rem 1.25rem;
                background: rgb(249 250 251);
                border-bottom: 1px solid rgb(229 231 235);
                position: sticky;
                top: 0;
                z-index: 1;
            }

            .dark .pw-infos-header {
                background: rgb(31 41 55);
                border-bottom-color: rgb(55 65 81);
            }

            .pw-infos-title {
                font-size: 0.75rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.05em;
                color: rgb(239 68 68);
            }

            /* Grille infos en onglets */
            .pw-info-tabs {
                display: flex;
                border-bottom: 1px solid rgb(229 231 235);
                background: rgb(249 250 251);
            }

            .dark .pw-info-tabs {
                background: rgb(31 41 55);
                border-bottom-color: rgb(55 65 81);
            }

            .pw-info-tab {
                padding: 0.5rem 1rem;
                font-size: 0.75rem;
                font-weight: 600;
                color: rgb(107 114 128);
                cursor: pointer;
                border-bottom: 2px solid transparent;
                background: none;
                border-top: none;
                border-left: none;
                border-right: none;
            }

            .pw-info-tab.active {
                color: rgb(239 68 68);
                border-bottom-color: rgb(239 68 68);
            }

            .pw-info-panel {
                padding: 1rem 1.25rem;
            }

            /* Champs info */
            .pw-info-grid {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 0.75rem 1.25rem;
            }

            .pw-info-grid-3 {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 0.75rem 1rem;
            }

            .pw-field-label {
                font-size: 0.6875rem;
                text-transform: uppercase;
                font-weight: 600;
                color: rgb(107 114 128);
                margin-bottom: 0.25rem;
            }

            .pw-field-value {
                font-size: 0.8125rem;
                font-weight: 500;
            }

            .pw-field-value.copyable {
                cursor: pointer;
                text-decoration: underline dotted;
                color: rgb(37 99 235);
            }

            .pw-field-value.copyable:hover {
                text-decoration: underline;
            }

            .pw-field-input {
                width: 100%;
                padding: 0.375rem 0.625rem;
                border: 1px solid rgb(209 213 219);
                border-radius: 0.375rem;
                font-size: 0.8125rem;
                color: inherit;
                outline: none;
            }

            .dark .pw-field-input {
                background: rgb(31 41 55);
                border-color: rgb(55 65 81);
            }

            .pw-field-full {
                grid-column: span 2;
            }

            .pw-field-full-3 {
                grid-column: span 3;
            }

            /* Bloc interlocuteur */
            .pw-interlocuteur-card {
                border: 1px solid rgb(229 231 235);
                border-radius: 0.5rem;
                padding: 0.875rem 1rem;
                background: rgb(249 250 251);
            }

            .dark .pw-interlocuteur-card {
                background: rgb(31 41 55);
                border-color: rgb(55 65 81);
            }

            /* Notes timeline */
            .pw-notes-list {
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
            }

            .pw-note-item {
                background: rgb(249 250 251);
                border-radius: 0.375rem;
                padding: 0.625rem 0.875rem;
                font-size: 0.8125rem;
                border-left: 3px solid rgb(229 231 235);
            }

            .dark .pw-note-item {
                background: rgb(31 41 55);
            }

            .pw-note-date {
                font-size: 0.6875rem;
                color: rgb(107 114 128);
                margin-bottom: 0.25rem;
            }

            /* ─── Colonne droite ─── */
            .pw-right {
                display: flex;
                flex-direction: column;
                overflow-y: auto;
                background: white;
                padding: 1rem;
            }

            .dark .pw-right {
                background: rgb(17 24 39);
            }

            .pw-nr-box {
                background: rgb(249 250 251);
                border-radius: 0.5rem;
                padding: 0.875rem 1rem;
                margin-bottom: 1.25rem;
                border: 1px solid rgb(229 231 235);
            }

            .dark .pw-nr-box {
                background: rgb(31 41 55);
                border-color: rgb(55 65 81);
            }

            .pw-nr-title {
                font-size: 0.75rem;
                font-weight: 600;
                color: rgb(107 114 128);
            }

            .pw-nr-subtitle {
                font-size: 0.6875rem;
                color: rgb(156 163 175);
            }

            .pw-nr-count {
                font-size: 2rem;
                font-weight: 700;
            }

            .pw-nr-tentatives {
                font-size: 0.875rem;
                color: rgb(107 114 128);
            }

            .pw-issue-title {
                font-size: 0.875rem;
                font-weight: 700;
                margin-bottom: 0.75rem;
            }

            .pw-issue-option {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 0.5rem 0.875rem;
                margin-bottom: 0.4rem;
                border-radius: 0.375rem;
                cursor: pointer;
                border: 1px solid rgb(229 231 235);
                transition: all 0.15s;
            }

            .dark .pw-issue-option {
                border-color: rgb(55 65 81);
            }

            .pw-issue-option:hover {
                background: rgb(249 250 251);
            }

            .dark .pw-issue-option:hover {
                background: rgb(31 41 55);
            }

            .pw-issue-label {
                font-size: 0.8125rem;
                font-weight: 600;
            }

            .pw-issue-sub {
                font-size: 0.6875rem;
                color: rgb(107 114 128);
            }

            .pw-opt-bar {
                width: 3px;
                height: 2rem;
                border-radius: 9999px;
                flex-shrink: 0;
                margin-right: 0.625rem;
            }

            .pw-opt-active-gray {
                border-color: rgb(107 114 128) !important;
                background: rgb(249 250 251);
            }

            .pw-opt-active-blue {
                border-color: rgb(59 130 246) !important;
                background: rgb(239 246 255);
            }

            .pw-opt-active-orange {
                border-color: rgb(249 115 22) !important;
                background: rgb(255 247 237);
            }

            .pw-opt-active-green {
                border-color: rgb(34 197 94) !important;
                background: rgb(240 253 244);
            }

            .pw-opt-active-teal {
                border-color: rgb(20 184 166) !important;
                background: rgb(240 253 250);
            }

            .pw-opt-active-red {
                border-color: rgb(239 68 68) !important;
                background: rgb(254 242 242);
            }

            .dark .pw-opt-active-gray {
                background: rgb(17 24 39 / 0.5);
            }

            .dark .pw-opt-active-blue {
                background: rgb(23 37 84 / 0.3);
            }

            .dark .pw-opt-active-orange {
                background: rgb(124 45 18 / 0.3);
            }

            .dark .pw-opt-active-green {
                background: rgb(5 46 22 / 0.3);
            }

            .dark .pw-opt-active-teal {
                background: rgb(19 78 74 / 0.3);
            }

            .dark .pw-opt-active-red {
                background: rgb(127 29 29 / 0.3);
            }

            /* Rappel conditionnel */
            .pw-rappel-box {
                background: rgb(240 253 244);
                border: 1px solid rgb(187 247 208);
                border-radius: 0.5rem;
                padding: 0.75rem;
                margin-top: 0.75rem;
                display: none;
            }

            .pw-rappel-box.visible {
                display: block;
            }

            .pw-rappel-box-title {
                font-size: 0.75rem;
                font-weight: 700;
                color: rgb(22 101 52);
                margin-bottom: 0.5rem;
            }

            .pw-textarea {
                width: 100%;
                padding: 0.5rem 0.75rem;
                border: 1px solid rgb(229 231 235);
                border-radius: 0.5rem;
                font-size: 0.8125rem;
                background: rgb(249 250 251);
                color: inherit;
                resize: vertical;
                outline: none;
                margin-top: 1rem;
                transition: box-shadow 0.15s;
                min-height: 4rem;
            }

            .pw-textarea:focus {
                box-shadow: 0 0 0 2px rgb(59 130 246 / 0.4);
                border-color: rgb(59 130 246);
            }

            .dark .pw-textarea {
                background: rgb(31 41 55 / 0.5);
                border-color: rgb(55 65 81);
            }

            .pw-actions {
                display: flex;
                gap: 0.75rem;
                margin-top: 0.875rem;
            }

            .pw-btn-primary {
                flex: 1;
                padding: 0.625rem;
                background: rgb(34 197 94);
                color: white;
                font-weight: 700;
                font-size: 0.8125rem;
                border-radius: 0.5rem;
                border: none;
                cursor: pointer;
                transition: background 0.15s;
            }

            .pw-btn-primary:hover {
                background: rgb(22 163 74);
            }

            .pw-btn-secondary {
                padding: 0.625rem 1rem;
                background: rgb(229 231 235);
                color: rgb(55 65 81);
                font-weight: 600;
                font-size: 0.8125rem;
                border-radius: 0.5rem;
                border: none;
                cursor: pointer;
            }

            .pw-btn-secondary:hover {
                background: rgb(209 213 219);
            }

            .dark .pw-btn-secondary {
                background: rgb(55 65 81);
                color: rgb(209 213 219);
            }
        </style>

        <script>
            // Timer d'appel
            let timerInterval = null;
            let timerSeconds = 0;

            function startTimer() {
                if (timerInterval) {
                    clearInterval(timerInterval);
                    timerSeconds = 0;
                }
                timerInterval = setInterval(() => {
                    timerSeconds++;
                    const m = String(Math.floor(timerSeconds / 60)).padStart(2, '0');
                    const s = String(timerSeconds % 60).padStart(2, '0');
                    const el = document.querySelector('.pw-timer-value');
                    if (el) el.textContent = m + ' : ' + s;
                }, 1000);
            }

            // Afficher/masquer le bloc rappel selon l'option choisie (codes dynamiques)
            const pwRappelCodes = @json($rappelCodes);
            function toggleRappel(val) {
                const box = document.getElementById('pw-rappel-box');
                if (box) {
                    box.classList.toggle('visible', pwRappelCodes.includes(val));
                }
            }

            // Copier dans le presse-papier
            function copyToClipboard(text) {
                navigator.clipboard.writeText(text).then(() => {
                    Livewire.dispatch('notify', {
                        message: 'Copié !'
                    });
                });
            }

            // Onglets infos
            function switchInfoTab(tab) {
                document.querySelectorAll('.pw-info-tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.pw-info-panel[data-tab]').forEach(p => p.style.display = 'none');
                document.querySelector(`.pw-info-tab[data-tab="${tab}"]`).classList.add('active');
                document.querySelector(`.pw-info-panel[data-tab="${tab}"]`).style.display = 'block';
            }

            document.addEventListener('livewire:navigated', () => {
                switchInfoTab('contact');
            });
            document.addEventListener('DOMContentLoaded', () => {
                switchInfoTab('contact');
            });
        </script>
    @endpush

    @php
        $info = $this->getContactInfo();
        $tel = $info['telephone'] ?? null;
        $teleprospecteurs = $this->getTeleprospecteurs();
        $nbEnFile = count($this->contactQueue);
        $progress = $this->progress;

        // Onglets script
        $onglets = \App\Models\ScriptAppel::ONGLETS;
        $scriptCourant = $this->getScriptCourant();
        $variables = $this->getVariablesScript();

        // Options issue — groupées par cas CSE v2
        $statutsGroupes = $this->getStatutsPhoningGroupes();
        $options = $this->getStatutsPhoning();

        // Historique d'appels depuis la base
$callHistory = $this->getCallHistory();

// Statut badge class
$statutCls = 'pw-badge-' . ($info['statut'] ?? 'ac');
if (!isset($info['statut'])) {
    $statutCls = 'pw-badge-gray';
}
$statutLabel = $info['statut_label'] ?? ($info['statut'] ?? 'AC');

// Préparer notes (parser les lignes horodatées)
$notes = $info['notes'] ?? null;
$noteLines = [];
if ($notes) {
    foreach (explode("\n", $notes) as $line) {
        $line = trim($line);
        if (!$line) {
            continue;
        }
        if (preg_match('/^\[(\d{2}\/\d{2}\/\d{4}[^\]]*)\]\s*(.+)$/', $line, $m)) {
            $noteLines[] = ['date' => $m[1], 'text' => $m[2]];
        } else {
            $noteLines[] = ['date' => null, 'text' => $line];
                }
            }
        }
    @endphp

    <div class="pw-wrap">

        {{-- ═══ BARRE SUPERVISION ═══ --}}


        @if ($currentContact)

            {{-- ═══ BARRE CONTACT ═══ --}}
            <div class="pw-contact-bar">
                <div style="display:flex; align-items:center; gap:1rem;">
                    <div class="pw-contact-avatar">
                        {{ strtoupper(substr($info['prenom'] ?? ($info['nom'] ?? 'C'), 0, 1) . substr($info['nom'] ?? '?', 0, 1)) }}
                    </div>
                    <div>
                        <div style="display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap;">
                            <h2 class="pw-contact-name">
                                {{ Str::upper(trim(($info['prenom'] ?? '') . ' ' . ($info['nom'] ?? ''))) ?: 'CONTACT SANS NOM' }}
                            </h2>
                            <span class="pw-badge {{ $statutCls }}">{{ $statutLabel }}</span>
                            @if (!empty($info['taux_engagement']))
                                <span style="font-size:0.875rem;">{{ $info['taux_engagement'] }}</span>
                            @endif
                            @if (!empty($info['rappel_en_retard']) && $info['rappel_en_retard'])
                                <span
                                    style="font-size:0.6875rem; background:rgb(254 226 226); color:rgb(153 27 27); padding:0.125rem 0.5rem; border-radius:9999px; font-weight:700; animation:pulse 2s infinite;">
                                    ⚠ RAPPEL EN RETARD
                                </span>
                            @endif
                        </div>
                        <div class="pw-contact-sub">
                            @if (!empty($info['siret']))
                                <span>SIRET&nbsp;{{ $info['siret'] }}</span> ·
                            @endif
                            @if (!empty($info['ville']))
                                <span>📍
                                    {{ $info['ville'] }}{{ !empty($info['departement']) ? ' (' . $info['departement'] . ')' : '' }}</span>
                            @endif
                            @if (!empty($info['type_pressenti']) && $info['type_pressenti'] !== 'Non défini')
                                · <span>🏢 {{ $info['type_pressenti'] }}</span>
                            @endif
                            @if (!empty($info['secteur_activite']))
                                · <span>{{ $info['secteur_activite'] }}</span>
                            @endif
                            @if (!empty($info['nb_salaries']))
                                · <span>👥 {{ $info['nb_salaries'] }} sal.</span>
                            @endif
                            @if (!empty($info['chiffre_affaires']))
                                · <span>💰 {{ $info['chiffre_affaires'] }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div style="display:flex; align-items:center; gap:1rem; flex-wrap:wrap;">
                    <div class="pw-timer">
                        <div class="pw-timer-label">Durée</div>
                        <div class="pw-timer-value">00 : 00</div>
                    </div>

                    {{-- Téléphone alt --}}
                    @if (!empty($info['telephone_alt']))
                        <button onclick="copyToClipboard('{{ $info['telephone_alt'] }}')" class="pw-btn-call-alt">
                            <svg style="width:0.875rem;height:0.875rem;" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                            Alt.
                        </button>
                    @endif

                    <button wire:click="callNow" onclick="startTimer()" class="pw-btn-call">
                        <svg style="width:1rem;height:1rem;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                        {{ $tel ?? 'Appeler' }}
                    </button>

                    <div class="pw-en-file">
                        <span class="pw-en-file-num">{{ $nbEnFile }}</span>
                        <span class="pw-en-file-label">EN FILE</span>
                    </div>
                </div>
            </div>

            {{-- ═══ CORPS PRINCIPAL ═══ --}}
            <div class="pw-body">

                {{-- ═══ COLONNE GAUCHE ═══ --}}
                <div class="pw-left">

                    {{-- Onglets script --}}
                    <div class="pw-tabs">
                        @foreach ($onglets as $key => $label)
                            <button wire:click="$set('activeScriptTab', '{{ $key }}')"
                                class="pw-tab {{ $activeScriptTab === $key ? 'active' : '' }}">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>

                    {{-- Zone script --}}
                    <div class="pw-script-area">
                        @if ($scriptCourant)
                            @if ($scriptCourant->onglet === 'objections' && $scriptCourant->objections)
                                @foreach ($scriptCourant->objections as $obj)
                                    <div class="pw-objection">
                                        <p class="pw-objection-q">"{{ $obj['question'] }}"</p>
                                        <p class="pw-objection-r">→ {{ $obj['reponse'] }}</p>
                                    </div>
                                @endforeach
                            @elseif($scriptCourant->onglet === 'argumentaire' && $scriptCourant->kpis)
                                @if ($scriptCourant->contenu)
                                    <div class="pw-script-text">{!! nl2br(e($scriptCourant->interpoler($variables))) !!}</div>
                                @endif
                                <div class="pw-kpis">
                                    @foreach ($scriptCourant->kpis as $kpi)
                                        @php
                                            $kpiColors = [
                                                'purple' => 'color:rgb(147 51 234)',
                                                'blue' => 'color:rgb(37 99 235)',
                                                'green' => 'color:rgb(22 163 74)',
                                                'orange' => 'color:rgb(234 88 12)',
                                            ];
                                            $kpiColor = $kpiColors[$kpi['couleur'] ?? 'purple'] ?? $kpiColors['purple'];
                                        @endphp
                                        <div class="pw-kpi">
                                            <div class="pw-kpi-val" style="{{ $kpiColor }}">{{ $kpi['valeur'] }}
                                            </div>
                                            <div class="pw-kpi-lbl">{{ $kpi['label'] }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                @if ($scriptCourant->contenu)
                                    <div class="pw-script-text">{!! nl2br(e($scriptCourant->interpoler($variables))) !!}</div>
                                @endif
                            @endif

                            @if ($scriptCourant->interpolerConseil($variables))
                                <div class="pw-conseil">
                                    <svg style="width:1rem;height:1rem;flex-shrink:0;margin-top:0.125rem;"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                                    </svg>
                                    <span>{{ $scriptCourant->interpolerConseil($variables) }}</span>
                                </div>
                            @endif
                        @else
                            <div class="pw-script-empty">
                                Aucun script disponible pour ce type de campagne.
                                <a href="{{ route('filament.ns-conseil.resources.script-appels.create') }}"
                                    style="margin-left:0.5rem; text-decoration:underline; font-weight:600;">
                                    Créer un script →
                                </a>
                            </div>
                        @endif
                    </div>

                    {{-- ═══ PANNEAU INFORMATIONS PROSPECT (onglets) ═══ --}}
                    <div class="pw-infos">
                        <div class="pw-infos-header">
                            <span class="pw-infos-title">🗂 Dossier Prospect</span>
                            @if (!empty($info['id']) && $info['type'] === 'prospect')
                                <a href="{{ route('filament.ns-conseil.resources.prospects.view', $info['id']) }}"
                                    target="_blank"
                                    style="font-size:0.75rem; color:rgb(37 99 235); text-decoration:underline;">
                                    Ouvrir le dossier complet →
                                </a>
                            @endif
                        </div>

                        {{-- Mini onglets infos --}}
                        <div class="pw-info-tabs">
                            <button class="pw-info-tab active" data-tab="contact" onclick="switchInfoTab('contact')">📞
                                Contact</button>
                            @if (($info['type'] ?? '') !== 'client')
                                <button class="pw-info-tab" data-tab="entreprise"
                                    onclick="switchInfoTab('entreprise')">🏢 Entreprise</button>
                            @endif
                            @if (!empty($info['interlocuteur_nom']) || !empty($info['interlocuteur']))
                                <button class="pw-info-tab" data-tab="interlocuteur"
                                    onclick="switchInfoTab('interlocuteur')">👤 Interlocuteur</button>
                            @endif
                            @if (($info['type'] ?? '') === 'prospect')
                                <button class="pw-info-tab" data-tab="standard-cse"
                                    onclick="switchInfoTab('standard-cse')">📋 Standard / CSE</button>
                            @endif
                            @if (($info['type'] ?? '') !== 'client')
                                <button class="pw-info-tab" data-tab="pipeline" onclick="switchInfoTab('pipeline')">📊
                                    Pipeline</button>
                            @endif
                            @if (($info['type'] ?? '') === 'prospect' && !empty($info['notes']))
                                <button class="pw-info-tab" data-tab="notes" onclick="switchInfoTab('notes')">📝
                                    Notes</button>
                            @endif
                            <button class="pw-info-tab" data-tab="journal" onclick="switchInfoTab('journal')">
                                📋 Journal
                                @if (count($callHistory) > 0)
                                    <span
                                        style="display:inline-flex;align-items:center;justify-content:center;min-width:1.25rem;height:1.25rem;padding:0 0.25rem;border-radius:9999px;background:rgb(99 102 241);color:white;font-size:0.65rem;font-weight:700;margin-left:0.25rem;">{{ count($callHistory) }}</span>
                                @endif
                            </button>
                            @if (($info['type'] ?? '') !== 'client')
                                <button class="pw-info-tab" data-tab="rdv" onclick="switchInfoTab('rdv')">📅
                                    RDV</button>
                            @endif
                        </div>

                        {{-- Panel : Contact --}}
                        <div class="pw-info-panel" data-tab="contact">
                            <div class="pw-info-grid">
                                <div class="pw-field-full">
                                    <div class="pw-field-label">Téléphone principal</div>
                                    <div style="display:flex; gap:0.5rem; align-items:center;">
                                        <span
                                            style="padding:0.375rem 0.5rem; background:rgb(249 250 251); border:1px solid rgb(209 213 219); border-radius:0.375rem; font-size:0.75rem; color:rgb(107 114 128);">🇫🇷
                                            +33</span>
                                        <input type="text" value="{{ $info['telephone'] ?? '' }}" readonly
                                            class="pw-field-input"
                                            style="flex:1; font-weight:600; font-size:0.9375rem; letter-spacing:0.025em;"
                                            onclick="this.select(); document.execCommand('copy');"
                                            title="Cliquer pour copier">
                                        @if (!empty($info['telephone']))
                                            <a href="tel:{{ $info['telephone'] }}"
                                                style="padding:0.375rem 0.625rem; background:rgb(34 197 94); color:white; border-radius:0.375rem; font-size:0.75rem; font-weight:600; text-decoration:none; white-space:nowrap;">
                                                Appeler
                                            </a>
                                        @endif
                                    </div>
                                </div>

                                @if (!empty($info['telephone_alt']))
                                    <div class="pw-field-full">
                                        <div class="pw-field-label">Téléphone secondaire</div>
                                        <input type="text" value="{{ $info['telephone_alt'] }}" readonly
                                            class="pw-field-input"
                                            onclick="this.select(); document.execCommand('copy');"
                                            title="Cliquer pour copier">
                                    </div>
                                @endif

                                <div>
                                    <div class="pw-field-label">Email</div>
                                    @if (!empty($info['email']))
                                        <a href="mailto:{{ $info['email'] }}"
                                            style="font-size:0.8125rem; color:rgb(37 99 235);">{{ $info['email'] }}</a>
                                    @else
                                        <span style="font-size:0.8125rem; color:rgb(156 163 175);">—</span>
                                    @endif
                                </div>

                                <div>
                                    <div class="pw-field-label">Localisation</div>
                                    <div class="pw-field-value">
                                        {{ collect([$info['ville'] ?? null, $info['code_postal'] ?? null, $info['departement'] ?? null])->filter()->implode(' · ') ?:'—' }}
                                    </div>
                                </div>

                                @if (!empty($info['adresse_complete']))
                                    <div class="pw-field-full">
                                        <div class="pw-field-label">Adresse complète</div>
                                        <div class="pw-field-value" style="font-size:0.8125rem;">
                                            {{ $info['adresse_complete'] }}</div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Panel : Entreprise --}}
                        <div class="pw-info-panel" data-tab="entreprise" style="display:none;">
                            <div class="pw-info-grid">
                                <div>
                                    <div class="pw-field-label">Raison sociale</div>
                                    <div class="pw-field-value" style="font-weight:700; font-size:0.9375rem;">
                                        {{ $info['nom'] ?? '—' }}</div>
                                </div>
                                <div>
                                    <div class="pw-field-label">SIRET</div>
                                    <div class="pw-field-value copyable"
                                        onclick="copyToClipboard('{{ $info['siret'] ?? '' }}')">
                                        {{ $info['siret'] ?? '—' }}
                                    </div>
                                </div>
                                <div>
                                    <div class="pw-field-label">Type pressenti</div>
                                    <div class="pw-field-value">{{ $info['type_pressenti'] ?? '—' }}</div>
                                </div>
                                <div>
                                    <div class="pw-field-label">Secteur d'activité</div>
                                    <div class="pw-field-value">{{ $info['secteur_activite'] ?? '—' }}</div>
                                </div>
                                <div>
                                    <div class="pw-field-label">Nombre de salariés</div>
                                    <div class="pw-field-value">
                                        {{ $info['nb_salaries'] ?? null ? $info['nb_salaries'] . ' salariés' : '—' }}
                                    </div>
                                </div>
                                <div>
                                    <div class="pw-field-label">Chiffre d'affaires</div>
                                    <div class="pw-field-value">{{ $info['chiffre_affaires'] ?? '—' }}</div>
                                </div>
                            </div>
                        </div>

                        {{-- Panel : Interlocuteur --}}
                        @if (!empty($info['interlocuteur_nom']) || !empty($info['interlocuteur']))
                            <div class="pw-info-panel" data-tab="interlocuteur" style="display:none;">
                                <div class="pw-interlocuteur-card">
                                    <div
                                        style="display:flex; align-items:center; gap:0.75rem; margin-bottom:0.875rem;">
                                        <div
                                            style="width:2.5rem; height:2.5rem; border-radius:9999px; background:rgb(219 234 254); display:flex; align-items:center; justify-content:center; font-weight:700; color:rgb(37 99 235);">
                                            {{ strtoupper(substr($info['interlocuteur_nom'] ?? ($info['interlocuteur'] ?? '?'), 0, 1)) }}
                                        </div>
                                        <div>
                                            <div style="font-weight:700; font-size:0.9375rem;">
                                                {{ $info['interlocuteur_nom'] ?? ($info['interlocuteur'] ?? '—') }}
                                            </div>
                                            @if (!empty($info['interlocuteur_fonction']))
                                                <div style="font-size:0.8125rem; color:rgb(107 114 128);">
                                                    {{ $info['interlocuteur_fonction'] }}</div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="pw-info-grid">
                                        <div>
                                            <div class="pw-field-label">Téléphone direct</div>
                                            <div class="pw-field-value">{{ $info['interlocuteur_telephone'] ?? '—' }}
                                            </div>
                                        </div>
                                        <div>
                                            <div class="pw-field-label">Email direct</div>
                                            @if (!empty($info['interlocuteur_email']))
                                                <a href="mailto:{{ $info['interlocuteur_email'] }}"
                                                    style="font-size:0.8125rem; color:rgb(37 99 235);">{{ $info['interlocuteur_email'] }}</a>
                                            @else
                                                <div class="pw-field-value">—</div>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                {{-- Nouveau contact identifié à l'appel --}}
                                <div style="margin-top:1rem;">
                                    <div class="pw-field-label" style="margin-bottom:0.5rem;">Nouveau contact
                                        identifié à l'appel</div>
                                    <div class="pw-info-grid">
                                        <div>
                                            <div class="pw-field-label">Prénom</div>
                                            <input type="text" class="pw-field-input" placeholder="Prénom">
                                        </div>
                                        <div>
                                            <div class="pw-field-label">Nom</div>
                                            <input type="text" class="pw-field-input" placeholder="Nom">
                                        </div>
                                        <div>
                                            <div class="pw-field-label">Fonction</div>
                                            <input type="text" class="pw-field-input" placeholder="Fonction">
                                        </div>
                                        <div>
                                            <div class="pw-field-label">Téléphone</div>
                                            <input type="text" class="pw-field-input" placeholder="Téléphone">
                                        </div>
                                        <div class="pw-field-full">
                                            <div class="pw-field-label">Email</div>
                                            <input type="email" class="pw-field-input"
                                                placeholder="email@domaine.fr">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Panel : Standard / CSE (prospect seulement) --}}
                        @if (($info['type'] ?? '') === 'prospect')
                            <div class="pw-info-panel" data-tab="standard-cse" style="display:none;">

                                {{-- Section Interlocuteur Standard --}}
                                <div style="margin-bottom:1rem;">
                                    <div style="font-size:0.6875rem; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; color:rgb(100 116 139); margin-bottom:0.625rem; padding-bottom:0.375rem; border-bottom:1px solid rgb(241 245 249);">
                                        📞 Interlocuteur Standard
                                    </div>
                                    <div class="pw-info-grid">
                                        <div class="pw-field-full">
                                            <div class="pw-field-label">Nom interlocuteur standard</div>
                                            <input type="text" wire:model="nom_interlocuteur_standard"
                                                class="pw-field-input" placeholder="Nom obtenu au standard">
                                        </div>
                                        <div class="pw-field-full">
                                            <div class="pw-field-label">Créneaux de permanence CSE</div>
                                            <input type="text" wire:model="creneaux_permanence_cse"
                                                class="pw-field-input" placeholder="ex : Lundi 14h-16h">
                                        </div>
                                        <div class="pw-field-full">
                                            <div class="pw-field-label">Email général (si obtenu au standard)</div>
                                            <input type="email" wire:model="email_general_standard"
                                                class="pw-field-input" placeholder="contact@entreprise.fr">
                                        </div>
                                    </div>
                                </div>

                                {{-- Section Interlocuteur CSE --}}
                                <div>
                                    <div style="font-size:0.6875rem; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; color:rgb(100 116 139); margin-bottom:0.625rem; padding-bottom:0.375rem; border-bottom:1px solid rgb(241 245 249);">
                                        👥 Interlocuteur CSE
                                        <span style="font-size:0.6rem; color:rgb(239 68 68); margin-left:0.25rem;">* Obligatoire QF</span>
                                    </div>
                                    <div class="pw-info-grid">
                                        <div class="pw-field-full">
                                            <div class="pw-field-label">
                                                Prénom / Nom du CSE
                                                <span style="color:rgb(239 68 68);">*</span>
                                            </div>
                                            <input type="text" wire:model="interlocuteur_nom"
                                                class="pw-field-input" placeholder="Prénom Nom du responsable CSE">
                                        </div>
                                        <div class="pw-field-full">
                                            <div class="pw-field-label">Fonction du CSE</div>
                                            <select wire:model="interlocuteur_fonction" class="pw-field-input">
                                                <option value="">— Sélectionner —</option>
                                                <option value="Secrétaire">Secrétaire</option>
                                                <option value="Trésorier">Trésorier</option>
                                                <option value="Président">Président</option>
                                                <option value="Élu">Élu</option>
                                            </select>
                                        </div>
                                        <div>
                                            <div class="pw-field-label">Téléphone direct CSE</div>
                                            <input type="tel" wire:model="interlocuteur_telephone"
                                                class="pw-field-input" placeholder="06 XX XX XX XX">
                                        </div>
                                        <div>
                                            <div class="pw-field-label">
                                                Email CSE
                                                <span style="color:rgb(239 68 68);">*</span>
                                                <span style="font-size:0.6rem; color:rgb(100 116 139);">Déclenche Mail 1</span>
                                            </div>
                                            <input type="email" wire:model="interlocuteur_email"
                                                class="pw-field-input" placeholder="cse@entreprise.fr">
                                        </div>
                                    </div>
                                </div>

                            </div>
                        @endif

                        {{-- Panel : Pipeline --}}
                        <div class="pw-info-panel" data-tab="pipeline" style="display:none;">
                            <div class="pw-info-grid">
                                <div>
                                    <div class="pw-field-label">Statut actuel</div>
                                    <span class="pw-badge {{ $statutCls }}"
                                        style="font-size:0.8125rem; padding:0.25rem 0.625rem;">
                                        {{ $statutLabel }}
                                    </span>
                                </div>
                                <div>
                                    <div class="pw-field-label">Téléprospecteur</div>
                                    <div class="pw-field-value">{{ $info['teleprospecteur'] ?? '—' }}</div>
                                </div>
                                <div>
                                    <div class="pw-field-label">Commercial</div>
                                    <div class="pw-field-value">{{ $info['commercial'] ?? '—' }}</div>
                                </div>
                                <div>
                                    <div class="pw-field-label">1er contact</div>
                                    <div class="pw-field-value">
                                        {{ $info['date_premier_contact'] ?? 'Jamais contacté' }}</div>
                                </div>
                                @if (!empty($info['rappel_planifie_at']))
                                    <div class="pw-field-full">
                                        <div class="pw-field-label">Rappel planifié</div>
                                        <div class="pw-field-value {{ $info['rappel_en_retard'] ? 'pbo-rappel-retard' : '' }}"
                                            style="{{ $info['rappel_en_retard'] ? 'color:rgb(239 68 68); font-weight:700;' : '' }}">
                                            🕐 {{ $info['rappel_planifie_at'] }}
                                            @if ($info['rappel_en_retard'])
                                                — EN RETARD
                                            @endif
                                        </div>
                                    </div>
                                @endif
                                @if (!empty($info['statut_description']))
                                    <div class="pw-field-full">
                                        <div class="pw-field-label">Description statut</div>
                                        <div class="pw-field-value"
                                            style="font-size:0.8125rem; color:rgb(107 114 128);">
                                            {{ $info['statut_description'] }}</div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Panel : Notes --}}
                        @if (!empty($noteLines))
                            <div class="pw-info-panel" data-tab="notes" style="display:none;">
                                <div class="pw-notes-list">
                                    @foreach (array_reverse($noteLines) as $note)
                                        <div class="pw-note-item">
                                            @if ($note['date'])
                                                <div class="pw-note-date">{{ $note['date'] }}</div>
                                            @endif
                                            <div style="font-size:0.8125rem;">{{ $note['text'] }}</div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Panel : Journal d'appels --}}
                        <div class="pw-info-panel" data-tab="journal" style="display:none;">
                            @if (count($callHistory) === 0)
                                <div style="text-align:center; padding:2rem 1rem; color:rgb(156 163 175);">
                                    <div style="font-size:2rem; margin-bottom:0.5rem;">📋</div>
                                    <div style="font-size:0.875rem;">Aucun appel enregistré pour ce contact.</div>
                                </div>
                            @else
                                <div style="display:flex; flex-direction:column; gap:0.625rem;">
                                    @foreach ($callHistory as $appel)
                                        @php
                                            $jColor = match ($appel['statut'] ?? '') {
                                                'std_nr', 'cse_nr' => [
                                                    'bg' => 'rgb(243 244 246)',
                                                    'border' => 'rgb(209 213 219)',
                                                    'badge' => 'rgb(107 114 128)',
                                                ],
                                                'std_joint' => [
                                                    'bg' => 'rgb(239 246 255)',
                                                    'border' => 'rgb(147 197 253)',
                                                    'badge' => 'rgb(59 130 246)',
                                                ],
                                                'rp' => [
                                                    'bg' => 'rgb(240 253 244)',
                                                    'border' => 'rgb(134 239 172)',
                                                    'badge' => 'rgb(22 163 74)',
                                                ],
                                                'rpc' => [
                                                    'bg' => 'rgb(240 253 250)',
                                                    'border' => 'rgb(94 234 212)',
                                                    'badge' => 'rgb(13 148 136)',
                                                ],
                                                'ko' => [
                                                    'bg' => 'rgb(255 241 242)',
                                                    'border' => 'rgb(252 165 165)',
                                                    'badge' => 'rgb(220 38 38)',
                                                ],
                                                default => [
                                                    'bg' => 'rgb(248 250 252)',
                                                    'border' => 'rgb(226 232 240)',
                                                    'badge' => 'rgb(100 116 139)',
                                                ],
                                            };
                                        @endphp
                                        <div
                                            style="border-radius:0.5rem; border:1px solid {{ $jColor['border'] }}; background:{{ $jColor['bg'] }}; padding:0.625rem 0.75rem;">
                                            <div
                                                style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:0.375rem;">
                                                <div
                                                    style="display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap;">
                                                    <span
                                                        style="font-size:0.7rem; font-weight:700; padding:0.125rem 0.5rem; border-radius:9999px; background:{{ $jColor['badge'] }}; color:white; text-transform:uppercase; letter-spacing:0.05em;">
                                                        {{ $appel['statut_label'] }}
                                                    </span>
                                                    @if ($appel['campagne'] ?? null)
                                                        <span
                                                            style="font-size:0.7rem; padding:0.125rem 0.375rem; border-radius:9999px; background:rgb(238 242 255); color:rgb(79 70 229); border:1px solid rgb(199 210 254);">
                                                            {{ $appel['campagne'] }}
                                                        </span>
                                                    @endif
                                                </div>
                                                <span
                                                    style="font-size:0.7rem; color:rgb(107 114 128); white-space:nowrap; margin-left:0.5rem;">{{ $appel['date'] }}</span>
                                            </div>
                                            <div
                                                style="font-size:0.75rem; color:rgb(55 65 81); margin-bottom:{{ $appel['notes'] ? '0.25rem' : '0' }};">
                                                <span style="font-weight:600;">{{ $appel['agent'] }}</span>
                                            </div>
                                            @if ($appel['notes'])
                                                <div
                                                    style="font-size:0.75rem; color:rgb(75 85 99); background:rgba(255,255,255,0.6); border-radius:0.25rem; padding:0.25rem 0.5rem; margin-top:0.25rem; border-left:2px solid {{ $jColor['badge'] }};">
                                                    {{ $appel['notes'] }}
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        {{-- Panel : RDV --}}
                        <div class="pw-info-panel" data-tab="rdv" style="display:none;">
                            <div class="pw-info-grid">
                                <div>
                                    <div class="pw-field-label">Date RDV</div>
                                    <input type="date" wire:model="rappel_date" class="pw-field-input">
                                </div>
                                <div>
                                    <div class="pw-field-label">Heure RDV</div>
                                    <input type="time" wire:model="rappel_heure" class="pw-field-input">
                                </div>
                                <div class="pw-field-full">
                                    <div class="pw-field-label">Lieu / Adresse du RDV</div>
                                    <input type="text" class="pw-field-input"
                                        placeholder="Adresse ou visioconférence">
                                </div>
                                <div class="pw-field-full">
                                    <div class="pw-field-label">Note RDV</div>
                                    <textarea class="pw-field-input" rows="2" placeholder="Précisions sur le rendez-vous..."></textarea>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>{{-- /pw-left --}}

                {{-- ═══ COLONNE DROITE ═══ --}}
                <div class="pw-right">

                    {{-- Tentatives --}}
                    <div class="pw-nr-box">
                        <div
                            style="display:flex; justify-content:space-between; align-items:center; margin-bottom:0.25rem;">
                            <span class="pw-nr-title">Sans réponse</span>
                            <span class="pw-nr-subtitle">08:00 – 18:00</span>
                        </div>
                        <div style="display:flex; align-items:baseline; gap:0.375rem; margin-bottom:0.5rem;">
                            <span class="pw-nr-count">{{ $tentativesActuelles }}</span>
                            <span class="pw-nr-tentatives">/ {{ $maxTentatives }} tentatives</span>
                        </div>
                        {{-- Mini barre tentatives --}}
                        <div style="display:flex; gap:0.25rem;">
                            @for ($i = 0; $i < $maxTentatives; $i++)
                                <div
                                    style="flex:1; height:0.25rem; border-radius:9999px; background:{{ $i < $tentativesActuelles ? 'rgb(249 115 22)' : 'rgb(229 231 235)' }};">
                                </div>
                            @endfor
                        </div>
                    </div>

                    {{-- Issue de l'appel --}}
                    <div
                        style="font-size:0.6875rem; font-weight:700; text-transform:uppercase; letter-spacing:0.08em; color:rgb(100 116 139); margin-bottom:0.5rem; padding-bottom:0.375rem; border-bottom:1px solid rgb(241 245 249);">
                        Résultat de l'appel
                    </div>

                    <div style="display:flex; flex-direction:column; gap:0.875rem;">
                        @foreach ($statutsGroupes as $groupeKey => $groupe)
                            <div>
                                <div style="font-size:0.65rem; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; color:rgb(100 116 139); margin-bottom:0.375rem; padding-bottom:0.25rem; border-bottom:1px solid rgb(241 245 249);">
                                    {{ $groupe['label'] }}
                                </div>
                                <div style="display:grid; gap:0.375rem;">
                                    @foreach ($groupe['statuts'] as $option)
                                        @php
                                            $isActive = $statut_resultat === $option['value'];
                                        @endphp
                                        <label wire:click="$set('statut_resultat', '{{ $option['value'] }}')"
                                            onclick="toggleRappel('{{ $option['value'] }}')"
                                            style="display:flex; align-items:center; gap:0.625rem; padding:0.5rem 0.625rem; border-radius:0.5rem; cursor:pointer; transition:all .15s ease;
                                      border:1.5px solid {{ $isActive ? 'currentColor' : 'rgb(226 232 240)' }};
                                      background:{{ $isActive ? 'rgba(0,0,0,0.03)' : 'white' }};
                                      {{ $isActive ? $option['bar'] . '; border-color:' . \Illuminate\Support\Str::after($option['bar'], 'background:') . ';' : '' }}">
                                            <div style="font-size:1.1rem; width:1.5rem; text-align:center; flex-shrink:0;">
                                                {{ $option['icon'] ?? '•' }}</div>
                                            <div style="flex:1; min-width:0;">
                                                <div
                                                    style="font-size:0.8125rem; font-weight:600; color:{{ $isActive ? 'white' : 'rgb(30 41 59)' }};">
                                                    {{ $option['label'] }}
                                                    @if (!empty($option['prioritaire']))
                                                        <span style="font-size:0.6rem; background:#E0FAF9; color:#006b68; padding:1px 5px; border-radius:8px; margin-left:4px;">prioritaire</span>
                                                    @endif
                                                </div>
                                                <div
                                                    style="font-size:0.7rem; color:{{ $isActive ? 'rgba(255,255,255,0.8)' : 'rgb(100 116 139)' }};">
                                                    {{ $option['sub'] }}</div>
                                                @if (!empty($option['action']))
                                                    <div style="font-size:0.65rem; color:{{ $isActive ? 'rgba(255,255,255,0.7)' : 'rgb(148 163 184)' }}; margin-top:2px;">
                                                        → {{ $option['action'] }}
                                                    </div>
                                                @endif
                                            </div>
                                            @if ($isActive)
                                                <svg style="width:1rem;height:1rem;color:white;flex-shrink:0;" fill="currentColor"
                                                    viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd"
                                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                        clip-rule="evenodd" />
                                                </svg>
                                            @endif
                                            <input type="radio" wire:model="statut_resultat" value="{{ $option['value'] }}"
                                                style="display:none;">
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Bloc rappel conditionnel --}}
                    <div id="pw-rappel-box"
                        class="pw-rappel-box {{ in_array($statut_resultat, $rappelCodes) ? 'visible' : '' }}">
                        <div class="pw-rappel-box-title">
                            @if (in_array($statut_resultat, ['rapl_elu', 'rapl_std']))
                                ⏰ Créneau de rappel
                            @else
                                📅 Planifier le rappel / RDV
                            @endif
                        </div>
                        @if ($statut_resultat === 'rapl_elu')
                            <div style="font-size:0.7rem; background:#fffbe6; border:1px dashed #d4a800; border-radius:4px; padding:4px 8px; color:#7a5c00; margin-bottom:0.5rem;">
                                📝 Note obligatoire dans le compte rendu : date + heure + nom de l'élu
                            </div>
                        @elseif ($statut_resultat === 'rapl_std')
                            <div style="font-size:0.7rem; background:#fffbe6; border:1px dashed #d4a800; border-radius:4px; padding:4px 8px; color:#7a5c00; margin-bottom:0.5rem;">
                                📝 Note obligatoire dans le compte rendu : date + heure + nom du standard
                            </div>
                        @endif
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.5rem;">
                            <div>
                                <div class="pw-field-label">Date</div>
                                <input type="date" wire:model="rappel_date" class="pw-field-input">
                            </div>
                            <div>
                                <div class="pw-field-label">Heure</div>
                                <input type="time" wire:model="rappel_heure" class="pw-field-input">
                            </div>
                        </div>
                    </div>

                    {{-- ═══ FICHE BLEUE — RDV confirmé ═══ --}}
                    @if ($statut_resultat === 'rdv')
                        <div style="margin-top:0.75rem; background:rgb(239 246 255); border:2px solid rgb(59 130 246); border-radius:0.5rem; overflow:hidden;">
                            <div style="background:rgb(59 130 246); color:white; padding:0.5rem 0.875rem; font-size:0.75rem; font-weight:700; display:flex; align-items:center; gap:0.5rem;">
                                🔵 FICHE RECAP RDV PRIS
                            </div>
                            <div style="padding:0.875rem; display:flex; flex-direction:column; gap:0.625rem;">
                                <div>
                                    <div class="pw-field-label">Lieu du RDV</div>
                                    <input type="text" wire:model="lieu_rdv" class="pw-field-input"
                                        placeholder="Adresse / Agence AOPIA / Visioconférence">
                                </div>
                                <div style="display:grid; grid-template-columns:1fr 1fr; gap:0.5rem;">
                                    <label style="display:flex; align-items:center; gap:0.5rem; font-size:0.8125rem; cursor:pointer; padding:0.375rem; background:white; border-radius:0.375rem; border:1px solid rgb(209 213 219);">
                                        <input type="checkbox" wire:model="invitation_agenda_envoyee" style="width:1rem;height:1rem;">
                                        Invitation agenda envoyée
                                    </label>
                                    <div>
                                        <label style="display:flex; align-items:center; gap:0.5rem; font-size:0.8125rem; cursor:pointer; padding:0.375rem; background:white; border-radius:0.375rem; border:1px solid rgb(209 213 219);">
                                            <input type="checkbox" wire:model="enregistrement_appel_joint" style="width:1rem;height:1rem;">
                                            Enregistrement joint
                                        </label>
                                        @if (!$enregistrement_appel_joint)
                                            <input type="text" wire:model="enregistrement_raison" class="pw-field-input" style="margin-top:0.25rem; font-size:0.75rem;" placeholder="Raison...">
                                        @endif
                                    </div>
                                </div>
                                <div>
                                    <div class="pw-field-label">Besoins exprimés par le CSE</div>
                                    <textarea wire:model="besoins_exprimes" rows="2" class="pw-field-input" style="resize:vertical; margin-top:0;" placeholder="Résumé des besoins / attentes identifiées..."></textarea>
                                </div>
                                <div>
                                    <div class="pw-field-label">Objections soulevées</div>
                                    <textarea wire:model="objections_soulevees" rows="2" class="pw-field-input" style="resize:vertical; margin-top:0;" placeholder="Objections rencontrées et façon dont elles ont été traitées..."></textarea>
                                </div>
                                <div>
                                    <div class="pw-field-label">Points d'attention pour le RDV</div>
                                    <textarea wire:model="points_attention_rdv" rows="2" class="pw-field-input" style="resize:vertical; margin-top:0;" placeholder="Éléments particuliers à transmettre au Responsable de Secteur..."></textarea>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- ═══ FICHE JAUNE — CSE non intéressé ═══ --}}
                    @if ($statut_resultat === 'cse_ni')
                        <div style="margin-top:0.75rem; background:rgb(255 251 235); border:2px solid rgb(234 179 8); border-radius:0.5rem; overflow:hidden;">
                            <div style="background:rgb(234 179 8); color:rgb(66 32 6); padding:0.5rem 0.875rem; font-size:0.75rem; font-weight:700; display:flex; align-items:center; gap:0.5rem;">
                                🟡 FICHE RECAP RDV À PRENDRE — Rappel J+7
                            </div>
                            <div style="padding:0.875rem; font-size:0.8125rem; color:rgb(92 52 8);">
                                <p style="margin:0 0 0.5rem; font-weight:600;">Un email sera envoyé par l'assistante commerciale.</p>
                                <ul style="margin:0; padding-left:1.25rem; font-size:0.75rem; color:rgb(120 53 15); line-height:1.7;">
                                    <li>Coordonnées CSE → onglet <strong>Standard / CSE</strong> ci-contre</li>
                                    <li>Commentaires → champ compte rendu ci-dessous</li>
                                    <li>Date rappel J+7 → bloc rappel ci-dessus (auto : {{ now()->addDays(7)->format('d/m/Y') }})</li>
                                </ul>
                            </div>
                        </div>
                    @endif

                    {{-- ═══ FICHE VERTE — RDV à conclure ═══ --}}
                    @if (in_array($statut_resultat, ['bloc2', 'ncse_50', 'ncse_plus50', 'cse_zone']))
                        <div style="margin-top:0.75rem; background:rgb(240 253 244); border:2px solid rgb(34 197 94); border-radius:0.5rem; overflow:hidden;">
                            <div style="background:rgb(34 197 94); color:white; padding:0.5rem 0.875rem; font-size:0.75rem; font-weight:700; display:flex; align-items:center; gap:0.5rem;">
                                🟢 FICHE RECAP RDV À CONCLURE — Commercial
                            </div>
                            <div style="padding:0.875rem; display:flex; flex-direction:column; gap:0.625rem;">
                                <div>
                                    <div class="pw-field-label">Présence d'un CSE</div>
                                    <select wire:model="presence_cse" class="pw-field-input">
                                        <option value="">— Sélectionner —</option>
                                        <option value="oui">Oui</option>
                                        <option value="non">Non</option>
                                        <option value="a_confirmer">À confirmer</option>
                                    </select>
                                </div>
                                <div>
                                    <div class="pw-field-label">Jour disponible pour l'appel</div>
                                    <input type="text" wire:model="jour_dispo_appel" class="pw-field-input"
                                        placeholder="ex : Lundi matin, Mercredi 14h-16h">
                                </div>
                                <div style="font-size:0.75rem; color:rgb(22 101 52); background:rgb(220 252 231); border-radius:0.375rem; padding:0.5rem 0.75rem; line-height:1.6;">
                                    Coordonnées CSE → onglet <strong>Standard / CSE</strong> · Commentaires → champ ci-dessous
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Compte rendu --}}
                    <textarea wire:model="commentaires" rows="4"
                        placeholder="Compte rendu : interlocuteur joint, objections, décision, prochaine étape..." class="pw-textarea"></textarea>

                    {{-- Validation --}}
                    @if ($statut_resultat && in_array($statut_resultat, ['rapl_elu', 'rapl_std']) && !$commentaires)
                        <div
                            style="font-size:0.75rem; color:rgb(220 38 38); margin-top:0.5rem; display:flex; align-items:center; gap:0.25rem;">
                            📝 Note obligatoire : date + heure + nom {{ $statut_resultat === 'rapl_elu' ? 'de l\'élu' : 'du standard' }}.
                        </div>
                    @elseif ($statut_resultat && !$commentaires && !in_array($statut_resultat, ['nrp', 'fax', 'maj']))
                        <div
                            style="font-size:0.75rem; color:rgb(249 115 22); margin-top:0.5rem; display:flex; align-items:center; gap:0.25rem;">
                            ⚠ Ajoutez un commentaire avant d'enregistrer.
                        </div>
                    @endif

                    {{-- Actions --}}
                    <div class="pw-actions">
                        <button wire:click="submitResult" class="pw-btn-primary"
                            {{ !$statut_resultat ? 'disabled style=opacity:.5;cursor:not-allowed' : '' }}>
                            ✓ Enregistrer &amp; suivant
                        </button>
                        <button wire:click.prevent="skipCall" class="pw-btn-secondary"
                            title="Repousser en fin de file">
                            ↷ Passer
                        </button>
                    </div>

                </div>{{-- /pw-right --}}

            </div>{{-- /pw-body --}}
        @else
            {{-- ── État vide ── --}}
            <div style="display:flex; align-items:center; justify-content:center; min-height:60vh;">
                <div style="text-align:center;">
                    <div
                        style="width:5rem; height:5rem; border-radius:9999px; background:rgb(243 244 246); display:flex; align-items:center; justify-content:center; margin:0 auto 1rem auto;">
                        <svg style="width:2.25rem;height:2.25rem;color:rgb(156 163 175);" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                        </svg>
                    </div>
                    <h3 style="font-size:1.125rem; font-weight:700; margin:0 0 0.5rem;">File vide</h3>
                    <p style="color:rgb(107 114 128); margin:0 0 1.5rem;">
                        Tous les contacts ont été traités ou aucun prospect n'est assigné à ce téléprospecteur.
                    </p>
                    <div style="display:flex; gap:0.75rem; justify-content:center; flex-wrap:wrap;">
                        <button wire:click="loadQueue" wire:then="loadNextContact"
                            style="padding:0.5rem 1.5rem; background:rgb(37 99 235); color:white; border-radius:0.5rem; font-weight:600; border:none; cursor:pointer;">
                            Rafraîchir
                        </button>
                        <a href="{{ route('filament.ns-conseil.pages.phoning-back-office') }}"
                            style="padding:0.5rem 1.5rem; background:rgb(249 250 251); color:rgb(55 65 81); border-radius:0.5rem; font-weight:600; border:1px solid rgb(229 231 235); text-decoration:none;">
                            ⚙ Gérer la file
                        </a>
                    </div>
                </div>
            </div>
        @endif

    </div>{{-- /pw-wrap --}}

</x-filament-panels::page>
