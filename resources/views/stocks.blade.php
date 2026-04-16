@extends('layouts.mc')

@section('title', 'LOST MC -- Stocks')

@section('content')
<div class="menu-board" style="width:1100px;">
    <div class="inner-board">

        <div class="mc-page-header">
            <img src="{{ asset('img/3651.webp') }}" alt="Lost MC">
            <div class="mc-page-title">Stocks generiques</div>
            <div class="mc-page-motto">Le Tout-Puissant pardonne. Pas les Lost.</div>
            <a href="/mc" class="mc-page-back">&larr; Retour a l'accueil</a>
        </div>

        <div id="stocksNotLogged" class="contract-lock">
            <div class="lock-text">Connectez-vous via le bouton en haut a droite pour acceder a cette section.</div>
        </div>
        <div id="stocksNoAccess" class="contract-lock" style="display:none;">
            <div class="lock-text">Acces refuse. Cette page necessite au minimum le role Officier.</div>
        </div>

        <div id="stocksContent" style="display:none;">

            <div class="sub-tab-bar">
                <button class="sub-tab active" data-subtab="overview">Vue d'ensemble</button>
                <button class="sub-tab" data-subtab="attribute">Attribuer</button>
                <button class="sub-tab" data-subtab="attributions">Attributions en cours</button>
                <button class="sub-tab" data-subtab="validations" id="tabValidations" style="display:none;">Validations</button>
                <button class="sub-tab" data-subtab="import" id="tabImport" style="display:none;">Import</button>
            </div>

            {{-- SUB : Overview --}}
            <div class="sub-content active" id="sub-overview">
                <div class="stocks-capacity" id="stocksCapacity"></div>
                <div class="members-toolbar" style="margin-bottom:10px;">
                    <input type="text" id="stockSearch" class="fm-input" placeholder="Rechercher un article...">
                    <select id="stockCategory" class="fm-input">
                        <option value="">Toutes categories</option>
                        @foreach($categoriesMap as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="stocks-totals" id="stocksTotals"></div>
                <div class="stocks-list" id="stocksList">
                    <div class="empty-msg">Chargement...</div>
                </div>
            </div>

            {{-- SUB : Attribuer --}}
            <div class="sub-content" id="sub-attribute">
                <div class="action-card">
                    <div class="action-card-title">Attribuer un article a un membre</div>
                    <p class="action-hint">
                        L'article sort du stock central du MC et est marque comme « en possession » du membre.
                        Celui-ci pourra le reconcilier (vendu / retour / perte / don) depuis son espace.
                        Cochez « hors stock » si la marchandise vient de l'exterieur sans passage par le coffre (trace seule, sans decrement).
                    </p>
                    <div class="action-form">
                        <div class="form-row">
                            <div class="form-group full">
                                <label class="cb-inline" style="margin-bottom:8px;">
                                    <input type="checkbox" id="aFromExternal"> Hors stock central (provenance exterieure, pas de sortie de coffre)
                                </label>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group full">
                                <label>Article</label>
                                <select id="aItem" class="fm-input"></select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group sm">
                                <label>Quantite</label>
                                <input type="number" id="aQty" class="fm-input" value="1" min="1" max="999999999">
                            </div>
                            <div class="form-group">
                                <label>Beneficiaire</label>
                                <select id="aMember" class="fm-input"></select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group full">
                                <label>Notes <span class="optional">(contexte, mission, contrat...)</span></label>
                                <input type="text" id="aNotes" class="fm-input" placeholder="...">
                            </div>
                        </div>
                        <div id="aValueHint" class="action-hint" style="font-style:italic;"></div>
                        <button class="action-btn sale-btn" id="aBtnSave">Enregistrer l'attribution</button>
                    </div>
                </div>
            </div>

            {{-- SUB : Attributions en cours --}}
            <div class="sub-content" id="sub-attributions">
                <div class="action-card">
                    <div class="action-card-title">Attributions en cours</div>
                    <div class="members-toolbar" style="margin-bottom:10px;">
                        <select id="attScope" class="fm-input">
                            <option value="all" selected>Tous les membres</option>
                            <option value="mine">Mes attributions</option>
                        </select>
                        <select id="attStatus" class="fm-input">
                            <option value="open" selected>Ouvertes</option>
                            <option value="reconciled">Reconciliees</option>
                            <option value="rejected">Rejetees</option>
                            <option value="all">Toutes</option>
                        </select>
                    </div>
                    <div class="members-table" id="attList">
                        <div class="empty-msg">Chargement...</div>
                    </div>
                </div>
            </div>

            {{-- SUB : Validations (tresorier+) --}}
            <div class="sub-content" id="sub-validations">
                <div class="action-card">
                    <div class="action-card-title">Validations en attente</div>
                    <p class="action-hint">
                        Attributions necessitant l'approbation du tresorier (au-dela du seuil configure dans les parametres).
                    </p>
                    <div class="members-table" id="valList">
                        <div class="empty-msg">Chargement...</div>
                    </div>
                </div>
            </div>

            {{-- SUB : Import (tresorier+) --}}
            <div class="sub-content" id="sub-import">
                <div class="action-card">
                    <div class="action-card-title">Import CSV du stock physique</div>
                    <p class="action-hint">
                        Collez ici un CSV / TSV avec au minimum deux colonnes : <code>slug</code> et <code>quantity</code>.
                        L'import ECRASE la quantite en stock central (pas les attributions en cours).
                        Chaque ligne modifiee genere un mouvement <em>adjustment</em> trace dans l'historique.
                    </p>
                    <div class="action-form">
                        <div class="form-row">
                            <div class="form-group full">
                                <label>Libelle de l'import <span class="optional">(optionnel, visible dans l'historique)</span></label>
                                <input type="text" id="impLabel" class="fm-input" placeholder="Ex: Inventaire hebdo 16/04">
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group full">
                                <label>CSV</label>
                                <textarea id="impCsv" class="fm-input" rows="8" style="font-family:monospace;" placeholder="slug,quantity&#10;weapon_combatpistol,12&#10;ammo_9mm,450"></textarea>
                            </div>
                        </div>
                        <div style="display:flex; gap:8px;">
                            <button class="action-btn mv-btn" id="impBtnPreview">Previsualiser</button>
                            <button class="action-btn sale-btn" id="impBtnCommit" disabled>Valider l'import</button>
                        </div>
                    </div>
                    <div class="members-table" id="impPreview" style="margin-top:12px;"></div>
                    <div id="impErrors" class="alert-banner" style="display:none; margin-top:10px;"></div>
                </div>
            </div>

        </div>

    </div>
</div>
@endsection

@section('scripts')
<script>
window.MC_CATEGORIES = {!! json_encode($categoriesMap) !!};
window.MC_REASONS = {!! json_encode($reasonsMap) !!};
</script>
<script src="{{ asset('js/stocks.js') }}?v={{ filemtime(public_path('js/stocks.js')) }}"></script>
@endsection
