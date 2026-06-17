<?php

namespace App\Filament\NsConseil\Pages;

use App\Enums\ProspectStatut;
use App\Filament\NsConseil\Concerns\HasRoleAccess;
use App\Models\Prospect;
use App\Models\User;
use App\Services\Crm\CrmSettingsService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class PhoningBackOffice extends Page
{
    use HasRoleAccess;

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';

    protected static ?string $navigationLabel = 'File d\'appels — Back-office';

    protected static ?string $navigationGroup = 'Activités';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return static::userHasAnyRole(['admin', 'superviseur']);
    }

    protected static string $view = 'filament.ns-conseil.pages.phoning-back-office';

    public ?int $selectedUserId = null;

    public array $prospectList = [];

    public array $selectedIds = [];

    // ── Filtres ──────────────────────────────────────────────────────
    public string $filterStatut = '';

    public string $filterDept = '';

    public bool $filterRappelOnly = false;

    // ── Mount ────────────────────────────────────────────────────────
    public function mount(): void
    {
        $first = $this->queryTeleprospecteurs()->first();
        if ($first) {
            $this->selectedUserId = $first->id;
            $this->loadProspects();
        }
    }

    // ── Requête centrale ─────────────────────────────────────────────
    protected function queryTeleprospecteurs()
    {
        $roles = app(CrmSettingsService::class)->get('roles.teleprospecteur_roles', ['teleprospecteur']);

        return User::query()
            ->where(function ($q) use ($roles) {
                $q->whereHas('roles', fn ($r) => $r->whereIn('name', $roles));
                foreach ($roles as $role) {
                    $q->orWhere('role_cache', $role);
                }
            })
            ->where('actif', true)
            ->orderBy('nom')
            ->orderBy('prenom');
    }

    // ── Sélectionner un téléprospecteur ──────────────────────────────
    public function selectUser(int $userId): void
    {
        $this->selectedUserId = $userId;
        $this->clearFilters();   // reset filtres au changement d'agent
        $this->loadProspects();
    }

    // ── Charger la liste des prospects ───────────────────────────────
    public function loadProspects(): void
    {
        if (! $this->selectedUserId) {
            $this->prospectList = [];

            return;
        }

        $cacheKey = "phoning_queue_user_{$this->selectedUserId}";
        $savedQueue = Cache::get($cacheKey);

        if ($savedQueue) {
            $ids = collect($savedQueue)
                ->where('type', 'prospect')
                ->pluck('id')
                ->toArray();

            $prospects = Prospect::query()
                ->whereIn('id', $ids)
                ->whereNotIn('statut', [ProspectStatut::KO->value, ProspectStatut::QF->value])
                ->whereNull('deleted_at')
                ->get()
                ->keyBy('id');

            $ordered = collect($ids)
                ->filter(fn ($id) => $prospects->has($id))
                ->map(fn ($id) => $this->formatProspect($prospects[$id]))
                ->values();
        } else {
            $ordered = Prospect::query()
                ->where('teleprospecteur_id', $this->selectedUserId)
                ->whereNotIn('statut', [ProspectStatut::KO->value, ProspectStatut::QF->value])
                ->whereNull('deleted_at')
                ->orderByRaw("CASE
                    WHEN statut = 'rpc'       THEN 1
                    WHEN statut = 'rp'        THEN 2
                    WHEN statut = 'std_joint' THEN 3
                    WHEN statut = 'ac'        THEN 4
                    WHEN statut = 'std_nr'    THEN 5
                    WHEN statut = 'cse_nr'    THEN 6
                    ELSE 7 END")
                ->orderBy('rappel_planifie_at', 'asc')
                ->get()
                ->map(fn ($p) => $this->formatProspect($p));
        }

        $this->prospectList = $this->applyFiltersToCollection($ordered)->toArray();
    }

    // ── Appliquer les filtres sur la collection formatée ─────────────
    protected function applyFiltersToCollection(Collection $col): Collection
    {
        if ($this->filterStatut !== '') {
            $col = $col->where('statut', $this->filterStatut)->values();
        }
        if ($this->filterDept !== '') {
            $dept = trim($this->filterDept);
            $col = $col->filter(function ($p) use ($dept) {
                return str_contains($p['departement'] ?? '', $dept)
                    || str_contains($p['ville'] ?? '', $dept);
            })->values();
        }
        if ($this->filterRappelOnly) {
            $col = $col->filter(fn ($p) => ! empty($p['rappel_planifie_at']))->values();
        }

        return $col;
    }

    // ── Actions filtres (appelées par les boutons Blade) ─────────────
    public function applyFilters(): void
    {
        $this->loadProspects();
    }

    public function clearFilters(): void
    {
        $this->filterStatut = '';
        $this->filterDept = '';
        $this->filterRappelOnly = false;
        $this->loadProspects();
    }

    // ── Formatage ─────────────────────────────────────────────────────
    protected function formatProspect(Prospect $p): array
    {
        return [
            'id' => $p->id,
            'nom' => $p->nom,
            'statut' => $p->statut->value,
            'statut_label' => $p->statut_label,
            'statut_color' => $p->statut_color,
            'telephone' => $p->telephone,
            'ville' => $p->ville,
            'departement' => $p->departement,
            'type_pressenti' => $p->type_pressenti_label,
            'secteur_activite' => $p->secteur_activite,
            'nb_salaries' => $p->nb_salaries,
            'rappel_planifie_at' => $p->rappel_planifie_at?->format('d/m/Y H:i'),
            'rappel_en_retard' => $p->rappel_est_en_retard,
            'date_premier_contact' => $p->date_premier_contact?->format('d/m/Y'),
            'taux_engagement' => $p->taux_engagement,
            'interlocuteur' => $p->interlocuteur_complet,
            'description' => $p->description ? \Str::limit($p->description, 80) : null,
        ];
    }

    // ── Réordonner depuis le drag & drop ─────────────────────────────
    public function reorderFromDrag(array $orderedIds): void
    {
        if (empty($orderedIds) || ! $this->selectedUserId) {
            return;
        }
        $indexed = collect($this->prospectList)->keyBy('id');
        $this->prospectList = collect($orderedIds)
            ->filter(fn ($id) => $indexed->has($id))
            ->map(fn ($id) => $indexed[$id])
            ->values()
            ->toArray();
        $this->saveQueue();
    }

    // ── Déplacer (boutons — conservés pour la sélection multiple) ────
    public function moveUp(int $prospectId): void
    {
        $index = $this->findIndex($prospectId);
        if ($index === null || $index === 0) {
            return;
        }
        [$this->prospectList[$index - 1], $this->prospectList[$index]] =
            [$this->prospectList[$index], $this->prospectList[$index - 1]];
        $this->saveQueue();
    }

    public function moveDown(int $prospectId): void
    {
        $index = $this->findIndex($prospectId);
        $last = count($this->prospectList) - 1;
        if ($index === null || $index === $last) {
            return;
        }
        [$this->prospectList[$index], $this->prospectList[$index + 1]] =
            [$this->prospectList[$index + 1], $this->prospectList[$index]];
        $this->saveQueue();
    }

    public function moveToTop(int $prospectId): void
    {
        $index = $this->findIndex($prospectId);
        if ($index === null || $index === 0) {
            return;
        }
        $item = array_splice($this->prospectList, $index, 1)[0];
        array_unshift($this->prospectList, $item);
        $this->saveQueue();
        Notification::make()->title('Prospect mis en tête ✓')->success()->send();
    }

    public function moveToBottom(int $prospectId): void
    {
        $index = $this->findIndex($prospectId);
        if ($index === null) {
            return;
        }
        $item = array_splice($this->prospectList, $index, 1)[0];
        $this->prospectList[] = $item;
        $this->saveQueue();
    }

    public function resetOrder(): void
    {
        if (! $this->selectedUserId) {
            return;
        }
        Cache::forget("phoning_queue_user_{$this->selectedUserId}");
        $this->loadProspects();
        Notification::make()->title('Ordre réinitialisé')->warning()->send();
    }

    // ── Sauvegarde cache ─────────────────────────────────────────────
    protected function saveQueue(): void
    {
        if (! $this->selectedUserId) {
            return;
        }
        $queue = collect($this->prospectList)
            ->map(fn ($p) => ['type' => 'prospect', 'id' => $p['id']])
            ->toArray();
        Cache::put("phoning_queue_user_{$this->selectedUserId}", $queue, now()->addHours(24));
    }

    protected function findIndex(int $prospectId): ?int
    {
        foreach ($this->prospectList as $i => $p) {
            if ($p['id'] === $prospectId) {
                return $i;
            }
        }

        return null;
    }

    // ── Données pour la vue ───────────────────────────────────────────
    public function getTeleprospecteurs(): array
    {
        return $this->queryTeleprospecteurs()
            ->get()
            ->map(fn ($u) => [
                'id' => $u->id,
                'nom_complet' => trim("{$u->prenom} {$u->nom}"),
                'initiales' => $u->initiales,
                'nb_actifs' => Prospect::query()
                    ->where('teleprospecteur_id', $u->id)
                    ->whereNotIn('statut', [ProspectStatut::KO->value, ProspectStatut::QF->value])
                    ->whereNull('deleted_at')
                    ->count(),
            ])
            ->toArray();
    }

    public function getSelectedUser(): ?array
    {
        if (! $this->selectedUserId) {
            return null;
        }
        $u = User::find($this->selectedUserId);
        if (! $u) {
            return null;
        }

        return [
            'id' => $u->id,
            'nom_complet' => trim("{$u->prenom} {$u->nom}"),
            'initiales' => $u->initiales,
        ];
    }

    public function moveSelectedToTop(): void
    {
        if (empty($this->selectedIds)) {
            Notification::make()->title('Aucun prospect sélectionné')->warning()->send();

            return;
        }

        $selected = [];
        $remaining = [];

        foreach ($this->prospectList as $p) {
            if (in_array($p['id'], $this->selectedIds)) {
                $selected[] = $p;
            } else {
                $remaining[] = $p;
            }
        }

        // Les sélectionnés en tête, dans leur ordre relatif actuel
        $this->prospectList = array_merge($selected, $remaining);
        $this->selectedIds = [];
        $this->saveQueue();

        Notification::make()
            ->title(count($selected).' prospect(s) mis en tête ✓')
            ->success()
            ->send();
    }

    public function removeSelected(): void
    {
        if (empty($this->selectedIds)) {
            Notification::make()->title('Aucun prospect sélectionné')->warning()->send();

            return;
        }

        $count = count($this->selectedIds);

        $this->prospectList = array_values(
            array_filter(
                $this->prospectList,
                fn ($p) => ! in_array($p['id'], $this->selectedIds)
            )
        );

        $this->selectedIds = [];
        $this->saveQueue();

        Notification::make()
            ->title("{$count} prospect(s) retirés de la file ✓")
            ->body('La file a été sauvegardée. Les prospects restent dans la base.')
            ->warning()
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('go_phoning')
                ->label('→ Workflow d\'appels')
                ->icon('heroicon-o-phone-arrow-up-right')
                ->color('success')
                ->url(fn () => route('filament.ns-conseil.pages.phoning-workflow')),

            Action::make('reset_order')
                ->label('Réinitialiser l\'ordre')
                ->icon('heroicon-o-arrow-path')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Réinitialiser l\'ordre ?')
                ->modalDescription('L\'ordre par défaut (par statut et rappel) sera restauré.')
                ->action(fn () => $this->resetOrder()),
        ];
    }
}
