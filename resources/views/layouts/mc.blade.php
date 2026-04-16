<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'LOST MC')</title>
    <link rel="stylesheet" href="{{ asset('css/common.css') }}">
    <link rel="stylesheet" href="{{ asset('css/simulateur-armes.css') }}">
    <link rel="stylesheet" href="{{ asset('css/mc-layout.css') }}">
    @yield('css')
</head>
<body class="simulateur-armes @yield('body-class')">

    <div class="mc-top-bar" id="mcTopBar">
        <a href="/mc" class="mc-top-home" title="Accueil MC">LOST MC</a>
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
    </script>
    <script src="{{ asset('js/mc-auth.js') }}"></script>
    @yield('scripts')
</body>
</html>
