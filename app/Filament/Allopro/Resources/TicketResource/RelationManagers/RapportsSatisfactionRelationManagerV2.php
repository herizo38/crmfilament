<?php
namespace App\Filament\Allopro\Resources\TicketResource\RelationManagers;
;
use App\Enums\TicketStatut;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * RapportsSatisfactionRelationManager — VERSION COMPLÈTE
 * Remplace la version basique dans TicketResource/RelationManagers/
 */
class RapportsSatisfactionRelationManagerV2 extends RelationManager
{
    protected static string $relationship = 'rapportsSatisfaction';
    protected static ?string $title = 'Rapport satisfaction P6';
    protected static ?string $icon  = 'heroicon-o-star';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\DatePicker::make('date_appel_j1')
                ->label('Date appel J+1')
                ->required()->native(false)
                ->default(now()->addDay())
                ->helperText('SLA : dans les 72h post-intervention'),

            Forms\Components\Select::make('note_nps')
                ->label('Note NPS (0-10)')
                ->options(array_combine(range(0, 10), range(0, 10)))
                ->required()->native(false)->live()
                ->afterStateUpdated(function ($state, Forms\Set $set) {
                    if ($state === null) return;
                    $set('statut_cloture', match(true) {
                        $state >= 8 => 'satisfait',
                        $state >= 6 => 'suivi_qualite_requis',
                        default     => 'reclamation_ouverte',
                    });
                })
                ->helperText(fn(\Filament\Forms\Get $get) => match(true) {
                    ($get('note_nps') ?? -1) >= 9 => '⭐ Promoteur',
                    ($get('note_nps') ?? -1) >= 7 => '😐 Passif',
                    ($get('note_nps') ?? -1) >= 6 => '⚠️ Suivi qualité requis (P7)',
                    ($get('note_nps') ?? -1) >= 0 => '🚨 Détracteur → P8 déclenchée automatiquement',
                    default => '',
                }),

            Forms\Components\Select::make('statut_cloture')
                ->label('Statut de clôture')
                ->options([
                    'satisfait'            => '✅ Satisfait',
                    'suivi_qualite_requis' => '⚠️ Suivi qualité requis',
                    'reclamation_ouverte'  => '🚨 Réclamation ouverte (P8)',
                ])
                ->required()->native(false),

            Forms\Components\Toggle::make('feedback_artisan')
                ->label('Feedback transmis à l\'artisan')
                ->helperText('Obligatoire CDC — règle non négociable')
                ->default(false)->inline(false),

            Forms\Components\Textarea::make('verbatim_client')
                ->label('Verbatim client (mot à mot)')->rows(4)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('date_appel_j1', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('note_nps')->label('NPS')->badge()
                    ->formatStateUsing(fn($s) => $s . ' / 10')
                    ->color(fn($s) => match(true) {
                        $s >= 9 => 'success', $s >= 7 => 'warning', $s >= 6 => 'orange', default => 'danger',
                    }),

                Tables\Columns\TextColumn::make('statut_cloture')->label('Résultat')->badge()
                    ->formatStateUsing(fn($s) => match(is_object($s) ? $s->value : $s) {
                        'satisfait'            => '✅ Satisfait',
                        'suivi_qualite_requis' => '⚠️ Suivi qualité',
                        'reclamation_ouverte'  => '🚨 Réclamation P8',
                        default                => $s,
                    })
                    ->color(fn($s) => match(is_object($s) ? $s->value : $s) {
                        'satisfait'            => 'success',
                        'suivi_qualite_requis' => 'warning',
                        'reclamation_ouverte'  => 'danger',
                        default                => 'gray',
                    }),

                Tables\Columns\IconColumn::make('feedback_artisan')->label('Feedback')
                    ->boolean()->trueColor('success')->falseColor('danger'),

                Tables\Columns\TextColumn::make('verbatim_client')->label('Verbatim')
                    ->limit(50)->placeholder('—'),

                Tables\Columns\TextColumn::make('date_appel_j1')->label('Date J+1')->date('d/m/Y'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Saisir rapport P6')
                    ->visible(fn() =>
                        $this->getOwnerRecord()->statut === TicketStatut::InterventionRealisee
                    )
                    ->after(function ($record) {
                        // Mise à jour statut ticket après saisie NPS
                        $ticket = $this->getOwnerRecord();
                        $nouveauStatut = match(true) {
                            $record->note_nps >= 8 => TicketStatut::ClotureSatisfait,
                            $record->note_nps >= 6 => TicketStatut::SuiviQualiteRequis,
                            default                => TicketStatut::ReclamationOuverte,
                        };
                        $ticket->update(['statut' => $nouveauStatut->value]);
                        if ($record->note_nps <= 5) {
                            Notification::make()
                                ->title('🚨 NPS ≤ 5 — Réclamation P8 ouverte automatiquement')
                                ->danger()->persistent()->send();
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('feedback')
                    ->label('Feedback transmis')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn($r) => !$r->feedback_artisan)
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update(['feedback_artisan' => true]);
                        Notification::make()->title('Feedback marqué transmis')->success()->send();
                    }),
                Tables\Actions\EditAction::make(),
            ]);
    }
}
