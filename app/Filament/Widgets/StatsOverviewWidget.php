<?php

namespace App\Filament\Widgets;

use App\Models\Category;
use App\Models\Enterprise;
use App\Models\Menu;
use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Produits retail', Product::where('is_retail', true)->count())
                ->description('En vente en boutique')
                ->color('success'),
            Stat::make('Produits entreprise', Product::where('is_enterprise', true)->count())
                ->description('Disponibles pour entreprises')
                ->color('info'),
            Stat::make('Menus', Menu::where('type', 'menu')->count())
                ->description('Formules actives')
                ->color('warning'),
            Stat::make('Entreprises partenaires', Enterprise::count())
                ->description('Clients entreprise')
                ->color('danger'),
        ];
    }
}
