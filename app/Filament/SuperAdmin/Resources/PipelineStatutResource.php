<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Filament\SuperAdmin\Resources\PipelineStatutResource\Pages\EditPipelineStatut;
use App\Filament\SuperAdmin\Resources\PipelineStatutResource\Pages\ListPipelineStatuts;
use App\Models\PipelineStatut;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PipelineStatutResource extends Resource
{
    protected static ?string $model = PipelineStatut::class;
    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';
    protected static ?string $navigationLabel = 'Statuts pipeline';
    protected static ?string $navigationGroup = 'Paramétrage CRM';
    protected static ?int $navigationSort = 3;
    protected static ?string $modelLabel = 'Statut pipeline';
    protected static ?string $pluralModelLabel = 'Statuts pipeline';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('model_type')
                ->label('Entité')
                ->options([
                    'prospect' => 'Prospect',
                    'partenaire' => 'Partenaire',
                    'opportunite' => 'Opportunité',
                ])
                ->required()
                ->native(false),

            Forms\Components\TextInput::make('code')->label('Code')->required(),
            Forms\Components\TextInput::make('label')->label('Libellé')->required(),
            Forms\Components\Textarea::make('description')->label('Description'),

            Forms\Components\TagsInput::make('transitions')
                ->label('Transitions autorisées vers')
                ->helperText('Codes des statuts cibles autorisés'),

            Forms\Components\TextInput::make('couleur')->label('Couleur Filament')->default('gray'),
            Forms\Components\TextInput::make('icone')->label('Icône Heroicon'),
            Forms\Components\TextInput::make('ordre')->numeric()->default(0),

            Forms\Components\Toggle::make('is_terminal')->label('Statut terminal'),
            Forms\Components\Toggle::make('is_archive')->label('Archive / KO'),
            Forms\Components\Toggle::make('actif')->label('Actif')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('model_type')->label('Entité')->badge()->sortable(),
                Tables\Columns\TextColumn::make('ordre')->label('#')->sortable(),
                Tables\Columns\TextColumn::make('code')->label('Code')->fontFamily('mono'),
                Tables\Columns\TextColumn::make('label')->label('Libellé')->searchable(),
                Tables\Columns\IconColumn::make('is_terminal')->boolean()->label('Terminal'),
                Tables\Columns\IconColumn::make('actif')->boolean()->label('Actif'),
            ])
            ->defaultSort('model_type')
            ->reorderable('ordre')
            ->filters([
                Tables\Filters\SelectFilter::make('model_type')
                    ->options(['prospect' => 'Prospect', 'partenaire' => 'Partenaire', 'opportunite' => 'Opportunité']),
            ])
            ->actions([Tables\Actions\EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPipelineStatuts::route('/'),
            'edit' => EditPipelineStatut::route('/{record}/edit'),
        ];
    }
}
