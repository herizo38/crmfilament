<?php

namespace App\Filament\NsConseil\Resources;

use App\Filament\NsConseil\Concerns\HasRoleAccess;
use App\Filament\NsConseil\Resources\ClientResource\Actions\ImportClientsAction;
use App\Filament\NsConseil\Resources\ClientResource\Pages;
use App\Filament\NsConseil\Resources\ClientResource\RelationManagers\DocumentsRelationManager;
use App\Filament\NsConseil\Resources\ClientResource\RelationManagers\DossierFormationsRelationManager;
use App\Filament\NsConseil\Resources\ClientResource\RelationManagers\PropositionsRelationManager;
use App\Filament\NsConseil\Resources\ClientResource\RelationManagers\RendezVousRelationManager;
use App\Models\Client;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ClientResource extends Resource
{
    use HasRoleAccess;

    protected static ?string $model = Client::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Clients & Formations';

    protected static ?string $navigationLabel = 'Clients';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return static::userHasAnyRole(['admin', 'superviseur', 'commercial']);
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) Client::count();
    }

    // ─────────────────────────────────────────────────────────────────
    // FORMULAIRE
    // ─────────────────────────────────────────────────────────────────
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identité')
                ->icon('heroicon-o-user')
                ->schema([
                    Forms\Components\Select::make('civilite')
                        ->label('Civilité')
                        ->options([
                            'M.' => 'M.',
                            'Mme' => 'Mme',
                            'Mlle' => 'Mlle',
                            'Dr' => 'Dr',
                        ]),

                    Forms\Components\TextInput::make('nom_tiers')
                        ->label('Nom')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('email')
                        ->label('Email')
                        ->email(),

                    Forms\Components\TextInput::make('telephone')
                        ->label('Téléphone')
                        ->tel(),

                    Forms\Components\DatePicker::make('date_naissance')
                        ->label('Date de naissance')
                        ->displayFormat('d/m/Y'),

                    Forms\Components\TextInput::make('entreprise')
                        ->label('Entreprise'),

                    Forms\Components\TextInput::make('ref_client')
                        ->label('Réf. Client')
                        ->disabled()
                        ->helperText('Généré automatiquement'),

                    Forms\Components\Select::make('partenaire_id')
                        ->label('Partenaire')
                        ->relationship('partenaire', 'nom')
                        ->searchable()
                        ->preload(),

                    Forms\Components\Select::make('parrain_id')
                        ->label('Parrain')
                        ->relationship('parrain', 'nom_prenom')
                        ->searchable()
                        ->preload(),
                ])->columns(3),

            Forms\Components\Section::make('Adresse')
                ->icon('heroicon-o-map-pin')
                ->schema([
                    Forms\Components\Textarea::make('adresse')
                        ->label('Adresse')
                        ->rows(2)
                        ->columnSpan(2),

                    Forms\Components\TextInput::make('code_postal')
                        ->label('Code postal')
                        ->maxLength(5),

                    Forms\Components\TextInput::make('ville')
                        ->label('Ville'),

                    Forms\Components\TextInput::make('departement')
                        ->label('Département')
                        ->maxLength(3),

                    Forms\Components\TextInput::make('region')
                        ->label('Région'),
                ])->columns(3),

            Forms\Components\Section::make('Formation & CPF')
                ->icon('heroicon-o-academic-cap')
                ->schema([
                    Forms\Components\Select::make('etat')
                        ->label('État')
                        ->options([
                            'prospect' => 'Prospect',
                            'en_cours' => 'En cours',
                            'termine' => 'Terminé',
                            'certifie' => 'Certifié',
                            'abandonne' => 'Abandonné',
                        ]),

                    Forms\Components\TextInput::make('montant_cpf')
                        ->label('Montant CPF (€)')
                        ->numeric()
                        ->prefix('€'),

                    Forms\Components\Toggle::make('ne_plus_contacter')
                        ->label('Ne plus contacter')
                        ->inline(false),

                    Forms\Components\TextInput::make('source_sheet')
                        ->label('Source (fichier)')
                        ->disabled(),
                ])->columns(2),

            Forms\Components\Section::make('Données supplémentaires')
                ->schema([
                    Forms\Components\KeyValue::make('extra_data')
                        ->label('Données extra')
                        ->columnSpanFull(),
                ])
                ->collapsible()
                ->collapsed(),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // TABLE - Version optimisée
    // ─────────────────────────────────────────────────────────────────
    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                // 🔵 Colonne principale : Nom + Civilité
                Tables\Columns\TextColumn::make('nom_tiers')
                    ->label('Client')
                    ->searchable(['nom_tiers', 'email', 'telephone'])
                    ->sortable()
                    ->weight('bold')
                    ->formatStateUsing(fn ($state, Client $record) => trim(($record->civilite ? $record->civilite.' ' : '').$state)
                    )
                    ->description(fn (Client $record) => $record->email ?? $record->telephone)
                    ->toggleable(),

                // 📞 Contact
                Tables\Columns\TextColumn::make('telephone')
                    ->label('Tél.')
                    ->copyable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->copyable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),

                // 📍 Localisation
                Tables\Columns\TextColumn::make('ville')
                    ->label('Ville')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('departement')
                    ->label('Dép.')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),

                // 🤝 Relations
                Tables\Columns\TextColumn::make('partenaire.nom')
                    ->label('Partenaire')
                    ->searchable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),

                Tables\Columns\TextColumn::make('parrain.nom_prenom')
                    ->label('Parrain')
                    ->searchable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),

                // 📊 État & Formation
                Tables\Columns\TextColumn::make('etat')
                    ->label('État')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'prospect' => 'Prospect',
                        'en_cours' => 'En cours',
                        'termine' => 'Terminé',
                        'certifie' => 'Certifié',
                        'abandonne' => 'Abandonné',
                        default => $state ?? '—',
                    })
                    ->color(fn ($state) => match ($state) {
                        'prospect' => 'gray',
                        'en_cours' => 'primary',
                        'termine' => 'success',
                        'certifie' => 'success',
                        'abandonne' => 'danger',
                        default => 'gray',
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('montant_cpf')
                    ->label('CPF')
                    ->money('EUR')
                    ->sortable()
                    ->alignRight()
                    ->toggleable(),

                // 🚫 Ne plus contacter
                Tables\Columns\IconColumn::make('ne_plus_contacter')
                    ->label('NPC')
                    ->boolean()
                    ->trueColor('danger')
                    ->falseColor('gray')
                    ->toggleable()
                    ->toggledHiddenByDefault(),

                // 📅 Dates
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
            ])
            ->filters([
                // 📊 Filtres principaux
                Tables\Filters\SelectFilter::make('etat')
                    ->label('État')
                    ->options([
                        'prospect' => 'Prospect',
                        'en_cours' => 'En cours',
                        'termine' => 'Terminé',
                        'certifie' => 'Certifié',
                        'abandonne' => 'Abandonné',
                    ])
                    ->placeholder('Tous les états'),

                // 🤝 Filtres relations
                Tables\Filters\SelectFilter::make('partenaire_id')
                    ->label('Partenaire')
                    ->relationship('partenaire', 'nom')
                    ->searchable()
                    ->preload()
                    ->placeholder('Tous les partenaires'),

                Tables\Filters\SelectFilter::make('parrain_id')
                    ->label('Parrain')
                    ->relationship('parrain', 'nom_prenom')
                    ->searchable()
                    ->preload()
                    ->placeholder('Tous les parrains'),

                // 📍 Filtres géographiques
                Tables\Filters\SelectFilter::make('region')
                    ->label('Région')
                    ->options(
                        fn () => Client::whereNotNull('region')
                            ->where('region', '!=', '')
                            ->distinct()
                            ->orderBy('region')
                            ->pluck('region', 'region')
                            ->toArray()
                    )
                    ->placeholder('Toutes les régions')
                    ->searchable(),

                Tables\Filters\SelectFilter::make('departement')
                    ->label('Département')
                    ->options(
                        fn () => Client::whereNotNull('departement')
                            ->where('departement', '!=', '')
                            ->distinct()
                            ->orderBy('departement')
                            ->pluck('departement', 'departement')
                            ->toArray()
                    )
                    ->placeholder('Tous les départements')
                    ->searchable(),

                // 🎯 Filtres booléens
                Tables\Filters\Filter::make('contactables')
                    ->label('Contactables')
                    ->query(
                        fn (Builder $q) => $q->where('ne_plus_contacter', false)
                            ->where(function ($q) {
                                $q->whereNotNull('email')->orWhereNotNull('telephone');
                            })
                    )
                    ->toggle(),

                Tables\Filters\Filter::make('avec_cpf')
                    ->label('Avec CPF')
                    ->query(fn (Builder $q) => $q->whereNotNull('montant_cpf')->where('montant_cpf', '>', 0))
                    ->toggle(),

                Tables\Filters\Filter::make('sans_proposition')
                    ->label('Sans proposition')
                    ->query(fn (Builder $q) => $q->doesntHave('propositions')),

                // 🗑️ Corbeille
                Tables\Filters\TrashedFilter::make()
                    ->label('Corbeille'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label(''),
                Tables\Actions\EditAction::make()
                    ->label(''),

                Tables\Actions\Action::make('toggle_contact')
                    ->label(fn (Client $record) => $record->ne_plus_contacter ? 'Réactiver' : 'Bloquer')
                    ->icon(fn (Client $record) => $record->ne_plus_contacter ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle')
                    ->color(fn (Client $record) => $record->ne_plus_contacter ? 'success' : 'danger')
                    ->action(function (Client $record) {
                        if ($record->ne_plus_contacter) {
                            $record->reactiver();
                        } else {
                            $record->marquerNePlusContacter('Bloqué manuellement');
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Aucun client')
            ->emptyStateDescription('Importez des clients depuis un fichier CSV.')
            ->emptyStateActions([
                Tables\Actions\Action::make('import')
                    ->label('Importer des clients')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->url(ImportClientsAction::class),
            ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // INFOLIST
    // ─────────────────────────────────────────────────────────────────
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Identité')
                ->schema([
                    Infolists\Components\TextEntry::make('nom_tiers')
                        ->label('Nom')
                        ->weight('bold')
                        ->formatStateUsing(fn ($state, Client $record) => $record->nom_complet),
                    Infolists\Components\TextEntry::make('ref_client')
                        ->label('Référence'),
                    Infolists\Components\TextEntry::make('civilite')
                        ->label('Civilité'),
                    Infolists\Components\TextEntry::make('date_naissance')
                        ->label('Né(e) le')
                        ->date('d/m/Y'),
                    Infolists\Components\TextEntry::make('age')
                        ->label('Âge')
                        ->suffix(' ans'),
                    Infolists\Components\TextEntry::make('entreprise')
                        ->label('Entreprise'),
                    Infolists\Components\TextEntry::make('partenaire.nom')
                        ->label('Partenaire')
                        ->placeholder('Aucun'),
                    Infolists\Components\TextEntry::make('parrain.nom_prenom')
                        ->label('Parrain')
                        ->placeholder('Aucun'),
                ])->columns(3),

            Infolists\Components\Section::make('Coordonnées')
                ->schema([
                    Infolists\Components\TextEntry::make('telephone')
                        ->label('Téléphone')
                        ->copyable(),
                    Infolists\Components\TextEntry::make('email')
                        ->label('Email')
                        ->copyable(),
                    Infolists\Components\TextEntry::make('adresse_complete')
                        ->label('Adresse'),
                    Infolists\Components\TextEntry::make('localisation')
                        ->label('Localisation'),
                ])->columns(2),

            Infolists\Components\Section::make('Formation')
                ->schema([
                    Infolists\Components\TextEntry::make('etat')
                        ->label('État')
                        ->badge()
                        ->formatStateUsing(fn ($state) => match ($state) {
                            'prospect' => 'Prospect',
                            'en_cours' => 'En cours',
                            'termine' => 'Terminé',
                            'certifie' => 'Certifié',
                            'abandonne' => 'Abandonné',
                            default => $state ?? '—',
                        })
                        ->color(fn ($state) => match ($state) {
                            'prospect' => 'gray',
                            'en_cours' => 'primary',
                            'termine' => 'success',
                            'certifie' => 'success',
                            'abandonne' => 'danger',
                            default => 'gray',
                        }),
                    Infolists\Components\TextEntry::make('montant_cpf')
                        ->label('Montant CPF')
                        ->money('EUR'),
                    Infolists\Components\IconEntry::make('ne_plus_contacter')
                        ->label('Ne plus contacter')
                        ->boolean(),
                    Infolists\Components\TextEntry::make('source_sheet')
                        ->label('Fichier source'),
                ])->columns(2),

            Infolists\Components\Section::make('Statistiques formation')
                ->schema([
                    Infolists\Components\TextEntry::make('total_heures_formation')
                        ->label('Total heures formation')
                        ->numeric()
                        ->placeholder('0'),
                    Infolists\Components\TextEntry::make('total_heures_realisees')
                        ->label('Heures réalisées')
                        ->numeric()
                        ->placeholder('0'),
                    Infolists\Components\TextEntry::make('total_heures_restantes')
                        ->label('Heures restantes')
                        ->numeric()
                        ->placeholder('0'),
                    Infolists\Components\TextEntry::make('progression_formation')
                        ->label('Progression')
                        ->suffix('%')
                        ->numeric()
                        ->placeholder('0'),
                    Infolists\Components\TextEntry::make('montant_total_cpf')
                        ->label('Montant total CPF')
                        ->money('EUR')
                        ->placeholder('0,00 €'),
                ])->columns(3),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            PropositionsRelationManager::class,
            DocumentsRelationManager::class,
            RendezVousRelationManager::class,
            DossierFormationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'edit' => Pages\EditClient::route('/{record}/edit'),
            'view' => Pages\ViewClient::route('/{record}'),
        ];
    }
}
