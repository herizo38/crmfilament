<?php

namespace App\Filament\Allopro\Resources;

use App\Filament\Allopro\Resources\ContactPartenaireResource\Pages;
use App\Models\ContactPartenaire;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ContactPartenaireResource extends Resource
{
    protected static ?string $model = ContactPartenaire::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'AlloPro 24/24';

    protected static ?string $modelLabel = 'Contact Partenaire';

    protected static ?string $pluralModelLabel = 'Contacts Partenaires';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make(3)
                    ->schema([
                        // Colonne de gauche : Identité & Coordonnées (Prend 2 colonnes)
                        Forms\Components\Grid::make(1)
                            ->columnSpan(2)
                            ->schema([
                                Forms\Components\Section::make('Identité & Poste')
                                    ->schema([
                                        Forms\Components\Select::make('partenaire_id')
                                            ->relationship('partenaire', 'nom')
                                            ->label('Entreprise / Partenaire')
                                            ->searchable()
                                            ->preload()
                                            ->required(),

                                        Forms\Components\Grid::make(3)
                                            ->schema([
                                                Forms\Components\TextInput::make('civilite')
                                                    ->label('Civilité')
                                                    ->maxLength(50),
                                                Forms\Components\TextInput::make('nom')
                                                    ->label('Nom')
                                                    ->required()
                                                    ->maxLength(255),
                                                Forms\Components\TextInput::make('prenom')
                                                    ->label('Prénom')
                                                    ->required()
                                                    ->maxLength(255),
                                            ]),

                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('fonction')
                                                    ->label('Fonction / Poste')
                                                    ->maxLength(255),
                                                Forms\Components\TextInput::make('service')
                                                    ->label('Service')
                                                    ->maxLength(255),
                                            ]),
                                    ]),

                                Forms\Components\Section::make('Coordonnées (Professionnelles & Personnelles)')
                                    ->schema([
                                        Forms\Components\Grid::make(2)
                                            ->schema([
                                                Forms\Components\TextInput::make('email')
                                                    ->label('Email Professionnel')
                                                    ->email()
                                                    ->maxLength(255),
                                                Forms\Components\TextInput::make('email_perso')
                                                    ->label('Email Personnel')
                                                    ->email()
                                                    ->maxLength(255),
                                                Forms\Components\TextInput::make('telephone_direct')
                                                    ->label('Téléphone Direct')
                                                    ->tel()
                                                    ->maxLength(255),
                                                Forms\Components\TextInput::make('telephone_mobile')
                                                    ->label('Téléphone Mobile')
                                                    ->tel()
                                                    ->maxLength(255),
                                                Forms\Components\TextInput::make('telephone_perso')
                                                    ->label('Téléphone Personnel')
                                                    ->tel()
                                                    ->maxLength(255),
                                                Forms\Components\DatePicker::make('date_naissance')
                                                    ->label('Date de naissance'),
                                            ]),
                                    ]),
                            ]),

                        // Colonne de droite : Profil, Décision & Notes (Prend 1 colonne)
                        Forms\Components\Grid::make(1)
                            ->columnSpan(1)
                            ->schema([
                                Forms\Components\Section::make('Rôle & Influence')
                                    ->schema([
                                        Forms\Components\Toggle::make('est_principal')
                                            ->label('Contact Principal')
                                            ->helperText('Premier point de contact de l\'entreprise')
                                            ->default(false),

                                        Forms\Components\Toggle::make('est_decisionnaire')
                                            ->label('Décisionnaire')
                                            ->default(false),

                                        Forms\Components\Select::make('niveau_influence')
                                            ->label('Niveau d\'influence')
                                            ->options([
                                                1 => 'Faible',
                                                2 => 'Moyen',
                                                3 => 'Fort',
                                                4 => 'Très fort',
                                                5 => 'Décisionnaire',
                                            ])
                                            ->default(null),

                                        Forms\Components\TextInput::make('canal_prefere')
                                            ->label('Canal Préféré')
                                            ->placeholder('ex: Email, Mobile, WhatsApp')
                                            ->maxLength(255),
                                    ]),

                                Forms\Components\Section::make('Notes & Commentaires')
                                    ->schema([
                                        Forms\Components\Textarea::make('notes')
                                            ->label('Historique des notes')
                                            ->rows(6)
                                            ->placeholder('Les nouvelles notes via l\'action dédiée s\'ajouteront à la suite.'),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nom_affichage')
                    ->label('Nom complet')
                    ->searchable(['nom', 'prenom'])
                    ->sortable(['nom']),
                Tables\Columns\TextColumn::make('partenaire.nom')
                    ->label('Partenaire')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('fonction_complete')
                    ->label('Poste / Service')
                    ->state(fn(ContactPartenaire $record): string => $record->fonction_complete),
                Tables\Columns\TextColumn::make('telephone_principal')
                    ->label('Téléphone')
                    ->state(fn(ContactPartenaire $record): string => $record->telephone_principal),
                Tables\Columns\TextColumn::make('email_principal')
                    ->label('Email')
                    ->state(fn(ContactPartenaire $record): string => $record->email_principal)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('est_principal')
                    ->label('Principal')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->trueColor('success')
                    ->falseIcon('heroicon-o-x-mark')
                    ->falseColor('gray'),
                Tables\Columns\TextColumn::make('niveau_influence')
                    ->label('Influence')
                    ->badge()
                    ->state(fn(ContactPartenaire $record): string => $record->niveau_influence_label)
                    ->color(fn(ContactPartenaire $record): string => $record->niveau_influence_color),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('est_principal')
                    ->label('Contact Principal')
                    ->placeholder('Tous les contacts')
                    ->trueLabel('Uniquement les principaux')
                    ->falseLabel('Masquer les principaux'),

                Tables\Filters\TernaryFilter::make('est_decisionnaire')
                    ->label('Décisionnaire')
                    ->placeholder('Tous')
                    ->trueLabel('Oui')
                    ->falseLabel('Non'),

                Tables\Filters\SelectFilter::make('niveau_influence')
                    ->label('Niveau d\'influence')
                    ->options([
                        1 => 'Faible',
                        2 => 'Moyen',
                        3 => 'Fort',
                        4 => 'Très fort',
                        5 => 'Décisionnaire',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                // Action personnalisée pour ajouter rapidement une note historique sans modifier toute la fiche
                Tables\Actions\Action::make('ajouterNote')
                    ->label('Ajouter une note')
                    ->icon('heroicon-o-chat-bubble-bottom-center-text')
                    ->color('info')
                    ->form([
                        Forms\Components\Textarea::make('nouvelle_note')
                            ->label('Contenu de la note')
                            ->required(),
                    ])
                    ->action(function (ContactPartenaire $record, array $data): void {
                        $record->ajouterNote($data['nouvelle_note']);
                    }),
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
            'index' => Pages\ListContactPartenaires::route('/'),
            'create' => Pages\CreateContactPartenaire::route('/create'),
            'edit' => Pages\EditContactPartenaire::route('/{record}/edit'),
        ];
    }
}
