<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EnterpriseGroupResource\Pages;
use App\Filament\Resources\EnterpriseGroupResource\RelationManagers;
use App\Models\EnterpriseGroup;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class EnterpriseGroupResource extends Resource
{
    protected static ?string $model = EnterpriseGroup::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-building-office';
    protected static string | \UnitEnum | null $navigationGroup = 'Entreprises';
    protected static ?string $modelLabel = 'Groupe entreprise';
    protected static ?string $pluralModelLabel = 'Groupes entreprise';
    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('name')
                ->label('Nom')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('sort_order')
                ->label('Ordre')
                ->numeric()
                ->default(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Entreprise')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('products_count')->label('Produits')
                    ->counts('products')->sortable(),
                Tables\Columns\TextColumn::make('sort_order')->label('Ordre')->sortable(),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])])
            ->defaultSort('sort_order')
            ->reorderable('sort_order');
    }

    public static function getRelations(): array
    {
        return [RelationManagers\ProductsRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEnterpriseGroups::route('/'),
            'create' => Pages\CreateEnterpriseGroup::route('/create'),
            'edit' => Pages\EditEnterpriseGroup::route('/{record}/edit'),
        ];
    }
}
