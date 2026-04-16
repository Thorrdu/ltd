<?php

namespace App\Filament\Armurerie\Resources;

use App\Filament\Armurerie\Resources\SaleResource\Pages;
use App\Models\Sale;
use App\Models\StockItem;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-banknotes';

    protected static string | \UnitEnum | null $navigationGroup = 'Finance';

    protected static ?string $modelLabel = 'Vente';

    protected static ?string $pluralModelLabel = 'Ventes';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label('Date')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('stockItem.name')->label('Article')->searchable(),
                TextColumn::make('stockItem.category')->label('Catégorie')
                    ->badge()
                    ->color(fn (string $state): string => StockItem::CATEGORY_COLORS[$state] ?? 'gray')
                    ->formatStateUsing(fn (string $state) => StockItem::CATEGORIES[$state] ?? $state),
                TextColumn::make('quantity')->label('Qté'),
                TextColumn::make('unit_price')->label('Prix unit.')->money('usd', divideBy: 1),
                TextColumn::make('total_price')->label('Total')
                    ->money('usd', divideBy: 1)
                    ->weight('bold')
                    ->color('success'),
                TextColumn::make('buyer_name')->label('Acheteur')->searchable(),
                TextColumn::make('contract.name')->label('Contrat')->placeholder('—'),
                TextColumn::make('soldBy.name')->label('Vendu par')->placeholder('—'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('stockItem')
                    ->label('Article')
                    ->relationship('stockItem', 'name'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSales::route('/'),
        ];
    }
}
