<?php

namespace App\Filament\NsConseil\Resources;

use App\Filament\NsConseil\Concerns\HasRoleAccess;
use App\Filament\NsConseil\Resources\DossierFormationResource\Pages;
use App\Filament\NsConseil\Resources\DossierFormationResource\RelationManagers\HeuresRelationManager;
use App\Filament\NsConseil\Resources\DossierFormationResource\RelationManagers\PlanningRelationManager;
use App\Models\DossierFormation;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DossierFormationResource extends Resource
{
    use HasRoleAccess;

    protected static ?string $model = DossierFormation::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'Clients & Formations';

    protected static ?string $navigationLabel = 'Dossiers Formation';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return static::userHasAnyRole(['admin', 'superviseur', 'commercial']);
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) DossierFormation::count();
    }

    // ─────────────────────────────────────────────────────────────────
    // FORMULAIRE
    // ─────────────────────────────────────────────────────────────────
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informations générales')
                ->icon('heroicon-o-information-circle')
                ->schema([
                    Forms\Components\Select::make('personne_id')
                        ->label('Client')
                        ->relationship('personne', 'nom_tiers')
                        ->searchable(['nom_tiers', 'email', 'ref_client'])
                        ->preload()
                        ->required()
                        ->helperText('Sélectionnez le client concerné par cette formation'),

                    Forms\Components\TextInput::make('ref_client')
                        ->label('Référence client')
                        ->disabled()
                        ->dehydrated(false)
                        ->helperText('Référence du client associé'),

                    Forms\Components\TextInput::make('intitule_programme')
                        ->label('Intitulé du programme')
                        ->required()
                        ->maxLength(255)
                        ->placeholder('Ex: Formation Management, Certification Google...'),

                    Forms\Components\Select::make('entite_id')
                        ->label('Entité commerciale')
                        ->relationship('entite', 'nom')
                        ->searchable(['nom', 'code'])
                        ->preload()
                        ->nullable(),
                ])->columns(2),

            Forms\Components\Section::make('Montants financiers')
                ->icon('heroicon-o-currency-euro')
                ->schema([
                    Forms\Components\TextInput::make('montant_ht')
                        ->label('Montant HT (€)')
                        ->numeric()
                        ->prefix('€')
                        ->step(0.01)
                        ->nullable()
                        ->helperText('Montant hors taxe de la formation'),

                    Forms\Components\TextInput::make('montant_cpf')
                        ->label('Montant CPF (€)')
                        ->numeric()
                        ->prefix('€')
                        ->step(0.01)
                        ->nullable()
                        ->helperText('Montant financé par le CPF'),
                ])->columns(2),

            Forms\Components\Section::make('Suivi de la formation')
                ->icon('heroicon-o-clock')
                ->schema([
                    Forms\Components\DatePicker::make('date_vente')
                        ->label('Date de vente')
                        ->displayFormat('d/m/Y')
                        ->nullable(),

                    Forms\Components\Select::make('statut_formation')
                        ->label('Statut de la formation')
                        ->options([
                            'a_venir' => 'À venir',
                            'en_cours' => 'En cours',
                            'termine' => 'Terminé',
                            'valide' => 'Validé',
                            'annule' => 'Annulé',
                            'reporte' => 'Reporté',
                        ])
                        ->nullable(),

                    Forms\Components\TextInput::make('no_dossier_edof')
                        ->label('N° dossier EDOF')
                        ->maxLength(100)
                        ->nullable()
                        ->helperText('Identifiant dans le système EDOF'),

                    Forms\Components\Select::make('etat')
                        ->label('État du dossier')
                        ->options([
                            'brouillon' => 'Brouillon',
                            'en_cours' => 'En cours',
                            'soumis' => 'Soumis',
                            'approuve' => 'Approuvé',
                            'rejete' => 'Rejeté',
                            'cloture' => 'Clôturé',
                        ])
                        ->default('brouillon')
                        ->nullable(),
                ])->columns(2),

            Forms\Components\Section::make('Consultants associés')
                ->icon('heroicon-o-user-group')
                ->schema([
                    Forms\Components\Select::make('consultant_accueil_id')
                        ->label('Consultant accueil')
                        ->relationship('consultantAccueil', 'nom')
                        ->searchable(['nom', 'prenom'])
                        ->preload()
                        ->nullable()
                        ->helperText('Consultant qui a reçu le client'),

                    Forms\Components\Select::make('consultant_formateur_id')
                        ->label('Consultant formateur')
                        ->relationship('consultantFormateur', 'nom')
                        ->searchable(['nom', 'prenom'])
                        ->preload()
                        ->nullable()
                        ->helperText('Consultant qui assure la formation'),
                ])->columns(2),
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
                // 🔵 Informations principales
                Tables\Columns\TextColumn::make('personne.nom_tiers')
                    ->label('Client')
                    ->searchable(['personne.nom_tiers', 'personne.email'])
                    ->sortable()
                    ->weight('bold')
                    ->description(fn (DossierFormation $record) => $record->personne?->email),

                Tables\Columns\TextColumn::make('ref_client')
                    ->label('Réf. client')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),

                Tables\Columns\TextColumn::make('intitule_programme')
                    ->label('Programme')
                    ->searchable()
                    ->sortable()
                    ->limit(30)
                    ->tooltip(fn (DossierFormation $record) => $record->intitule_programme),

                // 🏢 Entité
                Tables\Columns\TextColumn::make('entite.nom')
                    ->label('Entité')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),

                // 💰 Montants
                Tables\Columns\TextColumn::make('montant_ht')
                    ->label('Montant HT')
                    ->money('EUR')
                    ->sortable()
                    ->alignRight()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('montant_cpf')
                    ->label('Montant CPF')
                    ->money('EUR')
                    ->sortable()
                    ->alignRight()
                    ->toggleable(),

                // 📅 Dates
                Tables\Columns\TextColumn::make('date_vente')
                    ->label('Date vente')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),

                // 📊 Statut
                Tables\Columns\TextColumn::make('statut_formation')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'a_venir' => 'À venir',
                        'en_cours' => 'En cours',
                        'termine' => 'Terminé',
                        'valide' => 'Validé',
                        'annule' => 'Annulé',
                        'reporte' => 'Reporté',
                        default => $state ?? '—',
                    })
                    ->color(fn ($state) => match ($state) {
                        'a_venir' => 'info',
                        'en_cours' => 'warning',
                        'termine' => 'success',
                        'valide' => 'primary',
                        'annule' => 'danger',
                        'reporte' => 'gray',
                        default => 'gray',
                    })
                    ->toggleable(),

                Tables\Columns\TextColumn::make('etat')
                    ->label('État')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'brouillon' => 'Brouillon',
                        'en_cours' => 'En cours',
                        'soumis' => 'Soumis',
                        'approuve' => 'Approuvé',
                        'rejete' => 'Rejeté',
                        'cloture' => 'Clôturé',
                        default => $state ?? '—',
                    })
                    ->color(fn ($state) => match ($state) {
                        'brouillon' => 'gray',
                        'en_cours' => 'primary',
                        'soumis' => 'warning',
                        'approuve' => 'success',
                        'rejete' => 'danger',
                        'cloture' => 'success',
                        default => 'gray',
                    })
                    ->toggleable()
                    ->toggledHiddenByDefault(),

                // 👤 Consultants
                Tables\Columns\TextColumn::make('consultantAccueil.nom_complet')
                    ->label('Accueil')
                    ->searchable(['consultantAccueil.nom', 'consultantAccueil.prenom'])
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),

                Tables\Columns\TextColumn::make('consultantFormateur.nom_complet')
                    ->label('Formateur')
                    ->searchable(['consultantFormateur.nom', 'consultantFormateur.prenom'])
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),

                // 📅 Dates système
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),

                Tables\Columns\TextColumn::make('deleted_at')
                    ->label('Supprimé le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault()
                    ->color('danger'),
            ])
            ->filters([
                // 📊 Filtres statut
                Tables\Filters\SelectFilter::make('statut_formation')
                    ->label('Statut de la formation')
                    ->options([
                        'En attente' => 'En attente',
                        'Validé' => 'Validé',
                        'En cours' => 'En cours',
                        'Terminé' => 'Terminé',
                        'Annulé' => 'Annulé',
                        'Reporté' => 'Reporté',
                    ])
                    ->placeholder('Tous les statuts'),

                Tables\Filters\SelectFilter::make('etat')
                    ->label('État du dossier')
                    ->options([
                        'Brouillon' => 'Brouillon',
                        'En cours' => 'En cours',
                        'Soumis' => 'Soumis',
                        'Approuvé' => 'Approuvé',
                        'Rejeté' => 'Rejeté',
                        'Clôturé' => 'Clôturé',
                    ])
                    ->placeholder('Tous les états'),

                // 🤝 Relations
                Tables\Filters\SelectFilter::make('entite_id')
                    ->label('Entité commerciale')
                    ->relationship('entite', 'nom')
                    ->searchable()
                    ->preload()
                    ->placeholder('Toutes les entités'),

                Tables\Filters\SelectFilter::make('personne_id')
                    ->label('Client')
                    ->relationship('personne', 'nom_tiers')
                    ->searchable()
                    ->preload()
                    ->placeholder('Tous les clients'),

                Tables\Filters\SelectFilter::make('consultant_accueil_id')
                    ->label('Consultant accueil')
                    ->relationship('consultantAccueil', 'nom')
                    ->searchable()
                    ->preload()
                    ->placeholder('Tous les consultants'),

                Tables\Filters\SelectFilter::make('consultant_formateur_id')
                    ->label('Consultant formateur')
                    ->relationship('consultantFormateur', 'nom')
                    ->searchable()
                    ->preload()
                    ->placeholder('Tous les formateurs'),

                // 💰 Filtres montants
                Tables\Filters\Filter::make('avec_cpf')
                    ->label('Avec financement CPF')
                    ->query(fn (Builder $q) => $q->whereNotNull('montant_cpf')->where('montant_cpf', '>', 0))
                    ->toggle(),

                Tables\Filters\Filter::make('sans_cpf')
                    ->label('Sans financement CPF')
                    ->query(fn (Builder $q) => $q->whereNull('montant_cpf')->orWhere('montant_cpf', '=', 0))
                    ->toggle(),

                // 📅 Filtres dates
                Tables\Filters\Filter::make('date_vente_recente')
                    ->label('Vendu ce mois-ci')
                    ->query(fn (Builder $q) => $q->whereMonth('date_vente', now()->month)
                        ->whereYear('date_vente', now()->year))
                    ->toggle(),

                Tables\Filters\Filter::make('date_vente_trimestre')
                    ->label('Vendu ce trimestre')
                    ->query(fn (Builder $q) => $q->whereBetween('date_vente', [
                        now()->startOfQuarter(),
                        now()->endOfQuarter(),
                    ]))
                    ->toggle(),

                // 🗑️ Corbeille
                Tables\Filters\TrashedFilter::make()
                    ->label('Corbeille'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('')
                    ->modalHeading('Dossier de formation'),

                Tables\Actions\EditAction::make()
                    ->label(''),

                // ✅ CORRECTION : Utilisation de modal au lieu de route
                Tables\Actions\Action::make('ajouter_heures')
                    ->label('Ajouter heures')
                    ->icon('heroicon-o-clock')
                    ->modalHeading('Ajouter des heures de formation')
                    ->modalSubmitActionLabel('Enregistrer')
                    ->form([
                        Forms\Components\TextInput::make('heures_obligatoires')
                            ->label('Heures obligatoires')
                            ->numeric()
                            ->step(0.5)
                            ->minValue(0)
                            ->required()
                            ->prefix('h'),
                        Forms\Components\TextInput::make('heures_complementaires')
                            ->label('Heures complémentaires')
                            ->numeric()
                            ->step(0.5)
                            ->minValue(0)
                            ->required()
                            ->prefix('h'),
                        Forms\Components\TextInput::make('heures_elearning')
                            ->label('Heures e-learning')
                            ->numeric()
                            ->step(0.5)
                            ->minValue(0)
                            ->required()
                            ->prefix('h'),
                        Forms\Components\TextInput::make('heures_realisees')
                            ->label('Heures réalisées')
                            ->numeric()
                            ->step(0.5)
                            ->minValue(0)
                            ->required()
                            ->prefix('h'),
                    ])
                    ->action(function (DossierFormation $record, array $data) {
                        $total = ($data['heures_obligatoires'] ?? 0)
                            + ($data['heures_complementaires'] ?? 0)
                            + ($data['heures_elearning'] ?? 0);
                        $restantes = $total - ($data['heures_realisees'] ?? 0);

                        $record->heures()->create([
                            'heures_obligatoires' => $data['heures_obligatoires'],
                            'heures_complementaires' => $data['heures_complementaires'],
                            'heures_elearning' => $data['heures_elearning'],
                            'heures_realisees' => $data['heures_realisees'],
                            'total_heures' => $total,
                            'heures_restantes' => $restantes,
                        ]);

                        Filament::notify('success', 'Heures ajoutées avec succès !');
                    }),

                Tables\Actions\Action::make('ajouter_planning')
                    ->label('Ajouter planning')
                    ->icon('heroicon-o-calendar')
                    ->modalHeading('Ajouter un planning de formation')
                    ->modalSubmitActionLabel('Enregistrer')
                    ->form([
                        Forms\Components\DatePicker::make('date_lancement')
                            ->label('Date de lancement')
                            ->displayFormat('d/m/Y')
                            ->required(),
                        Forms\Components\DatePicker::make('date_debut')
                            ->label('Date de début')
                            ->displayFormat('d/m/Y')
                            ->required()
                            ->afterOrEqual('date_lancement'),
                        Forms\Components\DatePicker::make('date_fin_theorique')
                            ->label('Date de fin théorique')
                            ->displayFormat('d/m/Y')
                            ->required()
                            ->afterOrEqual('date_debut'),
                        Forms\Components\DatePicker::make('date_certification')
                            ->label('Date de certification')
                            ->displayFormat('d/m/Y')
                            ->nullable()
                            ->afterOrEqual('date_fin_theorique'),
                        Forms\Components\DatePicker::make('date_questionnaire_chaud')
                            ->label('Date questionnaire chaud')
                            ->displayFormat('d/m/Y')
                            ->nullable()
                            ->afterOrEqual('date_debut'),
                    ])
                    ->action(function (DossierFormation $record, array $data) {
                        $record->planning()->create($data);
                        Filament::notify('success', 'Planning ajouté avec succès !');
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Supprimer'),
                    Tables\Actions\RestoreBulkAction::make()
                        ->label('Restaurer'),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->label('Supprimer définitivement'),
                ]),
            ])
            ->emptyStateHeading('Aucun dossier de formation')
            ->emptyStateDescription('Créez un dossier de formation pour suivre les formations de vos clients.')
            ->emptyStateActions([
                Tables\Actions\Action::make('create')
                    ->label('Créer un dossier')
                    ->icon('heroicon-o-plus')
                    ->url(route('filament.ns-conseil.resources.dossier-formations.create')),
            ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // INFOLIST
    // ─────────────────────────────────────────────────────────────────
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Informations générales')
                ->schema([
                    Infolists\Components\TextEntry::make('personne.nom_tiers')
                        ->label('Client')
                        ->weight('bold')
                        ->url(
                            fn (DossierFormation $record) => route('filament.ns-conseil.resources.clients.view', ['record' => $record->personne_id])
                        )
                        ->openUrlInNewTab(),

                    Infolists\Components\TextEntry::make('ref_client')
                        ->label('Référence client'),

                    Infolists\Components\TextEntry::make('intitule_programme')
                        ->label('Programme'),

                    Infolists\Components\TextEntry::make('entite.nom')
                        ->label('Entité commerciale')
                        ->placeholder('Aucune'),
                ])->columns(2),

            Infolists\Components\Section::make('Montants')
                ->schema([
                    Infolists\Components\TextEntry::make('montant_ht')
                        ->label('Montant HT')
                        ->money('EUR')
                        ->placeholder('0,00 €'),

                    Infolists\Components\TextEntry::make('montant_cpf')
                        ->label('Montant CPF')
                        ->money('EUR')
                        ->placeholder('0,00 €'),

                    Infolists\Components\TextEntry::make('total_ht_cpf')
                        ->label('Total HT + CPF')
                        ->money('EUR')
                        ->placeholder('0,00 €')
                        ->formatStateUsing(function (DossierFormation $record) {
                            $total = ($record->montant_ht ?? 0) + ($record->montant_cpf ?? 0);

                            return number_format($total, 2, ',', ' ').' €';
                        }),
                ])->columns(3),

            Infolists\Components\Section::make('Suivi')
                ->schema([
                    Infolists\Components\TextEntry::make('date_vente')
                        ->label('Date de vente')
                        ->date('d/m/Y')
                        ->placeholder('Non définie'),

                    Infolists\Components\TextEntry::make('statut_formation')
                        ->label('Statut')
                        ->badge()
                        ->formatStateUsing(fn ($state) => $state ?? '—')
                        ->color(fn ($state) => match ($state) {
                            'En attente' => 'gray',
                            'Validé' => 'primary',
                            'En cours' => 'warning',
                            'Terminé' => 'success',
                            'Annulé' => 'danger',
                            'Reporté' => 'info',
                            default => 'gray',
                        }),

                    Infolists\Components\TextEntry::make('etat')
                        ->label('État du dossier')
                        ->badge()
                        ->formatStateUsing(fn ($state) => $state ?? '—')
                        ->color(fn ($state) => match ($state) {
                            'Brouillon' => 'gray',
                            'En cours' => 'primary',
                            'Soumis' => 'warning',
                            'Approuvé' => 'success',
                            'Rejeté' => 'danger',
                            'Clôturé' => 'success',
                            default => 'gray',
                        }),

                    Infolists\Components\TextEntry::make('no_dossier_edof')
                        ->label('N° dossier EDOF')
                        ->placeholder('Non défini')
                        ->copyable(),
                ])->columns(2),

            Infolists\Components\Section::make('Consultants')
                ->schema([
                    Infolists\Components\TextEntry::make('consultantAccueil.nom_complet')
                        ->label('Consultant accueil')
                        ->placeholder('Aucun'),

                    Infolists\Components\TextEntry::make('consultantFormateur.nom_complet')
                        ->label('Consultant formateur')
                        ->placeholder('Aucun'),
                ])->columns(2),

            Infolists\Components\Section::make('Historique')
                ->schema([
                    Infolists\Components\TextEntry::make('created_at')
                        ->label('Créé le')
                        ->dateTime('d/m/Y H:i'),

                    Infolists\Components\TextEntry::make('updated_at')
                        ->label('Modifié le')
                        ->dateTime('d/m/Y H:i'),

                    Infolists\Components\TextEntry::make('deleted_at')
                        ->label('Supprimé le')
                        ->dateTime('d/m/Y H:i')
                        ->color('danger'),
                ])->columns(3),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // RELATIONS
    // ─────────────────────────────────────────────────────────────────
    public static function getRelations(): array
    {
        return [
            HeuresRelationManager::class,
            PlanningRelationManager::class,
        ];
    }

    // ─────────────────────────────────────────────────────────────────
    // PAGES
    // ─────────────────────────────────────────────────────────────────
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDossierFormations::route('/'),
            'create' => Pages\CreateDossierFormation::route('/create'),
            'edit' => Pages\EditDossierFormation::route('/{record}/edit'),
            'view' => Pages\ViewDossierFormation::route('/{record}'),
        ];
    }

    // ─────────────────────────────────────────────────────────────────
    // GLOBALE SEARCH
    // ─────────────────────────────────────────────────────────────────
    public static function getGlobalSearchResultTitle($record): string
    {
        return $record->intitule_programme;
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            'Client' => $record->personne?->nom_tiers ?? 'N/A',
            'Statut' => $record->statut_formation ?? 'N/A',
            'Montant CPF' => $record->montant_cpf ? number_format($record->montant_cpf, 2, ',', ' ').' €' : '0,00 €',
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['intitule_programme', 'ref_client', 'no_dossier_edof'];
    }
}
