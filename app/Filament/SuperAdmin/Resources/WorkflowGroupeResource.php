<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Filament\SuperAdmin\Resources\WorkflowGroupeResource\Pages\EditWorkflowGroupe;
use App\Filament\SuperAdmin\Resources\WorkflowGroupeResource\Pages\ListWorkflowGroupes;
use App\Models\WorkflowGroupe;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WorkflowGroupeResource extends Resource
{
    protected static ?string $model = WorkflowGroupe::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-group';
    protected static ?string $navigationLabel = 'Groupes workflow';
    protected static ?string $navigationGroup = 'Paramétrage CRM';
    protected static ?int $navigationSort = 4;
    protected static ?string $modelLabel = 'Groupe workflow';
    protected static ?string $pluralModelLabel = 'Groupes workflow';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('model_type')
                ->options(['prospect' => 'Prospect', 'partenaire' => 'Partenaire'])
                ->required()
                ->native(false),
            Forms\Components\TextInput::make('code')->required(),
            Forms\Components\TextInput::make('label')->required(),
            Forms\Components\TextInput::make('ordre')->numeric()->default(0),
            Forms\Components\Toggle::make('actif')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('model_type')->badge(),
                Tables\Columns\TextColumn::make('ordre')->sortable(),
                Tables\Columns\TextColumn::make('code')->fontFamily('mono'),
                Tables\Columns\TextColumn::make('label')->searchable(),
                Tables\Columns\IconColumn::make('actif')->boolean(),
            ])
            ->reorderable('ordre')
            ->actions([Tables\Actions\EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWorkflowGroupes::route('/'),
            'edit' => EditWorkflowGroupe::route('/{record}/edit'),
        ];
    }
}
