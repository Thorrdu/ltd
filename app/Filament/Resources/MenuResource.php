<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MenuResource\Pages;
use App\Filament\Resources\MenuResource\RelationManagers;
use App\Models\Menu;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Tables;
use Filament\Tables\Table;

class MenuResource extends Resource
{
    protected static ?string $model = Menu::class;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static string | \UnitEnum | null $navigationGroup = 'Catalogue';
    protected static ?string $modelLabel = 'Menu';
    protected static ?string $pluralModelLabel = 'Menus';
    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Select::make('type')
                ->label('Type')
                ->options(['menu' => 'Menu', 'promo' => 'Promotion'])
                ->required()
                ->live(),
            Forms\Components\TextInput::make('name')
                ->label('Nom')
                ->maxLength(255)
                ->visible(fn (Get $get) => $get('type') === 'menu'),
            Forms\Components\TextInput::make('price')
                ->label('Prix')
                ->numeric()
                ->suffix('€')
                ->visible(fn (Get $get) => $get('type') === 'menu'),
            Forms\Components\TextInput::make('promo_text')
                ->label('Texte promotionnel')
                ->maxLength(255)
                ->visible(fn (Get $get) => $get('type') === 'promo'),
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
                Tables\Columns\TextColumn::make('type')->label('Type')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'menu' => 'success',
                        'promo' => 'warning',
                    }),
                Tables\Columns\TextColumn::make('name')->label('Nom')->searchable()->sortable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('price')->label('Prix')->sortable()
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 0, ',', ' ') . ' €' : '—'),
                Tables\Columns\TextColumn::make('promo_text')->label('Promo')->limit(50)
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('products_count')->label('Produits')
                    ->counts('products'),
                Tables\Columns\TextColumn::make('sort_order')->label('Ordre')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('Type')
                    ->options(['menu' => 'Menu', 'promo' => 'Promotion']),
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
            'index' => Pages\ListMenus::route('/'),
            'create' => Pages\CreateMenu::route('/create'),
            'edit' => Pages\EditMenu::route('/{record}/edit'),
        ];
    }
}
