@extends('layouts.mc')

@section('title', 'LOST MC -- Fiche membre')

@section('content')
<div class="menu-board mc-board-lg">
    <div class="inner-board">

        <div class="mc-page-header">
            <img src="{{ asset('img/3651.webp') }}" alt="Lost MC">
            <div class="mc-page-title" id="profilTitle">Fiche membre</div>
            <div class="mc-page-motto">Le Tout-Puissant pardonne. Pas les Lost.</div>
            <a href="/membres" class="mc-page-back">&larr; Retour a la gestion</a>
        </div>

        <div id="profilNotLogged" class="contract-lock">
            <div class="lock-text">Connectez-vous via le bouton en haut a droite pour acceder a cette section.</div>
        </div>

        <div id="profilNoAccess" class="contract-lock" style="display:none;">
            <div class="lock-text">Acces refuse. Cette page necessite au minimum le role Officier.</div>
        </div>

        <div id="profilContent" style="display:none;">

            {{-- Info card --}}
            <div class="profil-info-card" id="profilInfo"></div>

            {{-- Stats rapides --}}
            <div class="members-stats" id="profilStats"></div>

            {{-- Sub-tabs --}}
            <div class="sub-tab-bar">
                <button class="sub-tab active" data-subtab="attributions">En possession</button>
                <button class="sub-tab" data-subtab="ventes">Ventes</button>
                <button class="sub-tab" data-subtab="mouvements">Mouvements</button>
                <button class="sub-tab" data-subtab="cotisations">Cotisations</button>
                <button class="sub-tab" data-subtab="demandes">Demandes</button>
            </div>

            {{-- Attributions --}}
            <div class="sub-tab-content active" data-subtab="attributions">
                <div id="profilAttributions"><div class="empty-msg">Chargement...</div></div>
            </div>

            {{-- Ventes --}}
            <div class="sub-tab-content" data-subtab="ventes" style="display:none;">
                <div id="profilVentes"><div class="empty-msg">Chargement...</div></div>
            </div>

            {{-- Mouvements --}}
            <div class="sub-tab-content" data-subtab="mouvements" style="display:none;">
                <div id="profilMouvements"><div class="empty-msg">Chargement...</div></div>
            </div>

            {{-- Cotisations --}}
            <div class="sub-tab-content" data-subtab="cotisations" style="display:none;">
                <div id="profilCotisations"><div class="empty-msg">Chargement...</div></div>
            </div>

            {{-- Demandes --}}
            <div class="sub-tab-content" data-subtab="demandes" style="display:none;">
                <div id="profilDemandes"><div class="empty-msg">Chargement...</div></div>
            </div>

        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>window.PROFIL_MEMBER_ID = {{ $memberId }};</script>
<script src="{{ asset('js/membre-profil.js') }}?v={{ filemtime(public_path('js/membre-profil.js')) }}"></script>
@endsection
