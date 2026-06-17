<?php

namespace App\Filament\NsConseil\Resources;

use App\Filament\NsConseil\Resources\ClientResource\RelationManagers\DocumentsRelationManager;
use App\Filament\NsConseil\Resources\ClientResource\RelationManagers\RendezVousRelationManager;
use App\Filament\NsConseil\Resources\OpportuniteResource\Pages;
use App\Filament\NsConseil\Resources\ProspectResource\RelationManagers\AppelsRelationManager;
use App\Models\Opportunite;
use App\Models\Prospect;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OpportuniteResource extends Resource
{
    protected static ?string $model = Opportunite::class;
    protected static ?string $navigationIcon = 'heroicon-o-light-bulb';
    protected static ?string $navigationGroup = 'Pipeline';
    protected static ?string $navigationLabel = 'Opportunités';
    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        return (string) Opportunite::whereNotIn('statut', ['converti', 'perdu'])->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    // ─────────────────────────────────────────────────────────────────
    // FORMULAIRE
    // ─────────────────────────────────────────────────────────────────
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identification')
                ->icon('heroicon-o-building-office')
                ->schema([
                    Forms\Components\TextInput::make('nom_entite')
                        ->label("Nom de l'entité")
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(2),

                    Forms\Components\Select::make('type_pressenti')
                        ->label('Type pressenti')
                        ->options(\App\Enums\OrganizationType::class),

                    Forms\Components\TextInput::make('departement')
                        ->label('Département')
                        ->maxLength(3),

                    Forms\Components\TextInput::make('siret')
                        ->label('SIRET')
                        ->maxLength(14),

                    Forms\Components\TextInput::make('secteur_activite')
                        ->label("Secteur d'activité"),

                    Forms\Components\TextInput::make('nb_salaries')
                        ->label('Nombre de salariés')
                        ->numeric(),

                    Forms\Components\TextInput::make('chiffre_affaires')
                        ->label("CA (€)")
                        ->numeric()
                        ->prefix('€'),
                ])->columns(3),

            Forms\Components\Section::make('Contact')
                ->icon('heroicon-o-phone')
                ->schema([
                    Forms\Components\TextInput::make('telephone')
                        ->label('Téléphone')
                        ->tel(),

                    Forms\Components\TextInput::make('email')
                        ->label('Email')
                        ->email(),

                    Forms\Components\Textarea::make('adresse')
                        ->label('Adresse')
                        ->rows(2)
                        ->columnSpan(2),

                    Forms\Components\TextInput::make('interlocuteur_nom')
                        ->label('Interlocuteur — Nom'),

                    Forms\Components\TextInput::make('interlocuteur_fonction')
                        ->label('Fonction'),

                    Forms\Components\TextInput::make('interlocuteur_telephone')
                        ->label('Tél. interlocuteur')
                        ->tel(),

                    Forms\Components\TextInput::make('interlocuteur_email')
                        ->label('Email interlocuteur')
                        ->email(),
                ])->columns(3),

            Forms\Components\Section::make('Pipeline')
                ->icon('heroicon-o-chart-bar')
                ->schema([
                    Forms\Components\Select::make('statut')
                        ->label('Statut')
                        ->options(Opportunite::STATUTS)
                        ->default('nouveau')
                        ->required()
                        ->native(false)
                        ->reactive(),

                    Forms\Components\Select::make('potentiel')
                        ->label('Potentiel')
                        ->options(Opportunite::POTENTIELS)
                        ->default('moyen')
                        ->required(),

                    Forms\Components\Select::make('source_detection')
                        ->label('Source')
                        ->options(Opportunite::SOURCES)
                        ->required(),

                    Forms\Components\Textarea::make('details_source')
                        ->label('Détails source')
                        ->rows(2),

                    Forms\Components\Select::make('assigne_a')
                        ->label('Assigné à')
                        ->relationship('assigneA', 'nom')
                        ->getOptionLabelFromRecordUsing(fn(User $r) => "{$r->prenom} {$r->nom}")
                        ->searchable()
                        ->preload(),

                    Forms\Components\DatePicker::make('date_detection')
                        ->label('Détectée le')
                        ->displayFormat('d/m/Y')
                        ->default(now()),

                    Forms\Components\DatePicker::make('date_premier_contact')
                        ->label('1er contact le')
                        ->displayFormat('d/m/Y'),

                    Forms\Components\Textarea::make('raison_perte')
                        ->label('Raison de perte')
                        ->rows(2)
                        ->visible(fn(Get $get) => $get('statut') === 'perdu'),
                ])->columns(3),

            Forms\Components\Section::make('Notes')
                ->icon('heroicon-o-pencil-square')
                ->schema([
                    Forms\Components\Textarea::make('notes')
                        ->label('Notes')
                        ->rows(4)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // TABLE
    // ─────────────────────────────────────────────────────────────────
    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('date_detection', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('nom_entite')
                    ->label('Entité')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('departement')
                    ->label('Dép.')
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('statut')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn($state) => Opportunite::STATUTS[$state] ?? $state)
                    ->color(fn($state) => match ($state) {
                        'nouveau' => 'info',
                        'en_qualification' => 'warning',
                        'contacte' => 'primary',
                        'rdv_planifie' => 'orange',
                        'en_negociation' => 'purple',
                        'qualifiee' => 'primary',
                        'converti' => 'success',
                        'perdu' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('potentiel')
                    ->label('Potentiel')
                    ->badge()
                    ->formatStateUsing(fn($state) => Opportunite::POTENTIELS[$state] ?? $state)
                    ->color(fn($state) => match ($state) {
                        'faible' => 'gray',
                        'moyen' => 'info',
                        'eleve' => 'warning',
                        'tres_eleve' => 'success',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('source_detection')
                    ->label('Source')
                    ->formatStateUsing(fn($state) => Opportunite::SOURCES[$state] ?? $state),

                Tables\Columns\TextColumn::make('assigneA.nom')
                    ->label('Assigné')
                    ->formatStateUsing(fn($record) => $record->assigneA
                        ? "{$record->assigneA->prenom} {$record->assigneA->nom}"
                        : '—'),

                Tables\Columns\TextColumn::make('date_detection')
                    ->label('Détectée')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('age_jours')
                    ->label('Âge (j)')
                    ->alignCenter()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('statut')
                    ->options(Opportunite::STATUTS)
                    ->label('Statut'),

                Tables\Filters\SelectFilter::make('potentiel')
                    ->options(Opportunite::POTENTIELS)
                    ->label('Potentiel'),

                Tables\Filters\SelectFilter::make('source_detection')
                    ->options(Opportunite::SOURCES)
                    ->label('Source'),

                Tables\Filters\SelectFilter::make('assigne_a')
                    ->relationship('assigneA', 'nom')
                    ->label('Assigné'),

                Tables\Filters\Filter::make('actives')
                    ->label('Actives uniquement')
                    ->query(fn(Builder $q) => $q->whereNotIn('statut', ['converti', 'perdu']))
                    ->default(),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                // Qualifier (CDC §4.3 : pré-requis à la conversion)
                Tables\Actions\Action::make('qualifier')
                    ->label('Qualifier')
                    ->icon('heroicon-o-check-badge')
                    ->color('primary')
                    ->visible(fn(Opportunite $record) => !in_array($record->statut, ['qualifiee', 'converti', 'perdu']))
                    ->action(function (Opportunite $record) {
                        $record->marquerQualifiee();
                        Notification::make()
                            ->title('Opportunité qualifiée ✓')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Qualifier l\'opportunité')
                    ->modalDescription('L\'opportunité passe au statut « Qualifiée » et devient convertible en prospect.'),

                // Convertir en prospect
                Tables\Actions\Action::make('convertir')
                    ->label('→ Prospect')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color('success')
                    ->visible(fn(Opportunite $record) => $record->est_convertible)
                    ->action(function (Opportunite $record) {
                        $record->convertirEnProspect();
                        Notification::make()
                            ->title('Converti en prospect ✓')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Convertir en prospect')
                    ->modalDescription('Cette action créera un nouveau prospect à partir de cette opportunité.'),

                // Marquer comme perdue
                Tables\Actions\Action::make('perdre')
                    ->label('Perdue')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn(Opportunite $record) => !in_array($record->statut, ['converti', 'perdu']))
                    ->form([
                        Forms\Components\Textarea::make('raison_perte')
                            ->label('Raison de la perte')
                            ->required()
                            ->rows(3),
                    ])
                    ->action(function (Opportunite $record, array $data) {
                        $record->marquerPerdue($data['raison_perte']);
                        Notification::make()
                            ->title('Opportunité marquée comme perdue')
                            ->warning()
                            ->send();
                    })
                    ->modalHeading('Marquer comme perdue'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Aucune opportunité')
            ->emptyStateDescription('Créez votre première opportunité.');
    }

    public static function getRelations(): array
    {
        return [
            AppelsRelationManager::class,
            RendezVousRelationManager::class,
            DocumentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOpportunites::route('/'),
            'create' => Pages\CreateOpportunite::route('/create'),
            'edit' => Pages\EditOpportunite::route('/{record}/edit'),
        ];
    }
}
