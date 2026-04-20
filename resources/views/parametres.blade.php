@extends('layouts.mc')

@section('title', 'LOST MC -- Parametres')

@section('content')
<div class="menu-board mc-board-lg">
    <div class="inner-board">

        <div class="mc-page-header">
            <img src="{{ asset('img/3651.webp') }}" alt="Lost MC">
            <div class="mc-page-title">Parametres</div>
            <div class="mc-page-motto">Le Tout-Puissant pardonne. Pas les Lost.</div>
            <a href="/mc" class="mc-page-back">&larr; Retour a l'accueil</a>
        </div>

        <div id="setNotLogged" class="contract-lock">
            <div class="lock-text">Connectez-vous via le bouton en haut a droite pour acceder a cette section.</div>
        </div>

        <div id="setNoAccess" class="contract-lock" style="display:none;">
            <div class="lock-text">Acces refuse. Cette page necessite au minimum le role Tresorier.</div>
        </div>

        <div id="setContent" style="display:none;">
            <div class="sub-tab-bar" id="setGroupBar"></div>
            <div id="setBody"></div>
        </div>

    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/parametres.js') }}?v={{ filemtime(public_path('js/parametres.js')) }}"></script>
@endsection
