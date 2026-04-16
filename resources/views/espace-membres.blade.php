@extends('layouts.mc')

@section('title', 'LOST MC -- Espace Membres')

@section('content')
<div class="menu-board" style="width:960px;">
    <div class="inner-board">

        <div class="mc-page-header">
            <img src="{{ asset('img/3651.webp') }}" alt="Lost MC">
            <div class="mc-page-title">Espace Membres</div>
            <a href="/mc" class="mc-page-back">&larr; Retour a l'accueil</a>
        </div>

        {{-- Non connecte --}}
        <div id="membresNotLoggedIn" class="contract-lock">
            <div class="lock-text">Connectez-vous via le bouton en haut a droite pour acceder a cette section.</div>
        </div>

        {{-- Dashboard (visible une fois connecte) --}}
        <div id="memberDashboard" style="display:none;">

            <div id="alertBanner" class="alert-banner" style="display:none;"></div>

            <div class="sub-tab-bar">
                <button class="sub-tab active" data-subtab="overview">Stocks</button>
                <button class="sub-tab" data-subtab="actions">Ventes</button>
                <button class="sub-tab" data-subtab="contrats">Contrats</button>
                <button class="sub-tab" data-subtab="historique">Historique</button>
                <button class="sub-tab" data-subtab="gestion" id="subTabGestion" style="display:none;">Gestion</button>
            </div>

            {{-- SUB: Stocks --}}
            <div class="sub-content active" id="sub-overview">
                <div class="stats-row" id="statsRow"></div>
                <div class="sim-section-title">Armes en stock</div>
                <div class="stock-cards" id="stockWeaponsCards"></div>
                <div class="sim-section-title">Pieces & Plans</div>
                <div class="stock-mini-grid" id="stockPiecesGrid"></div>
                <div class="sim-section-title">Matieres premieres</div>
                <div class="stock-mini-grid" id="stockRawGrid"></div>
            </div>

            {{-- SUB: Ventes & Mouvements --}}
            <div class="sub-content" id="sub-actions">
                <div class="action-card">
                    <div class="action-card-title">Declarer une vente</div>
                    <div class="action-form">
                        <div class="form-row">
                            <div class="form-group"><label>Arme</label><select id="saleWeapon" class="fm-input"></select></div>
                            <div class="form-group sm"><label>Qte</label><input type="number" id="saleQty" class="fm-input" value="1" min="1" max="99"></div>
                            <div class="form-group sm"><label>Prix unit. EUR</label><input type="number" id="salePrice" class="fm-input" value="0" min="0"></div>
                        </div>
                        <div class="form-row">
                            <div class="form-group"><label>Acheteur</label><input type="text" id="saleBuyer" class="fm-input" placeholder="Nom du client"></div>
                            <div class="form-group"><label>Notes <span class="optional">(opt.)</span></label><input type="text" id="saleNotes" class="fm-input" placeholder="..."></div>
                        </div>
                        <div class="sale-preview" id="salePreview"></div>
                        <button class="action-btn sale-btn" id="btnSale">Enregistrer la vente</button>
                    </div>
                </div>

                <div class="action-card">
                    <div class="action-card-title">Mouvement de stock</div>
                    <p class="action-hint">Coffre, recolte, achat, ajustement...</p>
                    <div class="action-form">
                        <div class="form-row">
                            <div class="form-group"><label>Article</label><select id="mvStock" class="fm-input"></select></div>
                            <div class="form-group sm"><label>Quantite</label><input type="number" id="mvQty" class="fm-input" value="1" min="1" max="999"></div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Direction</label>
                                <div class="direction-toggle">
                                    <button class="dir-btn active" data-dir="in" id="mvDirIn">+ Entree</button>
                                    <button class="dir-btn" data-dir="out" id="mvDirOut">- Sortie</button>
                                </div>
                            </div>
                            <div class="form-group"><label>Raison</label><select id="mvReason" class="fm-input"></select></div>
                        </div>
                        <div class="form-row" id="mvCostRow" style="display:none;">
                            <div class="form-group sm"><label>Prix unit. EUR</label><input type="number" id="mvUnitCost" class="fm-input" value="0" min="0"></div>
                            <div class="form-group"><div class="mv-cost-preview" id="mvCostPreview"></div></div>
                        </div>
                        <div class="form-row">
                            <div class="form-group full"><label>Notes <span class="optional">(opt.)</span></label><input type="text" id="mvNotes" class="fm-input" placeholder="Ex: pris 2 WN du coffre"></div>
                        </div>
                        <button class="action-btn mv-btn" id="btnMovement">Enregistrer le mouvement</button>
                    </div>
                </div>
            </div>

            {{-- SUB: Contrats --}}
            <div class="sub-content" id="sub-contrats">
                <div class="action-card" id="newContractCard">
                    <div class="action-card-title">Nouveau contrat</div>
                    <div class="action-form">
                        <div class="form-row">
                            <div class="form-group"><label>Nom du contrat</label><input type="text" id="ctName" class="fm-input" placeholder="Ex: 3x Cal 50 pour Morana"></div>
                            <div class="form-group"><label>Client</label><input type="text" id="ctClient" class="fm-input" placeholder="Nom du client"></div>
                        </div>
                        <div class="sim-section-title" style="font-size:11px;">Armes commandees</div>
                        <div id="ctItemsContainer">
                            <div class="form-row ct-item-row">
                                <div class="form-group"><select class="fm-input ct-weapon"></select></div>
                                <div class="form-group sm"><input type="number" class="fm-input ct-qty" value="1" min="1" max="999" placeholder="Qte"></div>
                            </div>
                        </div>
                        <button type="button" class="action-btn-sm" id="btnAddCtItem">+ Ajouter une arme</button>
                        <div class="form-row" style="margin-top:6px;">
                            <div class="form-group full"><label>Notes <span class="optional">(opt.)</span></label><input type="text" id="ctNotes" class="fm-input" placeholder="..."></div>
                        </div>
                        <button class="action-btn sale-btn" id="btnCreateContract">Creer le contrat</button>
                    </div>
                </div>

                <div class="sim-section-title" style="margin-top:12px;">Contrats actifs</div>
                <div id="contractsList"></div>

                <div class="sim-section-title" style="margin-top:12px;">A fabriquer (stock deduit)</div>
                <div class="results-table" id="contractWeaponsToProduce"></div>

                <div id="contractFullBreakdown" style="display:none;">
                    <div class="sim-section-title">Pieces necessaires (pour la fabrication)</div>
                    <div class="results-table" id="contractPiecesNeeded"></div>
                    <div class="sim-section-title">Craft de materiaux (table du sud)</div>
                    <div class="results-table" id="contractMaterialCraft"></div>
                    <div class="sim-section-title">Matieres premieres totales</div>
                    <div class="results-table" id="contractRawMaterials"></div>
                    <div class="sim-section-title">Cout estime</div>
                    <div class="results-table" id="contractCostTable"></div>
                    <div class="sim-section-title">Temps de craft total</div>
                    <div class="craft-time-display" id="contractCraftTime"></div>
                </div>

                <div class="sim-section-title" style="margin-top:16px;">Tous les contrats</div>
                <div id="allContractsList"></div>
            </div>

            {{-- SUB: Historique --}}
            <div class="sub-content" id="sub-historique">
                <div class="sim-section-title">Derniers mouvements</div>
                <div class="movements-list" id="movementsList"></div>
                <div class="sim-section-title" style="margin-top:10px;">Dernieres ventes</div>
                <div class="movements-list" id="salesList"></div>
            </div>

            {{-- SUB: Gestion (officers only) --}}
            <div class="sub-content" id="sub-gestion">
                <div class="action-card">
                    <div class="action-card-title">Ajouter un membre</div>
                    <div class="action-form">
                        <div class="form-row">
                            <div class="form-group"><label>Nom RP</label><input type="text" id="newMemberName" class="fm-input" placeholder="Prenom Nom"></div>
                            <div class="form-group sm"><label>PIN</label><input type="text" id="newMemberPin" class="fm-input" placeholder="1234" maxlength="20"></div>
                            <div class="form-group sm"><label>Role</label>
                                <select id="newMemberRole" class="fm-input">
                                    <option value="member">Membre</option>
                                    <option value="officer">Officier</option>
                                </select>
                            </div>
                        </div>
                        <button class="action-btn sale-btn" id="btnCreateMember">Creer le membre</button>
                    </div>
                </div>

                <div class="sim-section-title">Membres</div>
                <div id="membersList"></div>

                <div class="action-card" style="margin-top:10px;">
                    <div class="action-card-title">Changer mon PIN</div>
                    <div class="action-form">
                        <div class="form-row">
                            <div class="form-group"><label>PIN actuel</label><input type="password" id="pinCurrent" class="fm-input" maxlength="20"></div>
                            <div class="form-group"><label>Nouveau PIN</label><input type="password" id="pinNew" class="fm-input" maxlength="20"></div>
                        </div>
                        <button class="action-btn mv-btn" id="btnChangePin">Modifier</button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection

@section('scripts')
<script>
window.WEAPONS = {!! $weaponsJson !!};
window.MEMBERS = {!! $membersJson !!};
</script>
<script src="{{ asset('js/simulateur-armes.js') }}"></script>
<script>
(function() {
    var dash = document.getElementById('memberDashboard');
    var msg = document.getElementById('membresNotLoggedIn');
    function toggle() {
        var loggedIn = window.McAuth && window.McAuth.isLoggedIn;
        if (dash) dash.style.display = loggedIn ? '' : 'none';
        if (msg) msg.style.display = loggedIn ? 'none' : '';
    }
    toggle();
    if (window.McAuth) {
        window.McAuth.onLogin(toggle);
        window.McAuth.onLogout(toggle);
    }
})();
</script>
@endsection
