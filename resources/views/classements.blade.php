@extends('layouts.mc')

@section('title', 'LOST MC -- Classements')

@section('content')
<div class="menu-board" style="width:1000px;">
    <div class="inner-board">

        <div class="mc-page-header">
            <img src="{{ asset('img/3651.webp') }}" alt="Lost MC">
            <div class="mc-page-title">Classements</div>
            <div class="mc-page-motto">Le Tout-Puissant pardonne. Pas les Lost.</div>
            <a href="/mc" class="mc-page-back">&larr; Retour a l'accueil</a>
        </div>

        {{-- Non connecte --}}
        <div id="rankNotLogged" class="contract-lock">
            <div class="lock-text">Connectez-vous via le bouton en haut a droite pour acceder a cette section.</div>
        </div>

        {{-- Acces refuse --}}
        <div id="rankNoAccess" class="contract-lock" style="display:none;">
            <div class="lock-text">Acces refuse. Cette page necessite au minimum le role Membre.</div>
        </div>

        {{-- Contenu --}}
        <div id="rankContent" style="display:none;">

            {{-- EAGLE OF THE WEEK --}}
            <div class="rank-eagle-banner" id="rankEagleBanner" style="display:none;">
                <span class="rank-eagle-icon">&#x1F985;</span>
                <div class="rank-eagle-info">
                    <div class="rank-eagle-title">Aigle de la semaine</div>
                    <div class="rank-eagle-name" id="rankEagleName"></div>
                    <div class="rank-eagle-score" id="rankEagleScore"></div>
                </div>
            </div>

            {{-- SUB TABS --}}
            <div class="sub-tab-bar">
                <button class="sub-tab active" data-subtab="rankings">Classement</button>
                <button class="sub-tab" data-subtab="eagles">Historique Aigles</button>
                <button class="sub-tab sub-tab-officer" data-subtab="config" style="display:none;">Configuration</button>
            </div>

            {{-- TAB: Rankings --}}
            <div class="sub-tab-content active" data-subtab="rankings">
                {{-- Period selector --}}
                <div class="rank-period-bar">
                    <button class="rank-period-btn active" data-period="week">Semaine</button>
                    <button class="rank-period-btn" data-period="last_week">Sem. prec.</button>
                    <button class="rank-period-btn" data-period="month">Mois</button>
                    <button class="rank-period-btn" data-period="all">Global</button>
                </div>

                <div class="rank-criteria-label" id="rankCriteriaLabel"></div>

                {{-- Ranking table --}}
                <div class="rank-table" id="rankTable">
                    <div class="empty-msg">Chargement...</div>
                </div>
            </div>

            {{-- TAB: Eagle History --}}
            <div class="sub-tab-content" data-subtab="eagles" style="display:none;">
                <div class="rank-eagle-history" id="rankEagleHistory">
                    <div class="empty-msg">Chargement...</div>
                </div>
            </div>

            {{-- TAB: Config (officer+) --}}
            <div class="sub-tab-content" data-subtab="config" style="display:none;">
                <div class="rank-config-section">
                    <h3 class="rank-config-title">Categories eligibles</h3>
                    <p class="rank-config-desc">Cochez les categories de stock qui comptent pour le classement.</p>
                    <div class="rank-cat-grid" id="rankCatGrid"></div>
                </div>
                <div class="rank-config-section">
                    <h3 class="rank-config-title">Critere de classement</h3>
                    <div class="rank-criteria-choices">
                        <label class="rank-radio"><input type="radio" name="rankCriteria" value="revenue"> Chiffre d'affaires (CA)</label>
                        <label class="rank-radio"><input type="radio" name="rankCriteria" value="count"> Nombre de ventes</label>
                        <label class="rank-radio"><input type="radio" name="rankCriteria" value="quantity"> Quantite totale vendue</label>
                    </div>
                </div>
                <button class="btn-primary" id="rankSaveConfig">Enregistrer la configuration</button>
            </div>

        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
window.MC_RANK_CATEGORIES = @json($categories);
</script>
<script src="{{ asset('js/classements.js') }}?v={{ filemtime(public_path('js/classements.js')) }}"></script>
@endsection
