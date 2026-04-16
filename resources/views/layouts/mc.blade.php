<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'LOST MC')</title>
    <link rel="stylesheet" href="{{ asset('css/common.css') }}?v={{ filemtime(public_path('css/common.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/simulateur-armes.css') }}?v={{ filemtime(public_path('css/simulateur-armes.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/mc-layout.css') }}?v={{ filemtime(public_path('css/mc-layout.css')) }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.min.css">
    <link rel="stylesheet" href="{{ asset('css/mc-tom-select.css') }}?v={{ filemtime(public_path('css/mc-tom-select.css')) }}">
    @yield('css')
</head>
<body class="simulateur-armes @yield('body-class')">

    @php
        $currentRoute = Route::currentRouteName();
        $navLinks = [
            ['route' => 'mc.hub',              'url' => '/mc',                   'label' => 'Accueil',    'gate' => 'any'],
            ['route' => 'simulateur-armes',    'url' => '/simulateur-armes',     'label' => 'Armes',      'gate' => 'any'],
            ['route' => 'simulateur-munitions','url' => '/simulateur-munitions', 'label' => 'Munitions',  'gate' => 'any'],
            ['route' => 'ventes',              'url' => '/ventes',               'label' => 'Ventes',     'gate' => 'logged'],
            ['route' => 'espace-membres',      'url' => '/espace-membres',       'label' => 'Espace',     'gate' => 'logged'],
            ['route' => 'stocks',              'url' => '/stocks',               'label' => 'Stocks',     'gate' => 'officer'],
            ['route' => 'membres',             'url' => '/membres',              'label' => 'Gestion',    'gate' => 'vice_president'],
        ];
    @endphp

    <div class="mc-top-bar" id="mcTopBar">
        <a href="/mc" class="mc-top-home" title="Accueil MC">LOST MC</a>
        <nav class="mc-top-nav" id="mcTopNav">
            @foreach($navLinks as $link)
                <a href="{{ $link['url'] }}"
                   class="mc-top-nav-link {{ $currentRoute === $link['route'] ? 'is-active' : '' }}"
                   data-gate="{{ $link['gate'] }}">
                    <span class="mc-top-nav-label">{{ $link['label'] }}</span>
                </a>
            @endforeach
        </nav>
        <div class="mc-top-spacer"></div>
        <div class="mc-top-auth" id="mcAuth">
            <div class="mc-auth-logged-out" id="mcAuthOut">
                <button class="mc-login-btn" id="mcLoginToggle">Se connecter</button>
            </div>
            <div class="mc-auth-logged-in" id="mcAuthIn" style="display:none;">
                <span class="mc-user-name" id="mcUserName"></span>
                <span class="mc-user-role" id="mcUserRole"></span>
                <button class="mc-logout-btn" id="mcLogoutBtn">Quitter</button>
            </div>
        </div>
    </div>

    <button class="mc-nav-toggle" id="mcNavToggle" type="button" aria-label="Menu">
        <span></span><span></span><span></span>
    </button>

    <div class="mc-login-panel" id="mcLoginPanel" style="display:none;">
        <div class="mc-login-inner">
            <div class="mc-login-title">Connexion membre</div>
            <select class="lock-input" id="loginMemberSelect">
                <option value="">-- Qui etes-vous ? --</option>
                @if(isset($members))
                    @foreach($members as $m)
                        <option value="{{ $m->id }}">{{ $m->name }}</option>
                    @endforeach
                @endif
            </select>
            <input type="password" class="lock-input" id="loginPin" placeholder="PIN" autocomplete="off" maxlength="20">
            <button class="lock-btn" id="btnLogin">Valider</button>
            <div class="lock-error" id="errLogin"></div>
        </div>
    </div>

    <div id="toast" class="toast"></div>

    @yield('content')

    <script>
    window.MC_MEMBERS = {!! json_encode($members ?? []) !!};
    window.MC_ASSIGNABLE_ROLES = {!! isset($assignableRoles) ? json_encode($assignableRoles) : '[]' !!};
    </script>
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    <script src="{{ asset('js/mc-auth.js') }}?v={{ filemtime(public_path('js/mc-auth.js')) }}"></script>
    @yield('scripts')
    <script>
    (function () {
        if (typeof TomSelect === 'undefined') return;
        var sel = document.getElementById('loginMemberSelect');
        if (sel && !sel.tomselect) {
            new TomSelect(sel, {
                placeholder: 'Qui etes-vous ?',
                searchField: ['text'],
                allowEmptyOption: true,
                maxOptions: 500,
                plugins: ['dropdown_input']
            });
        }
    })();
    </script>

    <script>
    // Cross-page nav : gating + mobile toggle
    (function () {
        var LEVEL = { prospect: 1, member: 2, officer: 3, vice_president: 4, president: 5, treasurer: 99 };
        function isAtLeast(role, min) { return (LEVEL[role] || 0) >= (LEVEL[min] || 0); }

        function updateNav() {
            var auth = window.McAuth;
            var loggedIn = auth && auth.isLoggedIn;
            var role = loggedIn ? auth.userRole : '';
            document.querySelectorAll('.mc-top-nav-link').forEach(function (el) {
                var gate = el.getAttribute('data-gate');
                var visible = true;
                if (gate === 'logged') visible = loggedIn;
                else if (gate === 'any') visible = true;
                else visible = loggedIn && isAtLeast(role, gate);
                el.style.display = visible ? '' : 'none';
            });
        }

        updateNav();
        if (window.McAuth) {
            window.McAuth.onLogin(updateNav);
            window.McAuth.onLogout(updateNav);
        }

        var toggle = document.getElementById('mcNavToggle');
        var nav = document.getElementById('mcTopNav');
        if (toggle && nav) {
            toggle.addEventListener('click', function () {
                nav.classList.toggle('is-open');
                toggle.classList.toggle('is-open');
            });
            document.addEventListener('click', function (e) {
                if (!nav.contains(e.target) && !toggle.contains(e.target)) {
                    nav.classList.remove('is-open');
                    toggle.classList.remove('is-open');
                }
            });
        }
    })();
    </script>
</body>
</html>
