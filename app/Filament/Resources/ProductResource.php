<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use App\Models\Category;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cube';
    protected static string | \UnitEnum | null $navigationGroup = 'Catalogue';
    protected static ?string $modelLabel = 'Produit';
    protected static ?string $pluralModelLabel = 'Produits';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Select::make('category_id')
                ->label('Catégorie')
                ->relationship('category', 'name')
                ->required()
                ->searchable()
                ->preload(),
            Forms\Components\TextInput::make('name')
                ->label('Nom')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('purchase_price')
                ->label('Prix d\'achat')
                ->numeric()
                ->nullable()
                ->suffix('€')
                ->helperText('Prix auquel LTD achete le produit'),
            Forms\Components\TextInput::make('usual_price')
                ->label('Prix habituel')
                ->numeric()
                ->nullable()
                ->suffix('€')
                ->helperText('Prix du marche / prix concurrent'),
            Forms\Components\TextInput::make('price')
                ->label('Prix de vente')
                ->numeric()
                ->required()
                ->suffix('€'),
            Forms\Components\Toggle::make('is_retail')
                ->label('Disponible en boutique (retail)')
                ->default(true),
            Forms\Components\Toggle::make('is_enterprise')
                ->label('Disponible pour entreprises')
                ->default(false),
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
                Tables\Columns\TextColumn::make('name')->label('Nom')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('purchase_price')->label('Achat')->sortable()
                    ->formatStateUsing(fn ($state) => $state !== null ? number_format($state, 0, ',', ' ') . ' €' : '—')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('usual_price')->label('Habituel')->sortable()
                    ->formatStateUsing(fn ($state) => $state !== null ? number_format($state, 0, ',', ' ') . ' €' : '—')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('price')->label('Vente')->sortable()
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', ' ') . ' €'),
                Tables\Columns\TextColumn::make('category.name')->label('Catégorie')->sortable()->badge(),
                Tables\Columns\IconColumn::make('is_retail')->label('Retail')->boolean(),
                Tables\Columns\IconColumn::make('is_enterprise')->label('Entreprise')->boolean(),
                Tables\Columns\TextColumn::make('sort_order')->label('Ordre')->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Catégorie')
                    ->relationship('category', 'name'),
                Tables\Filters\TernaryFilter::make('is_retail')->label('Retail'),
                Tables\Filters\TernaryFilter::make('is_enterprise')->label('Entreprise'),
            ])
            ->actions([Actions\EditAction::make()])
            ->bulkActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('category_id');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
