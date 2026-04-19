@extends('layouts.mc')

@section('title', 'LOST MC -- Stocks')

@section('content')
<div class="menu-board mc-board-xl">
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
                <button class="sub-tab" data-subtab="movement">Mouvement</button>
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
                    <button class="action-btn-sm" id="btnNewItem" style="margin-left:auto;">+ Nouvel article</button>
                </div>

                {{-- Formulaire creation article (masque par defaut) --}}
                <div class="action-card" id="newItemForm" style="display:none; margin-bottom:12px;">
                    <div class="action-card-title">Creer un nouvel article</div>
                    <div class="action-form">
                        <div class="form-row">
                            <div class="form-group"><label>Nom</label><input type="text" id="niName" class="fm-input" maxlength="120" placeholder="Ex: Brique de cocaine"></div>
                            <div class="form-group sm"><label>Categorie</label>
                                <select id="niCategory" class="fm-input">
                                    @foreach($categoriesMap as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group sm"><label>Quantite initiale</label><input type="number" id="niQty" class="fm-input" value="0" min="0" max="999999999"></div>
                            <div class="form-group sm"><label>Prix vente ($)</label><input type="number" id="niSellPrice" class="fm-input" value="0" min="0"></div>
                            <div class="form-group sm"><label>Prix achat ($)</label><input type="number" id="niPurchPrice" class="fm-input" value="0" min="0"></div>
                            <div class="form-group sm"><label>Poids (g)</label><input type="number" id="niWeight" class="fm-input" value="0" min="0"></div>
                        </div>
                        <div class="form-row">
                            <div class="form-group full"><label>Notes <span class="optional">(opt.)</span></label><input type="text" id="niNotes" class="fm-input" placeholder="..."></div>
                        </div>
                        <div style="display:flex; gap:8px;">
                            <button class="action-btn sale-btn" id="niBtnSave" style="flex:1;">Creer l'article</button>
                            <button class="action-btn-sm" id="niBtnCancel">Annuler</button>
                        </div>
                    </div>
                </div>
                <div class="stocks-totals" id="stocksTotals"></div>
                <div class="stocks-list" id="stocksList">
                    <div class="empty-msg">Chargement...</div>
                </div>
            </div>

            {{-- SUB : Mouvement de stock --}}
            <div class="sub-content" id="sub-movement">
                <div class="action-card">
                    <div class="action-card-title">Enregistrer un mouvement de stock</div>
                    <p class="action-hint">
                        Entree (achat, recolte, craft) ou sortie (pret, don, perte, ajustement) d'articles dans le coffre central.
                    </p>
                    <div class="action-form">
                        <div class="form-row">
                            <div class="form-group full"><label>Article</label><select id="mItem" class="fm-input"></select></div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Direction</label>
                                <div class="direction-toggle">
                                    <button class="dir-btn active" data-dir="in" id="mDirIn">+ Entree</button>
                                    <button class="dir-btn" data-dir="out" id="mDirOut">- Sortie</button>
                                </div>
                            </div>
                            <div class="form-group sm"><label>Quantite</label><input type="number" id="mQty" class="fm-input" value="1" min="1" max="999999999"></div>
                            <div class="form-group"><label>Raison</label>
                                <select id="mReason" class="fm-input">
                                    @foreach($reasonsMap as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-row" id="mCostRow" style="display:none;">
                            <div class="form-group sm"><label>Cout unitaire ($)</label><input type="number" id="mUnitCost" class="fm-input" value="0" min="0"></div>
                            <div class="form-group"><div class="mv-cost-preview" id="mCostPreview"></div></div>
                        </div>
                        <div class="form-row">
                            <div class="form-group full"><label>Notes <span class="optional">(opt.)</span></label><input type="text" id="mNotes" class="fm-input" placeholder="Ex: achat 5x metal a la fonderie"></div>
                        </div>
                        <button class="action-btn mv-btn" id="mBtnSave">Enregistrer le mouvement</button>
                    </div>
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

                    {{-- Barre d'actions groupees --}}
                    <div class="att-bulk-bar" id="attBulkBar" style="display:none;">
                        <label class="att-bulk-count" id="attBulkCount">0 selectionnee(s)</label>
                        <input type="text" id="attBulkMotif" class="fm-input fm-sm" placeholder="Motif (opt.)" style="flex:1; max-width:250px;">
                        <button class="btn-xs return" id="attBulkReturn">Retour groupé</button>
                        <button class="btn-xs" id="attBulkCancel" style="background:rgba(255,165,0,0.15); color:#ffa500;">Annuler groupé</button>
                        <button class="btn-xs loss" id="attBulkLoss">Perte groupée</button>
                        <button class="btn-xs" id="attBulkAlready" style="background:rgba(100,200,100,0.12); color:#6c6;">Déjà en stock</button>
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
