@extends('layouts.mc')

@section('title', 'LOST MC -- Cotisations')

@section('content')
<div class="menu-board mc-board-lg">
    <div class="inner-board">

        <div class="mc-page-header">
            <img src="{{ asset('img/3651.webp') }}" alt="Lost MC">
            <div class="mc-page-title">Cotisations</div>
            <div class="mc-page-motto">Le Tout-Puissant pardonne. Pas les Lost.</div>
            <a href="/mc" class="mc-page-back">&larr; Retour a l'accueil</a>
        </div>

        <div id="cotNotLogged" class="contract-lock">
            <div class="lock-text">Connectez-vous via le bouton en haut a droite pour acceder a cette section.</div>
        </div>

        <div id="cotNoAccess" class="contract-lock" style="display:none;">
            <div class="lock-text">Acces refuse. Cette page necessite au minimum le role Membre.</div>
        </div>

        <div id="cotContent" style="display:none;">

            {{-- Stats --}}
            <div class="members-stats" id="cotStats"></div>

            {{-- Sub-tabs --}}
            <div class="sub-tab-bar">
                <button class="sub-tab active" data-subtab="semaine">Semaine en cours</button>
                <button class="sub-tab" data-subtab="historique">Historique</button>
            </div>

            {{-- TAB : Semaine en cours --}}
            <div class="sub-tab-content active" data-subtab="semaine">
                <div class="cot-week-nav">
                    <button class="btn-sm" id="cotPrevWeek">&larr; Semaine prec.</button>
                    <span class="cot-week-label" id="cotWeekLabel"></span>
                    <button class="btn-sm" id="cotNextWeek">Semaine suiv. &rarr;</button>
                </div>
                <div class="cot-alert" id="cotAlert" style="display:none;"></div>
                <div class="members-table" id="cotWeekList">
                    <div class="empty-msg">Chargement...</div>
                </div>
            </div>

            {{-- TAB : Historique --}}
            <div class="sub-tab-content" data-subtab="historique" style="display:none;">
                <div class="req-filter-bar" id="cotHistoryFilter" style="display:none;">
                    <select id="cotHistoryMember" class="lock-input lock-input-sm">
                        <option value="">Tous les membres</option>
                    </select>
                </div>
                <div class="members-table" id="cotHistoryList">
                    <div class="empty-msg">Chargement...</div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/cotisations.js') }}?v={{ filemtime(public_path('js/cotisations.js')) }}"></script>
@endsection
