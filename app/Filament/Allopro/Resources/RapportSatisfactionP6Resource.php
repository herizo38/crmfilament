<?php

namespace App\Filament\Allopro\Resources;

use App\Enums\StatutClotureP6;
use App\Enums\TicketStatut;
use App\Filament\Allopro\Resources\RapportSatisfactionP6Resource\Pages\CreateRapportSatisfactionP6;
use App\Filament\Allopro\Resources\RapportSatisfactionP6Resource\Pages\ListRapportSatisfactionP6s;
use App\Filament\Allopro\Resources\RapportSatisfactionP6Resource\Pages\ViewRapportSatisfactionP6;
use App\Filament\Allopro\Resources\RapportSatisfactionP6Resource\RelationManagers\ReclamationRelationManager;
use App\Models\RapportSatisfactionP6;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RapportSatisfactionP6Resource extends Resource
{
    protected static ?string $model               = RapportSatisfactionP6::class;
    protected static ?string $navigationIcon      = 'heroicon-o-star';
    protected static ?string $navigationLabel     = 'Satisfaction P6';
    protected static ?string $navigationGroup     = 'Qualité & Suivi';
    protected static ?int    $navigationSort      = 1;
    protected static ?string $recordTitleAttribute = 'id';

    // ── Badge : appels J+1 à planifier ───────────────────────────
    public static function getNavigationBadge(): ?string
    {
        // Tickets interv. réalisée sans rapport P6 depuis plus de 24h
        $count = \App\Models\Ticket::where('statut', TicketStatut::InterventionRealisee->value)
            ->doesntHave('rapportSatisfaction')
            ->where('updated_at', '<', now()->subDay())
            ->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    // ── Formulaire ───────────────────────────────────────────────
    public static function form(Form $form): Form
    {
        return $form->schema([

            Forms\Components\Section::make('Ticket & Contexte')
                ->icon('heroicon-o-ticket')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('ticket_id')
                        ->label('Ticket')
                        ->relationship(
                            'ticket',
                            'reference',
                            fn(Builder $q) => $q->where('statut', TicketStatut::InterventionRealisee->value)
                        )
                        ->searchable()
                        ->preload()
                        ->required()
                        ->reactive()
                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                            if (!$state) return;
                            $ticket = \App\Models\Ticket::find($state);
                            if ($ticket) {
                                $set('artisan_id', $ticket->artisan_id);
                                $set('operateur_id', auth()->id());
                            }
                        })
                        ->helperText('Seuls les tickets "Intervention réalisée" sont listés'),

                    Forms\Components\Select::make('artisan_id')
                        ->label('Artisan concerné')
                        ->relationship('artisan', 'nom')
                        ->getOptionLabelFromRecordUsing(fn($r) => $r->nom_complet)
                        ->searchable()
                        ->required(),
                ]),

            Forms\Components\Section::make('Appel J+1')
                ->icon('heroicon-o-phone')
                ->columns(2)
                ->schema([
                    Forms\Components\DatePicker::make('date_appel_j1')
                        ->label('Date de l\'appel J+1')
                        ->required()
                        ->native(false)
                        ->default(now()->addDay())
                        ->maxDate(today()->addDays(3))
                        ->helperText('Dans les 72h suivant l\'intervention — SLA CDC'),

                    Forms\Components\Select::make('operateur_id')
                        ->label('Agent Back-Office')
                        ->relationship('operateur', 'nom')
                        ->getOptionLabelFromRecordUsing(fn($r) => trim($r->prenom . ' ' . $r->nom))
                        ->default(auth()->id())
                        ->required()
                        ->searchable(),
                ]),

            Forms\Components\Section::make('Score NPS')
                ->icon('heroicon-o-chart-bar')
                ->columns(1)
                ->schema([
                    Forms\Components\Select::make('note_nps')
                        ->label('Note NPS (0 → 10)')
                        ->options(array_combine(range(0, 10), range(0, 10)))
                        ->required()
                        ->native(false)
                        ->live()
                        ->afterStateUpdated(function ($state, Forms\Set $set) {
                            if ($state === null) return;
                            $statut = match(true) {
                                $state >= 8 => 'satisfait',
                                $state >= 6 => 'suivi_qualite_requis',
                                default     => 'reclamation_ouverte',
                            };
                            $set('statut_cloture', $statut);
                        })
                        ->helperText(fn(Get $get) => match(true) {
                            ($get('note_nps') ?? -1) >= 9  => '⭐ Promoteur — Client très satisfait',
                            ($get('note_nps') ?? -1) >= 7  => '😐 Passif — Satisfaction neutre',
                            ($get('note_nps') ?? -1) >= 6  => '⚠️ À surveiller — Suivi qualité P7',
                            ($get('note_nps') ?? -1) >= 0  => '🚨 Détracteur — Réclamation P8 déclenchée automatiquement',
                            default => 'Saisir une note de 0 à 10',
                        }),

                    Forms\Components\Select::make('statut_cloture')
                        ->label('Statut de clôture (auto-calculé)')
                        ->options([
                            'satisfait'            => '✅ Satisfait — Clôture définitive',
                            'suivi_qualite_requis' => '⚠️ Suivi qualité requis — Alerte P7',
                            'reclamation_ouverte'  => '🚨 Réclamation ouverte — P8 déclenché',
                        ])
                        ->required()
                        ->native(false)
                        ->helperText('Calculé automatiquement depuis le NPS — modifiable si besoin'),

                    Forms\Components\Textarea::make('verbatim_client')
                        ->label('Verbatim client (mot à mot)')
                        ->rows(4)
                        ->placeholder('Retranscription exacte des mots du client…')
                        ->helperText('Utile pour alimenter le rapport P7 et traiter la P8 si nécessaire'),

                    Forms\Components\Toggle::make('feedback_artisan')
                        ->label('Feedback transmis à l\'artisan')
                        ->helperText('Obligatoire selon les règles non négociables du CDC')
                        ->default(false)
                        ->inline(false),
                ]),
        ]);
    }

    // ── Table ────────────────────────────────────────────────────
    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('date_appel_j1', 'desc')
            ->poll('60s')
            ->columns([
                Tables\Columns\TextColumn::make('ticket.reference')
                    ->label('Ticket')
                    ->searchable()
                    ->weight('semibold')
                    ->url(fn($r) => $r->ticket_id
                        ? TicketResource::getUrl('view', ['record' => $r->ticket_id])
                        : null)
                    ->color('primary'),

                Tables\Columns\TextColumn::make('note_nps')
                    ->label('NPS')
                    ->badge()
                    ->formatStateUsing(fn($s) => $s . ' / 10')
                    ->color(fn($s) => match(true) {
                        $s >= 9 => 'success',
                        $s >= 7 => 'warning',
                        $s >= 6 => 'orange',
                        default => 'danger',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('classification_nps')
                    ->label('Classification')
                    ->getStateUsing(fn($r) => $r->getClassificationNPS())
                    ->badge()
                    ->color(fn($s) => match($s) {
                        'Promoteur'   => 'success',
                        'Passif'      => 'warning',
                        'Détracteur'  => 'danger',
                        default       => 'gray',
                    }),

                Tables\Columns\TextColumn::make('statut_cloture')
                    ->label('Clôture')
                    ->badge()
                    ->formatStateUsing(fn($s) => match($s instanceof \App\Enums\StatutClotureP6 ? $s->value : $s) {
                        'satisfait'            => 'Satisfait',
                        'suivi_qualite_requis' => 'Suivi qualité',
                        'reclamation_ouverte'  => 'Réclamation P8',
                        default                => $s,
                    })
                    ->color(fn($s) => match($s instanceof \App\Enums\StatutClotureP6 ? $s->value : $s) {
                        'satisfait'            => 'success',
                        'suivi_qualite_requis' => 'warning',
                        'reclamation_ouverte'  => 'danger',
                        default                => 'gray',
                    }),

                Tables\Columns\TextColumn::make('artisan.nom')
                    ->label('Artisan')
                    ->formatStateUsing(fn($s, $r) => $r->artisan?->nom_complet ?? '—')
                    ->description(fn($r) => $r->artisan?->corps_de_metier?->label()),

                Tables\Columns\TextColumn::make('verbatim_client')
                    ->label('Verbatim')
                    ->limit(50)
                    ->placeholder('—')
                    ->toggleable(),

                Tables\Columns\IconColumn::make('feedback_artisan')
                    ->label('Feedback')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger')
                    ->tooltip(fn($r) => $r->feedback_artisan ? 'Transmis' : '⚠️ Non transmis'),

                Tables\Columns\TextColumn::make('reclamation.statut')
                    ->label('P8')
                    ->badge()
                    ->formatStateUsing(fn($s) => $s ? ucfirst(str_replace('_', ' ', is_object($s) ? $s->value : $s)) : '—')
                    ->color(fn($s) => $s ? 'danger' : 'gray')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('date_appel_j1')
                    ->label('Appel J+1')
                    ->date('d/m/Y')
                    ->sortable(),
            ])

            ->filters([
                Tables\Filters\SelectFilter::make('statut_cloture')
                    ->label('Statut')
                    ->options([
                        'satisfait'            => 'Satisfait',
                        'suivi_qualite_requis' => 'Suivi qualité requis',
                        'reclamation_ouverte'  => 'Réclamation ouverte',
                    ])
                    ->native(false),

                Tables\Filters\Filter::make('detracteurs')
                    ->label('Détracteurs (NPS ≤ 6)')
                    ->query(fn(Builder $q) => $q->detracteurs()),

                Tables\Filters\Filter::make('sans_feedback')
                    ->label('Feedback non transmis')
                    ->query(fn(Builder $q) => $q->where('feedback_artisan', false)),

                Tables\Filters\Filter::make('du_mois')
                    ->label('Ce mois')
                    ->query(fn(Builder $q) => $q->duMois()),
            ])

            ->actions([
                // ── Transmettre feedback artisan ──
                Tables\Actions\Action::make('transmettre_feedback')
                    ->label('Feedback transmis')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('info')
                    ->visible(fn(RapportSatisfactionP6 $r) => !$r->feedback_artisan)
                    ->requiresConfirmation()
                    ->modalHeading('Confirmer la transmission du feedback à l\'artisan ?')
                    ->action(function (RapportSatisfactionP6 $record) {
                        $record->update(['feedback_artisan' => true]);
                        Notification::make()
                            ->title('Feedback marqué comme transmis')
                            ->success()->send();
                    }),

                // ── Ouvrir P8 manuellement si non auto-déclenché ──
                Tables\Actions\Action::make('ouvrir_p8')
                    ->label('Ouvrir P8')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('danger')
                    ->visible(fn(RapportSatisfactionP6 $r) =>
                        $r->declencheP8() && !$r->reclamation()->exists()
                    )
                    ->requiresConfirmation()
                    ->modalHeading('Ouvrir une réclamation P8 ?')
                    ->modalDescription('Une réclamation sera créée avec délai de résolution à J+5 ouvrés.')
                    ->action(function (RapportSatisfactionP6 $record) {
                        $reclamation = $record->ouvrirReclamationP8();
                        Notification::make()
                            ->title('Réclamation P8 ouverte — délai J+5')
                            ->danger()->send();
                    }),

                Tables\Actions\ViewAction::make(),
            ])

            ->emptyStateIcon('heroicon-o-star')
            ->emptyStateHeading('Aucun rapport P6')
            ->emptyStateDescription('Les rapports sont créés après chaque intervention réalisée (appel J+1).')
            ->striped();
    }

    // ── Infolist ─────────────────────────────────────────────────
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([

            Section::make('Score NPS')
                ->icon('heroicon-o-chart-bar')
                ->columns(4)
                ->schema([
                    TextEntry::make('note_nps')
                        ->label('Note NPS')
                        ->formatStateUsing(fn($s) => $s . ' / 10')
                        ->badge()
                        ->color(fn($s) => match(true) {
                            $s >= 9 => 'success',
                            $s >= 7 => 'warning',
                            default => 'danger',
                        })
                        ->size('xl'),

                    TextEntry::make('classification_nps')
                        ->label('Classification')
                        ->getStateUsing(fn($r) => $r->getClassificationNPS())
                        ->badge()
                        ->color(fn($s) => match($s) {
                            'Promoteur'  => 'success',
                            'Passif'     => 'warning',
                            'Détracteur' => 'danger',
                            default      => 'gray',
                        }),

                    TextEntry::make('statut_cloture')
                        ->label('Statut clôture')
                        ->formatStateUsing(fn($s) => match(is_object($s) ? $s->value : $s) {
                            'satisfait'            => '✅ Satisfait',
                            'suivi_qualite_requis' => '⚠️ Suivi qualité',
                            'reclamation_ouverte'  => '🚨 Réclamation P8',
                            default                => $s,
                        }),

                    IconEntry::make('feedback_artisan')
                        ->label('Feedback transmis')
                        ->boolean()
                        ->trueColor('success')->falseColor('danger'),
                ]),

            Section::make('Contexte')
                ->columns(3)
                ->schema([
                    TextEntry::make('ticket.reference')->label('Ticket')
                        ->url(fn($r) => $r->ticket_id
                            ? TicketResource::getUrl('view', ['record' => $r->ticket_id])
                            : null),
                    TextEntry::make('artisan.nom')->label('Artisan')
                        ->formatStateUsing(fn($s, $r) => $r->artisan?->nom_complet ?? '—'),
                    TextEntry::make('operateur.prenom')->label('Agent Back-Office')
                        ->formatStateUsing(fn($s, $r) =>
                            trim(($r->operateur?->prenom ?? '') . ' ' . ($r->operateur?->nom ?? '')) ?: '—'
                        ),
                    TextEntry::make('date_appel_j1')->label('Date appel J+1')->date('d/m/Y'),
                ]),

            Section::make('Verbatim client')
                ->collapsible()
                ->schema([
                    TextEntry::make('verbatim_client')->label('')->prose()->placeholder('Aucun verbatim'),
                ]),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            ReclamationRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListRapportSatisfactionP6s::route('/'),
            'create' => CreateRapportSatisfactionP6::route('/create'),
            'view'   => ViewRapportSatisfactionP6::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->hasAnyRole(['back_office', 'responsable_plateau']) ?? false;
    }
}
