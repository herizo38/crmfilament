<?php
namespace App\Filament\NsConseil\Resources;

use App\Enums\OrganizationStatus;
use App\Enums\OrganizationType;
use App\Filament\NsConseil\Resources\PartenaireResource\Actions\ImportPartenairesAction;
use App\Filament\NsConseil\Resources\PartenaireResource\Pages;
use App\Filament\NsConseil\Resources\PartenaireResource\RelationManagers;
use App\Models\Consultant;
use App\Models\Partenaire;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PartenaireResource extends Resource
{
    protected static ?string $model           = Partenaire::class;
    protected static ?string $navigationIcon  = 'heroicon-o-building-office-2';
    protected static ?string $navigationGroup = 'Pipeline';
    protected static ?string $navigationLabel = 'Partenaires';
    protected static ?int    $navigationSort  = 1;

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::whereNotIn('statut', [OrganizationStatus::Refus->value])->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    // ─────────────────────────────────────────────────────────────────
    // FORMULAIRE
    // ─────────────────────────────────────────────────────────────────
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identification')
                ->icon('heroicon-o-identification')
                ->schema([
                    Forms\Components\TextInput::make('nom')
                        ->label('Nom légal')->required()->maxLength(255)->columnSpan(2),
                    Forms\Components\TextInput::make('entreprise')
                        ->label('Entreprise')->maxLength(255)
                        ->helperText('Raison sociale — utilisée pour la nomenclature')
                        ->live(onBlur: true),
                    Forms\Components\TextInput::make('nom_retenu')
                        ->label('Nom retenu (nomenclature)')
                        ->maxLength(255)
                        ->columnSpan(2)
                        ->helperText('Nomenclature imposée : [Type] [Entreprise] [Ville]')
                        ->hintAction(
                            Forms\Components\Actions\Action::make('genererNomenclature')
                                ->label('Générer')
                                ->icon('heroicon-m-sparkles')
                                ->action(fn (Get $get, Set $set) => $set(
                                    'nom_retenu',
                                    Partenaire::genererNomenclature($get('type'), $get('entreprise'), $get('ville'))
                                ))
                        ),
                    Forms\Components\TextInput::make('siret')
                        ->label('SIRET')->maxLength(14)->minLength(14)
                        ->placeholder('14 chiffres exactement')
                        ->rules(['nullable', 'digits:14'])
                        ->unique(table: 'partenaires', column: 'siret', ignoreRecord: true)
                        ->helperText('14 chiffres — clé de déduplication'),
                    Forms\Components\Select::make('type')
                        ->label('Type')->required()
                        ->options(OrganizationType::class)
                        ->enum(OrganizationType::class)
                        ->reactive(),
                    Forms\Components\Select::make('nomenclature_interne')
                        ->label('Nomenclature interne')
                        ->options([
                            'CSE_PME'       => 'CSE PME (< 50 salariés)',
                            'CSE_ETI'       => 'CSE ETI (50–299 salariés)',
                            'CSE_GE'        => 'CSE Grande entreprise (300+)',
                            'SYND_BRANCHE'  => 'Syndicat de branche',
                            'SYND_INTERPRO' => 'Syndicat interprofessionnel',
                            'ENT_DIRECTE'   => 'Entreprise directe',
                            'ASSOC'         => 'Association',
                        ])
                        ->searchable()->nullable(),
                    Forms\Components\TextInput::make('departement')
                        ->label('Département')->maxLength(3)->placeholder('ex: 75'),
                    Forms\Components\TextInput::make('code_postal')
                        ->label('Code postal')->maxLength(5),
                    Forms\Components\TextInput::make('secteur_activite')
                        ->label("Secteur d'activité"),
                    Forms\Components\TextInput::make('nb_salaries')
                        ->label('Nombre de salariés')->numeric()->minValue(0),
                    Forms\Components\TextInput::make('chiffre_affaires')
                        ->label("Chiffre d'affaires (€)")->numeric()->prefix('€'),
                    Forms\Components\Select::make('entreprise_mere_id')
                        ->label('Entreprise mère (si agence)')
                        ->relationship('entrepriseMere', 'nom')
                        ->searchable()->preload()->nullable(),
                ])->columns(3),

            Forms\Components\Section::make('Pipeline et assignation')
                ->icon('heroicon-o-chart-bar')
                ->schema([
                    Forms\Components\Select::make('statut')
                        ->label('Statut')->required()
                        ->options(OrganizationStatus::class)
                        ->enum(OrganizationStatus::class)
                        ->default(OrganizationStatus::AProspecter)
                        ->native(false),

                    // CORRECTIF : conseiller_id → Consultant (pas User/commercial)
                    Forms\Components\Select::make('conseiller_id')
                        ->label('Conseiller assigné')
                        ->options(
                            fn() => Consultant::orderBy('nom')
                                ->get()
                                ->mapWithKeys(fn(Consultant $c) => [
                                    $c->id => trim("{$c->prenom} {$c->nom}"),
                                ])
                                ->toArray()
                        )
                        ->searchable()->preload()->nullable(),

                    Forms\Components\DatePicker::make('date_signature')
                        ->label('Date de signature')->displayFormat('d/m/Y'),
                    Forms\Components\DatePicker::make('date_convention')
                        ->label('Date de convention')->displayFormat('d/m/Y'),
                    Forms\Components\Select::make('origine_contact')
                        ->label('Origine du contact')
                        ->options([
                            'Salon'      => 'Salon',
                            'Démarchage' => 'Démarchage',
                            'Parrainage' => 'Parrainage',
                            'Réseau'     => 'Réseau',
                            'Autre'      => 'Autre',
                        ]),
                    Forms\Components\TextInput::make('parrain_marraine_texte')
                        ->label('Parrain / Marraine'),
                    Forms\Components\Toggle::make('parrainage_entreprise')
                        ->label('Parrainage entreprise'),
                    Forms\Components\TextInput::make('syndicat_majoritaire')
                        ->label('Syndicat majoritaire'),
                    Forms\Components\Textarea::make('possibilite_permanence')
                        ->label('Possibilité de permanence')->rows(1),
                    Forms\Components\Textarea::make('replicable')
                        ->label('Réplicable')->rows(1),
                ])->columns(3),

            Forms\Components\Section::make('Coordonnées')
                ->icon('heroicon-o-map-pin')
                ->schema([
                    Forms\Components\TextInput::make('telephone')->label('Téléphone')->tel(),
                    Forms\Components\TextInput::make('email')->label('Email générique')->email(),
                    Forms\Components\Textarea::make('adresse')->label('Adresse')->rows(2)->columnSpan(2),
                    Forms\Components\TextInput::make('ville')->label('Ville'),
                ])->columns(2),

            Forms\Components\Section::make('Contacts CSE')
                ->icon('heroicon-o-user-group')
                ->schema([
                    Forms\Components\Fieldset::make('Secrétaire')->schema([
                        Forms\Components\TextInput::make('cse_secretaire_nom')->label('Nom'),
                        Forms\Components\TextInput::make('cse_secretaire_prenom')->label('Prénom'),
                        Forms\Components\TextInput::make('cse_secretaire_tel_direct')->label('Tél. direct')->tel(),
                        Forms\Components\TextInput::make('cse_secretaire_tel_perso')->label('Tél. perso')->tel(),
                        Forms\Components\TextInput::make('cse_secretaire_email_pro')->label('Email pro')->email(),
                        Forms\Components\TextInput::make('cse_secretaire_email_perso')->label('Email perso')->email(),
                    ])->columns(3),
                    Forms\Components\Fieldset::make('Trésorier')->schema([
                        Forms\Components\TextInput::make('cse_tresorier_nom')->label('Nom'),
                        Forms\Components\TextInput::make('cse_tresorier_prenom')->label('Prénom'),
                        Forms\Components\TextInput::make('cse_tresorier_tel_direct')->label('Tél. direct')->tel(),
                        Forms\Components\TextInput::make('cse_tresorier_tel_perso')->label('Tél. perso')->tel(),
                        Forms\Components\TextInput::make('cse_tresorier_email_pro')->label('Email pro')->email(),
                        Forms\Components\TextInput::make('cse_tresorier_email_perso')->label('Email perso')->email(),
                    ])->columns(3),
                    Forms\Components\TextInput::make('cse_nb_elus')->label("Nombre d'élus")->numeric(),
                    Forms\Components\DatePicker::make('cse_date_fin_mandat')->label('Fin de mandat')->displayFormat('d/m/Y'),
                    Forms\Components\Toggle::make('cse_existence_juridique')->label('Existence juridique'),
                    Forms\Components\Textarea::make('cse_notes')->label('Notes CSE')->rows(2)->columnSpanFull(),
                ])
                ->columns(2)->collapsible()->collapsed()
                ->visible(fn(Get $get) => $get('type') === OrganizationType::CSE->value),

            Forms\Components\Section::make('Informations syndicales')
                ->icon('heroicon-o-users')
                ->schema([
                    Forms\Components\Select::make('syndicat_appartenance')->label('Appartenance syndicale')
                        ->options(['CGT' => 'CGT', 'CFDT' => 'CFDT', 'FO' => 'FO', 'CFTC' => 'CFTC', 'CFE-CGC' => 'CFE-CGC', 'UNSA' => 'UNSA', 'Autre' => 'Autre']),
                    Forms\Components\TextInput::make('syndicat_nom_organisation')->label("Nom de l'organisation"),
                    Forms\Components\TextInput::make('syndicat_responsable_nom')->label('Responsable — Nom'),
                    Forms\Components\TextInput::make('syndicat_responsable_prenom')->label('Responsable — Prénom'),
                    Forms\Components\TextInput::make('syndicat_responsable_fonction')->label('Fonction'),
                    Forms\Components\TextInput::make('syndicat_tel_direct')->label('Tél. direct')->tel(),
                    Forms\Components\TextInput::make('syndicat_tel_perso')->label('Tél. perso')->tel(),
                    Forms\Components\TextInput::make('syndicat_email_pro')->label('Email pro')->email(),
                    Forms\Components\TextInput::make('syndicat_email_perso')->label('Email perso')->email(),
                    Forms\Components\Textarea::make('syndicat_perimetre')->label('Périmètre')->rows(2),
                    Forms\Components\Textarea::make('syndicat_notes')->label('Notes syndicat')->rows(2),
                ])
                ->columns(3)->collapsible()->collapsed()
                ->visible(fn(Get $get) => $get('type') === OrganizationType::Syndicat->value),

            Forms\Components\Section::make('Dirigeant')
                ->icon('heroicon-o-user')
                ->schema([
                    Forms\Components\TextInput::make('dirigeant_nom')->label('Nom'),
                    Forms\Components\TextInput::make('dirigeant_prenom')->label('Prénom'),
                    Forms\Components\TextInput::make('dirigeant_fonction')->label('Fonction'),
                    Forms\Components\TextInput::make('dirigeant_telephone')->label('Téléphone')->tel(),
                    Forms\Components\TextInput::make('dirigeant_email')->label('Email')->email(),
                ])->columns(3)->collapsible()->collapsed(),

            Forms\Components\Section::make('Notes commerciales')
                ->icon('heroicon-o-pencil-square')
                ->schema([
                    Forms\Components\Textarea::make('commentaires')->label('Commentaires')->rows(3)->columnSpanFull(),
                    Forms\Components\Textarea::make('notes')->label('Notes internes')->rows(3)->columnSpanFull(),
                ]),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // TABLE
    // ─────────────────────────────────────────────────────────────────
    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('date_modification_statut', 'desc')

            ->columns([
                Tables\Columns\TextColumn::make('nom')
                    ->label('Nom légal')->searchable()->sortable()->weight('bold'),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')->badge()
                    ->formatStateUsing(
                        fn($state) => $state instanceof OrganizationType
                            ? $state->value
                            : OrganizationType::tryFrom((string) $state)?->value ?? $state
                    )
                    ->color(fn($state) => match ($state instanceof OrganizationType ? $state : OrganizationType::tryFrom((string) $state)) {
                        OrganizationType::CSE               => 'primary',
                        OrganizationType::Syndicat          => 'warning',
                        OrganizationType::EntrepriseDirecte => 'info',
                        OrganizationType::Association       => 'success',
                        default                             => 'gray',
                    }),

                Tables\Columns\TextColumn::make('departement')
                    ->label('Dép.')->sortable()->alignCenter(),

                Tables\Columns\TextColumn::make('statut')
                    ->label('Statut')->badge()
                    ->formatStateUsing(
                        fn($state) => $state instanceof OrganizationStatus
                            ? $state->label()
                            : OrganizationStatus::tryFrom((string) $state)?->label() ?? $state
                    )
                    ->color(fn($state) => match ($state instanceof OrganizationStatus ? $state : OrganizationStatus::tryFrom((string) $state)) {
                        OrganizationStatus::AProspecter          => 'gray',
                        OrganizationStatus::EnCoursProspection   => 'blue',
                        OrganizationStatus::RdvEnCours           => 'warning',
                        OrganizationStatus::SigneAccordCadre     => 'success',
                        OrganizationStatus::ConventionEngagement => 'success',
                        OrganizationStatus::Refus                => 'danger',
                        default                                  => 'gray',
                    })
                    ->icon(fn($state) => match ($state instanceof OrganizationStatus ? $state : OrganizationStatus::tryFrom((string) $state)) {
                        OrganizationStatus::AProspecter          => 'heroicon-o-queue-list',
                        OrganizationStatus::EnCoursProspection   => 'heroicon-o-phone',
                        OrganizationStatus::RdvEnCours           => 'heroicon-o-calendar',
                        OrganizationStatus::SigneAccordCadre     => 'heroicon-o-document-check',
                        OrganizationStatus::ConventionEngagement => 'heroicon-o-check-badge',
                        OrganizationStatus::Refus                => 'heroicon-o-x-circle',
                        default                                  => null,
                    }),

                // CORRECTIF : conseiller (Consultant) au lieu de commercial (User)
                Tables\Columns\TextColumn::make('conseiller.nom')
                    ->label('Conseiller')->sortable()
                    ->getStateUsing(fn($record) => $record->conseiller
                        ? trim("{$record->conseiller->prenom} {$record->conseiller->nom}") : '—'),

                Tables\Columns\TextColumn::make('date_signature')
                    ->label('Signature')->date('d/m/Y')->sortable()->toggleable(),

                Tables\Columns\TextColumn::make('date_modification_statut')
                    ->label('Modifié le')->dateTime('d/m/Y')->sortable()
                    ->description(fn($record) => $record->statut === OrganizationStatus::RdvEnCours
                        && $record->date_modification_statut?->lt(now()->subDays(80))
                        ? '⚠️ Approche 90j' : null),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('statut')
                    ->options(OrganizationStatus::class)->label('Statut')->multiple(),
                Tables\Filters\SelectFilter::make('type')
                    ->options(OrganizationType::class)->multiple(),

                // CORRECTIF : filtre sur conseiller_id → Consultant
                Tables\Filters\SelectFilter::make('conseiller_id')
                    ->label('Conseiller')
                    ->options(
                        fn() => Consultant::orderBy('nom')
                            ->get()
                            ->mapWithKeys(fn(Consultant $c) => [
                                $c->id => trim("{$c->prenom} {$c->nom}"),
                            ])
                            ->toArray()
                    )
                    ->searchable(),

                Tables\Filters\SelectFilter::make('departement')
                    ->options(fn() => Partenaire::distinct()->pluck('departement', 'departement')->filter()->sort())
                    ->label('Département')->searchable(),

                Tables\Filters\Filter::make('rdv_90_jours')
                    ->label('⚠️ RDV > 90 jours')
                    ->query(
                        fn (Builder $query): Builder => $query
                            ->where('statut', OrganizationStatus::RdvEnCours->value)
                            ->where('date_modification_statut', '<', now()->subDays(90))
                    )
                    ->toggle(),

                Tables\Filters\Filter::make('convention_active')
                    ->label('Conventions signées')
                    ->query(
                        fn (Builder $query): Builder => $query->whereIn('statut', [
                            OrganizationStatus::SigneAccordCadre->value,
                            OrganizationStatus::ConventionEngagement->value,
                        ])
                    )
                    ->toggle(),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('changer_statut')
                    ->label('Statut')->icon('heroicon-o-arrow-path')->color('gray')
                    ->form([
                        Forms\Components\Select::make('statut')
                            ->label('Nouveau statut')
                            ->options(OrganizationStatus::class)
                            ->required()->native(false),
                        Forms\Components\Textarea::make('commentaire')
                            ->label('Commentaire (optionnel)')->rows(2),
                    ])
                    ->action(fn(Partenaire $record, array $data) => $record->changerStatut(
                        OrganizationStatus::from($data['statut'])
                    ))
                    ->modalHeading('Changer le statut du partenaire')
                    ->modalWidth('md'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('assigner_conseiller')
                        ->label('Assigner un conseiller')->icon('heroicon-o-user-plus')
                        ->form([
                            Forms\Components\Select::make('conseiller_id')
                                ->label('Conseiller')
                                ->options(
                                    fn() => Consultant::orderBy('nom')
                                        ->get()
                                        ->mapWithKeys(fn(Consultant $c) => [
                                            $c->id => trim("{$c->prenom} {$c->nom}"),
                                        ])
                                        ->toArray()
                                )
                                ->required(),
                        ])
                        ->action(fn($records, array $data) => $records->each->update($data))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('changer_statut_bulk')
                        ->label('Changer le statut')->icon('heroicon-o-arrow-path')
                        ->form([
                            Forms\Components\Select::make('statut')
                                ->options(OrganizationStatus::class)->required(),
                        ])
                        ->action(fn($records, array $data) => $records->each->update(['statut' => $data['statut']]))
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Aucun partenaire')
            ->emptyStateDescription('Créez votre premier partenaire ou importez un fichier Excel.');
    }

    // ─────────────────────────────────────────────────────────────────
    // INFOLIST
    // ─────────────────────────────────────────────────────────────────
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([

            // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
            // SECTION 1 : IDENTIFICATION
            // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
            Infolists\Components\Section::make(' Identification')
                ->icon('heroicon-o-identification')
                ->schema([
                    Infolists\Components\Grid::make(3)->schema([
                        Infolists\Components\TextEntry::make('entite.nom')
                            ->label('Entité')
                            ->placeholder('—')
                            ->icon('heroicon-o-building-storefront')
                            ->color('info')
                            ->badge(),
                        Infolists\Components\TextEntry::make('nom')
                            ->label('Nom légal')
                            ->weight('bold')
                            ->size('lg')
                            ->copyable(),

                        Infolists\Components\TextEntry::make('nom_retenu')
                            ->label('Nom retenu')
                            ->placeholder('—')
                            ->copyable()
                            ->weight('semibold')
                            ->color('primary'),

                        Infolists\Components\TextEntry::make('siret')
                            ->label('SIRET')
                            ->copyable()
                            ->placeholder('—')
                            ->extraAttributes(['style' => 'font-family: monospace;']),

                        Infolists\Components\TextEntry::make('type')
                            ->label('Type')
                            ->badge()
                            ->formatStateUsing(
                                fn($state) => $state instanceof OrganizationType
                                    ? $state->value
                                    : OrganizationType::tryFrom((string) $state)?->value ?? $state
                            )
                            ->color(fn($state) => match ($state instanceof OrganizationType ? $state : OrganizationType::tryFrom((string) $state)) {
                                OrganizationType::CSE               => 'primary',
                                OrganizationType::Syndicat          => 'warning',
                                OrganizationType::EntrepriseDirecte => 'info',
                                OrganizationType::Association       => 'success',
                                default                             => 'gray',
                            }),

                        Infolists\Components\TextEntry::make('statut')
                            ->label('Statut')
                            ->badge()
                            ->formatStateUsing(
                                fn($state) => $state instanceof OrganizationStatus
                                    ? $state->label()
                                    : OrganizationStatus::tryFrom((string) $state)?->label() ?? $state
                            )
                            ->color(fn($state) => match ($state instanceof OrganizationStatus ? $state : OrganizationStatus::tryFrom((string) $state)) {
                                OrganizationStatus::AProspecter          => 'gray',
                                OrganizationStatus::EnCoursProspection   => 'blue',
                                OrganizationStatus::RdvEnCours           => 'warning',
                                OrganizationStatus::SigneAccordCadre,
                                OrganizationStatus::ConventionEngagement => 'success',
                                OrganizationStatus::Refus                => 'danger',
                                default                                  => 'gray',
                            }),

                        Infolists\Components\TextEntry::make('nomenclature_interne')
                            ->label('Nomenclature interne')
                            ->placeholder('—')
                            ->formatStateUsing(fn($state) => match ($state) {
                                'CSE_PME'       => 'CSE PME (< 50 salariés)',
                                'CSE_ETI'       => 'CSE ETI (50–299 salariés)',
                                'CSE_GE'        => 'CSE Grande entreprise (300+)',
                                'SYND_BRANCHE'  => 'Syndicat de branche',
                                'SYND_INTERPRO' => 'Syndicat interprofessionnel',
                                'ENT_DIRECTE'   => 'Entreprise directe',
                                'ASSOC'         => 'Association',
                                default         => $state ?? '—',
                            }),
                    ]),
                ]),

            // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
            // SECTION 2 : INFORMATIONS GÉNÉRALES
            // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
            Infolists\Components\Section::make(' Informations générales')
                ->icon('heroicon-o-building-office-2')
                ->schema([
                    Infolists\Components\Grid::make(3)->schema([
                        Infolists\Components\TextEntry::make('secteur_activite')
                            ->label("Secteur d'activité")
                            ->placeholder('—')
                            ->icon('heroicon-o-briefcase'),

                        Infolists\Components\TextEntry::make('nb_salaries')
                            ->label('Nombre de salariés')
                            ->placeholder('—')
                            ->numeric()
                            ->suffix(' salariés')
                            ->icon('heroicon-o-users'),

                        Infolists\Components\TextEntry::make('chiffre_affaires')
                            ->label("Chiffre d'affaires")
                            ->placeholder('—')
                            ->money('EUR')
                            ->icon('heroicon-o-currency-euro'),

                        Infolists\Components\TextEntry::make('entrepriseMere.nom')
                            ->label('Entreprise mère')
                            ->placeholder('—')
                            ->url(
                                fn($record) => $record->entrepriseMere ?
                                    PartenaireResource::getUrl('view', ['record' => $record->entrepriseMere]) : null
                            )
                            ->icon('heroicon-o-arrow-up-circle'),

                        Infolists\Components\TextEntry::make('filiales_count')
                            ->label('Filiales')
                            ->placeholder('0')
                            ->state(fn($record) => $record->filiales()->count())
                            ->badge()
                            ->color('info')
                            ->icon('heroicon-o-squares-2x2'),
                    ]),
                ]),

            // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
            // SECTION 3 : PIPELINE & SUIVI
            // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
            Infolists\Components\Section::make(' Pipeline & Suivi')
                ->icon('heroicon-o-chart-bar')
                ->schema([
                    Infolists\Components\Grid::make(3)->schema([
                        Infolists\Components\TextEntry::make('conseiller.nom')
                            ->label('Conseiller assigné')
                            ->getStateUsing(
                                fn($record) => $record->conseiller
                                    ? trim("{$record->conseiller->prenom} {$record->conseiller->nom}")
                                    : '—'
                            )
                            ->icon('heroicon-o-user')
                            ->badge()
                            ->color('primary'),

                        Infolists\Components\TextEntry::make('commercial.nom')
                            ->label('Commercial')
                            ->getStateUsing(
                                fn($record) => $record->commercial
                                    ? trim("{$record->commercial->prenom} {$record->commercial->nom}")
                                    : '—'
                            )
                            ->placeholder('—')
                            ->icon('heroicon-o-user-group'),

                        Infolists\Components\TextEntry::make('entite.nom')
                            ->label('Entité commerciale')
                            ->placeholder('—')
                            ->icon('heroicon-o-building-storefront'),

                        Infolists\Components\TextEntry::make('date_signature')
                            ->label('Date de signature')
                            ->date('d/m/Y')
                            ->placeholder('—')
                            ->icon('heroicon-o-calendar')
                            ->color(fn($state) => $state ? 'success' : 'gray'),

                        Infolists\Components\TextEntry::make('date_convention')
                            ->label('Date de convention')
                            ->date('d/m/Y')
                            ->placeholder('—')
                            ->icon('heroicon-o-calendar-days')
                            ->color(fn($state) => $state ? 'success' : 'gray'),

                        Infolists\Components\TextEntry::make('date_modification_statut')
                            ->label('Statut modifié le')
                            ->dateTime('d/m/Y à H:i')
                            ->placeholder('—')
                            ->icon('heroicon-o-clock')
                            ->since(),

                        Infolists\Components\TextEntry::make('origine_contact')
                            ->label('Origine du contact')
                            ->placeholder('—')
                            ->badge()
                            ->color('gray')
                            ->icon('heroicon-o-arrow-trending-up'),

                        Infolists\Components\TextEntry::make('parrain_marraine_texte')
                            ->label('Parrain / Marraine')
                            ->placeholder('—')
                            ->icon('heroicon-o-user-plus'),

                        Infolists\Components\IconEntry::make('parrainage_entreprise')
                            ->label('Parrainage entreprise')
                            ->boolean()
                            ->trueIcon('heroicon-o-check-circle')
                            ->falseIcon('heroicon-o-x-circle')
                            ->trueColor('success')
                            ->falseColor('gray'),

                        Infolists\Components\TextEntry::make('syndicat_majoritaire')
                            ->label('Syndicat majoritaire')
                            ->placeholder('—')
                            ->badge()
                            ->color('warning'),

                        Infolists\Components\TextEntry::make('possibilite_permanence')
                            ->label('Possibilité de permanence')
                            ->placeholder('—')
                            ->columnSpan(1),

                        Infolists\Components\TextEntry::make('replicable')
                            ->label('Réplicable')
                            ->placeholder('—')
                            ->columnSpan(1),
                    ]),
                ]),

            // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
            // SECTION 4 : COORDONNÉES
            // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
            Infolists\Components\Section::make(' Coordonnées')
                ->icon('heroicon-o-map-pin')
                ->schema([
                    Infolists\Components\Grid::make(3)->schema([
                        Infolists\Components\TextEntry::make('adresse')
                            ->label('Adresse')
                            ->placeholder('—')
                            ->columnSpan(2)
                            ->icon('heroicon-o-home'),

                        Infolists\Components\TextEntry::make('ville')
                            ->label('Ville')
                            ->placeholder('—')
                            ->icon('heroicon-o-building-office'),

                        Infolists\Components\TextEntry::make('code_postal')
                            ->label('Code postal')
                            ->placeholder('—')
                            ->icon('heroicon-o-map-pin'),

                        Infolists\Components\TextEntry::make('departement')
                            ->label('Département')
                            ->placeholder('—')
                            ->badge()
                            ->color('gray'),

                        Infolists\Components\TextEntry::make('telephone')
                            ->label('Téléphone')
                            ->placeholder('—')
                            ->copyable()
                            ->icon('heroicon-o-phone')
                            ->url(fn($state) => $state ? 'tel:' . $state : null),

                        Infolists\Components\TextEntry::make('email')
                            ->label('Email')
                            ->placeholder('—')
                            ->copyable()
                            ->icon('heroicon-o-envelope')
                            ->url(fn($state) => $state ? 'mailto:' . $state : null),
                    ]),
                ]),

            // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
            // SECTION 5 : DIRIGEANT
            // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
            Infolists\Components\Section::make(' Dirigeant')
                ->icon('heroicon-o-user')
                ->collapsible()
                ->collapsed(fn(Partenaire $record) => !$record->dirigeant_nom && !$record->dirigeant_email)
                ->schema([
                    Infolists\Components\Grid::make(3)->schema([
                        Infolists\Components\TextEntry::make('dirigeant_nom')
                            ->label('Nom')
                            ->placeholder('—')
                            ->weight('semibold'),

                        Infolists\Components\TextEntry::make('dirigeant_prenom')
                            ->label('Prénom')
                            ->placeholder('—'),

                        Infolists\Components\TextEntry::make('dirigeant_fonction')
                            ->label('Fonction')
                            ->placeholder('—')
                            ->badge()
                            ->color('info'),

                        Infolists\Components\TextEntry::make('dirigeant_telephone')
                            ->label('Téléphone')
                            ->placeholder('—')
                            ->copyable()
                            ->icon('heroicon-o-phone')
                            ->url(fn($state) => $state ? 'tel:' . $state : null),

                        Infolists\Components\TextEntry::make('dirigeant_email')
                            ->label('Email')
                            ->placeholder('—')
                            ->copyable()
                            ->icon('heroicon-o-envelope')
                            ->url(fn($state) => $state ? 'mailto:' . $state : null),
                    ]),
                ]),

            // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
            // SECTION 6 : CONTACTS CSE
            // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
            Infolists\Components\Section::make(' Contacts CSE')
                ->icon('heroicon-o-user-group')
                ->collapsible()
                ->collapsed()
                ->visible(fn(Partenaire $record) => $record->type === OrganizationType::CSE)
                ->schema([
                    // Secrétaire
                    Infolists\Components\Grid::make(2)->schema([
                        Infolists\Components\Fieldset::make('Secrétaire')
                            ->schema([
                                Infolists\Components\Grid::make(2)->schema([
                                    Infolists\Components\TextEntry::make('cse_secretaire_nom')
                                        ->label('Nom')
                                        ->placeholder('—')
                                        ->weight('semibold'),
                                    Infolists\Components\TextEntry::make('cse_secretaire_prenom')
                                        ->label('Prénom')
                                        ->placeholder('—'),
                                    Infolists\Components\TextEntry::make('cse_secretaire_tel_direct')
                                        ->label('Tél. direct')
                                        ->placeholder('—')
                                        ->copyable()
                                        ->icon('heroicon-o-phone'),
                                    Infolists\Components\TextEntry::make('cse_secretaire_tel_perso')
                                        ->label('Tél. perso')
                                        ->placeholder('—')
                                        ->copyable()
                                        ->icon('heroicon-o-device-phone-mobile'),
                                    Infolists\Components\TextEntry::make('cse_secretaire_email_pro')
                                        ->label('Email pro')
                                        ->placeholder('—')
                                        ->copyable()
                                        ->icon('heroicon-o-envelope'),
                                    Infolists\Components\TextEntry::make('cse_secretaire_email_perso')
                                        ->label('Email perso')
                                        ->placeholder('—')
                                        ->copyable()
                                        ->icon('heroicon-o-at-symbol'),
                                ]),
                            ]),

                        // Trésorier
                        Infolists\Components\Fieldset::make('Trésorier')
                            ->schema([
                                Infolists\Components\Grid::make(2)->schema([
                                    Infolists\Components\TextEntry::make('cse_tresorier_nom')
                                        ->label('Nom')
                                        ->placeholder('—')
                                        ->weight('semibold'),
                                    Infolists\Components\TextEntry::make('cse_tresorier_prenom')
                                        ->label('Prénom')
                                        ->placeholder('—'),
                                    Infolists\Components\TextEntry::make('cse_tresorier_tel_direct')
                                        ->label('Tél. direct')
                                        ->placeholder('—')
                                        ->copyable()
                                        ->icon('heroicon-o-phone'),
                                    Infolists\Components\TextEntry::make('cse_tresorier_tel_perso')
                                        ->label('Tél. perso')
                                        ->placeholder('—')
                                        ->copyable()
                                        ->icon('heroicon-o-device-phone-mobile'),
                                    Infolists\Components\TextEntry::make('cse_tresorier_email_pro')
                                        ->label('Email pro')
                                        ->placeholder('—')
                                        ->copyable()
                                        ->icon('heroicon-o-envelope'),
                                    Infolists\Components\TextEntry::make('cse_tresorier_email_perso')
                                        ->label('Email perso')
                                        ->placeholder('—')
                                        ->copyable()
                                        ->icon('heroicon-o-at-symbol'),
                                ]),
                            ]),
                    ]),

                    // Informations complémentaires CSE
                    Infolists\Components\Grid::make(3)->schema([
                        Infolists\Components\TextEntry::make('cse_nb_elus')
                            ->label("Nombre d'élus")
                            ->placeholder('—')
                            ->numeric()
                            ->suffix(' élus')
                            ->icon('heroicon-o-user-group'),

                        Infolists\Components\TextEntry::make('cse_date_fin_mandat')
                            ->label('Fin de mandat')
                            ->date('d/m/Y')
                            ->placeholder('—')
                            ->icon('heroicon-o-calendar')
                            ->color(fn($state) => $state && $state->isPast() ? 'danger' : 'success'),

                        Infolists\Components\IconEntry::make('cse_existence_juridique')
                            ->label('Existence juridique')
                            ->boolean()
                            ->trueIcon('heroicon-o-check-circle')
                            ->falseIcon('heroicon-o-x-circle')
                            ->trueColor('success')
                            ->falseColor('gray'),

                        Infolists\Components\TextEntry::make('cse_notes')
                            ->label('Notes CSE')
                            ->placeholder('—')
                            ->columnSpanFull()
                            ->prose()
                            ->html(),
                    ]),
                ]),

            // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
            // SECTION 7 : INFORMATIONS SYNDICALES
            // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
            Infolists\Components\Section::make(' Informations syndicales')
                ->icon('heroicon-o-users')
                ->collapsible()
                ->collapsed()
                ->visible(fn(Partenaire $record) => $record->type === OrganizationType::Syndicat)
                ->schema([
                    Infolists\Components\Grid::make(3)->schema([
                        Infolists\Components\TextEntry::make('syndicat_appartenance')
                            ->label('Appartenance')
                            ->placeholder('—')
                            ->badge()
                            ->color('warning'),

                        Infolists\Components\TextEntry::make('syndicat_nom_organisation')
                            ->label("Nom de l'organisation")
                            ->placeholder('—')
                            ->weight('semibold'),

                        Infolists\Components\TextEntry::make('syndicat_responsable_nom')
                            ->label('Responsable - Nom')
                            ->placeholder('—'),

                        Infolists\Components\TextEntry::make('syndicat_responsable_prenom')
                            ->label('Responsable - Prénom')
                            ->placeholder('—'),

                        Infolists\Components\TextEntry::make('syndicat_responsable_fonction')
                            ->label('Fonction')
                            ->placeholder('—')
                            ->badge()
                            ->color('info'),

                        Infolists\Components\TextEntry::make('syndicat_tel_direct')
                            ->label('Tél. direct')
                            ->placeholder('—')
                            ->copyable()
                            ->icon('heroicon-o-phone'),

                        Infolists\Components\TextEntry::make('syndicat_tel_perso')
                            ->label('Tél. perso')
                            ->placeholder('—')
                            ->copyable()
                            ->icon('heroicon-o-device-phone-mobile'),

                        Infolists\Components\TextEntry::make('syndicat_email_pro')
                            ->label('Email pro')
                            ->placeholder('—')
                            ->copyable()
                            ->icon('heroicon-o-envelope'),

                        Infolists\Components\TextEntry::make('syndicat_email_perso')
                            ->label('Email perso')
                            ->placeholder('—')
                            ->copyable()
                            ->icon('heroicon-o-at-symbol'),

                        Infolists\Components\TextEntry::make('syndicat_perimetre')
                            ->label('Périmètre')
                            ->placeholder('—')
                            ->columnSpanFull()
                            ->prose(),

                        Infolists\Components\TextEntry::make('syndicat_notes')
                            ->label('Notes syndicat')
                            ->placeholder('—')
                            ->columnSpanFull()
                            ->prose()
                            ->html(),
                    ]),
                ]),

            // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
            // SECTION 8 : NOTES
            // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
            Infolists\Components\Section::make('Notes')
                ->icon('heroicon-o-pencil-square')
                ->schema([
                    Infolists\Components\Grid::make(2)->schema([
                        Infolists\Components\TextEntry::make('commentaires')
                            ->label('Commentaires')
                            ->placeholder('—')
                            ->columnSpanFull()
                            ->prose()
                            ->html()
                            ->size('sm'),

                        Infolists\Components\TextEntry::make('notes')
                            ->label('Notes internes')
                            ->placeholder('—')
                            ->columnSpanFull()
                            ->prose()
                            ->html()
                            ->size('sm')
                            ->color('gray'),
                    ]),
                ]),

            // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
            // SECTION 9 : MÉTADONNÉES
            // ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
            Infolists\Components\Section::make('Métadonnées')
                ->icon('heroicon-o-cog-6-tooth')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Infolists\Components\Grid::make(3)->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Créé le')
                            ->dateTime('d/m/Y à H:i')
                            ->icon('heroicon-o-calendar'),

                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Modifié le')
                            ->dateTime('d/m/Y à H:i')
                            ->icon('heroicon-o-clock'),

                        Infolists\Components\TextEntry::make('deleted_at')
                            ->label('Supprimé le')
                            ->dateTime('d/m/Y à H:i')
                            ->placeholder('—')
                            ->icon('heroicon-o-trash')
                            ->color('danger')
                            ->visible(fn($record) => $record->trashed()),
                    ]),
                ]),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ContactsRelationManager::class,
            RelationManagers\AppelsRelationManager::class,
            RelationManagers\RendezVousRelationManager::class,
            RelationManagers\DocumentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPartenaires::route('/'),
            'create' => Pages\CreatePartenaire::route('/create'),
            'edit'   => Pages\EditPartenaire::route('/{record}/edit'),
            'view'   => Pages\ViewPartenaire::route('/{record}'),
        ];
    }
}
