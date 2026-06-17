<?php

namespace App\Filament\NsConseil\Resources;

use App\Filament\NsConseil\Concerns\HasRoleAccess;
use App\Filament\NsConseil\Resources\StatutPhoningResource\Pages\CreateStatutPhoning;
use App\Filament\NsConseil\Resources\StatutPhoningResource\Pages\EditStatutPhoning;
use App\Filament\NsConseil\Resources\StatutPhoningResource\Pages\ListStatutPhonings;
use App\Models\PipelineStatut;
use App\Models\StatutPhoning;
use App\Models\WorkflowGroupe;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StatutPhoningResource extends Resource
{
    use HasRoleAccess;

    protected static ?string $model = StatutPhoning::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationLabel = 'Statuts Phoning';

    protected static ?string $navigationGroup = 'Configuration';

    protected static ?int $navigationSort = 11;

    protected static ?string $modelLabel = 'Statut Phoning';

    protected static ?string $pluralModelLabel = 'Statuts Phoning';

    public static function canAccess(): bool
    {
        return static::userHasAnyRole(['admin', 'superviseur']);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Identification')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('model_type')
                        ->label('Type de modèle')
                        ->options(StatutPhoning::MODEL_TYPES)
                        ->required()
                        ->native(false)
                        ->helperText('Le type de contact auquel s\'applique ce statut.'),

                    Forms\Components\TextInput::make('code')
                        ->label('Code interne')
                        ->required()
                        ->maxLength(50)
                        ->helperText('Ex: std_nr, rp, ko — utilisé en interne pour identifier le statut.')
                        ->rules(['alpha_dash']),

                    Forms\Components\TextInput::make('label')
                        ->label('Libellé affiché')
                        ->required()
                        ->maxLength(100)
                        ->helperText('Ce que l\'agent voit dans le workflow (ex: STD-NR, RP, KO).'),

                    Forms\Components\TextInput::make('description')
                        ->label('Description')
                        ->maxLength(255)
                        ->helperText('Sous-titre affiché sous le libellé (ex: Standard sans réponse).'),
                ]),

            Forms\Components\Section::make('Apparence')
                ->columns(3)
                ->schema([
                    Forms\Components\Select::make('couleur')
                        ->label('Couleur')
                        ->options(StatutPhoning::COULEURS)
                        ->required()
                        ->native(false)
                        ->default('gray'),

                    Forms\Components\TextInput::make('icone')
                        ->label('Icône (emoji)')
                        ->maxLength(10)
                        ->default('📞')
                        ->helperText('Un emoji représentant le statut.'),

                    Forms\Components\TextInput::make('ordre')
                        ->label('Ordre d\'affichage')
                        ->numeric()
                        ->default(0)
                        ->minValue(0),
                ]),

            Forms\Components\Section::make('Workflow CSE')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('groupe')
                        ->label('Groupe / Cas')
                        ->options(fn () => WorkflowGroupe::forModelType('prospect')->pluck('label', 'code'))
                        ->searchable()
                        ->native(false),

                    Forms\Components\Select::make('pipeline_statut')
                        ->label('Statut pipeline cible')
                        ->options(fn () => PipelineStatut::optionsFor('prospect'))
                        ->searchable()
                        ->native(false)
                        ->helperText('Statut prospect appliqué après cet appel'),

                    Forms\Components\Textarea::make('action_immediate')
                        ->label('Action immédiate')
                        ->rows(2)
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('delai_rappel_jours')
                        ->label('Relance auto (jours)')
                        ->numeric()
                        ->minValue(0),

                    Forms\Components\Select::make('fiche_type')
                        ->label('Fiche récap')
                        ->options(['bleue' => 'Bleue (RDV)', 'jaune' => 'Jaune', 'verte' => 'Verte'])
                        ->native(false),

                    Forms\Components\Toggle::make('note_obligatoire')->label('Note obligatoire'),
                    Forms\Components\Toggle::make('compte_comme_tentative')->label('Compte comme tentative'),
                    Forms\Components\Toggle::make('prioritaire')->label('Prioritaire dans la file'),
                    Forms\Components\Toggle::make('retire_de_file')->label('Retire de la file'),

                    Forms\Components\TextInput::make('message_note_obligatoire')
                        ->label('Message note obligatoire')
                        ->columnSpanFull(),
                ]),

            Forms\Components\Toggle::make('actif')
                ->label('Actif')
                ->default(true)
                ->helperText('Un statut inactif n\'apparaît pas dans le workflow.'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('model_type')
                    ->label('Modèle')
                    ->formatStateUsing(fn ($state) => StatutPhoning::MODEL_TYPES[$state] ?? $state)
                    ->badge()
                    ->color('primary')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('ordre')
                    ->label('#')
                    ->sortable()
                    ->width(50),

                Tables\Columns\TextColumn::make('icone')
                    ->label('')
                    ->width(40),

                Tables\Columns\TextColumn::make('label')
                    ->label('Libellé')
                    ->weight('bold')
                    ->searchable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->color('gray')
                    ->searchable(),

                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->fontFamily('mono')
                    ->color('gray'),

                Tables\Columns\TextColumn::make('couleur')
                    ->label('Couleur')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'blue' => 'info',
                        'orange' => 'warning',
                        'green' => 'success',
                        'teal' => 'success',
                        'red' => 'danger',
                        'yellow' => 'warning',
                        'purple' => 'primary',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => StatutPhoning::COULEURS[$state] ?? $state),

                Tables\Columns\TextColumn::make('groupe')->label('Cas')->badge()->toggleable(),
                Tables\Columns\TextColumn::make('pipeline_statut')->label('Pipeline')->fontFamily('mono')->toggleable(),

                Tables\Columns\ToggleColumn::make('actif')
                    ->label('Actif'),
            ])
            ->defaultSort('model_type')
            ->defaultSort('ordre')
            ->filters([
                Tables\Filters\SelectFilter::make('model_type')
                    ->label('Type de modèle')
                    ->options(StatutPhoning::MODEL_TYPES)
                    ->native(false),

                Tables\Filters\TernaryFilter::make('actif')
                    ->label('Actif'),
            ])
            ->reorderable('ordre')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStatutPhonings::route('/'),
            'create' => CreateStatutPhoning::route('/create'),
            'edit' => EditStatutPhoning::route('/{record}/edit'),
        ];
    }
}
