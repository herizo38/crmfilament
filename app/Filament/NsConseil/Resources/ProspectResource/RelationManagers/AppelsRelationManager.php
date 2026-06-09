<?php

namespace App\Filament\NsConseil\Resources\ProspectResource\RelationManagers;

use App\Enums\EventType;
use App\Enums\EventResult;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AppelsRelationManager extends RelationManager
{
    protected static string $relationship = 'appels';
    protected static ?string $title = 'Appels';
    protected static ?string $icon = 'heroicon-o-phone';

    public function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('type')
                ->label('Type')
                ->options(EventType::class)
                ->required(),
            Forms\Components\Select::make('resultat')
                ->label('Résultat')
                ->options(EventResult::class)
                ->required(),
            Forms\Components\DateTimePicker::make('date_heure')
                ->label('Date et heure')
                ->required()
                ->default(now()),
            Forms\Components\Textarea::make('commentaire')
                ->label('Commentaire')
                ->rows(3)
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('date_heure', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('date_heure')
                    ->label('Date')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge(),
                Tables\Columns\TextColumn::make('resultat')
                    ->label('Résultat')
                    ->badge(),
                Tables\Columns\TextColumn::make('user.nom')
                    ->label('Par')
                    ->formatStateUsing(function ($state, $record) {
                        return $record->user
                            ? "{$record->user->prenom} {$record->user->nom}"
                            : '—';
                    }),
                Tables\Columns\TextColumn::make('commentaire')
                    ->limit(60),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Enregistrer un appel')
                    ->mutateFormDataUsing(fn(array $data) => array_merge($data, [
                        'user_id' => auth()->id(),
                    ])),
            ]);
    }
}
