@extends('layouts.app')

@section('title', 'Station LTD - Menu Produits')
@section('css')
<link rel="stylesheet" href="{{ asset('css/produits.css') }}">
@endsection

@section('content')
<div class="menu-board" id="menuBoard">
    <div class="inner-board">
        <div class="header">
            <div class="main-title">
                <img src="{{ asset('img/logo-ltd.png') }}" alt="LTD" class="main-title-logo">
                <h2>STATION LTD - PRODUITS</h2>
            </div>
        </div>

        <div class="categories-grid">
            @foreach($leftCategories as $category)
            @if($category->products->count())
            <div class="category-block" data-col="left">
                <div class="category-header"><h3>{{ $category->name }}</h3></div>
                <ul class="product-list">
                    @foreach($category->products as $product)
                    <li class="product-row{{ $product->promo_price !== null ? ' has-promo' : '' }}">
                        <span class="product-name">{{ $product->name }}</span>
                        <span class="dot-leader"></span>
                        @if($product->promo_price !== null)
                        <span class="product-price-old">{{ number_format($product->price, 0, ',', ' ') }} &euro;</span>
                        <span class="product-price promo">{{ number_format($product->promo_price, 0, ',', ' ') }} &euro;</span>
                        @else
                        <span class="product-price">{{ number_format($product->price, 0, ',', ' ') }} &euro;</span>
                        @endif
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif
            @endforeach
            @foreach($rightCategories as $category)
            @if($category->products->count())
            <div class="category-block" data-col="right">
                <div class="category-header"><h3>{{ $category->name }}</h3></div>
                <ul class="product-list">
                    @foreach($category->products as $product)
                    <li class="product-row{{ $product->promo_price !== null ? ' has-promo' : '' }}">
                        <span class="product-name">{{ $product->name }}</span>
                        <span class="dot-leader"></span>
                        @if($product->promo_price !== null)
                        <span class="product-price-old">{{ number_format($product->price, 0, ',', ' ') }} &euro;</span>
                        <span class="product-price promo">{{ number_format($product->promo_price, 0, ',', ' ') }} &euro;</span>
                        @else
                        <span class="product-price">{{ number_format($product->price, 0, ',', ' ') }} &euro;</span>
                        @endif
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif
            @endforeach
        </div>

        <div class="footer-section">
            <div class="footer-brand">LTD - Bruxelles</div>
            <div class="footer-slogan">Venez remplir le plein... et plus encore</div>
        </div>

        <nav class="page-nav">
            <a href="{{ route('home') }}">Accueil</a>
            <a href="{{ route('produits') }}" class="current">Produits</a>
            <a href="{{ route('menus') }}">Menus</a>
            <a href="{{ route('entreprises') }}">Entreprises</a>
        </nav>
    </div>
</div>

@endsection
