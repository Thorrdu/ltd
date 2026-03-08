@extends('layouts.app')

@section('title', 'Station LTD - Entreprises Only')
@section('css')
<link rel="stylesheet" href="{{ asset('css/entreprises.css') }}">
@endsection

@section('content')
<div class="password-overlay" id="passwordOverlay">
    <div class="password-box">
        <div class="password-inner">
            <h3>Acces restreint</h3>
            <p>Page reservee aux entreprises partenaires</p>
            <input type="password" id="pwInput" placeholder="Mot de passe" autocomplete="off">
            <button class="pw-btn" id="pwBtn">Entrer</button>
            <div class="pw-error" id="pwError">Mot de passe incorrect</div>
        </div>
    </div>
</div>

<div class="menu-board" id="menuBoard">
    <div class="inner-board">
        <div class="header">
            <div class="main-title">
                <img src="{{ asset('img/logo-ltd.png') }}" alt="LTD" class="main-title-logo">
                <h2>STATION LTD - ENTREPRISES</h2>
            </div>
        </div>

        <div class="enterprise-container">
            @foreach($enterprises as $enterprise)
            <div class="enterprise-block">
                <div class="enterprise-header"><h3>{{ $enterprise->name }}</h3></div>
                <ul class="product-list">
                    @foreach($enterprise->products as $product)
                    <li class="product-row">
                        <span class="product-name">{{ $product->name }}</span>
                        <span class="dot-leader"></span>
                        <span class="product-price">{{ number_format($product->pivot->price, 0, ',', ' ') }} &euro;</span>
                    </li>
                    @endforeach
                </ul>
            </div>
            @endforeach
        </div>

        <div class="footer-section">
            <div class="footer-brand">LTD - Bruxelles</div>
            <div class="footer-slogan">Venez remplir le plein... et plus encore</div>
            <div class="confidential-badge">Reservee aux entreprises partenaires</div>
        </div>

        <nav class="page-nav">
            <a href="{{ route('home') }}">Accueil</a>
            <a href="{{ route('produits') }}">Produits</a>
            <a href="{{ route('menus') }}">Menus</a>
            <a href="{{ route('entreprises') }}" class="current">Entreprises</a>
        </nav>
    </div>
</div>

<script>
(function() {
    var PASSWORD = 'ltd2026';
    var overlay = document.getElementById('passwordOverlay');
    var input = document.getElementById('pwInput');
    var btn = document.getElementById('pwBtn');
    var error = document.getElementById('pwError');

    if (sessionStorage.getItem('_ent_auth') === '1') {
        overlay.style.display = 'none';
    }

    function check() {
        if (input.value === PASSWORD) {
            sessionStorage.setItem('_ent_auth', '1');
            overlay.style.display = 'none';
        } else {
            error.style.display = 'block';
            input.value = '';
            input.focus();
        }
    }

    btn.addEventListener('click', check);
    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') check();
    });

    if (location.search.includes('clean')) {
        document.body.classList.add('clean-mode');
        overlay.style.display = 'none';
    }
})();
</script>
@endsection
