<?php

namespace App\Filament\SuperAdmin\Resources;

use App\Filament\SuperAdmin\Resources\CrmProfileResource\Pages\CreateCrmProfile;
use App\Filament\SuperAdmin\Resources\CrmProfileResource\Pages\EditCrmProfile;
use App\Filament\SuperAdmin\Resources\CrmProfileResource\Pages\ListCrmProfiles;
use App\Models\CrmProfile;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CrmProfileResource extends Resource
{
    protected static ?string $model = CrmProfile::class;
    protected static ?string $navigationIcon = 'heroicon-o-identification';
    protected static ?string $navigationLabel = 'Profils CRM';
    protected static ?string $navigationGroup = 'Paramétrage CRM';
    protected static ?int $navigationSort = 1;
    protected static ?string $modelLabel = 'Profil CRM';
    protected static ?string $pluralModelLabel = 'Profils CRM';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identité')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('role_name')
                        ->label('Rôle Spatie (slug)')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->helperText('Doit correspondre au rôle Spatie (ex: team_leader)'),

                    Forms\Components\TextInput::make('label')
                        ->label('Libellé affiché')
                        ->required(),

                    Forms\Components\Textarea::make('description')
                        ->label('Description')
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('landing_path')
                        ->label('Page d\'accueil après connexion')
                        ->placeholder('/ns-conseil/prospects'),

                    Forms\Components\TextInput::make('icone')
                        ->label('Icône Heroicon')
                        ->placeholder('heroicon-o-phone'),

                    Forms\Components\Select::make('couleur')
                        ->label('Couleur badge')
                        ->options([
                            'gray' => 'Gris', 'primary' => 'Bleu', 'success' => 'Vert',
                            'warning' => 'Orange', 'danger' => 'Rouge', 'info' => 'Info', 'purple' => 'Violet',
                        ])
                        ->native(false),

                    Forms\Components\TextInput::make('ordre')
                        ->label('Ordre')
                        ->numeric()
                        ->default(0),
                ]),

            Forms\Components\Section::make('Accès & capacités')
                ->columns(2)
                ->schema([
                    Forms\Components\CheckboxList::make('panels')
                        ->label('Panels Filament autorisés')
                        ->options([
                            'super-admin' => 'Super Admin',
                            'admin' => 'Admin',
                            'ns-conseil' => 'NS Conseil',
                            'allopro' => 'AlloPro',
                        ])
                        ->columns(2)
                        ->columnSpanFull(),

                    Forms\Components\Toggle::make('can_validate_qf')
                        ->label('Peut valider QF'),

                    Forms\Components\Toggle::make('can_import')
                        ->label('Peut importer des bases'),

                    Forms\Components\Toggle::make('is_supervisor')
                        ->label('Mode superviseur phoning'),

                    Forms\Components\Toggle::make('is_system')
                        ->label('Profil système')
                        ->helperText('Les profils système sont fournis par les seeders'),

                    Forms\Components\Toggle::make('actif')
                        ->label('Actif')
                        ->default(true),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ordre')->label('#')->sortable(),
                Tables\Columns\TextColumn::make('label')->label('Profil')->searchable()->weight('bold'),
                Tables\Columns\TextColumn::make('role_name')->label('Rôle')->badge()->color('gray'),
                Tables\Columns\TextColumn::make('panels')->label('Panels')->badge()->limit(30),
                Tables\Columns\IconColumn::make('can_validate_qf')->label('QF')->boolean(),
                Tables\Columns\IconColumn::make('can_import')->label('Import')->boolean(),
                Tables\Columns\IconColumn::make('is_supervisor')->label('Superviseur')->boolean(),
                Tables\Columns\IconColumn::make('actif')->label('Actif')->boolean(),
            ])
            ->defaultSort('ordre')
            ->reorderable('ordre')
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCrmProfiles::route('/'),
            'create' => CreateCrmProfile::route('/create'),
            'edit' => EditCrmProfile::route('/{record}/edit'),
        ];
    }
}
