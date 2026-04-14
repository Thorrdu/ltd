<?php

namespace App\Filament\Armurerie\Resources\WeaponContractResource\RelationManagers;

use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Armes du contrat';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('weapon_id')->label('Arme')
                ->relationship('weapon', 'name')
                ->required(),
            TextInput::make('qty_ordered')->label('Qté commandée')
                ->numeric()->required()->minValue(1),
            TextInput::make('qty_delivered')->label('Qté livrée')
                ->numeric()->default(0)->minValue(0),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('weapon.name')->label('Arme'),
                TextColumn::make('qty_ordered')->label('Commandé'),
                TextColumn::make('qty_delivered')->label('Livré'),
                TextColumn::make('remaining')->label('Restant')
                    ->color(fn ($record) => $record->remaining > 0 ? 'danger' : 'success')
                    ->weight('bold'),
            ])
            ->headerActions([
                Actions\CreateAction::make(),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ]);
    }
}
