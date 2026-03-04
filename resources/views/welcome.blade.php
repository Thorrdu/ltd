@extends('layouts.app')

@section('title', 'Station LTD - Little Seoul, Bruxelles')
@section('css')
<link rel="stylesheet" href="{{ asset('css/accueil.css') }}">
@endsection
@section('body-class', 'accueil')

@section('content')
<div class="hero">
    <div class="hero-bg">
        <img src="{{ asset('img/station-nuit.png') }}" alt="">
    </div>

    <div class="hero-content">
        <img src="{{ asset('img/logo-ltd.png') }}" alt="LTD Gasoline" class="hero-logo">
        <div class="hero-title">Station LTD</div>
        <hr class="hero-divider">
        <div class="hero-subtitle">Little Seoul - Bruxelles</div>
        <div class="hero-location">Little Seoul, Anderlecht - BXL Life</div>

        <div class="nav-cards">
            <a href="{{ route('produits') }}" class="nav-card">
                <div class="nav-card-icon">&#9733;</div>
                <div>
                    <div class="nav-card-title">Produits</div>
                    <div class="nav-card-desc">Snacks, boissons, accessoires et plus encore</div>
                </div>
            </a>
            <a href="{{ route('menus') }}" class="nav-card">
                <div class="nav-card-icon">&#9776;</div>
                <div>
                    <div class="nav-card-title">Menus</div>
                    <div class="nav-card-desc">Formules et promotions du moment</div>
                </div>
            </a>
            <a href="{{ route('entreprises') }}" class="nav-card locked">
                <span class="lock-indicator">&#128274;</span>
                <div class="nav-card-icon">&#9878;</div>
                <div>
                    <div class="nav-card-title">Entreprises</div>
                    <div class="nav-card-desc">Tarifs reserves aux partenaires</div>
                </div>
            </a>
        </div>

        <div class="gallery">
            <div class="gallery-img">
                <img src="{{ asset('img/station-jour.png') }}" alt="Station LTD de jour">
            </div>
            <div class="gallery-img">
                <img src="{{ asset('img/station-nuit.png') }}" alt="Station LTD de nuit">
            </div>
        </div>

        <div class="hero-footer">
            <div class="hero-footer-brand">LTD Gasoline - Bruxelles</div>
            <div class="hero-footer-slogan">Venez remplir le plein... et plus encore</div>
        </div>
    </div>
</div>
@endsection
