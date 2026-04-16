<?php

namespace App\Filament\Armurerie\Widgets;

use App\Models\Sale;
use App\Models\StockItem;
use App\Models\WeaponContract;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ArmurerieStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $finishedWeapons = StockItem::where('category', 'weapon_finished')->sum('quantity');
        $pendingContracts = WeaponContract::whereIn('status', ['pending', 'in_progress'])->count();
        $totalRevenue = Sale::query()
            ->whereHas('stockItem', fn ($q) => $q->where('category', 'weapon_finished'))
            ->sum('total_price');
        $lowStock = StockItem::where('quantity', '<=', 0)
            ->whereIn('category', ['weapon_piece', 'raw_material'])
            ->count();

        return [
            Stat::make('Armes en stock', $finishedWeapons)
                ->icon('heroicon-o-wrench-screwdriver')
                ->color('success'),
            Stat::make('Contrats en cours', $pendingContracts)
                ->icon('heroicon-o-document-text')
                ->color('warning'),
            Stat::make('Revenus armes', '$' . number_format($totalRevenue, 0, ',', ' '))
                ->icon('heroicon-o-banknotes')
                ->color('success'),
            Stat::make('Matières à 0', $lowStock)
                ->icon('heroicon-o-exclamation-triangle')
                ->color($lowStock > 0 ? 'danger' : 'success')
                ->description($lowStock > 0 ? 'Réapprovisionnement nécessaire' : 'Stock OK'),
        ];
    }
}
