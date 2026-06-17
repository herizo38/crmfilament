<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Filament\SuperAdmin\Resources\CrmSettingResource\Pages\CreateCrmSetting;
use App\Filament\SuperAdmin\Resources\CrmSettingResource\Pages\EditCrmSetting;
use App\Filament\SuperAdmin\Resources\CrmSettingResource\Pages\ListCrmSettings;
use App\Models\CrmSetting;
use App\Services\Crm\CrmSettingsService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CrmSettingResource extends Resource
{
    protected static ?string $model = CrmSetting::class;
    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';
    protected static ?string $navigationLabel = 'Paramètres CRM';
    protected static ?string $navigationGroup = 'Paramétrage CRM';
    protected static ?int $navigationSort = 2;
    protected static ?string $modelLabel = 'Paramètre';
    protected static ?string $pluralModelLabel = 'Paramètres CRM';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Select::make('groupe')
                ->label('Groupe')
                ->options([
                    'prospection' => 'Prospection',
                    'qf' => 'Qualification QF',
                    'roles' => 'Rôles',
                    'mail' => 'Emails',
                ])
                ->required()
                ->native(false),

            Forms\Components\TextInput::make('cle')
                ->label('Clé technique')
                ->required()
                ->helperText('Ex: max_standard_attempts'),

            Forms\Components\TextInput::make('label')
                ->label('Libellé')
                ->required(),

            Forms\Components\Select::make('type')
                ->label('Type de valeur')
                ->options([
                    'string' => 'Texte',
                    'int' => 'Nombre entier',
                    'bool' => 'Oui/Non',
                    'json' => 'JSON (liste, tableau)',
                ])
                ->required()
                ->native(false),

            Forms\Components\Textarea::make('valeur')
                ->label('Valeur')
                ->required()
                ->rows(3)
                ->helperText('Pour JSON : ["team_leader","administrateur"]'),

            Forms\Components\Textarea::make('description')
                ->label('Description')
                ->rows(2),

            Forms\Components\TextInput::make('ordre')
                ->label('Ordre')
                ->numeric()
                ->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('groupe')->label('Groupe')->badge()->sortable(),
                Tables\Columns\TextColumn::make('label')->label('Paramètre')->searchable()->weight('semibold'),
                Tables\Columns\TextColumn::make('cle')->label('Clé')->fontFamily('mono')->color('gray'),
                Tables\Columns\TextColumn::make('valeur')->label('Valeur')->limit(40)->wrap(),
                Tables\Columns\TextColumn::make('type')->label('Type')->badge(),
            ])
            ->defaultSort('groupe')
            ->filters([
                Tables\Filters\SelectFilter::make('groupe')
                    ->options([
                        'prospection' => 'Prospection',
                        'qf' => 'QF',
                        'roles' => 'Rôles',
                        'mail' => 'Emails',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->after(fn () => app(CrmSettingsService::class)->forget()),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCrmSettings::route('/'),
            'create' => CreateCrmSetting::route('/create'),
            'edit' => EditCrmSetting::route('/{record}/edit'),
        ];
    }
}
