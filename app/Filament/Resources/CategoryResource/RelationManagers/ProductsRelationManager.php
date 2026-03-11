<?php
namespace App\Filament\Resources\CategoryResource\RelationManagers;

use Filament\Actions;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';
    protected static ?string $title = 'Produits';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('name')->label('Nom')->required()->maxLength(255),
            Forms\Components\TextInput::make('purchase_price')->label('Prix d\'achat')->numeric()->nullable()->suffix('€'),
            Forms\Components\TextInput::make('usual_price')->label('Prix habituel')->numeric()->nullable()->suffix('€'),
            Forms\Components\TextInput::make('price')->label('Prix de vente')->numeric()->required()->suffix('€'),
            Forms\Components\Toggle::make('is_retail')->label('Retail')->default(true),
            Forms\Components\Toggle::make('is_enterprise')->label('Entreprise')->default(false),
            Forms\Components\TextInput::make('enterprise_price')->label('Prix entreprise (général)')->numeric()->nullable()->suffix('€')
                ->helperText('Prix par défaut pour toutes les entreprises.'),
            Forms\Components\TextInput::make('sort_order')->label('Ordre')->numeric()->default(0),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nom')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('purchase_price')->label('Achat')->sortable()
                    ->formatStateUsing(fn ($state) => $state !== null ? number_format($state, 0, ',', ' ') . ' €' : '—'),
                Tables\Columns\TextColumn::make('usual_price')->label('Habituel')->sortable()
                    ->formatStateUsing(fn ($state) => $state !== null ? number_format($state, 0, ',', ' ') . ' €' : '—'),
                Tables\Columns\TextColumn::make('price')->label('Vente')->sortable()
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', ' ') . ' €'),
                Tables\Columns\IconColumn::make('is_retail')->label('Retail')->boolean(),
                Tables\Columns\IconColumn::make('is_enterprise')->label('Entreprise')->boolean(),
                Tables\Columns\TextColumn::make('sort_order')->label('Ordre')->sortable(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->headerActions([Actions\CreateAction::make()])
            ->actions([Actions\EditAction::make(), Actions\DeleteAction::make()])
            ->bulkActions([Actions\BulkActionGroup::make([Actions\DeleteBulkAction::make()])]);
    }
}
