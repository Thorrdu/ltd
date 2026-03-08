@extends('layouts.app')

@section('title', 'Station LTD - Menus & Formules')
@section('css')
<link rel="stylesheet" href="{{ asset('css/menus.css') }}">
@endsection

@section('content')
<div class="menu-board" id="menuBoard">
    <div class="inner-board">
        <div class="header">
            <div class="main-title">
                <img src="{{ asset('img/logo-ltd.png') }}" alt="LTD" class="main-title-logo">
                <h2>STATION LTD - MENUS & FORMULES</h2>
            </div>
        </div>

        <div class="menus-container">
            @foreach($menus as $menu)
                @if($menu->type === 'promo')
                <div class="promo-banner">
                    <p class="promo-highlight">{{ $menu->promo_text }}</p>
                </div>
                @else
                <div class="menu-card">
                    <div class="menu-card-header">
                        <h3>{{ $menu->name }}</h3>
                        <span class="menu-card-price">{{ number_format($menu->price, 0, ',', ' ') }} &euro;</span>
                    </div>
                    <div class="menu-card-body">
                        <div class="menu-card-desc">{{ implode(' + ', $menu->display_items) }}</div>
                    </div>
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
            <a href="{{ route('produits') }}">Produits</a>
            <a href="{{ route('menus') }}" class="current">Menus</a>
            <a href="{{ route('entreprises') }}">Entreprises</a>
        </nav>
    </div>
</div>

@endsection
