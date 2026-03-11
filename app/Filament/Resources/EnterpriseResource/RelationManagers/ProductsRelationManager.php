<?php

namespace App\Filament\Resources\EnterpriseResource\RelationManagers;

use Filament\Actions;
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
                ->options(\App\Models\Product::where('is_enterprise', true)->pluck('name', 'id'))
                ->searchable()
                ->required(),
            Forms\Components\TextInput::make('price')
                ->label('Prix spécifique')
                ->numeric()
                ->nullable()
                ->suffix('€')
                ->helperText('Laisser vide pour utiliser le prix entreprise général du produit.'),
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
                Tables\Columns\TextColumn::make('enterprise_price')->label('Prix général')
                    ->formatStateUsing(fn ($state) => $state !== null ? number_format($state, 0, ',', ' ') . ' €' : '—')
                    ->color('gray'),
                Tables\Columns\TextColumn::make('pivot.price')->label('Prix spécifique')
                    ->placeholder('— (général)')
                    ->formatStateUsing(fn ($state) => $state !== null ? number_format($state, 0, ',', ' ') . ' €' : null)
                    ->sortable(),
                Tables\Columns\TextColumn::make('effective_price')->label('Prix effectif')
                    ->state(fn ($record) => $record->pivot->price ?? $record->enterprise_price ?? $record->price)
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', ' ') . ' €')
                    ->weight('bold')
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')->label('Prix retail')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', ' ') . ' €')
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->headerActions([Actions\AttachAction::make()->preloadRecordSelect()
                ->recordSelectOptionsQuery(fn ($query) => $query->where('is_enterprise', true))
                ->form(fn (Actions\AttachAction $action): array => [
                    $action->getRecordSelect(),
                    Forms\Components\TextInput::make('price')->label('Prix spécifique')->numeric()->nullable()->suffix('€')
                        ->helperText('Laisser vide pour utiliser le prix entreprise général du produit.'),
                    Forms\Components\TextInput::make('sort_order')->label('Ordre')->numeric()->default(0),
                ])])
            ->actions([
                Actions\EditAction::make()
                    ->form([
                        Forms\Components\TextInput::make('price')
                            ->label('Prix spécifique')
                            ->numeric()
                            ->nullable()
                            ->suffix('€')
                            ->helperText('Laisser vide pour utiliser le prix entreprise général du produit.'),
                        Forms\Components\TextInput::make('sort_order')
                            ->label('Ordre')
                            ->numeric()
                            ->default(0),
                    ]),
                Actions\DetachAction::make(),
            ])
            ->bulkActions([Actions\BulkActionGroup::make([Actions\DetachBulkAction::make()])]);
    }
}
