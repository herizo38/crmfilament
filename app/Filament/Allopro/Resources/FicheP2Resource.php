<?php

namespace App\Filament\Allopro\Resources;

use App\Enums\AncienneteProbleme;
use App\Enums\CorpsDeMetier;
use App\Enums\NiveauPriorite;
use App\Enums\StatutOccupant;
use App\Enums\TypeLogement;
use App\Filament\Allopro\Resources\FicheP2Resource\Pages;
use App\Models\FicheP2;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class FicheP2Resource extends Resource
{
    protected static ?string $model = FicheP2::class;

    protected static ?string $navigationIcon  = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Fiches P2';
    protected static ?string $navigationGroup = 'Tickets';
    protected static ?int $navigationSort     = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            // ─── BLOC TICKET ───
            Forms\Components\Section::make('Ticket Associé')
                ->icon('heroicon-o-ticket')
                ->schema([
                    Forms\Components\Select::make('ticket_id')
                        ->label('Ticket')
                        ->relationship('ticket', 'reference')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->disabledOn('edit'),
                ]),

            // ─── BLOC QUALIFICATION DU PROBLÈME ───
            Forms\Components\Section::make('Qualification de la panne')
                ->icon('heroicon-o-wrench-screwdriver')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('corps_de_metier')
                        ->label('Corps de métier')
                        ->options(CorpsDeMetier::pourSelect())
                        ->native(false)
                        ->required()
                        ->live(), // 🔑 Crucial pour recharger les questions métiers en temps réel

                    Forms\Components\TextInput::make('nature_probleme')
                        ->label('Nature du problème')
                        ->placeholder('Ex: Fuite sous évier, Panne totale de courant...')
                        ->required(),

                    Forms\Components\Textarea::make('description_detaillee')
                        ->label('Description détaillée')
                        ->placeholder('Minimum 30 caractères...')
                        ->rows(4)
                        ->required()
                        ->minLength(30)
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('localisation_precise')
                        ->label('Localisation précise dans le logement')
                        ->placeholder('Ex: Cuisine au fond à droite, Cave sous l\'escalier')
                        ->required(),

                    Forms\Components\Select::make('anciennete_probleme')
                        ->label('Ancienneté du problème')
                        ->options(collect(AncienneteProbleme::cases())->mapWithKeys(fn($e) => [$e->value => $e->label()]))
                        ->native(false)
                        ->required(),
                ]),

            // ─── QUESTIONS DYNAMIQUES MÉTIERS ───
            Forms\Components\Section::make('Questions spécifiques au métier')
                ->icon('heroicon-o-question-mark-circle')
                ->description('Ces champs dépendent du corps de métier sélectionné.')
                ->visible(fn (Get $get) => filled($get('corps_de_metier')))
                ->schema(function (Get $get) {
                    $metierValue = $get('corps_de_metier');
                    $metierEnum = CorpsDeMetier::tryFrom($metierValue);

                    if (! $metierEnum) return [];

                    $questions = $metierEnum->questionsMetier();
                    $inputs = [];

                    foreach ($questions as $key => $label) {
                        // Stocké proprement dans le JSON reponses_metier->key
                        $inputs[] = Forms\Components\TextInput::make("reponses_metier.{$key}")
                            ->label($label)
                            ->required();
                    }

                    return $inputs;
                }),

            // ─── BLOC CRITICITÉ & PRIORITÉ ───
            Forms\Components\Section::make('Priorité & Urgence')
                ->icon('heroicon-o-exclamation-triangle')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('niveau_priorite')
                        ->label('Niveau de priorité')
                        ->options(collect(NiveauPriorite::cases())->mapWithKeys(fn($e) => [$e->value => $e->label()]))
                        ->native(false)
                        ->required(),

                    Forms\Components\TextInput::make('justificatif_priorite')
                        ->label('Justificatif de la priorité')
                        ->placeholder('Pourquoi cette urgence ? (Ex: Personne âgée, Nourrisson...)')
                        ->required(),
                ]),

            // ─── BLOC CLIENT & LOGEMENT ───
            Forms\Components\Section::make('Informations Client & Intervention')
                ->icon('heroicon-o-user')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('nom_client')
                        ->label('Nom du client')
                        ->required(),

                    Forms\Components\TextInput::make('telephone_client')
                        ->label('Téléphone du client')
                        ->tel()
                        ->required(),

                    Forms\Components\Textarea::make('adresse_intervention')
                        ->label("Adresse complète d'intervention")
                        ->rows(2)
                        ->required()
                        ->columnSpanFull(),

                    Forms\Components\Select::make('type_logement')
                        ->label('Type de logement')
                        ->options(collect(TypeLogement::cases())->mapWithKeys(fn($e) => [$e->value => $e->label()]))
                        ->native(false)
                        ->required(),

                    Forms\Components\Select::make('statut_occupant')
                        ->label('Statut de l\'occupant')
                        ->options(collect(StatutOccupant::cases())->mapWithKeys(fn($e) => [$e->value => $e->label()]))
                        ->native(false)
                        ->required(),

                    Forms\Components\Toggle::make('presence_client')
                        ->label('Le client sera-t-il présent ?')
                        ->default(true)
                        ->inline(false),

                    Forms\Components\Toggle::make('fiche_complete')
                        ->label('Fiche complétée et conforme')
                        ->disabled() // Géré automatiquement par le Booted de votre modèle
                        ->dehydrated(false)
                        ->inline(false),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ticket.reference')
                    ->label('Ticket')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('corps_de_metier')
                    ->label('Métier')
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('nature_probleme')
                    ->label('Nature')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('niveau_priorite')
                    ->label('Priorité')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state instanceof NiveauPriorite ? $state->label() : $state)
                    ->color(fn ($state) => $state instanceof NiveauPriorite ? $state->color() : 'gray')
                    ->icon(fn ($state) => $state instanceof NiveauPriorite ? $state->icon() : null)
                    ->sortable(),

                Tables\Columns\TextColumn::make('nom_client')
                    ->label('Client')
                    ->searchable()
                    ->description(fn ($record) => $record->telephone_client),

                Tables\Columns\IconColumn::make('fiche_complete')
                    ->label('Complète')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créée le')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('corps_de_metier')
                    ->label('Corps de métier')
                    ->options(CorpsDeMetier::pourSelect()),

                Tables\Filters\SelectFilter::make('niveau_priorite')
                    ->label('Priorité')
                    ->options(collect(NiveauPriorite::cases())->mapWithKeys(fn($e) => [$e->value => $e->label()])),

                Tables\Filters\TernaryFilter::make('fiche_complete')
                    ->label('Statut de complétude'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index'  => Pages\ListFicheP2s::route('/'),
            'create' => Pages\CreateFicheP2::route('/create'),
            'view'   => Pages\ViewFicheP2::route('/{record}'),
            'edit'   => Pages\EditFicheP2::route('/{record}/edit'),
        ];
    }
}
