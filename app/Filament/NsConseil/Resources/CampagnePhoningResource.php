<?php

namespace App\Filament\NsConseil\Resources;

use App\Enums\OrganizationType;
use App\Enums\ProspectStatut;
use App\Filament\NsConseil\Resources\CampagnePhoningResource\Pages;
use App\Models\CampagnePhoning;
use App\Models\EntiteCommerciale;
use App\Models\Partenaire;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CampagnePhoningResource extends Resource
{
    protected static ?string $model = CampagnePhoning::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationGroup = 'Activités';

    protected static ?string $navigationLabel = 'Campagnes d\'appels';

    protected static ?int $navigationSort = 1;

    // ─────────────────────────────────────────────────────────────────
    // FORMULAIRE
    // ─────────────────────────────────────────────────────────────────
    public static function form(Form $form): Form
    {
        return $form->schema([

            // ── Identité ─────────────────────────────────────────────
            Forms\Components\Section::make('Campagne')
                ->icon('heroicon-o-megaphone')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('nom')
                        ->label('Nom de la campagne')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('description')
                        ->label('Description')
                        ->rows(3)
                        ->columnSpanFull(),

                    Forms\Components\Select::make('statut')
                        ->label('Statut')
                        ->options(CampagnePhoning::STATUTS)
                        ->default('brouillon')
                        ->required(),

                    Forms\Components\Select::make('entite_id')
                        ->label('Entité commerciale')
                        ->options(fn () => EntiteCommerciale::orderBy('nom')->pluck('nom', 'id'))
                        ->searchable()
                        ->nullable(),
                ]),

            // ── Assignation ──────────────────────────────────────────
            Forms\Components\Section::make('Assignation')
                ->icon('heroicon-o-user')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('user_id')
                        ->label('Assigné à')
                        ->options(
                            fn () => User::where('actif', true)
                                ->orderBy('nom')
                                ->get()
                                ->mapWithKeys(fn ($u) => [$u->id => trim("{$u->prenom} {$u->nom}")])
                        )
                        ->searchable()
                        ->nullable()
                        ->default(fn () => auth()->user()?->hasRoleCache('teleprospecteur') ? auth()->id() : null)
                        ->placeholder('Tous les agents'),

                    Forms\Components\Select::make('type_entite')
                        ->label('Cible')
                        ->options(CampagnePhoning::TYPES_ENTITE)
                        ->default('prospects')
                        ->required()
                        ->live()
                        ->afterStateUpdated(fn (Forms\Set $set) => $set('criteres', [])),

                    Forms\Components\DatePicker::make('date_debut')
                        ->label('Date de début')
                        ->nullable()
                        ->displayFormat('d/m/Y'),

                    Forms\Components\DatePicker::make('date_fin')
                        ->label('Date de fin')
                        ->nullable()
                        ->displayFormat('d/m/Y')
                        ->afterOrEqual('date_debut'),
                ]),

            // ── Critères Prospects ───────────────────────────────────
            Forms\Components\Section::make('Critères — Prospects')
                ->icon('heroicon-o-funnel')
                ->columns(2)
                ->visible(fn (Get $get) => $get('type_entite') === 'prospects')
                ->schema([
                    Forms\Components\CheckboxList::make('criteres.statuts')
                        ->label('Statuts à inclure')
                        ->options(collect(ProspectStatut::cases())->mapWithKeys(
                            fn ($case) => [$case->value => $case->label()]
                        ))
                        ->default([])
                        ->columns(3)
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('criteres.departement')
                        ->label('Département')
                        ->placeholder('ex: 75')
                        ->maxLength(3),

                    Forms\Components\TextInput::make('criteres.secteur_activite')
                        ->label("Secteur d'activité")
                        ->placeholder('ex: BTP, Industrie…'),

                    Forms\Components\TextInput::make('criteres.nb_salaries_min')
                        ->label('Nb salariés min')
                        ->numeric()
                        ->minValue(0),

                    Forms\Components\TextInput::make('criteres.nb_salaries_max')
                        ->label('Nb salariés max')
                        ->numeric()
                        ->minValue(0),

                    Forms\Components\Select::make('criteres.type_pressenti')
                        ->label('Type pressenti')
                        ->options([
                            'cse' => 'CSE',
                            'artisan' => 'Artisan',
                            'direct' => 'Direct',
                        ])
                        ->nullable()
                        ->placeholder('Tous'),
                ]),

            // ── Critères Partenaires ─────────────────────────────────
            Forms\Components\Section::make('Critères — Partenaires')
                ->icon('heroicon-o-funnel')
                ->columns(2)
                ->visible(fn (Get $get) => $get('type_entite') === 'partenaires')
                ->schema([
                    Forms\Components\CheckboxList::make('criteres.statuts')
                        ->label('Statuts à inclure')
                        ->default([])
                        ->options(Partenaire::STATUTS)
                        ->columns(2)
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('criteres.departement')
                        ->label('Département')
                        ->placeholder('ex: 75')
                        ->maxLength(3),

                    Forms\Components\TextInput::make('criteres.secteur_activite')
                        ->label("Secteur d'activité"),

                    Forms\Components\Select::make('criteres.type')
                        ->label('Type de partenaire')
                        ->options(OrganizationType::class)
                        ->nullable()
                        ->placeholder('Tous'),
                ]),

            // ── Critères Clients ─────────────────────────────────────
            Forms\Components\Section::make('Critères — Clients')
                ->icon('heroicon-o-funnel')
                ->columns(2)
                ->visible(fn (Get $get) => $get('type_entite') === 'clients')
                ->schema([
                    Forms\Components\TextInput::make('criteres.departement')
                        ->label('Département')
                        ->placeholder('ex: 75')
                        ->maxLength(3),

                    Forms\Components\Select::make('criteres.etat')
                        ->label('État')
                        ->options([
                            'actif' => 'Actif',
                            'inactif' => 'Inactif',
                            'prospect' => 'Prospect',
                        ])
                        ->nullable()
                        ->placeholder('Tous'),

                    Forms\Components\TextInput::make('criteres.type_tiers')
                        ->label('Type de tiers')
                        ->placeholder('ex: particulier, entreprise…'),
                ]),

        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // TABLE
    // ─────────────────────────────────────────────────────────────────
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nom')
                    ->label('Campagne')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\BadgeColumn::make('statut')
                    ->label('Statut')
                    ->formatStateUsing(fn ($state) => CampagnePhoning::STATUTS[$state] ?? $state)
                    ->colors([
                        'warning' => 'brouillon',
                        'success' => 'active',
                        'gray' => 'terminee',
                    ]),

                Tables\Columns\BadgeColumn::make('type_entite')
                    ->label('Cible')
                    ->formatStateUsing(fn ($state) => CampagnePhoning::TYPES_ENTITE[$state] ?? $state)
                    ->colors([
                        'warning' => 'prospects',
                        'primary' => 'partenaires',
                        'success' => 'clients',
                    ]),

                Tables\Columns\TextColumn::make('user.nom')
                    ->label('Assigné à')
                    ->formatStateUsing(
                        fn ($record) => $record->user
                            ? trim("{$record->user->prenom} {$record->user->nom}")
                            : '—'
                    )
                    ->sortable(),

                Tables\Columns\TextColumn::make('date_debut')
                    ->label('Début')
                    ->date('d/m/Y')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('date_fin')
                    ->label('Fin')
                    ->date('d/m/Y')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('contacts_count')
                    ->label('Contacts')
                    ->getStateUsing(fn ($record) => $record->countContacts())
                    ->suffix(' contacts')
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('entite.nom')
                    ->label('Entité')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('statut')
                    ->label('Statut')
                    ->options(CampagnePhoning::STATUTS),

                Tables\Filters\SelectFilter::make('type_entite')
                    ->label('Cible')
                    ->options(CampagnePhoning::TYPES_ENTITE),

                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Assigné à')
                    ->options(
                        fn () => User::where('actif', true)
                            ->orderBy('nom')
                            ->get()
                            ->mapWithKeys(fn ($u) => [$u->id => trim("{$u->prenom} {$u->nom}")])
                    ),
            ])
            ->actions([
                Tables\Actions\Action::make('activer')
                    ->label('Activer')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->visible(fn ($record) => $record->statut === 'brouillon')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update(['statut' => 'active']);
                        Notification::make()->title('Campagne activée')->success()->send();
                    }),

                Tables\Actions\Action::make('terminer')
                    ->label('Terminer')
                    ->icon('heroicon-o-stop')
                    ->color('danger')
                    ->visible(fn ($record) => $record->statut === 'active')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update(['statut' => 'terminee']);
                        Notification::make()->title('Campagne terminée')->warning()->send();
                    }),

                Tables\Actions\Action::make('lancer_phoning')
                    ->label('Lancer le phoning')
                    ->icon('heroicon-o-phone-arrow-up-right')
                    ->color('primary')
                    ->visible(fn ($record) => $record->statut === 'active')
                    ->url(fn ($record) => route('filament.ns-conseil.pages.phoning-workflow', ['campagne_id' => $record->id])),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    // ─────────────────────────────────────────────────────────────────
    // PAGES
    // ─────────────────────────────────────────────────────────────────
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCampagnesPhonings::route('/'),
            'create' => Pages\CreateCampagnePhoning::route('/create'),
            'view' => Pages\ViewCampagnePhoning::route('/{record}'),
            'edit' => Pages\EditCampagnePhoning::route('/{record}/edit'),
        ];
    }
}
