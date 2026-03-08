<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Enterprise;
use App\Models\Menu;

class PageController extends Controller
{
    public function home()
    {
        return view('welcome');
    }

    public function produits()
    {
        $leftCategories = Category::left()
            ->with(['products' => fn ($q) => $q->where('is_retail', true)->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();

        $rightCategories = Category::right()
            ->with(['products' => fn ($q) => $q->where('is_retail', true)->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();

        return view('produits', compact('leftCategories', 'rightCategories'));
    }

    public function menus()
    {
        $menus = Menu::with(['products' => fn ($q) => $q->orderByPivot('sort_order')])
            ->orderBy('sort_order')
            ->get();

        return view('menus', compact('menus'));
    }

    public function entreprises()
    {
        $enterprises = Enterprise::with(['products' => fn ($q) => $q->orderByPivot('sort_order')])
            ->orderBy('sort_order')
            ->get();

        return view('entreprises', compact('enterprises'));
    }
}
