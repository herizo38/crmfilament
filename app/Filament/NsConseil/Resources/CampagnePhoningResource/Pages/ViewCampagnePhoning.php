<?php

namespace App\Filament\NsConseil\Resources\CampagnePhoningResource\Pages;

use App\Filament\NsConseil\Resources\CampagnePhoningResource;
use App\Models\CampagnePhoning;
use Filament\Actions;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewCampagnePhoning extends ViewRecord
{
    protected static string $resource = CampagnePhoningResource::class;

    protected function getHeaderActions(): array
    {
        $record = $this->getRecord();

        return [
            Actions\Action::make('lancer_phoning')
                ->label('Lancer le phoning')
                ->icon('heroicon-o-phone-arrow-up-right')
                ->color('primary')
                ->visible(fn () => $record->statut === 'active')
                ->url(fn () => route('filament.ns-conseil.pages.phoning-workflow', ['campagne_id' => $record->id])),

            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Informations')
                ->icon('heroicon-o-megaphone')
                ->columns(3)
                ->schema([
                    TextEntry::make('nom')->label('Nom de la campagne')->weight('bold'),
                    TextEntry::make('statut')
                        ->label('Statut')
                        ->badge()
                        ->formatStateUsing(fn ($state) => CampagnePhoning::STATUTS[$state] ?? $state)
                        ->color(fn ($state) => match ($state) {
                            'active' => 'success',
                            'terminee' => 'gray',
                            default => 'warning',
                        }),
                    TextEntry::make('type_entite')
                        ->label('Cible')
                        ->formatStateUsing(fn ($state) => CampagnePhoning::TYPES_ENTITE[$state] ?? $state),
                    TextEntry::make('user.nom')
                        ->label('Assigné à')
                        ->formatStateUsing(fn ($record) => $record->user
                            ? trim("{$record->user->prenom} {$record->user->nom}")
                            : 'Tous les agents'),
                    TextEntry::make('date_debut')->label('Début')->date('d/m/Y')->placeholder('—'),
                    TextEntry::make('date_fin')->label('Fin')->date('d/m/Y')->placeholder('—'),
                    TextEntry::make('description')->label('Description')->columnSpanFull()->placeholder('—'),
                ]),

            Section::make('Progression')
                ->icon('heroicon-o-chart-bar')
                ->columns(4)
                ->schema([
                    TextEntry::make('stats_contacts')
                        ->label('Contacts total')
                        ->getStateUsing(fn ($record) => $record->getStats()['total_contacts'])
                        ->badge()
                        ->color('info'),
                    TextEntry::make('stats_traites')
                        ->label('Contacts traités')
                        ->getStateUsing(fn ($record) => $record->getStats()['contacts_traites'])
                        ->badge()
                        ->color('success'),
                    TextEntry::make('stats_restants')
                        ->label('Contacts restants')
                        ->getStateUsing(fn ($record) => $record->getStats()['contacts_restants'])
                        ->badge()
                        ->color('warning'),
                    TextEntry::make('stats_progression')
                        ->label('Progression')
                        ->getStateUsing(fn ($record) => $record->getStats()['progression'].'%')
                        ->badge()
                        ->color(fn ($record) => match (true) {
                            $record->getStats()['progression'] >= 80 => 'success',
                            $record->getStats()['progression'] >= 40 => 'warning',
                            default => 'danger',
                        }),
                ]),

            Section::make('Résultats des appels')
                ->icon('heroicon-o-phone')
                ->columns(2)
                ->schema([
                    TextEntry::make('stats_total_appels')
                        ->label('Total appels passés')
                        ->getStateUsing(fn ($record) => $record->getStats()['total_appels'])
                        ->badge()
                        ->color('info'),
                    TextEntry::make('stats_par_statut')
                        ->label('Répartition par statut')
                        ->getStateUsing(function ($record) {
                            $stats = $record->getStats();
                            if (empty($stats['par_statut'])) {
                                return 'Aucun appel enregistré';
                            }

                            return collect($stats['par_statut'])
                                ->map(fn ($count, $code) => "{$code}: {$count}")
                                ->implode(' | ');
                        })
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
