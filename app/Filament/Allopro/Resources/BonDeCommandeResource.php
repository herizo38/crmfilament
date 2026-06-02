<?php

namespace App\Filament\Allopro\Resources;

use App\Enums\StatutBonDeCommande;
use App\Filament\Allopro\Resources\BonDeCommandeResource\Pages\CreateBonDeCommande;
use App\Filament\Allopro\Resources\BonDeCommandeResource\Pages\EditBonDeCommande;
use App\Filament\Allopro\Resources\BonDeCommandeResource\Pages\ListBonDeCommandes;
use App\Filament\Allopro\Resources\BonDeCommandeResource\Pages\ViewBonDeCommande;
use App\Filament\Allopro\Resources\BonDeCommandeResource\RelationManagers\FactureRelationManager;
use App\Models\BonDeCommande;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BonDeCommandeResource extends Resource
{
    protected static ?string $model               = BonDeCommande::class;
    protected static ?string $navigationIcon      = 'heroicon-o-clipboard-document-check';
    protected static ?string $navigationLabel     = 'Bons de commande';
    protected static ?string $navigationGroup     = 'Facturation';
    protected static ?int    $navigationSort      = 2;
    protected static ?string $recordTitleAttribute = 'numero';

    public static function getNavigationBadge(): ?string
    {
        $count = BonDeCommande::enAttente()->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return BonDeCommande::avecAcompteEnAttente()->count() > 0 ? 'warning' : 'info';
    }

    // ── Formulaire ───────────────────────────────────────────────
    public static function form(Form $form): Form
    {
        return $form->schema([

            Forms\Components\Section::make('Identification')
                ->icon('heroicon-o-clipboard-document-check')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('numero')
                        ->label('N° BC')
                        ->disabled()->dehydrated(false)
                        ->placeholder('Généré automatiquement'),

                    Forms\Components\Select::make('statut')
                        ->label('Statut')
                        ->options(collect(StatutBonDeCommande::cases())
                            ->mapWithKeys(fn($e) => [$e->value => $e->label()])
                            ->toArray())
                        ->native(false)->required()
                        ->default(StatutBonDeCommande::EnAttente->value),

                    Forms\Components\Select::make('devis_id')
                        ->label('Devis d\'origine')
                        ->relationship('devis', 'numero')
                        ->searchable()->preload()->nullable()
                        ->helperText('Lien vers le devis accepté'),
                ]),

            Forms\Components\Section::make('Parties')
                ->icon('heroicon-o-users')
                ->columns(3)
                ->schema([
                    Forms\Components\Select::make('ticket_id')
                        ->label('Affaire / Ticket')
                        ->relationship('ticket', 'reference')
                        ->searchable()->preload()->required(),

                    Forms\Components\Select::make('artisan_id')
                        ->label('Artisan exécutant')
                        ->relationship('artisan', 'nom')
                        ->getOptionLabelFromRecordUsing(fn($r) => $r->nom_complet)
                        ->searchable()->required(),

                    Forms\Components\Select::make('contact_particulier_id')
                        ->label('Client')
                        ->relationship('contactParticulier', 'nom')
                        ->getOptionLabelFromRecordUsing(fn($r) => trim($r->prenom . ' ' . $r->nom))
                        ->searchable()->required(),
                ]),

            Forms\Components\Section::make('Intervention')
                ->icon('heroicon-o-calendar')
                ->columns(3)
                ->schema([
                    Forms\Components\DateTimePicker::make('date_intervention_prevue')
                        ->label('Date d\'intervention prévue')
                        ->native(false)
                        ->required(),

                    Forms\Components\TextInput::make('duree_estimee_heures')
                        ->label('Durée estimée (heures)')
                        ->numeric()->minValue(0.5)->step(0.5)
                        ->suffix('h'),

                    Forms\Components\Select::make('conditions_paiement')
                        ->label('Conditions de paiement')
                        ->options([
                            'acompte'            => 'Acompte à la commande',
                            'solde_intervention' => 'Solde à l\'intervention',
                            '30_jours'           => 'Paiement à 30 jours',
                        ])
                        ->native(false)->required()->default('solde_intervention'),
                ]),

            Forms\Components\Section::make('Acompte')
                ->icon('heroicon-o-banknotes')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('acompte_montant')
                        ->label('Montant acompte (€)')
                        ->numeric()->minValue(0)->prefix('€')->nullable(),

                    Forms\Components\Toggle::make('acompte_encaisse')
                        ->label('Acompte encaissé')
                        ->default(false)->inline(false),
                ]),

            Forms\Components\Section::make('Montant & Prestations')
                ->icon('heroicon-o-calculator')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('montant_total_ttc')
                        ->label('Montant total TTC (€)')
                        ->numeric()->minValue(0)->prefix('€')->required(),

                    Forms\Components\Textarea::make('instructions_artisan')
                        ->label('Instructions spéciales pour l\'artisan')
                        ->rows(3)
                        ->helperText('Accès, outils particuliers, précautions…')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    // ── Table ────────────────────────────────────────────────────
    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('numero')
                    ->label('N° BC')
                    ->searchable()->weight('semibold')->copyable(),

                Tables\Columns\TextColumn::make('statut')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn($s) => $s instanceof StatutBonDeCommande ? $s->label() : $s)
                    ->color(fn($s) => $s instanceof StatutBonDeCommande ? $s->color() : 'gray')
                    ->icon(fn($s) => $s instanceof StatutBonDeCommande ? $s->icon() : null),

                Tables\Columns\TextColumn::make('ticket.reference')
                    ->label('Ticket')
                    ->url(fn($r) => $r->ticket_id
                        ? TicketResource::getUrl('view', ['record' => $r->ticket_id])
                        : null)
                    ->color('primary'),

                Tables\Columns\TextColumn::make('artisan.nom')
                    ->label('Artisan')
                    ->formatStateUsing(fn($s, $r) => $r->artisan?->nom_complet ?? '—'),

                Tables\Columns\TextColumn::make('date_intervention_prevue')
                    ->label('Intervention prévue')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->color(fn($s, $r) => match(true) {
                        $r->date_intervention_prevue?->isPast() && !$r->est_realise => 'danger',
                        $r->date_intervention_prevue?->isToday()                    => 'warning',
                        default                                                      => 'gray',
                    }),

                Tables\Columns\TextColumn::make('montant_total_ttc')
                    ->label('Montant TTC')
                    ->formatStateUsing(fn($s) => number_format((float)$s, 2, ',', ' ') . ' €')
                    ->sortable()->weight('semibold'),

                Tables\Columns\IconColumn::make('acompte_encaisse')
                    ->label('Acompte')
                    ->boolean()
                    ->trueColor('success')->falseColor('gray')
                    ->tooltip(fn($r) => $r->acompte_montant
                        ? number_format($r->acompte_montant, 2, ',', ' ') . ' €'
                        : 'Pas d\'acompte'),

                Tables\Columns\IconColumn::make('facture')
                    ->label('Facture')
                    ->getStateUsing(fn($record) => $record->facture()->exists())
                    ->boolean()
                    ->trueColor('success')->falseColor('gray')
                    ->trueIcon('heroicon-o-document-check')
                    ->falseIcon('heroicon-o-document'),
            ])

            ->filters([
                Tables\Filters\SelectFilter::make('statut')
                    ->options(collect(StatutBonDeCommande::cases())
                        ->mapWithKeys(fn($e) => [$e->value => $e->label()])->toArray())
                    ->native(false)->multiple(),

                Tables\Filters\Filter::make('intervention_aujourd_hui')
                    ->label('Intervention aujourd\'hui')
                    ->query(fn(Builder $q) => $q->whereDate('date_intervention_prevue', today())),

                Tables\Filters\Filter::make('acompte_en_attente')
                    ->label('Acompte en attente')
                    ->query(fn(Builder $q) => $q->avecAcompteEnAttente()),

                Tables\Filters\Filter::make('sans_facture')
                    ->label('Réalisé sans facture')
                    ->query(fn(Builder $q) => $q->sansFacture()),
            ])

            ->actions([
                // ── Confirmer (artisan accuse réception) ──
                Tables\Actions\Action::make('confirmer')
                    ->label('Confirmer')
                    ->icon('heroicon-o-check')
                    ->color('info')
                    ->visible(fn(BonDeCommande $r) => $r->statut === StatutBonDeCommande::EnAttente)
                    ->requiresConfirmation()
                    ->action(function (BonDeCommande $record) {
                        $record->confirmerParArtisan();
                        Notification::make()->title('BC confirmé par l\'artisan')->success()->send();
                    }),

                // ── Encaisser acompte ──
                Tables\Actions\Action::make('encaisser_acompte')
                    ->label('Acompte encaissé')
                    ->icon('heroicon-o-banknotes')
                    ->color('warning')
                    ->visible(fn(BonDeCommande $r) => $r->necessite_acompte && !$r->acompte_encaisse && $r->est_actif)
                    ->form([
                        Forms\Components\TextInput::make('montant')
                            ->label('Montant encaissé (€)')
                            ->numeric()->prefix('€')->required()
                            ->default(fn(BonDeCommande $r) => $r->acompte_montant),
                    ])
                    ->action(function (BonDeCommande $record, array $data) {
                        $record->enregistrerAcompte($data['montant']);
                        Notification::make()->title('Acompte enregistré')->success()->send();
                    }),

                // ── Marquer réalisé → génère la facture ──
                Tables\Actions\Action::make('marquer_realise')
                    ->label('Marquer réalisé')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(BonDeCommande $r) => in_array($r->statut, [
                        StatutBonDeCommande::Confirme, StatutBonDeCommande::EnCours
                    ]))
                    ->requiresConfirmation()
                    ->modalHeading('Marquer l\'intervention comme réalisée ?')
                    ->modalDescription('Une facture sera générée automatiquement.')
                    ->action(function (BonDeCommande $record) {
                        $facture = $record->marquerRealise();
                        Notification::make()
                            ->title('Intervention réalisée — Facture ' . $facture->numero . ' générée')
                            ->success()->send();
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn(BonDeCommande $r) => $r->statut === StatutBonDeCommande::EnAttente),
            ])

            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => auth()->user()?->hasRole('responsable_plateau')),
                ]),
            ])
            ->striped();
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([

            Section::make('Bon de commande')
                ->icon('heroicon-o-clipboard-document-check')
                ->columns(4)
                ->schema([
                    TextEntry::make('numero')->label('N° BC')->weight('bold')->copyable(),
                    TextEntry::make('statut')->label('Statut')->badge()
                        ->formatStateUsing(fn($s) => $s instanceof StatutBonDeCommande ? $s->label() : $s)
                        ->color(fn($s) => $s instanceof StatutBonDeCommande ? $s->color() : 'gray'),
                    TextEntry::make('date_confirmation')->label('Confirmé le')->dateTime('d/m/Y H:i')->placeholder('—'),
                    TextEntry::make('devis.numero')->label('Devis d\'origine')->placeholder('—'),
                ]),

            Section::make('Parties')
                ->columns(3)
                ->schema([
                    TextEntry::make('ticket.reference')->label('Ticket'),
                    TextEntry::make('artisan.nom')->label('Artisan')
                        ->formatStateUsing(fn($s, $r) => $r->artisan?->nom_complet ?? '—'),
                    TextEntry::make('contactParticulier.nom')->label('Client')
                        ->formatStateUsing(fn($s, $r) =>
                            trim(($r->contactParticulier?->prenom ?? '') . ' ' . ($r->contactParticulier?->nom ?? '')) ?: '—'
                        ),
                ]),

            Section::make('Intervention & Financier')
                ->columns(4)
                ->schema([
                    TextEntry::make('date_intervention_prevue')->label('Intervention prévue')->dateTime('d/m/Y H:i'),
                    TextEntry::make('duree_estimee_heures')->label('Durée estimée')->formatStateUsing(fn($s) => $s ? $s . ' h' : '—'),
                    TextEntry::make('montant_total_ttc')->label('Montant TTC')
                        ->formatStateUsing(fn($s) => number_format((float)$s, 2, ',', ' ') . ' €')->weight('bold'),
                    TextEntry::make('solde_restant')->label('Solde restant')
                        ->formatStateUsing(fn($s, $r) => number_format((float)$r->solde_restant, 2, ',', ' ') . ' €'),
                ]),

            Section::make('Instructions artisan')
                ->collapsible()
                ->schema([
                    TextEntry::make('instructions_artisan')->label('')->prose()->placeholder('Aucune instruction'),
                ]),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            FactureRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListBonDeCommandes::route('/'),
            'create' => CreateBonDeCommande::route('/create'),
            'view'   => ViewBonDeCommande::route('/{record}'),
            'edit'   => EditBonDeCommande::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasAnyRole(['back_office', 'responsable_plateau']) ?? false;
    }
}
