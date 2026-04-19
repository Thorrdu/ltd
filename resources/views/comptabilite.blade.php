@extends('layouts.mc')

@section('title', 'LOST MC -- Comptabilite')

@section('content')
<div class="menu-board mc-board-lg">
    <div class="inner-board">

        <div class="mc-page-header">
            <img src="{{ asset('img/3651.webp') }}" alt="Lost MC">
            <div class="mc-page-title">Comptabilite MC</div>
            <div class="mc-page-motto">Le Tout-Puissant pardonne. Pas les Lost.</div>
            <a href="/mc" class="mc-page-back">&larr; Retour a l'accueil</a>
        </div>

        <div id="compNotLogged" class="contract-lock">
            <div class="lock-text">Connectez-vous via le bouton en haut a droite pour acceder a cette section.</div>
        </div>

        <div id="compNoAccess" class="contract-lock" style="display:none;">
            <div class="lock-text">Acces refuse. Cette page est reservee au tresorier.</div>
        </div>

        <div id="compContent" style="display:none;">

            {{-- Sub-tabs --}}
            <div class="sub-tab-bar">
                <button class="sub-tab active" data-subtab="dashboard">Vue d'ensemble</button>
                <button class="sub-tab" data-subtab="semaines">Par semaine</button>
                <button class="sub-tab" data-subtab="transactions">Transactions</button>
            </div>

            {{-- TAB : Dashboard --}}
            <div class="sub-tab-content active" data-subtab="dashboard">
                <div class="comp-period-bar">
                    <button class="btn-sm comp-period active" data-period="week">Semaine</button>
                    <button class="btn-sm comp-period" data-period="month">Mois</button>
                    <button class="btn-sm comp-period" data-period="all">Global</button>
                </div>

                {{-- Soldes --}}
                <div class="comp-section">
                    <div class="comp-section-title">Soldes</div>
                    <div class="members-stats" id="compSoldes"></div>
                </div>

                {{-- Recettes / Depenses --}}
                <div class="comp-section">
                    <div class="comp-section-title">Flux financiers</div>
                    <div class="members-stats" id="compFlux"></div>
                </div>
            </div>

            {{-- TAB : Par semaine --}}
            <div class="sub-tab-content" data-subtab="semaines" style="display:none;">
                <div class="members-table" id="compWeeksTable">
                    <div class="empty-msg">Chargement...</div>
                </div>
            </div>

            {{-- TAB : Transactions --}}
            <div class="sub-tab-content" data-subtab="transactions" style="display:none;">
                <div class="req-filter-bar">
                    <select id="compTxType" class="lock-input lock-input-sm">
                        <option value="all">Tous les types</option>
                        <option value="ventes">Ventes</option>
                        <option value="cotisations">Cotisations</option>
                        <option value="depenses">Depenses</option>
                    </select>
                </div>
                <div class="members-table" id="compTxList">
                    <div class="empty-msg">Chargement...</div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/comptabilite.js') }}?v={{ filemtime(public_path('js/comptabilite.js')) }}"></script>
@endsection
