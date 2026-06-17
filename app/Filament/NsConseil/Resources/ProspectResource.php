<?php

namespace App\Filament\NsConseil\Resources;

use App\Enums\OrganizationType;
use App\Enums\ProspectStatut;
use App\Filament\NsConseil\Resources\ProspectResource\Pages;
use App\Filament\NsConseil\Resources\ProspectResource\RelationManagers;
use App\Models\Prospect;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Group;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Split;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProspectResource extends Resource
{
    protected static ?string $model = Prospect::class;

    protected static ?string $navigationIcon = 'heroicon-o-funnel';

    protected static ?string $navigationGroup = 'Pipeline';

    protected static ?string $navigationLabel = 'Prospects';

    protected static ?int $navigationSort = 2;

    public static function getNavigationBadge(): ?string
    {
        return (string) Prospect::whereNotIn('statut', [
            ProspectStatut::KO->value,
            ProspectStatut::QF->value,
        ])->count();
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
                    Forms\Components\TextInput::make('nom')
                        ->label("Nom de l'entité")
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(2),

                    Forms\Components\Select::make('type_pressenti')
                        ->label('Type pressenti')
                        ->options(OrganizationType::class)
                        ->live(),

                    Forms\Components\TextInput::make('siret')
                        ->label('SIRET')
                        ->maxLength(14)
                        ->minLength(14),

                    Forms\Components\TextInput::make('departement')
                        ->label('Département')
                        ->maxLength(3),

                    Forms\Components\TextInput::make('code_postal')
                        ->label('Code postal')
                        ->maxLength(5),

                    Forms\Components\TextInput::make('ville')->label('Ville'),

                    Forms\Components\TextInput::make('secteur_activite')
                        ->label("Secteur d'activité"),

                    Forms\Components\TextInput::make('nb_salaries')
                        ->label('Nombre de salariés')
                        ->numeric(),

                    Forms\Components\TextInput::make('chiffre_affaires')
                        ->label("Chiffre d'affaires (€)")
                        ->numeric()
                        ->prefix('€'),
                ])->columns(3),

            Forms\Components\Section::make('Contact')
                ->icon('heroicon-o-phone')
                ->schema([
                    Forms\Components\TextInput::make('telephone')
                        ->label('Téléphone principal')
                        ->tel()
                        ->required(),

                    Forms\Components\TextInput::make('telephone_alt')
                        ->label('Téléphone alt.')
                        ->tel(),

                    Forms\Components\TextInput::make('email')
                        ->label('Email')
                        ->email(),

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

            Forms\Components\Section::make('Pipeline et assignation')
                ->icon('heroicon-o-chart-bar')
                ->schema([
                    Forms\Components\Select::make('statut')
                        ->label('Statut')
                        ->options(ProspectStatut::class)
                        ->default(ProspectStatut::AC)
                        ->required()
                        ->native(false),

                    Forms\Components\Select::make('teleprospecteur_id')
                        ->label('Téléprospecteur')
                        ->relationship('teleprospecteur', 'nom')
                        ->getOptionLabelFromRecordUsing(fn (User $r) => "{$r->prenom} {$r->nom}")
                        ->searchable()
                        ->preload()
                        ->default(fn () => auth()->user()?->hasRoleCache('teleprospecteur') ? auth()->id() : null),

                    Forms\Components\Select::make('commercial_id')
                        ->label('Commercial (si QF)')
                        ->relationship('commercial', 'nom')
                        ->getOptionLabelFromRecordUsing(fn (User $r) => "{$r->prenom} {$r->nom}")
                        ->searchable()
                        ->preload()
                        ->nullable()
                        ->default(fn () => auth()->user()?->hasRoleCache('commercial') ? auth()->id() : null),

                    Forms\Components\DatePicker::make('date_premier_contact')
                        ->label('1er contact le')
                        ->displayFormat('d/m/Y')
                        ->default(now()),

                    Forms\Components\DateTimePicker::make('rappel_planifie_at')
                        ->label('Rappel planifié le')
                        ->seconds(false),
                ])->columns(3),

            Forms\Components\Section::make('Qualification')
                ->icon('heroicon-o-clipboard-document-check')
                ->schema([
                    Forms\Components\Textarea::make('description')
                        ->label('Notes de qualification')
                        ->rows(3)
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('motif_ko')
                        ->label('Motif KO')
                        ->rows(2)
                        ->visible(fn (Get $get) => $get('statut') === ProspectStatut::KO->value),
                ]),
            Forms\Components\Section::make('Dirigeant')
                ->icon('heroicon-o-user-circle')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Forms\Components\TextInput::make('dirigeant_nom')
                        ->label('Nom'),
                    Forms\Components\TextInput::make('dirigeant_prenom')
                        ->label('Prénom'),
                    Forms\Components\TextInput::make('dirigeant_fonction')
                        ->label('Fonction'),
                    Forms\Components\TextInput::make('dirigeant_telephone')
                        ->label('Téléphone')
                        ->tel(),
                    Forms\Components\TextInput::make('dirigeant_email')
                        ->label('Email')
                        ->email(),
                ])->columns(3),

            Forms\Components\Section::make('Informations CSE')
                ->icon('heroicon-o-building-office')
                ->collapsible()
                ->collapsed()
                ->visible(fn (Get $get) => $get('type_pressenti') === OrganizationType::CSE->value)
                ->schema([
                    Forms\Components\TextInput::make('cse_secretaire_nom')->label('Secrétaire — Nom'),
                    Forms\Components\TextInput::make('cse_secretaire_prenom')->label('Secrétaire — Prénom'),
                    Forms\Components\TextInput::make('cse_secretaire_tel_direct')->label('Tél. direct')->tel(),
                    Forms\Components\TextInput::make('cse_secretaire_tel_perso')->label('Tél. perso')->tel(),
                    Forms\Components\TextInput::make('cse_secretaire_email_pro')->label('Email pro')->email(),
                    Forms\Components\TextInput::make('cse_secretaire_email_perso')->label('Email perso')->email(),

                    Forms\Components\TextInput::make('cse_tresorier_nom')->label('Trésorier — Nom'),
                    Forms\Components\TextInput::make('cse_tresorier_prenom')->label('Trésorier — Prénom'),
                    Forms\Components\TextInput::make('cse_tresorier_tel_direct')->label('Tél. direct')->tel(),
                    Forms\Components\TextInput::make('cse_tresorier_tel_perso')->label('Tél. perso')->tel(),
                    Forms\Components\TextInput::make('cse_tresorier_email_pro')->label('Email pro')->email(),
                    Forms\Components\TextInput::make('cse_tresorier_email_perso')->label('Email perso')->email(),

                    Forms\Components\TextInput::make('cse_nb_elus')->label('Nombre d\'élus')->numeric(),
                    Forms\Components\DatePicker::make('cse_date_fin_mandat')
                        ->label('Fin de mandat')
                        ->displayFormat('d/m/Y'),
                    Forms\Components\Toggle::make('cse_existence_juridique')
                        ->label('Existence juridique propre'),
                    Forms\Components\Textarea::make('cse_notes')
                        ->label('Notes CSE')
                        ->rows(2)
                        ->columnSpanFull(),
                ])->columns(3),

            Forms\Components\Section::make('Informations Syndicat')
                ->icon('heroicon-o-user-group')
                ->collapsible()
                ->collapsed()
                ->visible(fn (Get $get) => $get('type_pressenti') === OrganizationType::Syndicat->value)
                ->schema([
                    Forms\Components\TextInput::make('syndicat_appartenance')->label('Appartenance syndicale'),
                    Forms\Components\TextInput::make('syndicat_nom_organisation')->label('Nom organisation'),
                    Forms\Components\TextInput::make('syndicat_responsable_nom')->label('Responsable — Nom'),
                    Forms\Components\TextInput::make('syndicat_responsable_prenom')->label('Responsable — Prénom'),
                    Forms\Components\TextInput::make('syndicat_responsable_fonction')->label('Fonction'),
                    Forms\Components\TextInput::make('syndicat_tel_direct')->label('Tél. direct')->tel(),
                    Forms\Components\TextInput::make('syndicat_tel_perso')->label('Tél. perso')->tel(),
                    Forms\Components\TextInput::make('syndicat_email_pro')->label('Email pro')->email(),
                    Forms\Components\TextInput::make('syndicat_email_perso')->label('Email perso')->email(),
                    Forms\Components\Textarea::make('syndicat_perimetre')
                        ->label('Périmètre')
                        ->rows(2)
                        ->columnSpanFull(),
                    Forms\Components\Textarea::make('syndicat_notes')
                        ->label('Notes')
                        ->rows(2)
                        ->columnSpanFull(),
                ])->columns(3),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // TABLE
    // ─────────────────────────────────────────────────────────────────
    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('nom')
                    ->label("Nom de l'entité")
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('statut')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(
                        fn ($state) => $state instanceof ProspectStatut
                            ? $state->label()
                            : ProspectStatut::tryFrom($state)?->label() ?? $state
                    )
                    ->color(
                        fn ($state) => $state instanceof ProspectStatut
                            ? $state->color()
                            : ProspectStatut::tryFrom($state)?->color() ?? 'gray'
                    ),

                Tables\Columns\TextColumn::make('teleprospecteur.nom')
                    ->label('Commercial')
                    ->icon('heroicon-m-user')
                    ->formatStateUsing(fn ($record) => $record->teleprospecteur
                        ? "{$record->teleprospecteur->prenom} {$record->teleprospecteur->nom}"
                        : '—')
                    ->searchable(query: fn (Builder $q, string $search) => $q->whereHas(
                        'teleprospecteur',
                        fn (Builder $q2) => $q2->where('nom', 'like', "%{$search}%")
                            ->orWhere('prenom', 'like', "%{$search}%")
                    ))
                    ->sortable(),

                Tables\Columns\TextColumn::make('departement')
                    ->label('Dép.')
                    ->alignCenter()
                    ->sortable(),

                Tables\Columns\TextColumn::make('ville')
                    ->label('Ville')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('telephone')
                    ->label('Téléphone')
                    ->copyable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('rappel_planifie_at')
                    ->label('Rappel le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('—')
                    ->color(fn ($state) => $state && $state instanceof Carbon && $state->isPast() ? 'danger' : null),

                Tables\Columns\IconColumn::make('qf_valide')
                    ->label('QF')
                    ->boolean()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('statut')
                    ->options(ProspectStatut::class)
                    ->label('Statut'),

                Tables\Filters\SelectFilter::make('type_pressenti')
                    ->options(OrganizationType::class)
                    ->label('Type'),

                Tables\Filters\SelectFilter::make('teleprospecteur_id')
                    ->relationship('teleprospecteur', 'nom')
                    ->label('Commercial'),

                Tables\Filters\SelectFilter::make('commercial_id')
                    ->relationship('commercial', 'nom')
                    ->label('Commercial (QF)'),

                Tables\Filters\Filter::make('a_relancer')
                    ->label('À relancer')
                    ->query(fn (Builder $q) => $q->whereIn('statut', [
                        ProspectStatut::AC->value,
                        ProspectStatut::STD_NR->value,
                        ProspectStatut::CSE_NR->value,
                    ]))
                    ->toggle(),

                Tables\Filters\Filter::make('rappels_en_retard')
                    ->label('Rappels en retard')
                    ->query(
                        fn (Builder $q) => $q
                            ->whereNotNull('rappel_planifie_at')
                            ->where('rappel_planifie_at', '<', now())
                            ->whereNotIn('statut', [
                                ProspectStatut::KO->value,
                                ProspectStatut::QF->value,
                            ])
                    )
                    ->toggle(),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('qualifier_qf')
                    ->label('Qualifier QF')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(
                        fn (Prospect $record) => ! in_array($record->statut, [ProspectStatut::KO, ProspectStatut::QF])
                    )
                    ->action(function (Prospect $record) {
                        $record->qualifier();
                        Notification::make()
                            ->title('Prospect qualifié QF')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),

                Tables\Actions\Action::make('convertir_partenaire')
                    ->label('→ Partenaire')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color('success')
                    ->visible(fn (Prospect $record) => $record->statut === ProspectStatut::QF)
                    ->action(function (Prospect $record) {
                        $record->convertirEnPartenaire();
                        Notification::make()
                            ->title('Converti en partenaire ✓')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Aucun prospect')
            ->emptyStateDescription('Créez votre premier prospect.');
    }

    // ─────────────────────────────────────────────────────────────────
    // INFOLIST COMPLET
    // ─────────────────────────────────────────────────────────────────
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([

            // ── Ligne 1 : Statut + KPIs engagement ──
            Split::make([
                Section::make()
                    ->schema([
                        Grid::make(2)->schema([  // Changé de 3 à 2 colonnes
                            TextEntry::make('statut')
                                ->label('Statut pipeline')
                                ->badge()
                                ->formatStateUsing(
                                    fn ($state) => $state instanceof ProspectStatut
                                        ? $state->label()
                                        : ProspectStatut::tryFrom($state)?->label() ?? $state
                                )
                                ->color(
                                    fn ($state) => $state instanceof ProspectStatut
                                        ? $state->color()
                                        : ProspectStatut::tryFrom($state)?->color() ?? 'gray'
                                )
                                ->size(TextEntry\TextEntrySize::Large),

                            TextEntry::make('taux_engagement')
                                ->label('Engagement')
                                ->state(fn (Prospect $r) => $r->taux_engagement),

                            TextEntry::make('statut_description')
                                ->label('Description statut')
                                ->state(fn (Prospect $r) => $r->statut_description)
                                ->columnSpan(2)  // Prend les 2 colonnes sur la ligne suivante
                                ->extraAttributes(['class' => 'mt-2']), // Petit espacement
                        ]),
                    ])
                    ->grow(true),

                Section::make()
                    ->schema([
                        Grid::make(2)->schema([
                            TextEntry::make('jours_depuis_premier_contact')
                                ->label('Jours depuis 1er contact')
                                ->state(
                                    fn (Prospect $r) => $r->jours_depuis_premier_contact
                                        ? $r->jours_depuis_premier_contact.' j'
                                        : '—'
                                )
                                ->color(fn (Prospect $r) => ($r->jours_depuis_premier_contact ?? 0) > 30 ? 'warning' : 'success'),

                            TextEntry::make('jours_avant_rappel')
                                ->label('Rappel dans')
                                ->state(fn (Prospect $r) => match (true) {
                                    $r->rappel_planifie_at === null => '—',
                                    $r->rappel_est_en_retard => 'En retard de '.abs($r->jours_avant_rappel).' j',
                                    default => $r->jours_avant_rappel.' j',
                                })
                                ->color(fn (Prospect $r) => $r->rappel_est_en_retard ? 'danger' : 'success'),
                        ]),
                    ])
                    ->grow(true),
            ])
                ->from('md'),

            // ── Section 2 : Identification entreprise ──
            Section::make('Identification')
                ->icon('heroicon-o-building-office-2')
                ->collapsible()
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('nom')
                            ->label("Nom de l'entité")
                            ->weight(FontWeight::Bold)
                            ->size(TextEntry\TextEntrySize::Large)
                            ->columnSpan(2),

                        TextEntry::make('type_pressenti_label')
                            ->label('Type pressenti')
                            ->state(fn (Prospect $r) => $r->type_pressenti_label)
                            ->badge()
                            ->color('info'),

                        TextEntry::make('siret')
                            ->label('SIRET')
                            ->copyable()
                            ->copyMessage('SIRET copié !')
                            ->placeholder('—'),

                        TextEntry::make('secteur_activite')
                            ->label("Secteur d'activité")
                            ->placeholder('—'),

                        TextEntry::make('departement')
                            ->label('Département')
                            ->placeholder('—'),

                        TextEntry::make('nb_salaries')
                            ->label('Nombre de salariés')
                            ->numeric()
                            ->placeholder('—')
                            ->suffix(' salariés'),

                        TextEntry::make('chiffre_affaires')
                            ->label("Chiffre d'affaires")
                            ->money('EUR')
                            ->placeholder('—'),

                        TextEntry::make('adresse_complete')
                            ->label('Adresse complète')
                            ->state(fn (Prospect $r) => $r->adresse_complete ?: '—')
                            ->copyable(),
                    ]),
                ]),

            // ── Section 3 : Contacts ──
            Section::make('Coordonnées & Interlocuteur')
                ->icon('heroicon-o-phone')
                ->collapsible()
                ->schema([
                    Grid::make(3)->schema([
                        // Coordonnées entité
                        Group::make([
                            TextEntry::make('telephone')
                                ->label('Téléphone principal')
                                ->copyable()
                                ->copyMessage('Numéro copié !')
                                ->placeholder('—')
                                ->icon('heroicon-m-phone'),

                            TextEntry::make('telephone_alt')
                                ->label('Téléphone alt.')
                                ->copyable()
                                ->placeholder('—')
                                ->icon('heroicon-m-phone'),

                            TextEntry::make('email')
                                ->label('Email')
                                ->copyable()
                                ->copyMessage('Email copié !')
                                ->placeholder('—')
                                ->icon('heroicon-m-envelope'),
                        ])->label('Entité'),

                        // Interlocuteur
                        Group::make([
                            TextEntry::make('interlocuteur_complet')
                                ->label('Interlocuteur')
                                ->state(fn (Prospect $r) => $r->interlocuteur_complet)
                                ->weight(FontWeight::SemiBold)
                                ->placeholder('—'),

                            TextEntry::make('interlocuteur_telephone')
                                ->label('Tél. interlocuteur')
                                ->copyable()
                                ->placeholder('—')
                                ->icon('heroicon-m-phone'),

                            TextEntry::make('interlocuteur_email')
                                ->label('Email interlocuteur')
                                ->copyable()
                                ->placeholder('—')
                                ->icon('heroicon-m-envelope'),
                        ])->label('Interlocuteur'),

                        // Localisation
                        Group::make([
                            TextEntry::make('localisation')
                                ->label('Localisation')
                                ->state(fn (Prospect $r) => $r->localisation ?: '—')
                                ->icon('heroicon-m-map-pin'),

                            TextEntry::make('ville')
                                ->label('Ville')
                                ->placeholder('—'),

                            TextEntry::make('code_postal')
                                ->label('Code postal')
                                ->placeholder('—'),
                        ])->label('Localisation'),
                    ]),
                ]),

            // ── Section 4 : Pipeline & Assignation ──
            Section::make('Pipeline & Assignation')
                ->icon('heroicon-o-chart-bar-square')
                ->collapsible()
                ->schema([
                    Grid::make(2)->schema([
                        Group::make([
                            TextEntry::make('teleprospecteur.nom')
                                ->label('Commercial')
                                ->formatStateUsing(
                                    fn ($record) => $record->teleprospecteur
                                        ? "{$record->teleprospecteur->prenom} {$record->teleprospecteur->nom}"
                                        : '—'
                                )
                                ->icon('heroicon-m-user')
                                ->placeholder('—'),

                            TextEntry::make('commercial.nom')
                                ->label('Commercial (validation QF)')
                                ->formatStateUsing(
                                    fn ($record) => $record->commercial
                                        ? "{$record->commercial->prenom} {$record->commercial->nom}"
                                        : '—'
                                )
                                ->icon('heroicon-m-briefcase')
                                ->placeholder('—'),
                        ])->label('Assignation'),

                        Group::make([
                            TextEntry::make('date_premier_contact')
                                ->label('Premier contact')
                                ->date('d/m/Y')
                                ->placeholder('Jamais contacté')
                                ->icon('heroicon-m-calendar'),

                            TextEntry::make('rappel_planifie_at')
                                ->label('Rappel planifié')
                                ->dateTime('d/m/Y à H:i')
                                ->placeholder('Aucun rappel planifié')
                                ->icon('heroicon-m-clock')
                                ->color(fn (Prospect $r) => $r->rappel_est_en_retard ? 'danger' : null),

                            TextEntry::make('dernier_contact')
                                ->label('Dernier contact')
                                ->state(fn (Prospect $r) => $r->dernier_contact ?? 'Jamais')
                                ->icon('heroicon-m-arrow-path'),

                            TextEntry::make('created_at')
                                ->label('Créé le')
                                ->dateTime('d/m/Y à H:i')
                                ->icon('heroicon-m-plus-circle'),
                        ])->label('Suivi'),
                    ]),
                ]),

            // ── Section 5 : Qualification QF ──
            Section::make('Validation QF')
                ->icon('heroicon-o-clipboard-document-check')
                ->collapsible()
                ->visible(fn (Prospect $r) => $r->statut === ProspectStatut::QF || $r->qf_valide)
                ->schema([
                    Grid::make(3)->schema([
                        IconEntry::make('qf_valide')
                            ->label('QF Validé')
                            ->boolean()
                            ->trueIcon('heroicon-o-check-badge')
                            ->falseIcon('heroicon-o-clock')
                            ->trueColor('success')
                            ->falseColor('warning'),

                        TextEntry::make('validePar.nom')
                            ->label('Validé par')
                            ->formatStateUsing(
                                fn ($record) => $record->validePar
                                    ? "{$record->validePar->prenom} {$record->validePar->nom}"
                                    : '—'
                            )
                            ->placeholder('—'),

                        TextEntry::make('qf_valide_at')
                            ->label('Validé le')
                            ->dateTime('d/m/Y à H:i')
                            ->placeholder('—'),
                    ]),
                ]),

            // ── Section 6 : Motif KO ──
            Section::make('Motif KO')
                ->icon('heroicon-o-x-circle')
                ->collapsible()
                ->visible(fn (Prospect $r) => $r->statut === ProspectStatut::KO)
                ->schema([
                    TextEntry::make('motif_ko')
                        ->label('')
                        ->columnSpanFull()
                        ->placeholder('Aucun motif enregistré')
                        ->prose(),
                ]),
            // ── Section : Dirigeant ──
            Section::make('Dirigeant')
                ->icon('heroicon-o-user-circle')
                ->collapsible()
                ->collapsed()
                ->visible(fn (Prospect $r) => $r->dirigeant_nom || $r->dirigeant_email || $r->dirigeant_telephone)
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('dirigeant_nom')->label('Nom')->placeholder('—'),
                        TextEntry::make('dirigeant_prenom')->label('Prénom')->placeholder('—'),
                        TextEntry::make('dirigeant_fonction')->label('Fonction')->placeholder('—'),
                        TextEntry::make('dirigeant_telephone')
                            ->label('Téléphone')
                            ->copyable()
                            ->placeholder('—')
                            ->icon('heroicon-m-phone'),
                        TextEntry::make('dirigeant_email')
                            ->label('Email')
                            ->copyable()
                            ->placeholder('—')
                            ->icon('heroicon-m-envelope'),
                    ]),
                ]),

            // ── Section : CSE ──
            Section::make('Informations CSE')
                ->icon('heroicon-o-building-office')
                ->collapsible()
                ->collapsed()
                ->visible(fn (Prospect $r) => $r->type_pressenti === OrganizationType::CSE->value)
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('cse_secretaire_nom')->label('Secrétaire — Nom')->placeholder('—'),
                        TextEntry::make('cse_secretaire_prenom')->label('Secrétaire — Prénom')->placeholder('—'),
                        TextEntry::make('cse_secretaire_tel_direct')
                            ->label('Tél. direct')->copyable()->placeholder('—')->icon('heroicon-m-phone'),
                        TextEntry::make('cse_secretaire_tel_perso')
                            ->label('Tél. perso')->copyable()->placeholder('—')->icon('heroicon-m-phone'),
                        TextEntry::make('cse_secretaire_email_pro')
                            ->label('Email pro')->copyable()->placeholder('—')->icon('heroicon-m-envelope'),
                        TextEntry::make('cse_secretaire_email_perso')
                            ->label('Email perso')->copyable()->placeholder('—')->icon('heroicon-m-envelope'),

                        TextEntry::make('cse_tresorier_nom')->label('Trésorier — Nom')->placeholder('—'),
                        TextEntry::make('cse_tresorier_prenom')->label('Trésorier — Prénom')->placeholder('—'),
                        TextEntry::make('cse_tresorier_tel_direct')
                            ->label('Tél. direct')->copyable()->placeholder('—')->icon('heroicon-m-phone'),
                        TextEntry::make('cse_tresorier_tel_perso')
                            ->label('Tél. perso')->copyable()->placeholder('—')->icon('heroicon-m-phone'),
                        TextEntry::make('cse_tresorier_email_pro')
                            ->label('Email pro')->copyable()->placeholder('—')->icon('heroicon-m-envelope'),
                        TextEntry::make('cse_tresorier_email_perso')
                            ->label('Email perso')->copyable()->placeholder('—')->icon('heroicon-m-envelope'),

                        TextEntry::make('cse_nb_elus')->label('Nombre d\'élus')->placeholder('—')->suffix(' élus'),
                        TextEntry::make('cse_date_fin_mandat')
                            ->label('Fin de mandat')->date('d/m/Y')->placeholder('—'),
                        IconEntry::make('cse_existence_juridique')
                            ->label('Existence juridique')
                            ->boolean(),
                        TextEntry::make('cse_notes')
                            ->label('Notes CSE')
                            ->placeholder('—')
                            ->columnSpanFull()
                            ->prose(),
                    ]),
                ]),

            // ── Section : Syndicat ──
            Section::make('Informations Syndicat')
                ->icon('heroicon-o-user-group')
                ->collapsible()
                ->collapsed()
                ->visible(fn (Prospect $r) => $r->type_pressenti === OrganizationType::Syndicat->value)
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('syndicat_appartenance')->label('Appartenance')->placeholder('—'),
                        TextEntry::make('syndicat_nom_organisation')->label('Organisation')->placeholder('—'),
                        TextEntry::make('syndicat_responsable_nom')->label('Responsable — Nom')->placeholder('—'),
                        TextEntry::make('syndicat_responsable_prenom')->label('Responsable — Prénom')->placeholder('—'),
                        TextEntry::make('syndicat_responsable_fonction')->label('Fonction')->placeholder('—'),
                        TextEntry::make('syndicat_tel_direct')
                            ->label('Tél. direct')->copyable()->placeholder('—')->icon('heroicon-m-phone'),
                        TextEntry::make('syndicat_tel_perso')
                            ->label('Tél. perso')->copyable()->placeholder('—')->icon('heroicon-m-phone'),
                        TextEntry::make('syndicat_email_pro')
                            ->label('Email pro')->copyable()->placeholder('—')->icon('heroicon-m-envelope'),
                        TextEntry::make('syndicat_email_perso')
                            ->label('Email perso')->copyable()->placeholder('—')->icon('heroicon-m-envelope'),
                        TextEntry::make('syndicat_perimetre')
                            ->label('Périmètre')->placeholder('—')->columnSpanFull()->prose(),
                        TextEntry::make('syndicat_notes')
                            ->label('Notes')->placeholder('—')->columnSpanFull()->prose(),
                    ]),
                ]),
            // ── Section 7 : Notes / Description ──
            Section::make('Notes & Historique')
                ->icon('heroicon-o-document-text')
                ->collapsible()
                ->schema([
                    TextEntry::make('description')
                        ->label('')
                        ->columnSpanFull()
                        ->placeholder('Aucune note')
                        ->prose()
                        ->html(),
                ]),

            // ── Section 8 : Métadonnées ──
            Section::make('Métadonnées')
                ->icon('heroicon-o-information-circle')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('id')
                            ->label('ID')
                            ->prefix('#'),

                        TextEntry::make('created_at')
                            ->label('Créé le')
                            ->dateTime('d/m/Y à H:i'),

                        TextEntry::make('updated_at')
                            ->label('Mis à jour le')
                            ->dateTime('d/m/Y à H:i'),

                        TextEntry::make('deleted_at')
                            ->label('Supprimé le')
                            ->dateTime('d/m/Y à H:i')
                            ->placeholder('—')
                            ->visible(fn (Prospect $r) => $r->trashed()),
                    ]),
                ]),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\AppelsRelationManager::class,
            RelationManagers\RendezVousRelationManager::class,
            RelationManagers\DocumentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProspects::route('/'),
            'create' => Pages\CreateProspect::route('/create'),
            'edit' => Pages\EditProspect::route('/{record}/edit'),
            'view' => Pages\ViewProspect::route('/{record}'),
        ];
    }
}
