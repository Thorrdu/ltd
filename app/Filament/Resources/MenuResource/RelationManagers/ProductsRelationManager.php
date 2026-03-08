<?php
namespace App\Filament\Resources\MenuResource\RelationManagers;

use Filament\Actions;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';
    protected static ?string $title = 'Produits du menu';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Select::make('recordId')
                ->label('Produit')
                ->options(\App\Models\Product::pluck('name', 'id'))
                ->searchable()
                ->required(),
            Forms\Components\TextInput::make('choice_group')
                ->label('Groupe de choix')
                ->helperText('Ex: "fruit" pour regrouper des produits interchangeables')
                ->maxLength(255),
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
                Tables\Columns\TextColumn::make('pivot.choice_group')->label('Groupe de choix')
                    ->placeholder('—')->badge()->color('info'),
                Tables\Columns\TextColumn::make('pivot.sort_order')->label('Ordre')->sortable(),
            ])
            ->headerActions([Actions\AttachAction::make()->preloadRecordSelect()
                ->form(fn (Actions\AttachAction $action): array => [
                    $action->getRecordSelect(),
                    Forms\Components\TextInput::make('choice_group')->label('Groupe de choix'),
                    Forms\Components\TextInput::make('sort_order')->label('Ordre')->numeric()->default(0),
                ])])
            ->actions([Actions\DetachAction::make()])
            ->bulkActions([Actions\BulkActionGroup::make([Actions\DetachBulkAction::make()])]);
    }
}
