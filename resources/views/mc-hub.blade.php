@extends('layouts.mc')

@section('title', 'LOST MC')

@section('content')
<div class="mc-hub">
    <div class="mc-hub-header">
        <img src="{{ asset('img/3651.webp') }}" alt="Lost MC" class="mc-hub-emblem">
        <div class="mc-hub-motto">Le Tout-Puissant pardonne. Pas les Lost.</div>
    </div>

    {{-- SECTION: Simulateurs (visible si connecte) --}}
    <div class="mc-hub-section" id="hubSimSection" style="display:none;">
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
            <a href="/espace-membres" class="mc-hub-btn mc-hub-btn-wide">
                <span class="mc-hub-btn-label">Espace membres</span>
                <span class="mc-hub-btn-desc">Attributions, contrats, profil</span>
            </a>
            <a href="/cotisations" class="mc-hub-btn mc-hub-btn-wide">
                <span class="mc-hub-btn-label">Cotisations</span>
                <span class="mc-hub-btn-desc">Suivi des cotisations hebdomadaires</span>
            </a>
            <a href="/ventes" class="mc-hub-btn mc-hub-btn-wide mc-hub-btn-member" style="display:none;">
                <span class="mc-hub-btn-label">Ventes rapides</span>
                <span class="mc-hub-btn-desc">Saisie rapide (armes, drogues, autres)</span>
            </a>
            <a href="/classements" class="mc-hub-btn mc-hub-btn-wide mc-hub-btn-member" style="display:none;">
                <span class="mc-hub-btn-label">Classements</span>
                <span class="mc-hub-btn-desc">Performance, aigle de la semaine</span>
            </a>
            <a href="/demandes" class="mc-hub-btn mc-hub-btn-wide mc-hub-btn-member" style="display:none;">
                <span class="mc-hub-btn-label">Remboursements</span>
                <span class="mc-hub-btn-desc">Demandes de remboursement, amendes, frais</span>
            </a>
            <a href="/stocks" class="mc-hub-btn mc-hub-btn-officer" style="display:none;">
                <span class="mc-hub-btn-label">Stocks generiques</span>
                <span class="mc-hub-btn-desc">Vue globale, attributions, import (Officier+)</span>
            </a>
            <a href="/membres" class="mc-hub-btn mc-hub-btn-vp" style="display:none;">
                <span class="mc-hub-btn-label">Gestion membres</span>
                <span class="mc-hub-btn-desc">Roles, acces, PIN (VP+)</span>
            </a>

        </div>
    </div>

    {{-- SECTION: Invite a se connecter (visible si non connecte) --}}
    <div class="mc-hub-login-prompt" id="hubLoginPrompt">
        <p>Connectez-vous pour acceder aux stocks, ventes et contrats.</p>
    </div>

    {{-- SECTION: Dashboard personnalise (visible si connecte) --}}
    <div class="mc-dash" id="hubDashboard">
        <div class="mc-hub-section-label">Tableau de bord</div>
        <div id="dashAlerts"></div>
        <div class="mc-dash-grid" id="dashStats"></div>
    </div>
</div>
@endsection

@section('scripts')
<script>
(function() {
    var LEVEL = { prospect: 1, member: 2, officer: 3, vice_president: 4, president: 5, treasurer: 99 };
    function isAtLeast(role, min) { return (LEVEL[role] || 0) >= (LEVEL[min] || 0); }
    function esc(s) { if (!s) return ''; var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
    function update() {
        var auth = window.McAuth;
        var loggedIn = auth && auth.isLoggedIn;
        var role = loggedIn ? auth.userRole : '';
        var isOfficer = loggedIn && isAtLeast(role, 'officer');
        var isMember = loggedIn && isAtLeast(role, 'member');
        var isVp = loggedIn && isAtLeast(role, 'vice_president');
        var isSuperadmin = loggedIn && role === 'treasurer';
        document.getElementById('hubAuthSection').style.display = loggedIn ? '' : 'none';
        document.getElementById('hubSimSection').style.display = loggedIn ? '' : 'none';
        document.getElementById('hubLoginPrompt').style.display = loggedIn ? 'none' : '';
        document.getElementById('hubDashboard').classList.toggle('visible', !!loggedIn);
        document.querySelectorAll('.mc-hub-btn-member').forEach(function(el) {
            el.style.display = isMember ? '' : 'none';
        });
        document.querySelectorAll('.mc-hub-btn-officer').forEach(function(el) {
            el.style.display = isOfficer ? '' : 'none';
        });
        document.querySelectorAll('.mc-hub-btn-vp').forEach(function(el) {
            el.style.display = isVp ? '' : 'none';
        });
        document.querySelectorAll('.mc-hub-btn-treasurer').forEach(function(el) {
            el.style.display = isSuperadmin ? '' : 'none';
        });
        if (loggedIn) loadDashboard();
    }

    function loadDashboard() {
        window.McAuth.apiGet('/dashboard/api', function(err, data) {
            if (err || !data || data.error) return;
            renderDashboard(data);
        });
    }

    function renderDashboard(data) {
        // Alerts
        var alertsEl = document.getElementById('dashAlerts');
        var ah = '';
        (data.alerts || []).forEach(function(a) {
            var cls = 'mc-dash-alert';
            if (a.type === 'danger') cls += ' alert-danger';
            else if (a.type === 'info') cls += ' alert-info';
            ah += '<div class="' + cls + '">';
            ah += '<span class="mc-dash-alert-icon">' + (a.icon || '⚠️') + '</span>';
            if (a.link) {
                ah += '<a href="' + esc(a.link) + '">' + esc(a.text) + '</a>';
            } else {
                ah += '<span>' + esc(a.text) + '</span>';
            }
            ah += '</div>';
        });
        alertsEl.innerHTML = ah;

        // Stats cards
        var statsEl = document.getElementById('dashStats');
        var sh = '';
        var stats = data.stats || {};
        Object.keys(stats).forEach(function(key) {
            var s = stats[key];
            var valClass = 'mc-dash-value';
            if (s['class']) valClass += ' ' + s['class'];
            sh += '<div class="mc-dash-card">';
            sh += '<div class="mc-dash-label">' + esc(s.label) + '</div>';
            sh += '<div class="' + valClass + '">' + esc('' + s.value) + '</div>';
            if (s.sub) sh += '<div class="mc-dash-sub">' + esc(s.sub) + '</div>';
            sh += '</div>';
        });
        statsEl.innerHTML = sh || '<div style="color:#555;font-size:12px;">Aucune donnee a afficher.</div>';
    }

    update();
    if (window.McAuth) {
        window.McAuth.onLogin(update);
        window.McAuth.onLogout(function() {
            document.getElementById('hubDashboard').classList.remove('visible');
            document.getElementById('dashAlerts').innerHTML = '';
            document.getElementById('dashStats').innerHTML = '';
            update();
        });
    }
})();
</script>
@endsection
