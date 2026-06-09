<?php

namespace App\Filament\NsConseil\Resources\ProspectResource\Pages;

use App\Enums\ProspectStatut;
use App\Filament\NsConseil\Pages\PhoningWorkflow;
use App\Filament\NsConseil\Resources\ProspectResource;
use App\Filament\NsConseil\Resources\ProspectResource\Actions\ImportProspectsAction;
use App\Models\Prospect;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;

class ListProspects extends ListRecords
{
    protected static string $resource = ProspectResource::class;
    protected static string $view = 'filament.ns-conseil.pages.prospects-kanban';

    // ── Propriétés publiques Livewire ────────────────────────────────
    public string $viewMode     = 'table';
    public array  $kanbanGroups = [];

    // ── Mount ────────────────────────────────────────────────────────
    public function mount(): void
    {
        parent::mount();
    }

    // ── Switch de vue ────────────────────────────────────────────────
    public function switchView(string $mode): void
    {
        $this->viewMode = $mode;

        if ($mode === 'kanban') {
            $this->loadKanbanData();
        }
    }

    // ── Chargement Kanban ────────────────────────────────────────────
    public function loadKanbanData(): void
    {
        $columns = [
            [
                'status_key' => ProspectStatut::AC->value,
                'label'      => ProspectStatut::AC->label(),
                'color'      => 'gray',
                'prospects'  => collect(),
            ],
            [
                'status_key' => ProspectStatut::STD_NR->value,
                'label'      => ProspectStatut::STD_NR->label(),
                'color'      => 'warning',
                'prospects'  => collect(),
            ],
            [
                'status_key' => ProspectStatut::CSE_NR->value,
                'label'      => ProspectStatut::CSE_NR->label(),
                'color'      => 'orange',
                'prospects'  => collect(),
            ],
            [
                'status_key' => ProspectStatut::STD_Joint->value,
                'label'      => ProspectStatut::STD_Joint->label(),
                'color'      => 'info',
                'prospects'  => collect(),
            ],
            [
                'status_key' => ProspectStatut::RPC->value,
                'label'      => ProspectStatut::RPC->label(),
                'color'      => 'primary',
                'prospects'  => collect(),
            ],
            [
                'status_key' => ProspectStatut::RP->value,
                'label'      => ProspectStatut::RP->label(),
                'color'      => 'success',
                'prospects'  => collect(),
            ],
            [
                'status_key' => ProspectStatut::KO->value,
                'label'      => ProspectStatut::KO->label(),
                'color'      => 'danger',
                'prospects'  => collect(),
            ],
            [
                'status_key' => ProspectStatut::QF->value,
                'label'      => ProspectStatut::QF->label(),
                'color'      => 'info',
                'prospects'  => collect(),
            ],
        ];

        $prospects = Prospect::with(['teleprospecteur'])
            ->whereNull('deleted_at')
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($columns as &$column) {
            $column['prospects'] = $prospects->filter(
                fn($p) => $p->statut instanceof ProspectStatut
                    ? $p->statut->value === $column['status_key']
                    : $p->statut === $column['status_key']
            )->values();
        }
        unset($column);

        $this->kanbanGroups = $columns;
    }

    // ── Mise à jour statut depuis drag & drop ────────────────────────
    public function updateProspectStatus(int $prospectId, string $newStatus): void
    {
        try {
            $prospect = Prospect::find($prospectId);

            if (! $prospect) {
                throw new \Exception('Prospect non trouvé');
            }

            if (! ProspectStatut::tryFrom($newStatus)) {
                throw new \Exception('Statut invalide : ' . $newStatus);
            }

            $prospect->update(['statut' => $newStatus]);

            $this->loadKanbanData();

            Notification::make()
                ->title('Statut mis à jour')
                ->body("{$prospect->nom} → " . ProspectStatut::from($newStatus)->label())
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('Erreur')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    // ── Actions header ───────────────────────────────────────────────
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('campagne')
                ->label('Workflow Phoning')
                ->icon('heroicon-o-phone-arrow-up-right')
                ->url(PhoningWorkflow::getUrl())
                ->color('warning'),

            Actions\CreateAction::make()->label('Nouveau prospect'),

            ImportProspectsAction::make(),
        ];
    }

    protected function getListeners(): array
    {
        return [
            'updateProspectStatus' => 'handleStatusUpdate',
        ];
    }

    public function handleStatusUpdate($payload)
    {
        $this->updateProspectStatus($payload['prospectId'], $payload['newStatus']);
    }

    // ── Onglets ──────────────────────────────────────────────────────
    public function getTabs(): array
    {
        $tabs = [
            'tous' => Tab::make('Tous')->badge(Prospect::count()),
        ];

        foreach (ProspectStatut::cases() as $statut) {
            $tabs[$statut->value] = Tab::make($statut->label())
                ->modifyQueryUsing(fn(Builder $q) => $q->where('statut', $statut))
                ->badge(Prospect::where('statut', $statut)->count())
                ->badgeColor($statut->color());
        }

        return $tabs;
    }
}
