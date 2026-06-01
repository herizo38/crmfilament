<?php

namespace App\Filament\Allopro\Resources\TicketResource\RelationManagers;

use App\Filament\Allopro\Resources\FicheP2Resource;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class FicheP2RelationManager extends RelationManager
{
    protected static string $relationship = 'ficheP2';

    protected static ?string $title = 'Fiches Diagnostic P2';

    public function form(Form $form): Form
    {
        // On clone la structure exacte déjà définie dans la ressource principale
        return FicheP2Resource::form($form);
    }

    public function table(Table $table): Table
    {
        return FicheP2Resource::table($table)
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Créer une fiche P2'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }
}
