<?php
namespace App\Filament\Allopro\Resources\RapportSatisfactionP6Resource\Pages;

use App\Enums\TicketStatut;
use App\Filament\Allopro\Resources\RapportSatisfactionP6Resource;
use App\Models\RapportSatisfactionP6;
use App\Models\Ticket;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListRapportSatisfactionP6s extends ListRecords
{
    protected static string $resource = RapportSatisfactionP6Resource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Saisir rapport P6')
                ->icon('heroicon-o-plus'),
        ];
    }

    public function getTabs(): array
    {
        // Tickets en attente d'appel J+1
        $aAppeler = Ticket::where('statut', TicketStatut::InterventionRealisee->value)
            ->doesntHave('rapportSatisfaction')
            ->count();

        return [
            'tous' => Tab::make('Tous')->badge(RapportSatisfactionP6::count()),

            'a_appeler' => Tab::make('📞 À appeler J+1')
                ->badge($aAppeler)
                ->badgeColor($aAppeler > 0 ? 'danger' : 'gray')
                ->modifyQueryUsing(fn($q) => $q->whereHas('ticket', fn($t) =>
                    $t->where('statut', TicketStatut::InterventionRealisee->value)
                      ->doesntHave('rapportSatisfaction')
                )),

            'satisfaits' => Tab::make('✅ Satisfaits')
                ->badge(RapportSatisfactionP6::where('note_nps', '>=', 8)->count())
                ->badgeColor('success')
                ->modifyQueryUsing(fn($q) => $q->satisfaits()),

            'suivi_qualite' => Tab::make('⚠️ Suivi qualité')
                ->badge(RapportSatisfactionP6::whereBetween('note_nps', [6, 7])->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn($q) => $q->whereBetween('note_nps', [6, 7])),

            'detracteurs' => Tab::make('🚨 Détracteurs')
                ->badge(RapportSatisfactionP6::where('note_nps', '<=', 5)->count())
                ->badgeColor('danger')
                ->modifyQueryUsing(fn($q) => $q->detracteurs()),

            'sans_feedback' => Tab::make('Feedback manquant')
                ->badge(RapportSatisfactionP6::where('feedback_artisan', false)->count())
                ->badgeColor('warning')
                ->modifyQueryUsing(fn($q) => $q->where('feedback_artisan', false)),

            'du_mois' => Tab::make('Ce mois')
                ->badge(RapportSatisfactionP6::duMois()->count())
                ->modifyQueryUsing(fn($q) => $q->duMois()),
        ];
    }
}
