<?php
namespace App\Filament\Allopro\Resources\RapportSatisfactionP6Resource\Pages;

use App\Filament\Allopro\Resources\RapportSatisfactionP6Resource;

use Filament\Actions;
use Filament\Notifications\Notification;

use Filament\Resources\Pages\ViewRecord;
class ViewRapportSatisfactionP6 extends ViewRecord
{
    protected static string $resource = RapportSatisfactionP6Resource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('transmettre_feedback')
                ->label('Marquer feedback transmis')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('info')
                ->visible(fn() => !$this->record->feedback_artisan)
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update(['feedback_artisan' => true]);
                    $this->refreshFormData(['feedback_artisan']);
                    Notification::make()->title('Feedback transmis à l\'artisan')->success()->send();
                }),

            Actions\Action::make('ouvrir_p8')
                ->label('Ouvrir P8 manuellement')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('danger')
                ->visible(fn() => $this->record->declencheP8() && !$this->record->reclamation()->exists())
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->ouvrirReclamationP8();
                    Notification::make()->title('Réclamation P8 ouverte')->danger()->send();
                }),
        ];
    }
}
