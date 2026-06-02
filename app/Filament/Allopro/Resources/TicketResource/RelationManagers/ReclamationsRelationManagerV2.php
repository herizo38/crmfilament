<?php
namespace App\Filament\Allopro\Resources\TicketResource\RelationManagers;

use App\Enums\StatutReclamation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ReclamationsRelationManagerV2 extends RelationManager
{
    protected static string $relationship = 'reclamations';
    protected static ?string $title = 'Réclamations P8';
    protected static ?string $icon  = 'heroicon-o-exclamation-triangle';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('statut')
                ->label('Statut')
                ->options(collect(StatutReclamation::cases())
                    ->mapWithKeys(fn($e) => [$e->value => $e->label()])->toArray())
                ->required()->native(false)->default('ouverte'),
            Forms\Components\Textarea::make('description_reclamation')
                ->label('Description')->required()->rows(4)->columnSpanFull(),
            Forms\Components\Textarea::make('notes_resolution')
                ->label('Notes de résolution')->rows(3)->columnSpanFull(),
            Forms\Components\DatePicker::make('date_resolution_cible')
                ->label('Résolution cible (J+5)')->native(false)
                ->default(now()->addWeekdays(5)),
            Forms\Components\DatePicker::make('date_resolution_effective')
                ->label('Résolue le')->native(false)->nullable(),
            Forms\Components\Toggle::make('validation_superviseur')
                ->label('Validé superviseur')->inline(false),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('date_ouverture', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('statut')->label('Statut')->badge()
                    ->formatStateUsing(fn($s) => $s instanceof StatutReclamation ? $s->label() : $s)
                    ->color(fn($s) => $s instanceof StatutReclamation ? $s->color() : match(is_object($s) ? $s->value : $s) {
                        'ouverte' => 'danger', 'en_traitement' => 'warning',
                        'validee_superviseur' => 'info', 'cloturee' => 'success', default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('description_reclamation')->label('Description')->limit(50),

                Tables\Columns\TextColumn::make('delai_restant_formate')->label('Délai')
                    ->getStateUsing(fn($r) => $r->delai_restant_formate)
                    ->color(fn($r) => $r->est_en_retard ? 'danger' : 'success'),

                Tables\Columns\TextColumn::make('date_ouverture')->label('Ouverte le')
                    ->dateTime('d/m/Y H:i'),

                Tables\Columns\TextColumn::make('date_resolution_effective')->label('Résolue le')
                    ->date('d/m/Y')->placeholder('En cours'),

                Tables\Columns\IconColumn::make('validation_superviseur')->label('Superviseur')
                    ->boolean(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Ouvrir réclamation P8')
                    ->visible(fn() => auth()->user()?->hasAnyRole(['back_office', 'responsable_plateau'])),
            ])
            ->actions([
                Tables\Actions\Action::make('prendre_en_charge')
                    ->label('Prendre en charge')
                    ->icon('heroicon-o-hand-raised')
                    ->color('warning')
                    ->visible(fn($r) => $r->estOuverte())
                    ->form([
                        Forms\Components\Textarea::make('notes')->label('Plan d\'action')->required()->rows(3),
                    ])
                    ->action(function ($record, array $data) {
                        $record->mettreEnTraitement($data['notes']);
                        Notification::make()->title('Prise en charge')->warning()->send();
                    }),

                Tables\Actions\Action::make('cloturer')
                    ->label('Clôturer')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn($r) => $r->estValideeSuperviseur() || $r->estEnTraitement())
                    ->form([
                        Forms\Components\Textarea::make('notes')->label('Solution apportée')->required()->rows(4),
                    ])
                    ->action(function ($record, array $data) {
                        $record->cloturer($data['notes']);
                        Notification::make()->title('Réclamation clôturée ✅')->success()->send();
                    }),

                Tables\Actions\EditAction::make(),
            ]);
    }
}
