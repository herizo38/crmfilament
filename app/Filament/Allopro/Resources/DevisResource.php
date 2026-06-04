<?php

namespace App\Filament\Allopro\Resources;

use App\Enums\StatutDevis;
use App\Filament\Allopro\Resources\DevisResource\Pages\CreateDevis;
use App\Filament\Allopro\Resources\DevisResource\Pages\EditDevis;
use App\Filament\Allopro\Resources\DevisResource\Pages\ListDevis;
use App\Filament\Allopro\Resources\DevisResource\Pages\ViewDevis;
use App\Filament\Allopro\Resources\DevisResource\RelationManagers\BonDeCommandeRelationManager;
use App\Models\Devis;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Allopro\Resources\TicketResource;

class DevisResource extends Resource
{
    protected static ?string $model               = Devis::class;
    protected static ?string $navigationIcon      = 'heroicon-o-document-text';
    protected static ?string $navigationLabel     = 'Devis';
    protected static ?string $navigationGroup     = 'Facturation';
    protected static ?int    $navigationSort      = 1;
    protected static ?string $recordTitleAttribute = 'numero';

    // ── Badge navigation ─────────────────────────────────────────
    public static function getNavigationBadge(): ?string
    {
        $count = Devis::enAttente()->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        $expiresBientot = Devis::expiresBientot(3)->count();
        return $expiresBientot > 0 ? 'danger' : 'warning';
    }

