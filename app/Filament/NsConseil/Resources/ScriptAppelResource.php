<?php

namespace App\Filament\NsConseil\Resources;

use App\Filament\NsConseil\Concerns\HasRoleAccess;
use App\Filament\NsConseil\Resources\ScriptAppelResource\Pages\CreateScriptAppel;
use App\Filament\NsConseil\Resources\ScriptAppelResource\Pages\EditScriptAppel;
use App\Filament\NsConseil\Resources\ScriptAppelResource\Pages\ListScriptAppels;
use App\Models\CampagnePhoning;
use App\Models\ScriptAppel;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ScriptAppelResource extends Resource
{
    use HasRoleAccess;

    protected static ?string $model = ScriptAppel::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Scripts d\'appel';

    protected static ?string $navigationGroup = 'Configuration';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'Script d\'appel';

    protected static ?string $pluralModelLabel = 'Scripts d\'appel';

    public static function canAccess(): bool
    {
        return static::userHasAnyRole(['admin', 'superviseur']);
    }

    // ── Formulaire ───────────────────────────────────────────────────
    public static function form(Form $form): Form
    {
        return $form->schema([

            Section::make('Identification')
                ->columns(2)
                ->schema([
                    TextInput::make('titre')
                        ->label('Titre du script')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Select::make('onglet')
                        ->label('Onglet')
                        ->options(ScriptAppel::ONGLETS)
                        ->required()
                        ->native(false),

                    Select::make('type_contact')
                        ->label('Type de contact ciblé')
                        ->options(ScriptAppel::TYPES_CONTACT)
                        ->placeholder('Universel (tous types)')
                        ->nullable()
                        ->native(false)
                        ->helperText('Laisser vide pour un script applicable à tous les types de contact.'),

                    Select::make('campagne_id')
                        ->label('Campagne liée')
                        ->options(fn () => CampagnePhoning::orderBy('nom')->pluck('nom', 'id'))
                        ->searchable()
                        ->nullable()
                        ->native(false)
                        ->placeholder('Aucune — script générique')
                        ->helperText('Si renseigné, ce script n\'apparaît que lors des appels de cette campagne.')
                        ->columnSpanFull(),

                    TextInput::make('ordre')
                        ->label('Ordre d\'affichage')
                        ->numeric()
                        ->default(0)
                        ->minValue(0),

                    Toggle::make('actif')
                        ->label('Script actif')
                        ->default(true)
                        ->inline(false),
                ]),

            Section::make('Contenu principal')
                ->schema([
                    Textarea::make('contenu')
                        ->label('Texte du script')
                        ->rows(6)
                        ->helperText('Supporte le Markdown. Variables disponibles : {contact_nom}, {contact_prenom}, {commercial_nom}')
                        ->columnSpanFull(),

                    Textarea::make('conseil')
                        ->label('Conseil / Tip')
                        ->rows(2)
                        ->placeholder('Ex: Sourire au téléphone, noter le prénom dès l\'annonce...')
                        ->helperText('Affiché en encart jaune sous le script.')
                        ->columnSpanFull(),
                ]),

            Section::make('Objections & Réponses')
                ->description('Uniquement pour l\'onglet "Objections"')
                ->collapsible()
                ->collapsed(fn ($get) => $get('onglet') !== 'objections')
                ->schema([
                    Repeater::make('objections')
                        ->label('Paires Objection / Réponse')
                        ->schema([
                            TextInput::make('question')
                                ->label('Objection')
                                ->required()
                                ->placeholder('Ex: Je n\'ai pas le temps')
                                ->columnSpan(1),
                            Textarea::make('reponse')
                                ->label('Réponse suggérée')
                                ->required()
                                ->rows(2)
                                ->placeholder('Ex: Je comprends, je vous propose 5 minutes chrono...')
                                ->columnSpan(1),
                        ])
                        ->columns(2)
                        ->addActionLabel('+ Ajouter une objection')
                        ->defaultItems(0)
                        ->reorderable()
                        ->cloneable(),
                ]),

            Section::make('KPIs / Chiffres clés')
                ->description('Uniquement pour l\'onglet "Argumentaire" — affichés sous forme de cartes')
                ->collapsible()
                ->collapsed(fn ($get) => $get('onglet') !== 'argumentaire')
                ->schema([
                    Repeater::make('kpis')
                        ->label('Indicateurs')
                        ->schema([
                            TextInput::make('valeur')
                                ->label('Valeur')
                                ->required()
                                ->placeholder('Ex: 94%'),
                            TextInput::make('label')
                                ->label('Libellé')
                                ->required()
                                ->placeholder('Ex: de clients satisfaits'),
                            Select::make('couleur')
                                ->label('Couleur')
                                ->options([
                                    'purple' => 'Violet',
                                    'blue' => 'Bleu',
                                    'green' => 'Vert',
                                    'orange' => 'Orange',
                                ])
                                ->default('purple')
                                ->native(false),
                        ])
                        ->columns(3)
                        ->addActionLabel('+ Ajouter un KPI')
                        ->defaultItems(0)
                        ->reorderable(),
                ]),

            Section::make('Variables disponibles')
                ->description('Documentation des variables utilisables dans le contenu')
                ->collapsible()
                ->collapsed()
                ->schema([
                    Repeater::make('variables_disponibles')
                        ->label('Variables')
                        ->schema([
                            TextInput::make('cle')
                                ->label('Clé (sans accolades)')
                                ->placeholder('Ex: contact_nom'),
                            TextInput::make('description')
                                ->label('Description')
                                ->placeholder('Ex: Nom du contact courant'),
                        ])
                        ->columns(2)
                        ->addActionLabel('+ Documenter une variable')
                        ->defaultItems(0),
                ]),
        ]);
    }

    // ── Table ────────────────────────────────────────────────────────
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('titre')
                    ->label('Titre')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                BadgeColumn::make('onglet')
                    ->label('Onglet')
                    ->colors([
                        'primary' => 'accroche',
                        'success' => 'decouverte',
                        'warning' => 'argumentaire',
                        'danger' => 'objections',
                        'secondary' => 'closing',
                    ])
                    ->formatStateUsing(fn ($state) => ScriptAppel::ONGLETS[$state] ?? $state),

                BadgeColumn::make('type_contact')
                    ->label('Type contact')
                    ->colors(['gray' => null, 'info' => fn ($state) => $state !== null])
                    ->formatStateUsing(fn ($state) => $state ? (ScriptAppel::TYPES_CONTACT[$state] ?? $state) : 'Universel'),

                TextColumn::make('campagne.nom')
                    ->label('Campagne')
                    ->placeholder('Générique')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                TextColumn::make('contenu')
                    ->label('Aperçu')
                    ->limit(60)
                    ->color('gray'),

                ToggleColumn::make('actif')
                    ->label('Actif')
                    ->sortable(),

                TextColumn::make('ordre')
                    ->label('Ordre')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('updated_at')
                    ->label('Modifié le')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('ordre')
            ->filters([
                SelectFilter::make('onglet')
                    ->options(ScriptAppel::ONGLETS)
                    ->label('Onglet'),

                SelectFilter::make('type_contact')
                    ->options(array_merge(['_universal' => 'Universel'], ScriptAppel::TYPES_CONTACT))
                    ->label('Type de contact')
                    ->query(function ($query, $data) {
                        if ($data['value'] === '_universal') {
                            $query->whereNull('type_contact');
                        } elseif ($data['value']) {
                            $query->where('type_contact', $data['value']);
                        }
                    }),

                Tables\Filters\TernaryFilter::make('actif')
                    ->label('Statut'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ReplicateAction::make()
                    ->label('Dupliquer')
                    ->beforeReplicaSaved(function (ScriptAppel $replica) {
                        $replica->titre = 'Copie de '.$replica->titre;
                        $replica->slug = null; // sera regénéré
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('seeder')
                    ->label('Charger scripts par défaut')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->action(fn () => ScriptAppel::seederDefaut())
                    ->successNotificationTitle('Scripts par défaut chargés'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListScriptAppels::route('/'),
            'create' => CreateScriptAppel::route('/create'),
            'edit' => EditScriptAppel::route('/{record}/edit'),
        ];
    }
}
