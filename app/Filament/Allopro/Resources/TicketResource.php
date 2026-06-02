<?php

namespace App\Filament\Allopro\Resources;

use App\Enums\CorpsDeMetier;
use App\Enums\NiveauPriorite;
use App\Enums\TicketStatut;
use App\Filament\Allopro\Resources\TicketResource\Pages\CreateTicket;
use App\Filament\Allopro\Resources\TicketResource\Pages\EditTicket;
use App\Filament\Allopro\Resources\TicketResource\Pages\ListTickets;
use App\Filament\Allopro\Resources\TicketResource\Pages\ViewTicket;
use App\Filament\Allopro\Resources\TicketResource\RelationManagers\DevisRelationManager;
use App\Filament\Allopro\Resources\TicketResource\RelationManagers\FacturesRelationManager;
use App\Filament\Allopro\Resources\TicketResource\RelationManagers\FicheP2RelationManager;
use App\Filament\Allopro\Resources\TicketResource\RelationManagers\RapportsSatisfactionRelationManager;
use App\Filament\Allopro\Resources\TicketResource\RelationManagers\ReclamationsRelationManager;
use App\Models\Artisan;
use App\Models\Ticket;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TicketResource extends Resource
{
    protected static ?string $model                = Ticket::class;
    protected static ?string $navigationIcon       = 'heroicon-o-ticket';
    protected static ?string $navigationLabel      = 'Tickets';
    protected static ?string $navigationGroup      = 'Tickets';
    protected static ?int    $navigationSort       = 1;
    protected static ?string $recordTitleAttribute = 'reference';

    // ── Navigation badge ─────────────────────────────────────────
    public static function getNavigationBadge(): ?string
    {
        $count = Ticket::whereNotIn('statut', [
            TicketStatut::DossierCloture->value,
            TicketStatut::ClotureSatisfait->value,
        ])->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        $urgents = Ticket::where('niveau_priorite', NiveauPriorite::Urgence->value)
            ->whereNotIn('statut', [
                TicketStatut::DossierCloture->value,
                TicketStatut::ClotureSatisfait->value,
            ])->count();

        return $urgents > 0 ? 'danger' : 'warning';
    }

    // ── Formulaire ───────────────────────────────────────────────
    public static function form(Form $form): Form
    {
        return $form->schema([

            Forms\Components\Section::make('Ticket')
                ->icon('heroicon-o-ticket')
                ->columns(3)
                ->schema([
                    Forms\Components\TextInput::make('reference')
                        ->label('Référence')
                        ->disabled()
                        ->dehydrated(false)
                        ->placeholder('Généré automatiquement'),

                    Forms\Components\Select::make('statut')
                        ->label('Statut')
                        ->options(
                            collect(TicketStatut::cases())
                                ->mapWithKeys(fn($e) => [$e->value => $e->label()])
                                ->toArray()
                        )
                        ->native(false)
                        ->required()
                        ->default(TicketStatut::AppelRecu->value),

                    Forms\Components\Select::make('niveau_priorite')
                        ->label('Priorité')
                        ->options(
                            collect(NiveauPriorite::cases())
                                ->mapWithKeys(fn($e) => [$e->value => $e->label()])
                                ->toArray()
                        )
                        ->native(false),
                ]),

            Forms\Components\Section::make('Client')
                ->icon('heroicon-o-user')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('contact_particulier_id')
                        ->label('Client')
                        ->relationship('contactParticulier', 'nom')
                        ->getOptionLabelFromRecordUsing(
                            fn($record) =>
                            trim($record->prenom . ' ' . $record->nom) . ' — ' . $record->telephone
                        )
                        ->searchable(['nom', 'prenom', 'telephone'])
                        ->preload()
                        ->createOptionForm([
                            Forms\Components\TextInput::make('prenom')->label('Prénom')->required(),
                            Forms\Components\TextInput::make('nom')->label('Nom')->required(),
                            Forms\Components\TextInput::make('telephone')->label('Téléphone')->required()->tel(),
                            Forms\Components\TextInput::make('email')->label('Email')->email(),
                            Forms\Components\Textarea::make('adresse_complete')->label('Adresse')->required(),
                            Forms\Components\Select::make('type_logement')
                                ->label('Type de logement')
                                ->options(
                                    collect(\App\Enums\TypeLogement::cases())
                                        ->mapWithKeys(fn($e) => [$e->value => $e->label()])
                                        ->toArray()
                                )
                                ->native(false)
                                ->required(),
                            Forms\Components\Select::make('statut_occupant')
                                ->label('Statut occupant')
                                ->options(
                                    collect(\App\Enums\StatutOccupant::cases())
                                        ->mapWithKeys(fn($e) => [$e->value => $e->label()])
                                        ->toArray()
                                )
                                ->native(false)
                                ->required(),
                        ])
                        ->columnSpanFull(),
                ]),


            Forms\Components\Section::make('Artisan & Opérateur')
                ->icon('heroicon-o-wrench-screwdriver')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('artisan_id')
                        ->label('Artisan assigné')
                        ->relationship(
                            name: 'artisan',
                            titleAttribute: 'nom',
                            modifyQueryUsing: fn(Builder $query) => $query->where('statut_compte', 'actif')
                        )
                        ->getOptionLabelFromRecordUsing(function ($record) {
                            // Sécurité si le record de l'artisan est manquant ou vide
                            if (! $record) return '—';

                            $nomComplet = $record->nom_complet ?? trim(($record->prenom ?? '') . ' ' . ($record->nom ?? ''));
                            $metier = $record->corps_de_metier?->label() ?? 'Métier non défini';

                            return "{$nomComplet} — {$metier}";
                        })
                        ->searchable(['nom', 'prenom'])
                        ->preload()
                        ->nullable(),

                    Forms\Components\Select::make('operateur_id')
                        ->label('Opérateur')
                        ->relationship(name: 'operateur', titleAttribute: 'nom')
                        ->getOptionLabelFromRecordUsing(function ($record) {
                            if (! $record) return '—';
                            return trim(($record->prenom ?? '') . ' ' . ($record->nom ?? ''));
                        })
                        ->searchable()
                        ->default(fn() => auth()->id()),
                ]),
            Forms\Components\Section::make('Planification')
                ->icon('heroicon-o-calendar')
                ->columns(2)
                ->schema([
                    Forms\Components\DateTimePicker::make('rdv_planifie_at')
                        ->label('RDV planifié')
                        ->native(false)
                        ->nullable(),

                    Forms\Components\DateTimePicker::make('rappel_promise_at')
                        ->label('Rappel promis')
                        ->native(false)
                        ->nullable(),
                ]),

            Forms\Components\Section::make('Notes')
                ->icon('heroicon-o-document-text')
                ->collapsible()
                ->schema([
                    Forms\Components\Textarea::make('notes')
                        ->label('Notes internes')
                        ->rows(4)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    // ── Table ────────────────────────────────────────────────────
    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('date_creation', 'desc')
            ->poll('30s')
            ->columns([
                Tables\Columns\TextColumn::make('reference')
                    ->label('Réf.')
                    ->searchable()
                    ->weight('semibold')
                    ->copyable(),

                Tables\Columns\TextColumn::make('statut')
                    ->label('Statut')
                    ->sortable()
                    ->badge()
                    ->formatStateUsing(fn($state) => $state instanceof TicketStatut ? $state->label() : $state)
                    ->color(fn($state) => $state instanceof TicketStatut ? $state->color() : 'gray')
                    ->icon(fn($state) => $state instanceof TicketStatut ? $state->icon() : null),

                Tables\Columns\TextColumn::make('niveau_priorite')
                    ->label('Priorité')
                    ->sortable()
                    ->badge()
                    ->formatStateUsing(fn($state) => $state instanceof NiveauPriorite ? $state->label() : '—')
                    ->color(fn($state) => $state instanceof NiveauPriorite ? $state->color() : 'gray'),

                Tables\Columns\TextColumn::make('contactParticulier.nom')
                    ->label('Client')
                    ->formatStateUsing(
                        fn($state, $record) =>
                        trim(($record->contactParticulier?->prenom ?? '') . ' ' . ($record->contactParticulier?->nom ?? '')) ?: '—'
                    )
                    ->searchable(['contactParticulier.nom', 'contactParticulier.prenom'])
                    ->description(fn($record) => $record->contactParticulier?->telephone),

                Tables\Columns\TextColumn::make('artisan.nom')
                    ->label('Artisan')
                    ->formatStateUsing(fn($state, $record) => $record->artisan?->nom_complet ?? '—')
                    ->description(fn($record) => $record->artisan?->corps_de_metier?->label())
                    ->placeholder('Non assigné'),

                Tables\Columns\TextColumn::make('corps_de_metier')
                    ->label('Métier')
                    ->badge()
                    ->formatStateUsing(fn($state) => $state instanceof CorpsDeMetier ? $state->label() : '—')
                    ->color(fn($state) => $state instanceof CorpsDeMetier ? $state->color() : 'gray')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('date_creation')
                    ->label('Créé')
                    ->dateTime('d/m H:i')
                    ->sortable()
                    ->description(fn($record) => $record->duree_traitement_formatee),

                Tables\Columns\IconColumn::make('sla_respecte')
                    ->label('SLA')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-exclamation-circle')
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->tooltip(
                        fn($record) => $record->sla_respecte
                            ? 'Dans les délais'
                            : ($record->sla_depasse_depuis ?? 'Hors délai')
                    ),

                Tables\Columns\TextColumn::make('operateur.prenom')
                    ->label('Opérateur')
                    ->formatStateUsing(
                        fn($state, $record) =>
                        trim(($record->operateur?->prenom ?? '') . ' ' . ($record->operateur?->nom ?? '')) ?: '—'
                    )
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            ->filters([
                Tables\Filters\SelectFilter::make('statut')
                    ->label('Statut')
                    ->options(
                        collect(TicketStatut::cases())
                            ->mapWithKeys(fn($e) => [$e->value => $e->label()])
                            ->toArray()
                    )
                    ->native(false)
                    ->multiple(),

                Tables\Filters\SelectFilter::make('niveau_priorite')
                    ->label('Priorité')
                    ->options(
                        collect(NiveauPriorite::cases())
                            ->mapWithKeys(fn($e) => [$e->value => $e->label()])
                            ->toArray()
                    )
                    ->native(false),

                Tables\Filters\SelectFilter::make('corps_de_metier')
                    ->label('Métier')
                    ->options(
                        collect(CorpsDeMetier::cases())
                            ->mapWithKeys(fn($e) => [$e->value => $e->label()])
                            ->toArray()
                    )
                    ->native(false)
                    ->searchable(),

                Tables\Filters\Filter::make('sans_artisan')
                    ->label('Sans artisan')
                    ->query(
                        fn(Builder $q) => $q
                            ->whereNull('artisan_id')
                            ->whereIn('statut', [
                                TicketStatut::FicheComplete->value,
                                TicketStatut::RdvPlanifie->value,
                            ])
                    ),

                Tables\Filters\Filter::make('en_retard')
                    ->label('En retard SLA')
                    ->query(
                        fn(Builder $q) => $q
                            ->where(function (Builder $sub) {
                                $sub->where(function (Builder $s) {
                                    $s->where('niveau_priorite', NiveauPriorite::Urgence->value)
                                        ->where('date_creation', '<', now()->subMinutes(30));
                                })
                                    ->orWhere(function (Builder $s) {
                                        $s->where('niveau_priorite', NiveauPriorite::Prioritaire->value)
                                            ->where('date_creation', '<', now()->subMinutes(120));
                                    })
                                    ->orWhere(function (Builder $s) {
                                        $s->where('niveau_priorite', NiveauPriorite::Standard->value)
                                            ->where('date_creation', '<', now()->subMinutes(480));
                                    });
                            })
                            ->whereNotIn('statut', [
                                TicketStatut::DossierCloture->value,
                                TicketStatut::ClotureSatisfait->value,
                            ])
                    ),

                Tables\Filters\Filter::make('bloquants')
                    ->label('Bloquants')
                    ->query(
                        fn(Builder $q) => $q
                            ->whereIn('statut', [
                                TicketStatut::FicheIncomplete->value,
                                TicketStatut::ReclamationOuverte->value,
                                TicketStatut::SuiviQualiteRequis->value,
                            ])
                    ),

                Tables\Filters\Filter::make('du_jour')
                    ->label("Aujourd'hui")
                    ->query(fn(Builder $q) => $q->whereDate('date_creation', today())),
            ])

            ->actions([
                Tables\Actions\Action::make('changer_statut')
                    ->label('Avancer')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color('primary')
                    ->visible(function (Ticket $r) { // 🔑 Changé en 'function'
                        $statut = is_string($r->statut) ? TicketStatut::tryFrom($r->statut) : $r->statut;
                        return $statut && $r->estActif() && count($statut->statutsSuivants()) > 0;
                    })
                    ->form(function (Ticket $record) { // 🔑 Changé en 'function'
                        $statut = is_string($record->statut) ? TicketStatut::tryFrom($record->statut) : $record->statut;
                        return [
                            Forms\Components\Select::make('nouveau_statut')
                                ->label('Nouveau statut')
                                ->options(
                                    collect($statut?->statutsSuivants() ?? []) // Utilisation de ?-> pour éviter un crash si $statut est null
                                        ->mapWithKeys(fn($s) => [$s->value => $s->label()])
                                        ->toArray()
                                )
                                ->required()
                                ->native(false),
                            Forms\Components\Textarea::make('notes')
                                ->label('Notes (optionnel)')
                                ->rows(2),
                        ];
                    })
                    ->action(function (Ticket $record, array $data) {
                        $nouveau = TicketStatut::from($data['nouveau_statut']);
                        $record->changerStatut($nouveau, $data['notes'] ?? null);
                        Notification::make()
                            ->title('Statut mis à jour : ' . $nouveau->label())
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('assigner_artisan')
                    ->label('Assigner artisan')
                    ->icon('heroicon-o-wrench-screwdriver')
                    ->color('warning')
                    ->visible(fn(Ticket $r) => is_null($r->artisan_id) && $r->estActif())
                    ->form([
                        Forms\Components\Select::make('artisan_id')
                            ->label('Artisan')
                            ->options(
                                Artisan::disponibles()
                                    ->get()
                                    ->mapWithKeys(fn($a) => [
                                        $a->id => $a->nom_complet . ' — ' . $a->corps_de_metier->label()
                                    ])
                            )
                            ->required()
                            ->searchable()
                            ->native(false),
                    ])
                    ->action(function (Ticket $record, array $data) {
                        $artisan = Artisan::findOrFail($data['artisan_id']);
                        $record->assignerArtisan($artisan);
                        Notification::make()
                            ->title('Artisan assigné : ' . $artisan->nom_complet)
                            ->success()
                            ->send();
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])

            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => auth()->user()?->hasRole('responsable_plateau')),
                ]),
            ])

            ->emptyStateIcon('heroicon-o-ticket')
            ->emptyStateHeading('Aucun ticket')
            ->emptyStateDescription('Les tickets sont créés automatiquement à chaque appel entrant.')
            ->striped();
    }

    public static function getRelations(): array
    {
        return [
            FicheP2RelationManager::class,
            RapportsSatisfactionRelationManager::class,
            ReclamationsRelationManager::class,
            DevisRelationManager::class,
            FacturesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListTickets::route('/'),
            'create' => CreateTicket::route('/create'),
            'view'   => ViewTicket::route('/{record}'),
            'edit'   => EditTicket::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasAnyRole(['operateur_n1', 'responsable_plateau']) ?? false;
    }
}