    // ── Formulaire ───────────────────────────────────────────────
    public static function form(Form $form): Form
    {
        return $form->schema([

            Forms\Components\Section::make('Identification')
                ->icon('heroicon-o-document-text')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('numero')
                        ->label('N° Devis')
                        ->disabled()
                        ->dehydrated(false)
                        ->placeholder('Généré automatiquement'),

                    Forms\Components\Select::make('statut')
                        ->label('Statut')
                        ->options(collect(StatutDevis::cases())
                            ->mapWithKeys(fn($e) => [$e->value => $e->label()])
                            ->toArray())
                        ->native(false)
                        ->required()
                        ->default(StatutDevis::Brouillon->value),

                    Forms\Components\DatePicker::make('date_validite')
                        ->label('Valide jusqu\'au')
                        ->native(false)
                        ->required()
                        ->default(now()->addDays(30))
                        ->minDate(today()),
                ]),

            Forms\Components\Section::make('Parties')
                ->icon('heroicon-o-users')
                ->columns(3)
                ->schema([
                    Forms\Components\Select::make('ticket_id')
                        ->label('Affaire / Ticket')
                        ->relationship('ticket', 'reference')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live()
                        ->afterStateUpdated(function ($state, Set $set) {
                            if (!$state) return;
                            $ticket = \App\Models\Ticket::find($state);
                            if ($ticket) {
                                $set('artisan_id', $ticket->artisan_id);
                                $set('contact_particulier_id', $ticket->contact_particulier_id);
                            }
                        }),

                    Forms\Components\Select::make('artisan_id')
                        ->label('Artisan (émetteur)')
                        ->relationship('artisan', 'nom')
                        ->getOptionLabelFromRecordUsing(fn($record) => $record->nom_complet . ' — ' . ($record->siret ?? 'SIRET manquant'))
                        ->searchable(['nom', 'prenom'])
                        ->required()
                        ->helperText('SIRET obligatoire pour facturation'),

                    Forms\Components\Select::make('contact_particulier_id')
                        ->label('Client (destinataire)')
                        ->relationship('contactParticulier', 'nom')
                        ->getOptionLabelFromRecordUsing(fn($record) => trim($record->prenom . ' ' . $record->nom) . ' — ' . $record->telephone)
                        ->searchable(['nom', 'prenom', 'telephone'])
                        ->required(),
                ]),

            Forms\Components\Section::make('Prestations')
                ->icon('heroicon-o-wrench-screwdriver')
                ->schema([
                    Forms\Components\Repeater::make('lignes')
                        ->label('Lignes de prestations')
                        ->schema([
                            Forms\Components\TextInput::make('libelle')
                                ->label('Libellé de la prestation')
                                ->required()
                                ->columnSpan(3),

                            Forms\Components\TextInput::make('quantite')
                                ->label('Qté')
                                ->numeric()
                                ->minValue(0.01)
                                ->default(1)
                                ->required()
                                ->live(debounce: 500),

                            Forms\Components\TextInput::make('prix_unitaire_ht')
                                ->label('Prix unit. HT (€)')
                                ->numeric()
                                ->minValue(0)
                                ->required()
                                ->prefix('€')
                                ->live(debounce: 500),

                            Forms\Components\Select::make('taux_tva')
                                ->label('TVA')
                                ->options([
                                    5.5  => '5,5 %',
                                    10.0 => '10 %',
                                    20.0 => '20 %',
                                ])
                                ->default(10.0)
                                ->native(false)
                                ->required(),
                        ])
                        ->columns(6)
                        ->reorderable()
                        ->addActionLabel('Ajouter une ligne')
                        ->minItems(1)
                        ->defaultItems(1)
                        ->columnSpanFull(),
                ]),

            Forms\Components\Section::make('Remise & Conditions')
                ->icon('heroicon-o-banknotes')
                ->columns(4)
                ->schema([
                    Forms\Components\TextInput::make('remise_montant')
                        ->label('Remise (€)')
                        ->numeric()
                        ->minValue(0)
                        ->prefix('€')
                        ->default(0)
                        ->live(debounce: 500),

                    Forms\Components\TextInput::make('remise_pourcentage')
                        ->label('Remise (%)')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->suffix('%')
                        ->default(0)
                        ->helperText('La plus élevée des deux remises est appliquée')
                        ->live(debounce: 500),

                    Forms\Components\Select::make('conditions_paiement')
                        ->label('Conditions de paiement')
                        ->options([
                            'acompte'            => 'Acompte à la commande',
                            'solde_intervention' => 'Solde à l\'intervention',
                            '30_jours'           => 'Paiement à 30 jours',
                        ])
                        ->native(false)
                        ->required()
                        ->default('solde_intervention'),

                    Forms\Components\Select::make('mode_acceptation')
                        ->label('Mode d\'acceptation')
                        ->options([
                            'signature_electronique' => 'Signature électronique',
                            'appel'                  => 'Appel téléphonique',
                            'email'                  => 'Email',
                        ])
                        ->native(false)
                        ->nullable(),
                ]),

            Forms\Components\Section::make('Notes')
                ->icon('heroicon-o-pencil')
                ->collapsible()
                ->schema([
                    Forms\Components\Textarea::make('notes')
                        ->label('Conditions spécifiques / observations')
                        ->rows(3)
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
                    ->label('N° Devis')
                    ->searchable()
                    ->weight('semibold')
                    ->copyable(),

                Tables\Columns\TextColumn::make('statut')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state instanceof StatutDevis ? $state->label() : $state)
                    ->color(fn($state) => $state instanceof StatutDevis ? $state->color() : 'gray')
                    ->icon(fn($state) => $state instanceof StatutDevis ? $state->icon() : null),

                Tables\Columns\TextColumn::make('ticket.reference')
                    ->label('Ticket')
                    ->searchable()
                    ->url(fn($record) => $record->ticket_id
                        ? TicketResource::getUrl('view', ['record' => $record->ticket_id])
                        : null)
                    ->color('primary'),

                Tables\Columns\TextColumn::make('artisan.nom')
                    ->label('Artisan')
                    ->formatStateUsing(fn($record) => $record->artisan?->nom_complet ?? '—')
                    ->description(
                        fn($record) =>
                        $record->artisan?->siret
                            ? 'SIRET : ' . $record->artisan->siret
                            : '⚠️ SIRET manquant'
                    ),

                Tables\Columns\TextColumn::make('contactParticulier.nom')
                    ->label('Client')
                    ->formatStateUsing(
                        fn($state, $record) =>
                        trim(($record->contactParticulier?->prenom ?? '') . ' ' . ($record->contactParticulier?->nom ?? '')) ?: '—'
                    )
                    ->suffix(fn($record) => $record->contactParticulier?->telephone ? " | {$record->contactParticulier->telephone}" : ''),

                Tables\Columns\TextColumn::make('total_ttc')
                    ->label('Total TTC')
                    ->formatStateUsing(fn($state) => number_format((float)$state, 2, ',', ' ') . ' €')
                    ->sortable()
                    ->weight('semibold'),

                Tables\Columns\TextColumn::make('date_validite')
                    ->label('Expire le')
                    ->date('d/m/Y')
                    ->color(fn(Devis $record) => match (true) {
                        $record->est_expire                      => 'danger',
                        $record->jours_avant_expiration <= 3     => 'warning',
                        default                                  => 'gray',
                    })
                    ->description(fn(Devis $record) => match (true) {
                        $record->est_expire                      => 'Expiré',
                        $record->jours_avant_expiration <= 7     => 'J-' . $record->jours_avant_expiration,
                        default                                  => null,
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            ->filters([
                Tables\Filters\SelectFilter::make('statut')
                    ->label('Statut')
                    ->options(collect(StatutDevis::cases())
                        ->mapWithKeys(fn($e) => [$e->value => $e->label()])
                        ->toArray())
                    ->native(false)
                    ->multiple(),

                Tables\Filters\Filter::make('expires_bientot')
                    ->label('Expire dans 7 jours')
                    ->query(fn(Builder $q) => $q->expiresBientot(7)),

                Tables\Filters\Filter::make('a_relancer')
                    ->label('À relancer')
                    ->query(fn(Builder $q) => $q->aRelancer()),

                Tables\Filters\Filter::make('du_mois')
                    ->label('Ce mois')
                    ->query(fn(Builder $q) => $q->duMois()),
            ])

            ->actions([
                // ── Envoyer le devis ──
                Tables\Actions\Action::make('envoyer')
                    ->label('Envoyer')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->visible(fn(Devis $r) => $r->statut === StatutDevis::Brouillon)
                    ->requiresConfirmation()
                    ->modalHeading('Envoyer ce devis au client ?')
                    ->modalDescription('Le statut passera en "Envoyé" et le délai de validité commencera.')
                    ->action(function (Devis $record) {
                        $record->envoyer();
                        Notification::make()
                            ->title('Devis ' . $record->numero . ' envoyé')
                            ->success()->send();
                    }),

                // ── Accepter ──
                Tables\Actions\Action::make('accepter')
                    ->label('Accepter')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(Devis $r) => in_array($r->statut, [StatutDevis::Envoye, StatutDevis::Brouillon]))
                    ->form([
                        Forms\Components\Select::make('mode_acceptation')
                            ->label('Mode d\'acceptation')
                            ->options([
                                'signature_electronique' => 'Signature électronique',
                                'appel'                  => 'Appel téléphonique',
                                'email'                  => 'Email',
                            ])
                            ->required()
                            ->native(false)
                            ->default('appel'),
                    ])
                    ->action(function (Devis $record, array $data) {
                        $bc = $record->accepter($data['mode_acceptation']);
                        Notification::make()
                            ->title('Devis accepté → BC ' . $bc->numero . ' créé')
                            ->success()->send();
                    }),

                // ── Refuser ──
                Tables\Actions\Action::make('refuser')
                    ->label('Refuser')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn(Devis $r) => $r->statut === StatutDevis::Envoye)
                    ->form([
                        Forms\Components\Textarea::make('motif')
                            ->label('Motif du refus')
                            ->rows(3),
                    ])
                    ->action(function (Devis $record, array $data) {
                        $record->refuser($data['motif'] ?? null);
                        Notification::make()
                            ->title('Devis refusé')
                            ->warning()->send();
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn(Devis $r) => $r->statut === StatutDevis::Brouillon),
            ])

            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => auth()->user()?->hasRole('responsable_plateau')),
                ]),
            ])

            ->emptyStateIcon('heroicon-o-document-text')
            ->emptyStateHeading('Aucun devis')
            ->emptyStateDescription('Les devis sont créés depuis un ticket d\'intervention.')
            ->striped();
    }

    // ── Infolist ─────────────────────────────────────────────────
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([

            Section::make('Statut & Validité')
                ->icon('heroicon-o-document-text')
                ->columns(4)
                ->schema([
                    TextEntry::make('numero')->label('N° Devis')->weight('bold')->copyable(),

                    TextEntry::make('statut')
                        ->label('Statut')
                        ->badge()
                        ->formatStateUsing(fn($state) => $state instanceof StatutDevis ? $state->label() : $state)
                        ->color(fn($state) => $state instanceof StatutDevis ? $state->color() : 'gray'),

                    TextEntry::make('date_validite')->label('Expire le')->date('d/m/Y'),

                    TextEntry::make('jours_avant_expiration')
                        ->label('Délai restant')
                        ->formatStateUsing(fn($record) => $record->est_expire ? '⚠️ Expiré' : "J-{$record->jours_avant_expiration}"),
                ]),

            Section::make('Parties')
                ->icon('heroicon-o-users')
                ->columns(3)
                ->schema([
                    TextEntry::make('ticket.reference')->label('Ticket'),
                    TextEntry::make('artisan.nom')
                        ->label('Artisan')
                        ->formatStateUsing(fn($record) => $record->artisan?->nom_complet ?? '—')
                        ->hint(fn($record) => 'SIRET : ' . ($record->artisan?->siret ?? '⚠️ manquant'))
                        ->hintColor(fn($record) => $record->artisan?->siret ? null : 'danger'),
                    TextEntry::make('contactParticulier.nom')
                        ->label('Client')
                        ->formatStateUsing(
                            fn($state, Devis $record) =>
                            trim(($record->contactParticulier?->prenom ?? '') . ' ' . ($record->contactParticulier?->nom ?? '')) ?: '—'
                        )
                        ->suffix(fn(Devis $record) => $record->contactParticulier?->telephone ? " | {$record->contactParticulier->telephone}" : ''),
                ]),

            Section::make('Montants')
                ->icon('heroicon-o-banknotes')
                ->columns(4)
                ->schema([
                    TextEntry::make('total_ht')
                        ->label('Total HT')
                        ->formatStateUsing(fn($state) => number_format((float)$state, 2, ',', ' ') . ' €'),
                    TextEntry::make('montant_tva')
                        ->label('TVA')
                        ->formatStateUsing(fn($state) => number_format((float)$state, 2, ',', ' ') . ' €'),
                    TextEntry::make('total_ttc')
                        ->label('Total TTC')
                        ->formatStateUsing(fn($state) => number_format((float)$state, 2, ',', ' ') . ' €')
                        ->weight('bold')
                        ->color('success'),
                    TextEntry::make('conditions_paiement')->label('Conditions'),
                ]),

            Section::make('Notes')
                ->icon('heroicon-o-pencil')
                ->collapsible()
                ->schema([
                    TextEntry::make('notes')->label('')->prose()->placeholder('Aucune note'),
                ]),
        ]);
    }

    // ── Relations ────────────────────────────────────────────────
    public static function getRelations(): array
    {
        return [
            BonDeCommandeRelationManager::class,
        ];
    }

    // ── Pages ────────────────────────────────────────────────────
    public static function getPages(): array
    {
        return [
            'index'  => ListDevis::route('/'),
            'create' => CreateDevis::route('/create'),
            'view'   => ViewDevis::route('/{record}'),
            'edit'   => EditDevis::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasAnyRole(['back_office', 'responsable_plateau']) ?? false;
    }
}
