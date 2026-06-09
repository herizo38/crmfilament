{{-- resources/views/filament/ns-conseil/pages/phoning-back-office.blade.php --}}
<x-filament-panels::page>

    @push('styles')
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link
            href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap"
            rel="stylesheet">
        <style>
            /* ══════════════════════════════════════════════════
               PBO — Back-Office Phoning  (v3 — style tableau)
            ══════════════════════════════════════════════════ */
            .pbo *,
            .pbo *::before,
            .pbo *::after {
                box-sizing: border-box;
            }

            .pbo {
                font-family: 'Inter', sans-serif;
                display: flex;
                flex-direction: column;
                gap: 0;
                --pbo-border: #e5e7eb;
                --pbo-bg: #ffffff;
                --pbo-bg2: #f9fafb;
                --pbo-text: #111827;
                --pbo-muted: #6b7280;
                --pbo-accent: #2563eb;
                --pbo-radius: 10px;
            }

            .dark .pbo {
                --pbo-border: #1f2937;
                --pbo-bg: #111827;
                --pbo-bg2: #0d1117;
                --pbo-text: #f3f4f6;
                --pbo-muted: #6b7280;
            }

            /* ── Toolbar supérieure ── */
            .pbo-topbar {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 0 0 14px;
                flex-wrap: wrap;
            }

            /* .pbo-user-select-wrap {
                position: relative;
                min-width: 220px;
            } */
            .pbo-user-select-wrap::after {
                content: '';
                position: absolute;
                right: 10px;
                top: 50%;
                transform: translateY(-50%);
                width: 0;
                height: 0;
                border-left: 4px solid transparent;
                border-right: 4px solid transparent;
                border-top: 5px solid var(--pbo-muted);
                pointer-events: none;
            }

            .pbo-user-select {
                width: 100%;
                appearance: none;
                padding: 8px 30px 8px 12px;
                border: 1px solid var(--pbo-border);
                border-radius: 8px;
                font-size: 13.5px;
                font-weight: 500;
                font-family: 'Inter', sans-serif;
                /* background: var(--pbo-bg); */
                color: var(--pbo-text);
                cursor: pointer;
                outline: none;
                transition: border-color .15s, box-shadow .15s;
            }

            .pbo-user-select:focus {
                border-color: var(--pbo-accent);
                box-shadow: 0 0 0 3px rgba(37, 99, 235, .12);
            }

            .pbo-topbar-spacer {
                flex: 1;
            }

            /* Pills stats */
            .pbo-pills {
                display: flex;
                gap: 8px;
                align-items: center;
                flex-wrap: wrap;
            }

            .pbo-pill {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 5px 12px;
                border-radius: 20px;
                font-size: 12px;
                font-weight: 600;
                border: 1px solid var(--pbo-border);
                background: var(--pbo-bg2);
                color: var(--pbo-muted);
                font-family: 'JetBrains Mono', monospace;
                letter-spacing: -.01em;
            }

            .pbo-pill .pdot {
                width: 7px;
                height: 7px;
                border-radius: 50%;
            }

            .pbo-pill-danger {
                background: #fef2f2;
                border-color: #fecaca;
                color: #dc2626;
            }

            .dark .pbo-pill-danger {
                background: #2d1515;
                border-color: #7f1d1d;
                color: #f87171;
            }

            .pbo-pill-blue {
                background: #eff6ff;
                border-color: #bfdbfe;
                color: #1d4ed8;
            }

            .dark .pbo-pill-blue {
                background: #1e2240;
                border-color: #1e3a8a;
                color: #93c5fd;
            }

            /* Bouton reset */
            .pbo-btn-reset {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 7px 14px;
                border-radius: 8px;
                border: 1px solid #fca5a5;
                background: #fef2f2;
                color: #dc2626;
                font-size: 12.5px;
                font-weight: 600;
                font-family: 'Inter', sans-serif;
                cursor: pointer;
                transition: all .15s;
                white-space: nowrap;
            }

            .pbo-btn-reset:hover {
                background: #fee2e2;
                border-color: #f87171;
            }

            .dark .pbo-btn-reset {
                background: #2d1515;
                border-color: #7f1d1d;
                color: #f87171;
            }

            /* ── Bloc filtres ── */
            .pbo-filters {
                background: var(--pbo-bg);
                border: 1px solid var(--pbo-border);
                border-radius: var(--pbo-radius);
                padding: 12px 16px;
                margin-bottom: 12px;
            }

            .pbo-filters-head {
                display: flex;
                align-items: center;
                gap: 6px;
                font-size: 10px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: .08em;
                color: var(--pbo-muted);
                margin-bottom: 10px;
            }

            .pbo-filters-row {
                display: flex;
                align-items: flex-end;
                gap: 10px;
                flex-wrap: wrap;
            }

            .pbo-filter-group {
                display: flex;
                flex-direction: column;
                gap: 4px;
            }

            .pbo-filter-group label {
                font-size: 11px;
                font-weight: 500;
                color: var(--pbo-muted);
                display: flex;
                align-items: center;
                gap: 4px;
            }

            .pbo-finput {
                padding: 6px 10px;
                border: 1px solid var(--pbo-border);
                border-radius: 6px;
                font-size: 12.5px;
                font-family: 'Inter', sans-serif;
                background: var(--pbo-bg2);
                color: var(--pbo-text);
                outline: none;
                transition: border-color .15s, box-shadow .15s;
                height: 34px;
            }

            .pbo-finput:focus {
                border-color: var(--pbo-accent);
                box-shadow: 0 0 0 3px rgba(37, 99, 235, .1);
                background: var(--pbo-bg);
            }

            .dark .pbo-finput {
                background: #1a1d28;
                border-color: #2a2e3d;
                color: #e5e7eb;
            }

            .pbo-finput-search {
                min-width: 200px;
            }

            .pbo-finput-dept {
                width: 120px;
            }

            .pbo-finput-date {
                width: 140px;
            }

            .pbo-fselect {
                padding: 0 8px;
                border: 1px solid var(--pbo-border);
                border-radius: 6px;
                font-size: 12.5px;
                font-family: 'Inter', sans-serif;
                /* background: var(--pbo-bg2); */
                color: var(--pbo-text);
                outline: none;
                height: 34px;
                cursor: pointer;
                transition: border-color .15s;
            }

            .pbo-fselect:focus {
                border-color: var(--pbo-accent);
            }

            .dark .pbo-fselect {
                background: #1a1d28;
                border-color: #2a2e3d;
                color: #e5e7eb;
            }

            .pbo-filter-actions {
                display: flex;
                gap: 6px;
                align-items: flex-end;
            }

            .pbo-btn-filter {
                display: inline-flex;
                align-items: center;
                gap: 5px;
                padding: 0 14px;
                height: 34px;
                background: var(--pbo-accent);
                color: white;
                border: none;
                border-radius: 6px;
                font-size: 12.5px;
                font-weight: 600;
                font-family: 'Inter', sans-serif;
                cursor: pointer;
                transition: background .15s;
            }

            .pbo-btn-filter:hover {
                background: #1d4ed8;
            }

            .pbo-btn-clear {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 34px;
                height: 34px;
                background: var(--pbo-bg2);
                border: 1px solid var(--pbo-border);
                border-radius: 6px;
                color: var(--pbo-muted);
                cursor: pointer;
                transition: all .15s;
            }

            .pbo-btn-clear:hover {
                background: #f3f4f6;
                color: var(--pbo-text);
            }

            .dark .pbo-btn-clear {
                background: #1a1d28;
                border-color: #2a2e3d;
            }

            .dark .pbo-btn-clear:hover {
                background: #252836;
            }

            /* ── Tableau ── */
            .pbo-table-wrap {
                background: var(--pbo-bg);
                border: 1px solid var(--pbo-border);
                border-radius: var(--pbo-radius);
                overflow: hidden;
                flex: 1;
                display: flex;
                flex-direction: column;
            }

            .pbo-table-header {
                display: grid;
                grid-template-columns: 28px 28px 40px 1fr 110px 130px 100px 120px 60px;
                gap: 0;
                padding: 0 8px;
                border-bottom: 1px solid var(--pbo-border);
                background: var(--pbo-bg2);
            }

            .pbo-th {
                padding: 9px 8px;
                font-size: 10.5px;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: .06em;
                color: var(--pbo-muted);
                white-space: nowrap;
                display: flex;
                align-items: center;
            }

            .pbo-table-body {
                overflow-y: auto;
                flex: 1;
            }

            .pbo-table-body::-webkit-scrollbar {
                width: 5px;
            }

            .pbo-table-body::-webkit-scrollbar-track {
                background: transparent;
            }

            .pbo-table-body::-webkit-scrollbar-thumb {
                background: #d1d5db;
                border-radius: 9px;
            }

            .dark .pbo-table-body::-webkit-scrollbar-thumb {
                background: #374151;
            }

            /* Ligne */
            .pbo-row {
                display: grid;
                grid-template-columns: 28px 28px 40px 1fr 110px 130px 100px 120px 60px;
                gap: 0;
                padding: 0 8px;
                border-bottom: 1px solid #f3f4f6;
                background: var(--pbo-bg);
                transition: background .1s;
                align-items: center;
                min-height: 46px;
                position: relative;
            }

            .dark .pbo-row {
                border-bottom-color: #1a1d28;
            }

            .pbo-row:last-child {
                border-bottom: none;
            }

            .pbo-row:hover {
                background: #f8faff;
            }

            .dark .pbo-row:hover {
                background: #131724;
            }

            /* Drag states */
            .pbo-row.sortable-ghost {
                opacity: .35;
                background: #eff6ff;
            }

            .dark .pbo-row.sortable-ghost {
                background: #1c2240;
            }

            .pbo-row.sortable-chosen {
                background: #eff6ff;
                box-shadow: 0 4px 20px rgba(37, 99, 235, .15);
                z-index: 20;
                border-radius: 6px;
            }

            .dark .pbo-row.sortable-chosen {
                background: #1c2240;
            }

            /* Cellules */
            .pbo-cell {
                padding: 0 8px;
                font-size: 12.5px;
                color: var(--pbo-text);
                display: flex;
                align-items: center;
                overflow: hidden;
            }

            /* Col grip */
            .pbo-col-grip {
                display: flex;
                align-items: center;
                justify-content: center;
                cursor: grab;
                color: #d1d5db;
                padding: 0 4px;
                transition: color .12s;
            }

            .pbo-row:hover .pbo-col-grip {
                color: #9ca3af;
            }

            .pbo-col-grip:active {
                cursor: grabbing;
            }

            /* Col check */
            .pbo-col-check {
                display: flex;
                align-items: center;
                justify-content: center;
            }

            .pbo-checkbox {
                width: 15px;
                height: 15px;
                border: 1.5px solid #d1d5db;
                border-radius: 4px;
                cursor: pointer;
                appearance: none;
                background: white;
                flex-shrink: 0;
                transition: all .12s;
            }

            .pbo-checkbox:checked {
                background: var(--pbo-accent);
                border-color: var(--pbo-accent);
                background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 10 8' fill='none' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M1 4L3.5 6.5L9 1' stroke='white' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
                background-size: 10px 8px;
                background-position: center;
                background-repeat: no-repeat;
            }

            .dark .pbo-checkbox {
                background: #1a1d28;
                border-color: #374151;
            }

            /* Col rank */
            .pbo-col-rank {
                justify-content: center;
                font-size: 11px;
                font-weight: 700;
                color: #c5c9d2;
                font-family: 'JetBrains Mono', monospace;
            }

            .pbo-col-rank.r1 {
                color: #d97706;
            }

            .pbo-col-rank.r2 {
                color: #6b7280;
            }

            .pbo-col-rank.r3 {
                color: #92400e;
            }

            /* Col nom */
            .pbo-col-nom {
                gap: 8px;
                padding-left: 2px;
            }

            .pbo-nom-avatar {
                width: 26px;
                height: 26px;
                border-radius: 6px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 10px;
                font-weight: 700;
                color: white;
                flex-shrink: 0;
                letter-spacing: .02em;
            }

            .pbo-nom-text {
                min-width: 0;
                display: flex;
                flex-direction: column;
            }

            .pbo-nom-name {
                font-size: 13px;
                font-weight: 600;
                color: var(--pbo-text);
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                letter-spacing: -.01em;
                display: flex;
                align-items: center;
                gap: 5px;
            }

            .pbo-nom-sub {
                font-size: 10.5px;
                color: var(--pbo-muted);
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                margin-top: 1px;
            }

            .pbo-badge-next {
                font-size: 8.5px;
                font-weight: 700;
                background: #fefce8;
                color: #92400e;
                border: 1px solid #fde68a;
                padding: 0 5px;
                border-radius: 3px;
                letter-spacing: .05em;
                text-transform: uppercase;
                font-family: 'JetBrains Mono', monospace;
                flex-shrink: 0;
            }

            /* Col statut */
            .pbo-col-statut {
                gap: 6px;
            }

            .pbo-status-dot {
                width: 7px;
                height: 7px;
                border-radius: 50%;
                flex-shrink: 0;
            }

            .pbo-status-label {
                font-size: 12px;
                font-weight: 500;
            }

            .pbo-badge-late {
                font-size: 9px;
                font-weight: 700;
                color: #dc2626;
                background: #fef2f2;
                border: 1px solid #fecaca;
                padding: 0 4px;
                border-radius: 3px;
                font-family: 'JetBrains Mono', monospace;
                letter-spacing: .04em;
            }

            /* Col téléphone */
            .pbo-col-tel {
                font-family: 'JetBrains Mono', monospace;
                font-size: 11.5px;
                color: var(--pbo-muted);
            }

            /* Col département */
            .pbo-col-dept {
                font-size: 12px;
                color: var(--pbo-muted);
                gap: 4px;
            }

            /* Col rappel */
            .pbo-col-rappel {
                font-size: 11px;
                font-family: 'JetBrains Mono', monospace;
                color: var(--pbo-muted);
            }

            .pbo-rappel-late {
                color: #dc2626;
                font-weight: 600;
            }

            /* Col engagement */
            .pbo-col-eng {
                justify-content: center;
                font-size: 11.5px;
                font-family: 'JetBrains Mono', monospace;
                color: var(--pbo-muted);
            }

            /* ── Couleurs statuts ── */
            .dot-rpc {
                background: #14b8a6;
            }

            .dot-rp {
                background: #22c55e;
            }

            .dot-std_joint {
                background: #3b82f6;
            }

            .dot-ac {
                background: #9ca3af;
            }

            .dot-std_nr {
                background: #d1d5db;
            }

            .dot-cse_nr {
                background: #f97316;
            }

            .dot-ko {
                background: #ef4444;
            }

            .lbl-rpc {
                color: #0d9488;
            }

            .lbl-rp {
                color: #16a34a;
            }

            .lbl-std_joint {
                color: #2563eb;
            }

            .lbl-ac {
                color: #374151;
            }

            .lbl-std_nr {
                color: #6b7280;
            }

            .lbl-cse_nr {
                color: #c2410c;
            }

            .lbl-ko {
                color: #b91c1c;
            }

            .dark .lbl-ac {
                color: #9ca3af;
            }

            .dark .lbl-std_nr {
                color: #6b7280;
            }

            /* Couleur avatar par initiale */
            .av-a {
                background: linear-gradient(135deg, #6366f1, #8b5cf6);
            }

            .av-b {
                background: linear-gradient(135deg, #0ea5e9, #2563eb);
            }

            .av-c {
                background: linear-gradient(135deg, #10b981, #059669);
            }

            .av-d {
                background: linear-gradient(135deg, #f59e0b, #d97706);
            }

            .av-e {
                background: linear-gradient(135deg, #ec4899, #db2777);
            }

            .av-f {
                background: linear-gradient(135deg, #14b8a6, #0d9488);
            }

            .av-g {
                background: linear-gradient(135deg, #f97316, #ea580c);
            }

            .av-h {
                background: linear-gradient(135deg, #a855f7, #9333ea);
            }

            .av-i {
                background: linear-gradient(135deg, #06b6d4, #0891b2);
            }

            .av-j {
                background: linear-gradient(135deg, #84cc16, #65a30d);
            }

            .av-k {
                background: linear-gradient(135deg, #ef4444, #dc2626);
            }

            .av-l {
                background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            }

            .av-m {
                background: linear-gradient(135deg, #8b5cf6, #7c3aed);
            }

            .av-n {
                background: linear-gradient(135deg, #06b6d4, #2563eb);
            }

            .av-o {
                background: linear-gradient(135deg, #f59e0b, #ef4444);
            }

            .av-p {
                background: linear-gradient(135deg, #10b981, #6366f1);
            }

            .av-default {
                background: linear-gradient(135deg, #6b7280, #4b5563);
            }

            /* ── Sélection multiple barre d'actions ── */
            .pbo-bulk-bar {
                display: none;
                align-items: center;
                gap: 10px;
                padding: 8px 16px;
                background: #eff6ff;
                border-bottom: 1px solid #bfdbfe;
                font-size: 12.5px;
                font-weight: 500;
                color: #1d4ed8;
            }

            .dark .pbo-bulk-bar {
                background: #1c2240;
                border-color: #1e3a8a;
                color: #93c5fd;
            }

            .pbo-bulk-bar.visible {
                display: flex;
            }

            .pbo-bulk-btn {
                display: inline-flex;
                align-items: center;
                gap: 4px;
                padding: 4px 10px;
                border-radius: 6px;
                border: 1px solid #93c5fd;
                background: white;
                color: #1d4ed8;
                font-size: 12px;
                font-weight: 600;
                font-family: 'Inter', sans-serif;
                cursor: pointer;
                transition: all .12s;
            }

            .pbo-bulk-btn:hover {
                background: #dbeafe;
            }

            .dark .pbo-bulk-btn {
                background: #1e2240;
                border-color: #2d3a6e;
                color: #93c5fd;
            }

            .pbo-bulk-btn-danger {
                border-color: #fca5a5;
                color: #dc2626;
            }

            .pbo-bulk-btn-danger:hover {
                background: #fef2f2;
            }

            /* ── Footer ── */
            .pbo-footer {
                display: flex;
                align-items: center;
                gap: 14px;
                padding: 9px 16px;
                border-top: 1px solid var(--pbo-border);
                background: var(--pbo-bg2);
                border-radius: 0 0 var(--pbo-radius) var(--pbo-radius);
                flex-shrink: 0;
                flex-wrap: wrap;
            }

            .pbo-fstat {
                display: flex;
                align-items: center;
                gap: 5px;
                font-size: 11.5px;
                color: var(--pbo-muted);
            }

            .pbo-fstat-val {
                font-weight: 700;
                font-family: 'JetBrains Mono', monospace;
                font-size: 12px;
                color: var(--pbo-text);
            }

            .pbo-footer-note {
                margin-left: auto;
                font-size: 10.5px;
                color: #c5c9d2;
                font-style: italic;
                white-space: nowrap;
            }

            /* ── Empty ── */
            .pbo-empty {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                padding: 60px 20px;
                color: #c5c9d2;
            }

            .pbo-empty-icon {
                font-size: 32px;
                margin-bottom: 10px;
                opacity: .6;
            }

            .pbo-empty-title {
                font-size: 14px;
                font-weight: 600;
                color: var(--pbo-muted);
            }

            .pbo-empty-sub {
                font-size: 12px;
                margin-top: 4px;
                color: #c5c9d2;
            }

            /* ── Saving flash ── */
            @keyframes pbo-flash {

                0%,
                100% {
                    opacity: 0
                }

                20%,
                80% {
                    opacity: 1
                }
            }

            .pbo-saving {
                font-size: 11px;
                color: #2563eb;
                font-weight: 600;
                animation: pbo-flash .9s ease-out;
            }

            /* ── Légende inline ── */
            .pbo-legend-bar {
                display: flex;
                align-items: center;
                gap: 12px;
                flex-wrap: wrap;
                padding: 7px 16px;
                border-bottom: 1px solid var(--pbo-border);
                background: var(--pbo-bg2);
            }

            .pbo-leg-item {
                display: flex;
                align-items: center;
                gap: 4px;
                font-size: 10.5px;
                color: var(--pbo-muted);
                font-weight: 500;
            }

            .pbo-leg-dot {
                width: 7px;
                height: 7px;
                border-radius: 2px;
            }

            /* Drag hint */
            .pbo-drag-hint {
                margin-left: auto;
                font-size: 10.5px;
                color: #c5c9d2;
                font-style: italic;
                display: flex;
                align-items: center;
                gap: 4px;
            }
        </style>
    @endpush

    @push('scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.0/Sortable.min.js"></script>
        <script>
            // Livewire v3 : on initialise après chaque mise à jour du DOM
            let sortableInstance = null;

            function initSortable() {
                const list = document.getElementById('pbo-sortable-list');
                if (!list) return;

                if (sortableInstance) {
                    sortableInstance.destroy();
                    sortableInstance = null;
                }

                sortableInstance = new Sortable(list, {
                    animation: 150,
                    handle: '.pbo-col-grip',
                    ghostClass: 'sortable-ghost',
                    chosenClass: 'sortable-chosen',
                    easing: 'cubic-bezier(0.25, 0.46, 0.45, 0.94)',
                    onEnd: function() {
                        const ids = [...list.querySelectorAll('.pbo-row[data-id]')]
                            .map(el => parseInt(el.dataset.id));

                        // Afficher l'indicateur de sauvegarde
                        const ind = document.getElementById('pbo-save-ind');
                        if (ind) {
                            ind.style.display = 'inline';
                            ind.style.animation = 'none';
                            void ind.offsetWidth;
                            ind.style.animation = '';
                            setTimeout(() => ind.style.display = 'none', 1200);
                        }

                        // Appeler la méthode Livewire (sans @this qui cause l'erreur)
                        if (window.Livewire && ids.length > 0) {
                            // Récupérer l'instance Livewire du composant
                            const component = document.querySelector('[wire\\:id]')?.__livewire;
                            if (component && component.reorderFromDrag) {
                                component.reorderFromDrag(ids);
                            } else {
                                // Alternative: utiliser l'API Livewire
                                Livewire.dispatch('reorderFromDrag', {
                                    ids: ids
                                });
                            }
                        }
                    }
                });
            }

            // Initialisation
            document.addEventListener('DOMContentLoaded', () => {
                setTimeout(initSortable, 100);
            });

            // Écouter les événements Livewire
            document.addEventListener('livewire:init', () => {
                setTimeout(initSortable, 100);
            });

            document.addEventListener('livewire:navigated', () => {
                setTimeout(initSortable, 100);
            });

            // Gestion sélection cases à cocher
            document.addEventListener('change', (e) => {
                if (!e.target.matches('.pbo-checkbox')) return;
                const checked = document.querySelectorAll('.pbo-checkbox.row-check:checked');
                const bar = document.getElementById('pbo-bulk-bar');
                const cnt = document.getElementById('pbo-bulk-count');
                if (bar) bar.classList.toggle('visible', checked.length > 0);
                if (cnt) cnt.textContent = checked.length;
            });

            function pboDeselectAll() {
                document.querySelectorAll('.pbo-checkbox.row-check').forEach(c => c.checked = false);
                const bar = document.getElementById('pbo-bulk-bar');
                if (bar) bar.classList.remove('visible');
            }

            function pboSelectAll(el) {
                const checked = el.checked;
                document.querySelectorAll('.pbo-checkbox.row-check').forEach(c => c.checked = checked);
                const bar = document.getElementById('pbo-bulk-bar');
                const cnt = document.getElementById('pbo-bulk-count');
                const n = checked ? document.querySelectorAll('.pbo-checkbox.row-check').length : 0;
                if (bar) bar.classList.toggle('visible', checked && n > 0);
                if (cnt) cnt.textContent = n;
            }

            // Filtre client-side live
            document.addEventListener('input', (e) => {
                if (e.target.id !== 'pbo-search-input') return;
                const q = e.target.value.toLowerCase();
                document.querySelectorAll('.pbo-row[data-id]').forEach(row => {
                    const nom = (row.dataset.nom || '').toLowerCase();
                    const tel = (row.dataset.tel || '').toLowerCase();
                    row.style.display = (!q || nom.includes(q) || tel.includes(q)) ? '' : 'none';
                });
            });

            // Gestion sélection cases à cocher — synchronisé avec Livewire $selectedIds
            function pboUpdateSelection() {
                const checked = [...document.querySelectorAll('.pbo-checkbox.row-check:checked')]
                    .map(c => parseInt(c.value));
                const bar = document.getElementById('pbo-bulk-bar');
                const cnt = document.getElementById('pbo-bulk-count');
                if (bar) bar.classList.toggle('visible', checked.length > 0);
                if (cnt) cnt.textContent = checked.length;
                // Envoyer les IDs à Livewire
                @this.set('selectedIds', checked);
            }

            document.addEventListener('change', (e) => {
                if (!e.target.matches('.pbo-checkbox')) return;
                pboUpdateSelection();
            });

            function pboDeselectAll() {
                document.querySelectorAll('.pbo-checkbox.row-check').forEach(c => c.checked = false);
                const bar = document.getElementById('pbo-bulk-bar');
                if (bar) bar.classList.remove('visible');
                @this.set('selectedIds', []);
            }

            function pboSelectAll(el) {
                const checked = el.checked;
                document.querySelectorAll('.pbo-checkbox.row-check').forEach(c => c.checked = checked);
                pboUpdateSelection();
            }
        </script>
    @endpush

    @php
        $teleprospecteurs = $this->getTeleprospecteurs();
        $selectedUser = $this->getSelectedUser();
        $list = $this->prospectList;

        $nbTotal = count($list);
        $nbRappels = collect($list)->where('rappel_planifie_at', '!=', null)->count();
        $nbRetard = collect($list)->where('rappel_en_retard', true)->count();
        $nbRpc = collect($list)->where('statut', 'rpc')->count();
        $nbRp = collect($list)->where('statut', 'rp')->count();

        $statDots = [
            'rpc' => 'dot-rpc',
            'rp' => 'dot-rp',
            'std_joint' => 'dot-std_joint',
            'ac' => 'dot-ac',
            'std_nr' => 'dot-std_nr',
            'cse_nr' => 'dot-cse_nr',
            'ko' => 'dot-ko',
        ];
        $statLabels = [
            'rpc' => 'lbl-rpc',
            'rp' => 'lbl-rp',
            'std_joint' => 'lbl-std_joint',
            'ac' => 'lbl-ac',
            'std_nr' => 'lbl-std_nr',
            'cse_nr' => 'lbl-cse_nr',
            'ko' => 'lbl-ko',
        ];
        // Couleur avatar par première lettre
        $avColors = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p'];
    @endphp

    <div class="pbo">

        {{-- ══ TOPBAR ══ --}}
        <div class="pbo-topbar">
            {{-- Sélecteur téléprospecteur --}}
            <div class="pbo-user-select-wrap">
                <select wire:change="selectUser($event.target.value)" class="pbo-user-select">
                    @foreach ($teleprospecteurs as $user)
                        <option value="{{ $user['id'] }}" {{ $selectedUserId === $user['id'] ? 'selected' : '' }}>
                            {{ $user['nom_complet'] }} — {{ $user['nb_actifs'] }}
                            prospect{{ $user['nb_actifs'] > 1 ? 's' : '' }}
                        </option>
                    @endforeach
                    @if (empty($teleprospecteurs))
                        <option disabled>Aucun téléprospecteur actif</option>
                    @endif
                </select>
            </div>

            <div class="pbo-topbar-spacer"></div>

            <div class="pbo-pills">
                @if ($nbRetard > 0)
                    <div class="pbo-pill pbo-pill-danger">
                        <div class="pdot" style="background:#ef4444;"></div>
                        {{ $nbRetard }} retard{{ $nbRetard > 1 ? 's' : '' }}
                    </div>
                @endif
                @if ($nbTotal > 0)
                    <div class="pbo-pill pbo-pill-blue">
                        <div class="pdot" style="background:#3b82f6;"></div>
                        {{ $nbTotal }} en file
                    </div>
                @endif
                <span id="pbo-save-ind" class="pbo-saving" style="display:none;">✓ Sauvegardé</span>
                @if ($selectedUser && $nbTotal > 0)
                    <button wire:click="resetOrder" onclick="return confirm('Réinitialiser l\'ordre par défaut ?')"
                        class="pbo-btn-reset">
                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Réinitialiser
                    </button>
                @endif
            </div>
        </div>

        {{-- ══ FILTRES ══ --}}
        <div class="pbo-filters">
            <div class="pbo-filters-head">
                <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z" />
                </svg>
                Filtres
            </div>
            <div class="pbo-filters-row">
                {{-- Recherche --}}
                <div class="pbo-filter-group">
                    <label>
                        <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        Recherche
                    </label>
                    <input id="pbo-search-input" type="text" placeholder="Nom ou téléphone…"
                        class="pbo-finput pbo-finput-search">
                </div>

                {{-- Statut --}}
                <div class="pbo-filter-group">
                    <label>Statut</label>
                    <select wire:model.live="filterStatut" class="pbo-fselect">
                        <option value="">Tous</option>
                        <option value="rpc">RPC</option>
                        <option value="rp">RP</option>
                        <option value="std_joint">STD-Joint</option>
                        <option value="ac">AC</option>
                        <option value="cse_nr">CSE-NR</option>
                        <option value="std_nr">STD-NR</option>
                    </select>
                </div>

                {{-- Département --}}
                <div class="pbo-filter-group">
                    <label>
                        <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        </svg>
                        Département
                    </label>
                    <input wire:model.live="filterDept" type="text" placeholder="Ex : 75, 92…"
                        class="pbo-finput pbo-finput-dept">
                </div>

                {{-- Rappels uniquement --}}
                <div class="pbo-filter-group" style="justify-content: flex-end;">
                    <label>&nbsp;</label>
                    <label
                        style="flex-direction:row; align-items:center; gap:6px; height:34px; cursor:pointer; font-size:12.5px; font-weight:500; color:var(--pbo-text);">
                        <input wire:model.live="filterRappelOnly" type="checkbox" class="pbo-checkbox"
                            style="flex-shrink:0;">
                        Rappels seulement
                    </label>
                </div>

                <div class="pbo-filter-actions">
                    <button wire:click="applyFilters" class="pbo-btn-filter">
                        <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z" />
                        </svg>
                        Filtrer
                    </button>
                    <button wire:click="clearFilters" class="pbo-btn-clear" title="Réinitialiser les filtres">
                        <svg width="14" height="14" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        {{-- ══ TABLEAU ══ --}}
        <div class="pbo-table-wrap">

            {{-- Légende statuts + drag hint --}}
            <div class="pbo-legend-bar">
                @foreach (['rpc' => 'RPC', 'rp' => 'RP', 'std_joint' => 'STD-Joint', 'ac' => 'AC', 'cse_nr' => 'CSE-NR', 'std_nr' => 'STD-NR'] as $k => $l)
                    <div class="pbo-leg-item">
                        <div class="pbo-leg-dot {{ 'dot-' . $k }}"></div>
                        {{ $l }}
                    </div>
                @endforeach
                <div class="pbo-drag-hint">
                    <svg width="11" height="11" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 16V4m0 0L3 8m4-4l4 4M17 8v12m0 0l4-4m-4 4l-4-4" />
                    </svg>
                    Glisser ⠿ pour réordonner
                </div>
            </div>

            {{-- Barre sélection multiple --}}
            <div class="pbo-bulk-bar" id="pbo-bulk-bar">
                <span><span id="pbo-bulk-count">0</span> sélectionné(s)</span>
                <button class="pbo-bulk-btn" onclick="pboDeselectAll()">✕ Désélectionner</button>
                <button class="pbo-bulk-btn" wire:click="moveSelectedToTop">↑ Mettre en tête</button>
                <button class="pbo-bulk-btn pbo-bulk-btn-danger" wire:click="removeSelected">Retirer</button>
            </div>

            {{-- En-tête colonnes --}}
            <div class="pbo-table-header">
                <div class="pbo-th" style="justify-content:center;">
                    <input type="checkbox" class="pbo-checkbox" onchange="pboSelectAll(this)"
                        title="Tout sélectionner">
                </div>
                <div class="pbo-th"></div>{{-- grip --}}
                <div class="pbo-th" style="justify-content:center;">#</div>
                <div class="pbo-th">Nom</div>
                <div class="pbo-th">Statut</div>
                <div class="pbo-th">Téléphone</div>
                <div class="pbo-th">Département</div>
                <div class="pbo-th">Rappel</div>
                <div class="pbo-th" style="justify-content:center;">Eng.</div>
            </div>

            {{-- Corps --}}
            <div class="pbo-table-body">
                @if ($nbTotal === 0)
                    <div class="pbo-empty">
                        <div class="pbo-empty-icon">📭</div>
                        <div class="pbo-empty-title">Aucun prospect en file</div>
                        <div class="pbo-empty-sub">
                            {{ $selectedUser ? 'Ce téléprospecteur n\'a aucun prospect actif.' : 'Sélectionnez un téléprospecteur ci-dessus.' }}
                        </div>
                    </div>
                @else
                    <div id="pbo-sortable-list">
                        @foreach ($list as $i => $p)
                            @php
                                $rank = $i + 1;
                                $dotCls = $statDots[$p['statut']] ?? 'dot-ac';
                                $lblCls = $statLabels[$p['statut']] ?? 'lbl-ac';
                                $initial = mb_strtolower(mb_substr($p['nom'], 0, 1));
                                $avCls = in_array($initial, $avColors) ? 'av-' . $initial : 'av-default';
                                $initials = mb_strtoupper(mb_substr($p['nom'], 0, 1));
                            @endphp
                            <div class="pbo-row" wire:key="row-{{ $p['id'] }}" data-id="{{ $p['id'] }}"
                                data-nom="{{ strtolower($p['nom']) }}" data-tel="{{ $p['telephone'] ?? '' }}">

                                {{-- Check --}}
                                <div class="pbo-cell pbo-col-check">
                                    <input type="checkbox" class="pbo-checkbox row-check"
                                        value="{{ $p['id'] }}">
                                </div>

                                {{-- Grip --}}
                                <div class="pbo-cell pbo-col-grip" title="Glisser pour réordonner">
                                    <svg width="10" height="14" viewBox="0 0 10 14" fill="currentColor">
                                        <circle cx="3" cy="2.5" r="1.1" />
                                        <circle cx="3" cy="7" r="1.1" />
                                        <circle cx="3" cy="11.5" r="1.1" />
                                        <circle cx="7" cy="2.5" r="1.1" />
                                        <circle cx="7" cy="7" r="1.1" />
                                        <circle cx="7" cy="11.5" r="1.1" />
                                    </svg>
                                </div>

                                {{-- Rang --}}
                                <div class="pbo-cell pbo-col-rank {{ $rank <= 3 ? 'r' . $rank : '' }}">
                                    {{ $rank }}
                                </div>

                                {{-- Nom --}}
                                <div class="pbo-cell pbo-col-nom">
                                    <div class="pbo-nom-avatar {{ $avCls }}">{{ $initials }}</div>
                                    <div class="pbo-nom-text">
                                        <div class="pbo-nom-name">
                                            {{ $p['nom'] }}
                                            @if ($rank === 1)
                                                <span class="pbo-badge-next">PROCHAIN</span>
                                            @endif
                                        </div>
                                        <div class="pbo-nom-sub">
                                            @if ($p['interlocuteur'] && $p['interlocuteur'] !== 'Non défini')
                                                {{ $p['interlocuteur'] }}
                                            @elseif($p['secteur_activite'])
                                                {{ $p['secteur_activite'] }}
                                            @elseif($p['type_pressenti'] && $p['type_pressenti'] !== 'Non défini')
                                                {{ $p['type_pressenti'] }}
                                            @else
                                                &nbsp;
                                            @endif
                                            @if ($p['nb_salaries'])
                                                · {{ $p['nb_salaries'] }} sal.
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                {{-- Statut --}}
                                <div class="pbo-cell pbo-col-statut">
                                    <div class="pbo-status-dot {{ $dotCls }}"></div>
                                    <span
                                        class="pbo-status-label {{ $lblCls }}">{{ $p['statut_label'] }}</span>
                                    @if ($p['rappel_en_retard'])
                                        <span class="pbo-badge-late">⚠</span>
                                    @endif
                                </div>

                                {{-- Téléphone --}}
                                <div class="pbo-cell pbo-col-tel">
                                    {{ $p['telephone'] ?? '—' }}
                                </div>

                                {{-- Département --}}
                                <div class="pbo-cell pbo-col-dept">
                                    @if ($p['ville'] || $p['departement'])
                                        <svg width="10" height="10" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24" style="flex-shrink:0;">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        </svg>
                                        {{ $p['ville'] }}{{ $p['departement'] ? ' (' . $p['departement'] . ')' : '' }}
                                    @else
                                        <span style="color:#d1d5db;">—</span>
                                    @endif
                                </div>

                                {{-- Rappel --}}
                                <div class="pbo-cell pbo-col-rappel">
                                    @if ($p['rappel_planifie_at'])
                                        <span class="{{ $p['rappel_en_retard'] ? 'pbo-rappel-late' : '' }}">
                                            {{ $p['rappel_planifie_at'] }}
                                        </span>
                                    @else
                                        <span style="color:#d1d5db;">—</span>
                                    @endif
                                </div>

                                {{-- Engagement --}}
                                <div class="pbo-cell pbo-col-eng">
                                    {{ $p['taux_engagement'] ?? '—' }}
                                </div>

                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Footer --}}
            @if ($selectedUser)
                <div class="pbo-footer">
                    <div class="pbo-fstat">
                        <span class="pbo-fstat-val">{{ $nbTotal }}</span>
                        <span>prospect{{ $nbTotal > 1 ? 's' : '' }}</span>
                    </div>
                    @if ($nbRpc > 0)
                        <div class="pbo-fstat">
                            <span class="pbo-fstat-val" style="color:#0d9488;">{{ $nbRpc }}</span>
                            <span>RPC</span>
                        </div>
                    @endif
                    @if ($nbRp > 0)
                        <div class="pbo-fstat">
                            <span class="pbo-fstat-val" style="color:#16a34a;">{{ $nbRp }}</span>
                            <span>RP</span>
                        </div>
                    @endif
                    @if ($nbRappels > 0)
                        <div class="pbo-fstat">
                            <span class="pbo-fstat-val">{{ $nbRappels }}</span>
                            <span>avec rappel</span>
                        </div>
                    @endif
                    @if ($nbRetard > 0)
                        <div class="pbo-fstat" style="color:#dc2626;">
                            <span class="pbo-fstat-val" style="color:#dc2626;">{{ $nbRetard }}</span>
                            <span>en retard</span>
                        </div>
                    @endif
                    <div class="pbo-footer-note">Ordre sauvegardé 24h · Réinitialisé à minuit</div>
                </div>
            @endif

        </div>{{-- /pbo-table-wrap --}}

    </div>{{-- /pbo --}}

</x-filament-panels::page>
