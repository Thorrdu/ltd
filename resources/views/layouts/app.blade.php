<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Station LTD - Little Seoul, Bruxelles')</title>
    <link rel="stylesheet" href="{{ asset('css/common.css') }}">
    @yield('css')
</head>
<body class="@yield('body-class')">
    @yield('content')
    <script>
    (function() {
        var inIframe = false;
        try { inIframe = window.self !== window.top; } catch (e) { inIframe = true; }
        if (inIframe || location.search.includes('clean')) {
            document.body.classList.add('clean-mode');
        }
    })();
    </script>
    @yield('scripts')
</body>
</html>
