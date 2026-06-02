<?php

namespace App\Filament\Allopro\Resources;

use App\Enums\ModePaiement;
use App\Enums\StatutPaiement;
use App\Filament\Allopro\Resources\FactureResource\Pages\CreateFacture;
use App\Filament\Allopro\Resources\FactureResource\Pages\ListFactures;
use App\Filament\Allopro\Resources\FactureResource\Pages\ViewFacture;
use App\Models\Facture;
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

class FactureResource extends Resource
{
    protected static ?string $model               = Facture::class;
    protected static ?string $navigationIcon      = 'heroicon-o-receipt-percent';
    protected static ?string $navigationLabel     = 'Factures';
    protected static ?string $navigationGroup     = 'Facturation';
    protected static ?int    $navigationSort      = 3;
    protected static ?string $recordTitleAttribute = 'numero';

    public static function getNavigationBadge(): ?string
    {
        $enRetard = Facture::enRetard()->count();
        return $enRetard > 0 ? (string) $enRetard : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return Facture::litigieuses()->count() > 0 ? 'danger' : 'warning';
    }

    // ── Formulaire (création manuelle rare — surtout générées auto) ──
    public static function form(Form $form): Form
    {
        return $form->schema([

            Forms\Components\Section::make('Identification légale')
                ->icon('heroicon-o-receipt-percent')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('numero')
                        ->label('N° Facture')
                        ->disabled()->dehydrated(false)
                        ->placeholder('Généré automatiquement (séquentiel)'),

                    Forms\Components\Select::make('statut_paiement')
                        ->label('Statut paiement')
                        ->options(collect(StatutPaiement::cases())
                            ->mapWithKeys(fn($e) => [$e->value => $e->label()])
                            ->toArray())
                        ->native(false)->required()
                        ->default(StatutPaiement::EnAttente->value),

                    Forms\Components\DatePicker::make('date_echeance')
                        ->label('Date d\'échéance')
                        ->native(false)->required()
                        ->default(now()->addDays(30)),
                ]),

            Forms\Components\Section::make('Parties')
                ->icon('heroicon-o-users')
                ->columns(3)
                ->schema([
                    Forms\Components\Select::make('bon_de_commande_id')
                        ->label('Bon de commande')
                        ->relationship('bonDeCommande', 'numero')
                        ->searchable()->preload()->nullable(),

                    Forms\Components\Select::make('ticket_id')
                        ->label('Ticket')
                        ->relationship('ticket', 'reference')
                        ->searchable()->preload()->required(),

                    Forms\Components\Select::make('artisan_id')
                        ->label('Artisan (SIRET requis)')
                        ->relationship('artisan', 'nom')
                        ->getOptionLabelFromRecordUsing(fn($r) =>
                            $r->nom_complet . ($r->siret ? ' — ' . $r->siret : ' ⚠️ SIRET manquant')
                        )
                        ->searchable()->required(),

                    Forms\Components\Select::make('contact_particulier_id')
                        ->label('Client facturé')
                        ->relationship('contactParticulier', 'nom')
                        ->getOptionLabelFromRecordUsing(fn($r) => trim($r->prenom . ' ' . $r->nom))
                        ->searchable()->required(),
                ]),

            Forms\Components\Section::make('Prestations réalisées')
                ->icon('heroicon-o-wrench-screwdriver')
                ->schema([
                    Forms\Components\Repeater::make('lignes')
                        ->label('')
                        ->schema([
                            Forms\Components\TextInput::make('libelle')
                                ->label('Libellé prestation')->required()->columnSpan(3),
                            Forms\Components\TextInput::make('quantite')
                                ->label('Qté')->numeric()->minValue(0.01)->default(1)->required()->live(debounce: 500),
                            Forms\Components\TextInput::make('prix_unitaire_ht')
                                ->label('Prix HT (€)')->numeric()->minValue(0)->required()->prefix('€')->live(debounce: 500),
                            Forms\Components\Select::make('taux_tva')
                                ->label('TVA')
                                ->options([5.5 => '5,5 %', 10.0 => '10 %', 20.0 => '20 %'])
                                ->default(10.0)->native(false)->required(),
                        ])
                        ->columns(6)->reorderable()->addActionLabel('Ajouter une ligne')
                        ->minItems(1)->defaultItems(1)->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Paiement')
                ->icon('heroicon-o-banknotes')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('acompte_deja_verse')
                        ->label('Acompte déjà versé (€)')
                        ->numeric()->minValue(0)->prefix('€')->nullable()
                        ->helperText('Déduit du total TTC'),

                    Forms\Components\Select::make('mode_paiement')
                        ->label('Mode de paiement')
                        ->options(collect(ModePaiement::cases())
                            ->mapWithKeys(fn($e) => [$e->value => $e->label()])
                            ->toArray())
                        ->native(false)->nullable(),

                    Forms\Components\DatePicker::make('date_paiement_effectif')
                        ->label('Date de paiement effectif')
                        ->native(false)->nullable(),

                    Forms\Components\Textarea::make('notes')
                        ->label('Notes')
                        ->rows(2)->columnSpanFull(),
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
                    ->label('N° Facture')
                    ->searchable()->weight('semibold')->copyable(),

                Tables\Columns\TextColumn::make('statut_paiement')
                    ->label('Paiement')
                    ->badge()
                    ->formatStateUsing(fn($s) => $s instanceof StatutPaiement ? $s->label() : $s)
                    ->color(fn($s) => $s instanceof StatutPaiement ? $s->color() : 'gray')
                    ->icon(fn($s) => $s instanceof StatutPaiement ? $s->icon() : null),

                Tables\Columns\TextColumn::make('ticket.reference')
                    ->label('Ticket')
                    ->url(fn($r) => $r->ticket_id
                        ? TicketResource::getUrl('view', ['record' => $r->ticket_id])
                        : null)
                    ->color('primary'),

                Tables\Columns\TextColumn::make('artisan.nom')
                    ->label('Artisan')
                    ->formatStateUsing(fn($s, $r) => $r->artisan?->nom_complet ?? '—')
                    ->description(fn($r) => $r->artisan?->siret ?? '⚠️ SIRET manquant'),

                Tables\Columns\TextColumn::make('contactParticulier.nom')
                    ->label('Client')
                    ->formatStateUsing(fn($s, $r) =>
                        trim(($r->contactParticulier?->prenom ?? '') . ' ' . ($r->contactParticulier?->nom ?? '')) ?: '—'
                    ),

                Tables\Columns\TextColumn::make('total_ttc')
                    ->label('Total TTC')
                    ->formatStateUsing(fn($s) => number_format((float)$s, 2, ',', ' ') . ' €')
                    ->sortable()->weight('semibold'),

                Tables\Columns\TextColumn::make('solde_restant_du')
                    ->label('Solde dû')
                    ->formatStateUsing(fn($s) => number_format((float)$s, 2, ',', ' ') . ' €')
                    ->color(fn($s) => (float)$s > 0 ? 'danger' : 'success'),

                Tables\Columns\TextColumn::make('date_echeance')
                    ->label('Échéance')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn($s, $r) => match(true) {
                        $r->est_payee           => 'success',
                        $r->est_en_retard       => 'danger',
                        $s && $s->diffInDays(now()) <= 7 => 'warning',
                        default                 => 'gray',
                    })
                    ->description(fn($r) => $r->est_en_retard
                        ? 'Retard : ' . $r->jours_retard . ' j'
                        : null),

                Tables\Columns\TextColumn::make('penalites_retard')
                    ->label('Pénalités')
                    ->formatStateUsing(fn($s) => (float)$s > 0
                        ? number_format((float)$s, 2, ',', ' ') . ' €'
                        : '—')
                    ->color('danger')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            ->filters([
                Tables\Filters\SelectFilter::make('statut_paiement')
                    ->label('Statut paiement')
                    ->options(collect(StatutPaiement::cases())
                        ->mapWithKeys(fn($e) => [$e->value => $e->label()])->toArray())
                    ->native(false)->multiple(),

                Tables\Filters\Filter::make('en_retard')
                    ->label('En retard')
                    ->query(fn(Builder $q) => $q->enRetard()),

                Tables\Filters\Filter::make('litigieuses')
                    ->label('Litigieuses')
                    ->query(fn(Builder $q) => $q->litigieuses()),

                Tables\Filters\Filter::make('a_relancer')
                    ->label('À relancer')
                    ->query(fn(Builder $q) => $q->aRelancer()),

                Tables\Filters\Filter::make('du_mois')
                    ->label('Ce mois')
                    ->query(fn(Builder $q) => $q->duMois()),
            ])

            ->actions([
                // ── Enregistrer paiement ──
                Tables\Actions\Action::make('payer')
                    ->label('Enregistrer paiement')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn(Facture $r) => !$r->est_payee && !$r->est_litigieux)
                    ->form([
                        Forms\Components\TextInput::make('montant')
                            ->label('Montant reçu (€)')
                            ->numeric()->prefix('€')->required()
                            ->default(fn(Facture $r) => $r->solde_restant_du),
                        Forms\Components\Select::make('mode')
                            ->label('Mode de paiement')
                            ->options(collect(ModePaiement::cases())
                                ->mapWithKeys(fn($e) => [$e->value => $e->label()])->toArray())
                            ->native(false)->required(),
                        Forms\Components\DatePicker::make('date')
                            ->label('Date de paiement')
                            ->native(false)->required()->default(today()),
                    ])
                    ->action(function (Facture $record, array $data) {
                        $mode = ModePaiement::from($data['mode']);
                        $record->enregistrerPaiement($data['montant'], $mode, new \DateTime($data['date']));
                        Notification::make()->title('Paiement enregistré')->success()->send();
                    }),

                // ── Marquer litigieux ──
                Tables\Actions\Action::make('litige')
                    ->label('Litige')
                    ->icon('heroicon-o-shield-exclamation')
                    ->color('danger')
                    ->visible(fn(Facture $r) => $r->est_en_retard && !$r->est_litigieux)
                    ->form([
                        Forms\Components\Textarea::make('motif')
                            ->label('Motif du litige')->rows(3)->required(),
                    ])
                    ->action(function (Facture $record, array $data) {
                        $record->marquerLitigieux($data['motif']);
                        Notification::make()->title('Facture marquée litigieuse')->warning()->send();
                    }),

                Tables\Actions\ViewAction::make(),
            ])

            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => auth()->user()?->hasRole('responsable_plateau')),
                ]),
            ])

            ->emptyStateIcon('heroicon-o-receipt-percent')
            ->emptyStateHeading('Aucune facture')
            ->emptyStateDescription('Les factures sont générées automatiquement à la réalisation du bon de commande.')
            ->striped();
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([

            Section::make('Facture')
                ->icon('heroicon-o-receipt-percent')
                ->columns(4)
                ->schema([
                    TextEntry::make('numero')->label('N° Facture')->weight('bold')->copyable(),
                    TextEntry::make('statut_paiement')->label('Statut paiement')->badge()
                        ->formatStateUsing(fn($s) => $s instanceof StatutPaiement ? $s->label() : $s)
                        ->color(fn($s) => $s instanceof StatutPaiement ? $s->color() : 'gray'),
                    TextEntry::make('date_echeance')->label('Échéance')->date('d/m/Y'),
                    TextEntry::make('date_paiement_effectif')->label('Payé le')->date('d/m/Y')->placeholder('—'),
                ]),

            Section::make('Parties')
                ->columns(3)
                ->schema([
                    TextEntry::make('bonDeCommande.numero')->label('BC d\'origine')->placeholder('—'),
                    TextEntry::make('artisan.nom')->label('Artisan émetteur')
                        ->formatStateUsing(fn($s, $r) => $r->artisan?->nom_complet ?? '—')
                        ->description(fn($r) => 'SIRET : ' . ($r->artisan?->siret ?? '⚠️ manquant')),
                    TextEntry::make('contactParticulier.nom')->label('Client facturé')
                        ->formatStateUsing(fn($s, $r) =>
                            trim(($r->contactParticulier?->prenom ?? '') . ' ' . ($r->contactParticulier?->nom ?? '')) ?: '—'
                        ),
                ]),

            Section::make('Montants')
                ->columns(5)
                ->schema([
                    TextEntry::make('total_ht')->label('HT')
                        ->formatStateUsing(fn($s) => number_format((float)$s, 2, ',', ' ') . ' €'),
                    TextEntry::make('montant_tva')->label('TVA')
                        ->formatStateUsing(fn($s) => number_format((float)$s, 2, ',', ' ') . ' €'),
                    TextEntry::make('total_ttc')->label('TTC')
                        ->formatStateUsing(fn($s) => number_format((float)$s, 2, ',', ' ') . ' €')
                        ->weight('bold'),
                    TextEntry::make('acompte_deja_verse')->label('Acompte versé')
                        ->formatStateUsing(fn($s) => $s ? number_format((float)$s, 2, ',', ' ') . ' €' : '—'),
                    TextEntry::make('solde_restant_du')->label('Solde dû')
                        ->formatStateUsing(fn($s) => number_format((float)$s, 2, ',', ' ') . ' €')
                        ->color(fn($s) => (float)$s > 0 ? 'danger' : 'success'),
                ]),

            Section::make('Pénalités & Litiges')
                ->collapsible()
                ->columns(2)
                ->schema([
                    TextEntry::make('penalites_retard')->label('Pénalités de retard')
                        ->formatStateUsing(fn($s) => (float)$s > 0
                            ? number_format((float)$s, 2, ',', ' ') . ' €'
                            : 'Aucune')
                        ->color(fn($s) => (float)$s > 0 ? 'danger' : 'success'),
                    TextEntry::make('jours_retard')->label('Retard')
                        ->formatStateUsing(fn($s) => $s > 0 ? $s . ' jour(s)' : 'Dans les délais'),
                ]),

            Section::make('Notes')
                ->collapsible()
                ->schema([
                    TextEntry::make('notes')->label('')->prose()->placeholder('Aucune note'),
                ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListFactures::route('/'),
            'create' => CreateFacture::route('/create'),
            'view'   => ViewFacture::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasAnyRole(['back_office', 'responsable_plateau']) ?? false;
    }
}
