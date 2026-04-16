@extends('layouts.mc')

@section('title', 'LOST MC')

@section('content')
<div class="mc-hub">
    <div class="mc-hub-header">
        <img src="{{ asset('img/3651.webp') }}" alt="Lost MC" class="mc-hub-emblem">
        <div class="mc-hub-motto">Le Tout-Puissant pardonne. Pas les Lost.</div>
    </div>

    {{-- SECTION: Simulateurs (toujours visible) --}}
    <div class="mc-hub-section">
        <div class="mc-hub-section-label">Simulateurs</div>
        <div class="mc-hub-grid">
            <a href="/simulateur-armes" class="mc-hub-btn">
                <span class="mc-hub-btn-label">Armes</span>
                <span class="mc-hub-btn-desc">Calcul pieces, couts et marges</span>
            </a>
            <a href="/simulateur-munitions" class="mc-hub-btn">
                <span class="mc-hub-btn-label">Munitions</span>
                <span class="mc-hub-btn-desc">Couts craft par calibre</span>
            </a>
        </div>
    </div>

    {{-- SECTION: Espace membres (visible si connecte) --}}
    <div class="mc-hub-section mc-hub-auth-section" id="hubAuthSection" style="display:none;">
        <div class="mc-hub-section-label">Espace membres</div>
        <div class="mc-hub-grid">
            <a href="/ventes" class="mc-hub-btn mc-hub-btn-wide">
                <span class="mc-hub-btn-label">Ventes rapides</span>
                <span class="mc-hub-btn-desc">Saisie rapide (armes, drogues, autres)</span>
            </a>
            <a href="/espace-membres" class="mc-hub-btn mc-hub-btn-wide">
                <span class="mc-hub-btn-label">Espace membres</span>
                <span class="mc-hub-btn-desc">Stocks, contrats, historique</span>
            </a>
            <a href="/membres" class="mc-hub-btn mc-hub-btn-vp" style="display:none;">
                <span class="mc-hub-btn-label">Gestion membres</span>
                <span class="mc-hub-btn-desc">Roles, acces, PIN (VP+)</span>
            </a>
            <a href="/armurerie" target="_blank" class="mc-hub-btn mc-hub-btn-officer" style="display:none;">
                <span class="mc-hub-btn-label">Panel Armurerie</span>
                <span class="mc-hub-btn-desc">Gestion avancee (Filament)</span>
            </a>
            <a href="/admin" target="_blank" class="mc-hub-btn mc-hub-btn-treasurer" style="display:none;">
                <span class="mc-hub-btn-label">Panel Admin LTD</span>
                <span class="mc-hub-btn-desc">Catalogue + parametres (Tresorier)</span>
            </a>
        </div>
    </div>

    {{-- SECTION: Invite a se connecter (visible si non connecte) --}}
    <div class="mc-hub-login-prompt" id="hubLoginPrompt">
        <p>Connectez-vous pour acceder aux stocks, ventes et contrats.</p>
    </div>
</div>
@endsection

@section('scripts')
<script>
(function() {
    var LEVEL = { prospect: 1, member: 2, officer: 3, vice_president: 4, president: 5, treasurer: 99 };
    function isAtLeast(role, min) { return (LEVEL[role] || 0) >= (LEVEL[min] || 0); }
    function update() {
        var auth = window.McAuth;
        var loggedIn = auth && auth.isLoggedIn;
        var role = loggedIn ? auth.userRole : '';
        var isOfficer = loggedIn && isAtLeast(role, 'officer');
        var isVp = loggedIn && isAtLeast(role, 'vice_president');
        var isSuperadmin = loggedIn && role === 'treasurer';
        document.getElementById('hubAuthSection').style.display = loggedIn ? '' : 'none';
        document.getElementById('hubLoginPrompt').style.display = loggedIn ? 'none' : '';
        document.querySelectorAll('.mc-hub-btn-officer').forEach(function(el) {
            el.style.display = isOfficer ? '' : 'none';
        });
        document.querySelectorAll('.mc-hub-btn-vp').forEach(function(el) {
            el.style.display = isVp ? '' : 'none';
        });
        document.querySelectorAll('.mc-hub-btn-treasurer').forEach(function(el) {
            el.style.display = isSuperadmin ? '' : 'none';
        });
    }
    update();
    if (window.McAuth) {
        window.McAuth.onLogin(update);
        window.McAuth.onLogout(update);
    }
})();
</script>
@endsection
