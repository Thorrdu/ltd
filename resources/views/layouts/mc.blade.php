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
            ['route' => 'simulateur-armes',    'url' => '/simulateur-armes',     'label' => 'Armes',      'gate' => 'logged'],
            ['route' => 'simulateur-munitions','url' => '/simulateur-munitions', 'label' => 'Munitions',  'gate' => 'logged'],
            ['route' => 'ventes',              'url' => '/ventes',               'label' => 'Ventes',     'gate' => 'member'],
            ['route' => 'classements',         'url' => '/classements',          'label' => 'Classements','gate' => 'member'],
            ['route' => 'demandes',            'url' => '/demandes',             'label' => 'Remboursements','gate' => 'member'],
            ['route' => 'cotisations',         'url' => '/cotisations',          'label' => 'Cotisations','gate' => 'logged'],
            ['route' => 'espace-membres',      'url' => '/espace-membres',       'label' => 'Espace',     'gate' => 'logged'],
            ['route' => 'stocks',              'url' => '/stocks',               'label' => 'Stocks',     'gate' => 'officer'],
            ['route' => 'comptabilite',        'url' => '/comptabilite',         'label' => 'Compta',     'gate' => 'treasurer'],
            ['route' => 'parametres',          'url' => '/parametres',            'label' => 'Parametres','gate' => 'treasurer'],
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
            <div class="mc-notif-wrapper" id="mcNotifWrapper" style="display:none;">
                <button class="mc-notif-btn" id="mcNotifBtn" title="Notifications">
                    🔔
                    <span class="mc-notif-badge" id="mcNotifBadge" style="display:none;">0</span>
                </button>
                <div class="mc-notif-panel" id="mcNotifPanel" style="display:none;">
                    <div class="mc-notif-header">
                        <span class="mc-notif-title">Notifications</span>
                        <button class="mc-notif-clear" id="mcNotifClearAll">Tout marquer lu</button>
                    </div>
                    <div id="mcNotifList">
                        <div class="mc-notif-empty">Aucune notification</div>
                    </div>
                </div>
            </div>
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
            {{-- Dummy fields to absorb Chrome autofill --}}
            <input type="text" name="prevent_autofill" style="display:none" tabindex="-1" aria-hidden="true">
            <input type="password" name="prevent_autofill_pw" style="display:none" tabindex="-1" aria-hidden="true">
            <select class="lock-input" id="loginMemberSelect">
                <option value="">-- Qui etes-vous ? --</option>
                @foreach(($loginMembers ?? collect()) as $m)
                    <option value="{{ $m->id }}">{{ $m->name }}</option>
                @endforeach
            </select>
            <input type="password" class="lock-input" id="loginPin" placeholder="PIN" autocomplete="new-password" maxlength="20">
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
                plugins: ['dropdown_input'],
                onInitialize: function () {
                    var di = this.dropdown.querySelector('input');
                    if (di) {
                        di.setAttribute('type', 'search');
                        di.setAttribute('autocomplete', 'nope');
                        di.setAttribute('role', 'searchbox');
                    }
                }
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

    <script>
    // Notifications bell
    (function () {
        var wrapper = document.getElementById('mcNotifWrapper');
        var btn = document.getElementById('mcNotifBtn');
        var badge = document.getElementById('mcNotifBadge');
        var panel = document.getElementById('mcNotifPanel');
        var list = document.getElementById('mcNotifList');
        var clearBtn = document.getElementById('mcNotifClearAll');
        var pollTimer = null;

        function esc(s) { if (!s) return ''; var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

        var ICONS = {
            cotisation: '💰', attribution: '📦', demande: '📋',
            classement: '🏆', stock: '📊', system: '🔔'
        };

        function show(visible) { wrapper.style.display = visible ? '' : 'none'; }

        function updateBadge(count) {
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.style.display = '';
            } else {
                badge.style.display = 'none';
            }
        }

        function pollCount() {
            if (!window.McAuth || !window.McAuth.isLoggedIn) return;
            window.McAuth.apiGet('/notifications/api/count', function (err, data) {
                if (!err && data) updateBadge(data.unread || 0);
            });
        }

        function loadNotifs() {
            if (!window.McAuth || !window.McAuth.isLoggedIn) return;
            window.McAuth.apiGet('/notifications/api/list', function (err, data) {
                if (err || !data) return;
                updateBadge(data.unread || 0);
                var notifs = data.notifications || [];
                if (!notifs.length) {
                    list.innerHTML = '<div class="mc-notif-empty">Aucune notification</div>';
                    return;
                }
                var html = '';
                notifs.forEach(function (n) {
                    var cls = 'mc-notif-item' + (n.read ? '' : ' unread');
                    html += '<div class="' + cls + '" data-id="' + n.id + '"' + (n.link ? ' data-link="' + esc(n.link) + '"' : '') + '>';
                    html += '<div class="mc-notif-icon type-' + esc(n.type) + '">' + (ICONS[n.type] || '🔔') + '</div>';
                    html += '<div class="mc-notif-body">';
                    html += '<div class="mc-notif-text">' + esc(n.title) + '</div>';
                    if (n.body) html += '<div class="mc-notif-text" style="color:#888;font-size:11px;">' + esc(n.body) + '</div>';
                    html += '<div class="mc-notif-time">' + esc(n.ago) + '</div>';
                    html += '</div></div>';
                });
                list.innerHTML = html;

                // Click handler for each notification
                list.querySelectorAll('.mc-notif-item').forEach(function (el) {
                    el.addEventListener('click', function () {
                        var id = parseInt(el.getAttribute('data-id'));
                        var link = el.getAttribute('data-link');
                        if (el.classList.contains('unread')) {
                            window.McAuth.apiPost('/notifications/api/read', { ids: [id] }, function () {});
                            el.classList.remove('unread');
                        }
                        if (link) window.location.href = link;
                    });
                });
            });
        }

        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var visible = panel.style.display === 'none';
            panel.style.display = visible ? '' : 'none';
            if (visible) loadNotifs();
        });

        clearBtn.addEventListener('click', function () {
            window.McAuth.apiPost('/notifications/api/read', { all: true }, function (err, data) {
                if (!err && data) {
                    updateBadge(0);
                    list.querySelectorAll('.mc-notif-item.unread').forEach(function (el) {
                        el.classList.remove('unread');
                    });
                }
            });
        });

        document.addEventListener('click', function (e) {
            if (!wrapper.contains(e.target)) panel.style.display = 'none';
        });

        function start() {
            show(true);
            pollCount();
            if (pollTimer) clearInterval(pollTimer);
            pollTimer = setInterval(pollCount, 60000); // poll every 60s
        }
        function stop() {
            show(false);
            if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
        }

        if (window.McAuth) {
            if (window.McAuth.isLoggedIn) start();
            window.McAuth.onLogin(start);
            window.McAuth.onLogout(stop);
        }
    })();
    </script>
</body>
</html>
