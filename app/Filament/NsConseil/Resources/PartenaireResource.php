<?php

namespace App\Filament\NsConseil\Resources;

use App\Enums\OrganizationStatus;
use App\Enums\OrganizationType;
use App\Filament\NsConseil\Resources\PartenaireResource\Pages;
use App\Filament\NsConseil\Resources\PartenaireResource\RelationManagers;
use App\Models\Partenaire;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PartenaireResource extends Resource
{
    protected static ?string $model          = Partenaire::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
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
                        ->required()->searchable(),
                    Forms\Components\TextInput::make('departement')
                        ->label('Département')->maxLength(3)->required()->placeholder('ex: 75'),
                    Forms\Components\TextInput::make('code_postal')
                        ->label('Code postal')->maxLength(5),
                    Forms\Components\TextInput::make('secteur_activite')
                        ->label("Secteur d'activité")->required(),
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
                    Forms\Components\Select::make('commercial_id')
                        ->label('Commercial assigné')
                        ->relationship(
                            'commercial',
                            'nom',
                            fn(Builder $query) => $query->whereIn('role_cache', ['commercial', 'team_leader', 'administrateur'])
                        )
                        ->getOptionLabelFromRecordUsing(fn(User $r) => "{$r->prenom} {$r->nom}")
                        ->searchable()->preload()->required(),
                    Forms\Components\DatePicker::make('date_convention')
                        ->label('Date de convention')->displayFormat('d/m/Y'),
                    Forms\Components\Select::make('origine_contact')
                        ->label('Origine du contact')
                        ->options(['Salon' => 'Salon', 'Démarchage' => 'Démarchage', 'Parrainage' => 'Parrainage', 'Réseau' => 'Réseau', 'Autre' => 'Autre']),
                    Forms\Components\TextInput::make('parrain_marraine')->label('Parrain / Marraine'),
                    Forms\Components\TextInput::make('nombre_ventes_liees')
                        ->label('Ventes liées')->numeric()->default(0)->minValue(0),
                ])->columns(3),

            Forms\Components\Section::make('Coordonnées')
                ->icon('heroicon-o-map-pin')
                ->schema([
                    Forms\Components\TextInput::make('telephone')->label('Téléphone standard')->tel(),
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
                    Forms\Components\Toggle::make('cse_existence_juridique')->label('Existence juridique (personnalité morale)'),
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
                    Forms\Components\Textarea::make('notes')->label('Notes')->rows(4)->columnSpanFull(),
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

                Tables\Columns\TextColumn::make('siret')
                    ->label('SIRET')->searchable()->copyable()->toggleable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    // ✅ ->value sur l'enum, pas ->label()
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
                    ->label('Statut')
                    ->badge()
                    // ✅ ->value sur l'enum, pas ->label()
                    ->formatStateUsing(
                        fn($state) => $state instanceof OrganizationStatus
                            ? $state->value
                            : OrganizationStatus::tryFrom((string) $state)?->value ?? $state
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

                Tables\Columns\TextColumn::make('commercial.nom')
                    ->label('Commercial')->sortable()
                    ->getStateUsing(fn($record) => $record->commercial
                        ? "{$record->commercial->prenom} {$record->commercial->nom}" : '—'),

                Tables\Columns\TextColumn::make('nombre_ventes_liees')
                    ->label('Ventes')->sortable()->alignCenter()
                    ->badge()
                    ->color(fn($state) => $state > 0 ? 'success' : 'gray'),

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
                Tables\Filters\SelectFilter::make('commercial_id')
                    ->relationship('commercial', 'nom')
                    ->label('Commercial')
                    ->getOptionLabelFromRecordUsing(fn(User $r) => "{$r->prenom} {$r->nom}")
                    ->searchable()->preload(),
                Tables\Filters\SelectFilter::make('departement')
                    ->options(fn() => Partenaire::distinct()->pluck('departement', 'departement')->filter()->sort())
                    ->label('Département')->searchable(),
                Tables\Filters\Filter::make('rdv_90_jours')
                    ->label('⚠️ RDV > 90 jours')
                    ->query(
                        fn($query) => $query
                            ->where('statut', OrganizationStatus::RdvEnCours->value)
                            ->where('date_modification_statut', '<', now()->subDays(90))
                    )->toggle(),
                Tables\Filters\Filter::make('convention_active')
                    ->label('Conventions signées')
                    ->query(fn($q) => $q->where('statut', OrganizationStatus::ConventionEngagement->value))
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
                    ->action(fn(Partenaire $record, array $data) => $record->update(['statut' => $data['statut']]))
                    ->modalHeading('Changer le statut du partenaire')
                    ->modalWidth('md'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('assigner_commercial')
                        ->label('Assigner un commercial')->icon('heroicon-o-user-plus')
                        ->form([
                            Forms\Components\Select::make('commercial_id')
                                ->label('Commercial')
                                ->options(
                                    fn() => User::whereIn('role_cache', ['commercial', 'team_leader'])
                                        ->orderBy('nom')
                                        ->get()
                                        ->mapWithKeys(fn(User $u) => [$u->id => "{$u->prenom} {$u->nom}"])
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
            ->emptyStateDescription('Créez votre premier partenaire ou importez un fichier CSV.');
    }

    // ─────────────────────────────────────────────────────────────────
    // INFOLIST
    // ─────────────────────────────────────────────────────────────────
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([

            Infolists\Components\Section::make('Identification')->schema([
                Infolists\Components\TextEntry::make('nom')->label('Nom légal')->weight('bold'),
                Infolists\Components\TextEntry::make('siret')->label('SIRET')->copyable(),
                Infolists\Components\TextEntry::make('type')->label('Type')->badge()
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
                Infolists\Components\TextEntry::make('statut')->label('Statut')->badge()
                    ->formatStateUsing(
                        fn($state) => $state instanceof OrganizationStatus
                            ? $state->value
                            : OrganizationStatus::tryFrom((string) $state)?->value ?? $state
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
                Infolists\Components\TextEntry::make('nomenclature_interne')->label('Nomenclature'),
                Infolists\Components\TextEntry::make('secteur_activite')->label('Secteur'),
                Infolists\Components\TextEntry::make('departement')->label('Département'),
                Infolists\Components\TextEntry::make('nb_salaries')->label('Salariés'),
                Infolists\Components\TextEntry::make('chiffre_affaires')->label("CA (€)")->money('EUR'),
            ])->columns(3),

            Infolists\Components\Section::make('Pipeline')->schema([
                Infolists\Components\TextEntry::make('commercial.nom')->label('Commercial')
                    ->getStateUsing(fn($record) => $record->commercial
                        ? "{$record->commercial->prenom} {$record->commercial->nom}" : '—'),
                Infolists\Components\TextEntry::make('date_convention')->label('Date convention')->date('d/m/Y'),
                Infolists\Components\TextEntry::make('origine_contact')->label('Origine'),
                Infolists\Components\TextEntry::make('parrain_marraine')->label('Parrain / Marraine'),
                Infolists\Components\TextEntry::make('nombre_ventes_liees')->label('Ventes liées'),
                Infolists\Components\TextEntry::make('date_modification_statut')
                    ->label('Statut modifié le')->dateTime('d/m/Y à H:i'),
            ])->columns(3),

            Infolists\Components\Section::make('Coordonnées')->schema([
                Infolists\Components\TextEntry::make('telephone')->label('Téléphone')->copyable()->placeholder('—'),
                Infolists\Components\TextEntry::make('email')->label('Email')->copyable()->placeholder('—'),
                Infolists\Components\TextEntry::make('adresse')->label('Adresse')->placeholder('—'),
                Infolists\Components\TextEntry::make('ville')->label('Ville')->placeholder('—'),
                Infolists\Components\TextEntry::make('code_postal')->label('CP')->placeholder('—'),
            ])->columns(3),

            // ── Dirigeant ──────────────────────────────────────────────────
            Infolists\Components\Section::make('Dirigeant')
                ->icon('heroicon-o-user')
                ->collapsible()->collapsed()
                ->visible(fn(Partenaire $r) => $r->dirigeant_nom || $r->dirigeant_email || $r->dirigeant_telephone)
                ->schema([
                    Infolists\Components\Grid::make(3)->schema([
                        Infolists\Components\TextEntry::make('dirigeant_nom')->label('Nom')->placeholder('—'),
                        Infolists\Components\TextEntry::make('dirigeant_prenom')->label('Prénom')->placeholder('—'),
                        Infolists\Components\TextEntry::make('dirigeant_fonction')->label('Fonction')->placeholder('—'),
                        Infolists\Components\TextEntry::make('dirigeant_telephone')
                            ->label('Téléphone')->copyable()->placeholder('—')->icon('heroicon-m-phone'),
                        Infolists\Components\TextEntry::make('dirigeant_email')
                            ->label('Email')->copyable()->placeholder('—')->icon('heroicon-m-envelope'),
                    ]),
                ]),

            // ── CSE ────────────────────────────────────────────────────────
            Infolists\Components\Section::make('Contacts CSE')
                ->icon('heroicon-o-user-group')
                ->collapsible()->collapsed()
                ->visible(fn(Partenaire $r) => $r->type === OrganizationType::CSE)
                ->schema([
                    Infolists\Components\Grid::make(3)->schema([
                        // Secrétaire
                        Infolists\Components\TextEntry::make('cse_secretaire_nom')
                            ->label('Secrétaire — Nom')->placeholder('—'),
                        Infolists\Components\TextEntry::make('cse_secretaire_prenom')
                            ->label('Secrétaire — Prénom')->placeholder('—'),
                        Infolists\Components\TextEntry::make('cse_secretaire_tel_direct')
                            ->label('Tél. direct')->copyable()->placeholder('—')->icon('heroicon-m-phone'),
                        Infolists\Components\TextEntry::make('cse_secretaire_tel_perso')
                            ->label('Tél. perso')->copyable()->placeholder('—')->icon('heroicon-m-phone'),
                        Infolists\Components\TextEntry::make('cse_secretaire_email_pro')
                            ->label('Email pro')->copyable()->placeholder('—')->icon('heroicon-m-envelope'),
                        Infolists\Components\TextEntry::make('cse_secretaire_email_perso')
                            ->label('Email perso')->copyable()->placeholder('—')->icon('heroicon-m-envelope'),

                        // Trésorier
                        Infolists\Components\TextEntry::make('cse_tresorier_nom')
                            ->label('Trésorier — Nom')->placeholder('—'),
                        Infolists\Components\TextEntry::make('cse_tresorier_prenom')
                            ->label('Trésorier — Prénom')->placeholder('—'),
                        Infolists\Components\TextEntry::make('cse_tresorier_tel_direct')
                            ->label('Tél. direct')->copyable()->placeholder('—')->icon('heroicon-m-phone'),
                        Infolists\Components\TextEntry::make('cse_tresorier_tel_perso')
                            ->label('Tél. perso')->copyable()->placeholder('—')->icon('heroicon-m-phone'),
                        Infolists\Components\TextEntry::make('cse_tresorier_email_pro')
                            ->label('Email pro')->copyable()->placeholder('—')->icon('heroicon-m-envelope'),
                        Infolists\Components\TextEntry::make('cse_tresorier_email_perso')
                            ->label('Email perso')->copyable()->placeholder('—')->icon('heroicon-m-envelope'),

                        // Infos CSE
                        Infolists\Components\TextEntry::make('cse_nb_elus')
                            ->label("Nombre d'élus")->placeholder('—')->suffix(' élus'),
                        Infolists\Components\TextEntry::make('cse_date_fin_mandat')
                            ->label('Fin de mandat')->date('d/m/Y')->placeholder('—'),
                        Infolists\Components\IconEntry::make('cse_existence_juridique')
                            ->label('Existence juridique')->boolean(),
                        Infolists\Components\TextEntry::make('cse_notes')
                            ->label('Notes CSE')->placeholder('—')->columnSpanFull()->prose(),
                    ]),
                ]),

            // ── Syndicat ───────────────────────────────────────────────────
            Infolists\Components\Section::make('Informations syndicales')
                ->icon('heroicon-o-users')
                ->collapsible()->collapsed()
                ->visible(fn(Partenaire $r) => $r->type === OrganizationType::Syndicat)
                ->schema([
                    Infolists\Components\Grid::make(3)->schema([
                        Infolists\Components\TextEntry::make('syndicat_appartenance')
                            ->label('Appartenance')->placeholder('—'),
                        Infolists\Components\TextEntry::make('syndicat_nom_organisation')
                            ->label('Organisation')->placeholder('—'),
                        Infolists\Components\TextEntry::make('syndicat_responsable_nom')
                            ->label('Responsable — Nom')->placeholder('—'),
                        Infolists\Components\TextEntry::make('syndicat_responsable_prenom')
                            ->label('Responsable — Prénom')->placeholder('—'),
                        Infolists\Components\TextEntry::make('syndicat_responsable_fonction')
                            ->label('Fonction')->placeholder('—'),
                        Infolists\Components\TextEntry::make('syndicat_tel_direct')
                            ->label('Tél. direct')->copyable()->placeholder('—')->icon('heroicon-m-phone'),
                        Infolists\Components\TextEntry::make('syndicat_tel_perso')
                            ->label('Tél. perso')->copyable()->placeholder('—')->icon('heroicon-m-phone'),
                        Infolists\Components\TextEntry::make('syndicat_email_pro')
                            ->label('Email pro')->copyable()->placeholder('—')->icon('heroicon-m-envelope'),
                        Infolists\Components\TextEntry::make('syndicat_email_perso')
                            ->label('Email perso')->copyable()->placeholder('—')->icon('heroicon-m-envelope'),
                        Infolists\Components\TextEntry::make('syndicat_perimetre')
                            ->label('Périmètre')->placeholder('—')->columnSpanFull()->prose(),
                        Infolists\Components\TextEntry::make('syndicat_notes')
                            ->label('Notes syndicat')->placeholder('—')->columnSpanFull()->prose(),
                    ]),
                ]),

            Infolists\Components\Section::make('Notes commerciales')->schema([
                Infolists\Components\TextEntry::make('notes')->label('')->columnSpanFull()->html(),
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
