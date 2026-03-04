<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestProductsWidget extends BaseWidget
{
    protected static ?string $heading = 'Derniers produits modifiés';
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(Product::query()->latest('updated_at')->limit(5))
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nom'),
                Tables\Columns\TextColumn::make('price')->label('Prix')
                    ->formatStateUsing(fn ($state) => number_format($state, 0, ',', ' ') . ' €'),
                Tables\Columns\TextColumn::make('category.name')->label('Catégorie')->badge(),
                Tables\Columns\TextColumn::make('updated_at')->label('Modifié le')->dateTime('d/m/Y H:i'),
            ])
            ->paginated(false);
    }
}
