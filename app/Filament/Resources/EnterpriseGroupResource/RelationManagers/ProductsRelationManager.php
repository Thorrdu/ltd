<?php
namespace App\Filament\Resources\EnterpriseGroupResource\RelationManagers;

use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';
    protected static ?string $title = 'Produits entreprise';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Select::make('recordId')
                ->label('Produit')
                ->options(\App\Models\Product::pluck('name', 'id'))
                ->searchable()
                ->required(),
            Forms\Components\TextInput::make('price')
                ->label('Prix entreprise')
                ->numeric()
                ->required()
                ->suffix('€'),
            Forms\Components\TextInput::make('sort_order')
                ->label('Ordre')
                ->numeric()
                ->default(0),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Produit')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('pivot.price')->label('Prix entreprise')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', ' ') . ' €')->sortable(),
                Tables\Columns\TextColumn::make('price')->label('Prix retail')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', ' ') . ' €'),
                Tables\Columns\TextColumn::make('pivot.sort_order')->label('Ordre')->sortable(),
            ])
            ->defaultSort('pivot.sort_order')
            ->headerActions([Tables\Actions\AttachAction::make()->preloadRecordSelect()
                ->form(fn (Tables\Actions\AttachAction $action): array => [
                    $action->getRecordSelect(),
                    Forms\Components\TextInput::make('price')->label('Prix entreprise')->numeric()->required()->suffix('€'),
                    Forms\Components\TextInput::make('sort_order')->label('Ordre')->numeric()->default(0),
                ])])
            ->actions([Tables\Actions\DetachAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DetachBulkAction::make()])]);
    }
}
