<?php

namespace App\Filament\NsConseil\Resources;

use App\Enums\RendezVousStatut;
use App\Enums\RendezVousType;
use App\Filament\NsConseil\Resources\RendezVousResource\Pages\CreateRendezVous;
use App\Filament\NsConseil\Resources\RendezVousResource\Pages\EditRendezVous;
use App\Filament\NsConseil\Resources\RendezVousResource\Pages\ListRendezVous;
use App\Filament\NsConseil\Resources\RendezVousResource\Pages\ViewRendezVous;
use App\Models\RendezVous;
use App\Models\User;
use App\Services\GoogleCalendarService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RendezVousResource extends Resource
{
    protected static ?string $model = RendezVous::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Activités';

    protected static ?string $navigationLabel = 'Rendez-vous';

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'rendez-vous';

    public static function getNavigationBadge(): ?string
    {
        return (string) RendezVous::whereIn('statut', [
            RendezVousStatut::Planifie->value,
            RendezVousStatut::Decale->value,
        ])->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }

    // ─────────────────────────────────────────────────────────────────
    // FORMULAIRE
    // ─────────────────────────────────────────────────────────────────
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Informations générales')
                ->icon('heroicon-o-calendar')
                ->schema([
                    Forms\Components\Select::make('type')
                        ->label('Type')
                        ->options(collect(RendezVousType::cases())
                            ->mapWithKeys(fn ($t) => [$t->value => $t->label()])
                            ->toArray())
                        ->required()
                        ->native(false)
                        ->default(RendezVousType::Appel->value),

                    Forms\Components\Select::make('statut')
                        ->label('Statut')
                        ->options(collect(RendezVousStatut::cases())
                            ->mapWithKeys(fn ($s) => [$s->value => $s->label()])
                            ->toArray())
                        ->required()
                        ->native(false)
                        ->default(RendezVousStatut::Planifie->value),

                    Forms\Components\DateTimePicker::make('date_heure')
                        ->label('Date et heure')
                        ->required()
                        ->displayFormat('d/m/Y H:i')
                        ->seconds(false)
                        ->default(now()->addHour()->startOfHour()),

                    Forms\Components\TextInput::make('lieu')
                        ->label('Lieu'),

                    Forms\Components\TextInput::make('adresse_lieu')
                        ->label('Adresse du lieu')
                        ->columnSpan(2),
                ])->columns(3),

            Forms\Components\Section::make('Interlocuteur')
                ->icon('heroicon-o-user')
                ->schema([
                    Forms\Components\TextInput::make('interlocuteur_nom')
                        ->label('Nom')
                        ->required(),

                    Forms\Components\TextInput::make('interlocuteur_tel')
                        ->label('Téléphone')
                        ->tel(),

                    Forms\Components\TextInput::make('interlocuteur_email')
                        ->label('Email')
                        ->email(),
                ])->columns(3),

            Forms\Components\Section::make('Assignation')
                ->icon('heroicon-o-users')
                ->schema([
                    Forms\Components\Select::make('commercial_id')
                        ->label('Commercial')
                        ->options(fn () => User::whereIn('role_cache', ['commercial', 'team_leader', 'administrateur'])
                            ->orderBy('nom')
                            ->get()
                            ->mapWithKeys(fn (User $u) => [$u->id => "{$u->prenom} {$u->nom}"])
                            ->toArray()
                        )
                        ->searchable()
                        ->nullable()
                        ->default(fn () => auth()->user()?->hasRoleCache('commercial') ? auth()->id() : null),

                    Forms\Components\Select::make('teleprospecteur_id')
                        ->label('Téléprospecteur')
                        ->options(fn () => User::orderBy('nom')
                            ->get()
                            ->mapWithKeys(fn (User $u) => [$u->id => "{$u->prenom} {$u->nom}"])
                            ->toArray()
                        )
                        ->searchable()
                        ->nullable()
                        ->default(fn () => auth()->user()?->hasRoleCache('teleprospecteur') ? auth()->id() : null),
                ])->columns(2),

            Forms\Components\Section::make('Notes')
                ->icon('heroicon-o-pencil-square')
                ->schema([
                    Forms\Components\Textarea::make('notes')
                        ->label('Notes')
                        ->rows(4)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────
    // TABLE
    // ─────────────────────────────────────────────────────────────────
    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('date_heure', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof RendezVousType
                        ? $state->label()
                        : RendezVousType::tryFrom((string) $state)?->label() ?? $state
                    )
                    ->color(fn ($state) => match (
                        $state instanceof RendezVousType ? $state : RendezVousType::tryFrom((string) $state)
                    ) {
                        RendezVousType::Appel => 'primary',
                        RendezVousType::Permanence => 'success',
                        RendezVousType::Presentation => 'warning',
                        RendezVousType::Intervention => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn ($state) => match (
                        $state instanceof RendezVousType ? $state : RendezVousType::tryFrom((string) $state)
                    ) {
                        RendezVousType::Appel => 'heroicon-o-phone',
                        RendezVousType::Permanence => 'heroicon-o-building-office-2',
                        RendezVousType::Presentation => 'heroicon-o-presentation-chart-bar',
                        RendezVousType::Intervention => 'heroicon-o-wrench-screwdriver',
                        default => null,
                    }),

                Tables\Columns\TextColumn::make('date_heure')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('interlocuteur_nom')
                    ->label('Interlocuteur')
                    ->searchable(),

                Tables\Columns\TextColumn::make('interlocuteur_tel')
                    ->label('Téléphone')
                    ->copyable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('commercial.nom')
                    ->label('Commercial')
                    ->sortable()
                    ->getStateUsing(fn ($record) => $record->commercial
                        ? "{$record->commercial->prenom} {$record->commercial->nom}" : '—'),

                Tables\Columns\TextColumn::make('lieu')
                    ->label('Lieu')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('statut')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof RendezVousStatut
                        ? $state->label()
                        : RendezVousStatut::tryFrom((string) $state)?->label() ?? $state
                    )
                    ->color(fn ($state) => match (
                        $state instanceof RendezVousStatut ? $state : RendezVousStatut::tryFrom((string) $state)
                    ) {
                        RendezVousStatut::Planifie => 'info',
                        RendezVousStatut::Realise => 'success',
                        RendezVousStatut::Annule => 'danger',
                        RendezVousStatut::Decale => 'warning',
                        default => 'gray',
                    }),

                Tables\Columns\IconColumn::make('google_event_id')
                    ->label('Google')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->tooltip(fn ($state) => $state ? 'Synchronisé Google Calendar' : 'Non synchronisé'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options(collect(RendezVousType::cases())
                        ->mapWithKeys(fn ($t) => [$t->value => $t->label()])
                        ->toArray()
                    ),

                Tables\Filters\SelectFilter::make('statut')
                    ->label('Statut')
                    ->options(collect(RendezVousStatut::cases())
                        ->mapWithKeys(fn ($s) => [$s->value => $s->label()])
                        ->toArray()
                    ),

                Tables\Filters\SelectFilter::make('commercial_id')
                    ->label('Commercial')
                    ->options(fn () => User::whereIn('role_cache', ['commercial', 'team_leader', 'administrateur'])
                        ->orderBy('nom')
                        ->get()
                        ->mapWithKeys(fn (User $u) => [$u->id => "{$u->prenom} {$u->nom}"])
                        ->toArray()
                    ),

                Tables\Filters\Filter::make('a_venir')
                    ->label('À venir')
                    ->query(fn (Builder $q) => $q->where('date_heure', '>=', now()))
                    ->toggle(),

                Tables\Filters\Filter::make('aujourd_hui')
                    ->label("Aujourd'hui")
                    ->query(fn (Builder $q) => $q->whereDate('date_heure', today()))
                    ->toggle(),

                Tables\Filters\Filter::make('non_synchro')
                    ->label('Non sync Google')
                    ->query(fn (Builder $q) => $q->whereNull('google_event_id'))
                    ->toggle(),

                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('changer_statut')
                    ->label('Statut')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->form([
                        Forms\Components\Select::make('statut')
                            ->label('Nouveau statut')
                            ->options(collect(RendezVousStatut::cases())
                                ->mapWithKeys(fn ($s) => [$s->value => $s->label()])
                                ->toArray()
                            )
                            ->required()
                            ->native(false),
                        Forms\Components\Textarea::make('notes')
                            ->label('Note (optionnel)')
                            ->rows(2),
                    ])
                    ->action(function (RendezVous $record, array $data) {
                        $update = ['statut' => $data['statut']];
                        if (! empty($data['notes'])) {
                            $update['notes'] = ($record->notes ? $record->notes."\n" : '').$data['notes'];
                        }
                        $record->update($update);
                    })
                    ->modalWidth('md'),

                Tables\Actions\Action::make('sync_google')
                    ->label('Sync Google')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->visible(fn (RendezVous $record) => ! $record->google_event_id)
                    ->action(function (RendezVous $record) {
                        app(GoogleCalendarService::class)->createEvent($record);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                    Tables\Actions\BulkAction::make('sync_google_bulk')
                        ->label('Sync Google Calendar')
                        ->icon('heroicon-o-arrow-path')
                        ->action(function ($records) {
                            $service = app(GoogleCalendarService::class);
                            foreach ($records as $rdv) {
                                if (! $rdv->google_event_id) {
                                    $service->createEvent($rdv);
                                }
                            }
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->emptyStateHeading('Aucun rendez-vous')
            ->emptyStateDescription('Créez votre premier rendez-vous.');
    }

    // ─────────────────────────────────────────────────────────────────
    // INFOLIST
    // ─────────────────────────────────────────────────────────────────
    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Informations générales')->schema([
                Infolists\Components\TextEntry::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof RendezVousType
                        ? $state->label()
                        : RendezVousType::tryFrom((string) $state)?->label() ?? $state
                    )
                    ->color(fn ($state) => match (
                        $state instanceof RendezVousType ? $state : RendezVousType::tryFrom((string) $state)
                    ) {
                        RendezVousType::Appel => 'primary',
                        RendezVousType::Permanence => 'success',
                        RendezVousType::Presentation => 'warning',
                        RendezVousType::Intervention => 'danger',
                        default => 'gray',
                    }),

                Infolists\Components\TextEntry::make('statut')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof RendezVousStatut
                        ? $state->label()
                        : RendezVousStatut::tryFrom((string) $state)?->label() ?? $state
                    )
                    ->color(fn ($state) => match (
                        $state instanceof RendezVousStatut ? $state : RendezVousStatut::tryFrom((string) $state)
                    ) {
                        RendezVousStatut::Planifie => 'info',
                        RendezVousStatut::Realise => 'success',
                        RendezVousStatut::Annule => 'danger',
                        RendezVousStatut::Decale => 'warning',
                        default => 'gray',
                    }),

                Infolists\Components\TextEntry::make('date_heure')
                    ->label('Date et heure')
                    ->dateTime('d/m/Y à H:i'),

                Infolists\Components\TextEntry::make('lieu')
                    ->label('Lieu'),

                Infolists\Components\TextEntry::make('adresse_lieu')
                    ->label('Adresse'),
            ])->columns(3),

            Infolists\Components\Section::make('Interlocuteur')->schema([
                Infolists\Components\TextEntry::make('interlocuteur_nom')
                    ->label('Nom'),
                Infolists\Components\TextEntry::make('interlocuteur_tel')
                    ->label('Téléphone')
                    ->copyable(),
                Infolists\Components\TextEntry::make('interlocuteur_email')
                    ->label('Email')
                    ->copyable(),
            ])->columns(3),

            Infolists\Components\Section::make('Équipe')->schema([
                Infolists\Components\TextEntry::make('commercial.nom')
                    ->label('Commercial')
                    ->getStateUsing(fn ($record) => $record->commercial
                        ? "{$record->commercial->prenom} {$record->commercial->nom}" : '—'),
                Infolists\Components\TextEntry::make('teleprospecteur.nom')
                    ->label('Téléprospecteur')
                    ->getStateUsing(fn ($record) => $record->teleprospecteur
                        ? "{$record->teleprospecteur->prenom} {$record->teleprospecteur->nom}" : '—'),
                Infolists\Components\IconEntry::make('google_event_id')
                    ->label('Google Calendar')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->trueColor('success')
                    ->falseIcon('heroicon-o-x-circle')
                    ->falseColor('gray'),
            ])->columns(3),

            Infolists\Components\Section::make('Notes')->schema([
                Infolists\Components\TextEntry::make('notes')
                    ->label('')
                    ->columnSpanFull()
                    ->html(),
            ]),
        ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRendezVous::route('/'),
            'create' => CreateRendezVous::route('/create'),
            'edit' => EditRendezVous::route('/{record}/edit'),
            'view' => ViewRendezVous::route('/{record}'),
        ];
    }
}
