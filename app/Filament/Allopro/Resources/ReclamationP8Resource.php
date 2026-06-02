<?php

namespace App\Filament\Allopro\Resources;

use App\Enums\StatutReclamation;
use App\Filament\Allopro\Resources\ReclamationP8Resource\Pages\ListReclamationP8s;
use App\Filament\Allopro\Resources\ReclamationP8Resource\Pages\ViewReclamationP8;
use App\Models\ReclamationP8;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReclamationP8Resource extends Resource
{
    protected static ?string $model               = ReclamationP8::class;
    protected static ?string $navigationIcon      = 'heroicon-o-exclamation-triangle';
    protected static ?string $navigationLabel     = 'Réclamations P8';
    protected static ?string $navigationGroup     = 'Qualité & Suivi';
    protected static ?int    $navigationSort      = 2;
    protected static ?string $recordTitleAttribute = 'id';

    // ── Badge : réclamations actives ─────────────────────────────
    public static function getNavigationBadge(): ?string
    {
        $count = ReclamationP8::actives()->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return ReclamationP8::enRetard()->count() > 0 ? 'danger' : 'warning';
    }

    // ── Formulaire (édition uniquement — création via P6 auto) ───
    public static function form(Form $form): Form
    {
        return $form->schema([

            Forms\Components\Section::make('Réclamation')
                ->icon('heroicon-o-exclamation-triangle')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('statut')
                        ->label('Statut')
                        ->options(collect(StatutReclamation::cases())
                            ->mapWithKeys(fn($e) => [$e->value => $e->label()])
                            ->toArray())
                        ->native(false)
                        ->required(),

                    Forms\Components\DatePicker::make('date_resolution_cible')
                        ->label('Date résolution cible (J+5 max)')
                        ->native(false)
                        ->required(),

                    Forms\Components\DatePicker::make('date_resolution_effective')
                        ->label('Date résolution effective')
                        ->native(false)
                        ->nullable(),

                    Forms\Components\Toggle::make('validation_superviseur')
                        ->label('Validé par superviseur')
                        ->inline(false),
                ]),

            Forms\Components\Section::make('Suivi')
                ->icon('heroicon-o-document-text')
                ->schema([
                    Forms\Components\Textarea::make('description_reclamation')
                        ->label('Description')
                        ->required()
                        ->rows(4)
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('notes_resolution')
                        ->label('Notes de résolution')
                        ->rows(4)
                        ->placeholder('Actions menées, solution apportée…')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    // ── Table ────────────────────────────────────────────────────
    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('date_ouverture', 'desc')
            ->poll('30s')
            ->columns([
                // ── Urgence visuelle ──
                Tables\Columns\TextColumn::make('urgence_level')
                    ->label('')
                    ->getStateUsing(fn($r) => $r->est_en_retard ? '🔴' : ($r->delai_restant_jours <= 1 ? '🟠' : '🟢'))
                    ->width(40),

                Tables\Columns\TextColumn::make('ticket.reference')
                    ->label('Ticket')
                    ->searchable()
                    ->weight('semibold')
                    ->url(fn($r) => $r->ticket_id
                        ? TicketResource::getUrl('view', ['record' => $r->ticket_id])
                        : null)
                    ->color('primary'),

                Tables\Columns\TextColumn::make('statut')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn($s) => $s instanceof StatutReclamation ? $s->label() : $s)
                    ->color(fn($s) => $s instanceof StatutReclamation ? $s->color() : match($s) {
                        'ouverte'             => 'danger',
                        'en_traitement'       => 'warning',
                        'validee_superviseur' => 'info',
                        'cloturee'            => 'success',
                        default               => 'gray',
                    })
                    ->icon(fn($s) => $s instanceof StatutReclamation ? $s->icon() : null),

                Tables\Columns\TextColumn::make('rapportSatisfaction.note_nps')
                    ->label('NPS déclencheur')
                    ->badge()
                    ->formatStateUsing(fn($s) => $s . ' / 10')
                    ->color(fn($s) => $s <= 3 ? 'danger' : 'warning')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('ticket.artisan.nom')
                    ->label('Artisan')
                    ->formatStateUsing(fn($s, $r) => $r->ticket?->artisan?->nom_complet ?? '—'),

                Tables\Columns\TextColumn::make('ticket.contactParticulier.nom')
                    ->label('Client')
                    ->formatStateUsing(fn($s, $r) =>
                        trim(($r->ticket?->contactParticulier?->prenom ?? '') . ' ' . ($r->ticket?->contactParticulier?->nom ?? '')) ?: '—'
                    ),

                Tables\Columns\TextColumn::make('date_ouverture')
                    ->label('Ouverte le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('delai_restant_formate')
                    ->label('Délai restant')
                    ->getStateUsing(fn($r) => $r->delai_restant_formate)
                    ->color(fn($r) => match(true) {
                        $r->est_en_retard        => 'danger',
                        $r->delai_restant_jours <= 1 => 'warning',
                        default                  => 'success',
                    })
                    ->weight('semibold'),

                Tables\Columns\IconColumn::make('validation_superviseur')
                    ->label('Superviseur')
                    ->boolean()
                    ->trueColor('success')->falseColor('gray'),

                Tables\Columns\TextColumn::make('date_resolution_effective')
                    ->label('Résolue le')
                    ->date('d/m/Y')
                    ->placeholder('En cours')
                    ->toggleable(),
            ])

            ->filters([
                Tables\Filters\SelectFilter::make('statut')
                    ->label('Statut')
                    ->options(collect(StatutReclamation::cases())
                        ->mapWithKeys(fn($e) => [$e->value => $e->label()])->toArray())
                    ->native(false)->multiple(),

                Tables\Filters\Filter::make('en_retard')
                    ->label('🔴 En retard SLA')
                    ->query(fn(Builder $q) => $q->enRetard()),

                Tables\Filters\Filter::make('a_valider')
                    ->label('En attente superviseur')
                    ->query(fn(Builder $q) => $q->aValider()),

                Tables\Filters\Filter::make('actives')
                    ->label('Actives')
                    ->query(fn($query) => $query->actives())
                    ->default(),
            ])

            ->actions([
                // ── Prendre en charge ──
                Tables\Actions\Action::make('prendre_en_charge')
                    ->label('Prendre en charge')
                    ->icon('heroicon-o-hand-raised')
                    ->color('warning')
                    ->visible(fn(ReclamationP8 $r) => $r->estOuverte())
                    ->form([
                        Forms\Components\Textarea::make('notes')
                            ->label('Plan d\'action initial')
                            ->rows(3)
                            ->required()
                            ->placeholder('Actions à mener pour résoudre la réclamation…'),
                    ])
                    ->action(function (ReclamationP8 $record, array $data) {
                        $record->mettreEnTraitement($data['notes']);
                        Notification::make()
                            ->title('Réclamation prise en charge')
                            ->warning()->send();
                    }),

                // ── Valider superviseur ──
                Tables\Actions\Action::make('valider_superviseur')
                    ->label('Validation superviseur')
                    ->icon('heroicon-o-shield-check')
                    ->color('info')
                    ->visible(fn(ReclamationP8 $r) =>
                        $r->estEnTraitement() &&
                        auth()->user()?->hasAnyRole(['responsable_plateau', 'superviseur'])
                    )
                    ->form([
                        Forms\Components\Textarea::make('notes')
                            ->label('Observations superviseur')
                            ->rows(3),
                    ])
                    ->action(function (ReclamationP8 $record, array $data) {
                        $user = auth()->user();
                        $record->validerParSuperviseur($user, $data['notes'] ?? null);
                        Notification::make()
                            ->title('Validé par ' . $user->nom_complet)
                            ->info()->send();
                    }),

                // ── Clôturer ──
                Tables\Actions\Action::make('cloturer')
                    ->label('Clôturer')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(ReclamationP8 $r) =>
                        $r->estValideeSuperviseur() || $r->estEnTraitement()
                    )
                    ->form([
                        Forms\Components\Textarea::make('notes')
                            ->label('Solution apportée')
                            ->rows(4)
                            ->required()
                            ->placeholder('Description de la résolution : ce qui a été fait, résultat pour le client…'),
                    ])
                    ->action(function (ReclamationP8 $record, array $data) {
                        $record->cloturer($data['notes']);
                        Notification::make()
                            ->title('Réclamation clôturée ✅')
                            ->success()->send();
                    }),

                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn(ReclamationP8 $r) => $r->estActive()),
            ])

            ->emptyStateIcon('heroicon-o-check-circle')
            ->emptyStateHeading('Aucune réclamation active')
            ->emptyStateDescription('Les réclamations sont ouvertes automatiquement quand le NPS est ≤ 5.')
            ->striped();
    }

    // ── Infolist ─────────────────────────────────────────────────
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([

            Section::make('Réclamation')
                ->icon('heroicon-o-exclamation-triangle')
                ->columns(4)
                ->schema([
                    TextEntry::make('statut')
                        ->label('Statut')
                        ->badge()
                        ->formatStateUsing(fn($s) => $s instanceof StatutReclamation ? $s->label() : $s)
                        ->color(fn($s) => $s instanceof StatutReclamation ? $s->color() : 'gray'),

                    TextEntry::make('date_ouverture')->label('Ouverte le')->dateTime('d/m/Y H:i'),

                    TextEntry::make('date_resolution_cible')
                        ->label('Résolution cible')
                        ->date('d/m/Y')
                        ->color(fn($s, $r) => $r->est_en_retard ? 'danger' : 'warning'),

                    TextEntry::make('delai_restant_formate')
                        ->label('Délai restant')
                        ->getStateUsing(fn($r) => $r->delai_restant_formate)
                        ->color(fn($r) => $r->est_en_retard ? 'danger' : 'success'),
                ]),

            Section::make('Contexte NPS')
                ->columns(3)
                ->schema([
                    TextEntry::make('ticket.reference')->label('Ticket')
                        ->url(fn($r) => $r->ticket_id
                            ? TicketResource::getUrl('view', ['record' => $r->ticket_id])
                            : null),

                    TextEntry::make('rapportSatisfaction.note_nps')
                        ->label('NPS déclencheur')
                        ->formatStateUsing(fn($s) => $s . ' / 10')
                        ->badge()
                        ->color('danger'),

                    TextEntry::make('rapportSatisfaction.verbatim_client')
                        ->label('Verbatim client')
                        ->prose()
                        ->placeholder('—'),
                ]),

            Section::make('Artisan & Client')
                ->columns(2)
                ->schema([
                    TextEntry::make('ticket.artisan.nom')
                        ->label('Artisan')
                        ->formatStateUsing(fn($s, $r) => $r->ticket?->artisan?->nom_complet ?? '—'),
                    TextEntry::make('ticket.contactParticulier.nom')
                        ->label('Client')
                        ->formatStateUsing(fn($s, $r) =>
                            trim(($r->ticket?->contactParticulier?->prenom ?? '') . ' ' . ($r->ticket?->contactParticulier?->nom ?? '')) ?: '—'
                        )
                        ->description(fn($r) => $r->ticket?->contactParticulier?->telephone),
                ]),

            Section::make('Suivi résolution')
                ->collapsible()
                ->schema([
                    TextEntry::make('description_reclamation')->label('Description initiale')->prose(),
                    TextEntry::make('notes_resolution')->label('Notes de résolution')->prose()->placeholder('En attente…'),

                    IconEntry::make('validation_superviseur')->label('Validé superviseur')
                        ->boolean()->trueColor('success')->falseColor('gray'),

                    TextEntry::make('superviseur.prenom')->label('Superviseur')
                        ->formatStateUsing(fn($s, $r) =>
                            trim(($r->superviseur?->prenom ?? '') . ' ' . ($r->superviseur?->nom ?? '')) ?: '—'
                        ),

                    TextEntry::make('date_resolution_effective')->label('Résolue le')
                        ->date('d/m/Y')->placeholder('—'),

                    TextEntry::make('delai_resolution_jours')->label('Délai réel de résolution')
                        ->formatStateUsing(fn($s) => $s ? $s . ' jour(s)' : '—'),
                ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReclamationP8s::route('/'),
            'view'  => ViewReclamationP8::route('/{record}'),
        ];
    }

    // P8 créées uniquement via NPS ≤ 5 — pas de création manuelle standard
    public static function canCreate(): bool
    {
        return false;
    }
}
