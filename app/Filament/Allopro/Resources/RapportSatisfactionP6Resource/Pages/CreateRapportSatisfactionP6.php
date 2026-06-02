<?php
namespace App\Filament\Allopro\Resources\RapportSatisfactionP6Resource\Pages;

use App\Filament\Allopro\Resources\RapportSatisfactionP6Resource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;


class CreateRapportSatisfactionP6 extends CreateRecord
{
    protected static string $resource = RapportSatisfactionP6Resource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['operateur_id'] = $data['operateur_id'] ?? auth()->id();
        return $data;
    }

    protected function afterCreate(): void
    {
        $record = $this->getRecord();

        // Mise à jour statut ticket
        if ($record->ticket && $record->ticket->statut === \App\Enums\TicketStatut::InterventionRealisee) {
            $nouveauStatut = match(true) {
                $record->note_nps >= 8 => \App\Enums\TicketStatut::ClotureSatisfait,
                $record->note_nps >= 6 => \App\Enums\TicketStatut::SuiviQualiteRequis,
                default                => \App\Enums\TicketStatut::ReclamationOuverte,
            };
            $record->ticket->changerStatut(
                $nouveauStatut,
                "NPS {$record->note_nps}/10 — Rapport P6 saisi le " . now()->format('d/m/Y')
            );
        }

        // Notification si P8 déclenché
        if ($record->note_nps <= 5) {
            Notification::make()
                ->title('🚨 NPS ≤ 5 — Réclamation P8 ouverte automatiquement')
                ->body('Délai de résolution : 5 jours ouvrés.')
                ->danger()
                ->persistent()
                ->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
