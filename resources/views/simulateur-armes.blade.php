@extends('layouts.app')

@section('title', 'LOST MC — Armurerie')
@section('css')
<link rel="stylesheet" href="{{ asset('css/simulateur-armes.css') }}">
<meta name="csrf-token" content="{{ csrf_token() }}">
@endsection
@section('body-class', 'simulateur-armes')

@section('content')
<div class="menu-board" style="width:960px;">
    <div class="inner-board">

		<div class="header lost-header">
			<img src="{{ asset('img/3651.webp') }}" alt="Lost MC" class="lost-emblem">
			<div class="lost-tagline">SYSTÈME ARMURERIE</div>
			<div class="lost-motto">« Le Tout-Puissant pardonne. Pas les Lost. »</div>
		</div>

        {{-- TAB NAVIGATION --}}
        <div class="tab-bar">
            <button class="tab-btn active" data-tab="simulateur">Simulateur</button>
            <button class="tab-btn" data-tab="membres">Espace Membres</button>
        </div>

        {{-- ============================================ --}}
        {{-- TAB 1: SIMULATEUR (public) --}}
        {{-- ============================================ --}}
        <div class="tab-content active" id="tab-simulateur">
            <div class="sim-section">
                <div class="sim-section-title">Sélection des armes</div>
                <div class="weapons-grid" id="weaponsGrid"></div>
            </div>

            <div class="sim-section" id="resultsSection" style="display:none;">
                <div class="sim-section-title">Pièces par arme</div>
                <div class="results-table" id="piecesTable"></div>

                <div class="sim-section-title">Total pièces nécessaires</div>
                <div class="results-table" id="totalPieces"></div>

                {{-- Stock comparison (logged-in only) --}}
                <div id="simStockCompare" style="display:none;">
                    <div class="sim-section-title">Comparaison avec le stock</div>
                    <div class="results-table" id="simStockTable"></div>
                </div>

                <div class="sim-section-title">Craft de matériaux (table du sud)</div>
                <div class="results-table" id="materialCraft"></div>

                <div class="sim-section-title">Matières premières totales</div>
                <div class="results-table" id="rawMaterials"></div>

                <div class="sim-section-title">Coût estimé</div>
                <div class="results-table" id="costTable"></div>

                <div class="sim-section-title">Temps de craft total</div>
                <div class="craft-time-display" id="craftTime"></div>
            </div>

            <div class="sim-section" id="ammoCraftSection">
                <div class="sim-section-title">Craft munitions</div>
                <p class="ammo-sim-intro">Recette : <strong>1 craft = 10 munitions</strong>. Poudre <strong>100 €</strong>/u, fer au prix saisi, <strong>1 fer → 2 fragments</strong>. <strong>Toutes les colonnes du tableau sont par munition</strong>. Vente <strong>par munition</strong> (multiples de <strong>5 €</strong>) : repères <strong>.45 ACP 100 €</strong> et <strong>7.62×39 300 €</strong> (réf.) ; autres calibres <strong>estimés</strong> pour s’aligner sur le coût matière (fer <strong>30 €</strong>, poudre <strong>100 €</strong>) et sur cette échelle. Ambre si le prix du fer augmente fortement.</p>
                <div class="ammo-sim-params">
                    <label class="ammo-sim-label" for="ammoFerPrice">Prix du fer (€ / unité)</label>
                    <input type="number" class="ammo-sim-input" id="ammoFerPrice" min="0" step="0.01" value="30" inputmode="decimal">
                </div>
                <div class="ammo-craft-wrap">
                    <table class="ammo-craft-table" aria-label="Coûts et marges des munitions">
                        <thead>
                            <tr>
                                <th>Munition</th>
                                <th>Tps craft</th>
                                <th>Pdr / craft</th>
                                <th>Frag / craft</th>
                                <th>Coût / mun (F ach.)</th>
                                <th>Coût / mun (F réc.)</th>
                                <th>Vente / mun</th>
                                <th>Marge / mun (F ach.)</th>
                                <th>Marge / mun (F réc.)</th>
                            </tr>
                        </thead>
                        <tbody id="ammoCraftBody"></tbody>
                        <tfoot>
                            <tr class="ammo-craft-foot">
                                <td colspan="9">€ par munition (prix au multiple de 5 €). Pdr/Frag = par craft. (réf.) repères RP ; (estim.) même logique de marge.</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="ammo-target-block">
                    <div class="sim-section-title">Objectif en munitions</div>
                    <p class="ammo-sim-intro ammo-sim-intro-tight">Saisissez le <strong>nombre de munitions</strong> à fabriquer (ex. 1000 munitions, pas 1000 crafts). Les crafts se font par lots de <strong>10 munitions</strong> : le nombre de crafts est arrondi au supérieur. Le <strong>prix de vente / munition</strong> est pris du tableau par défaut ; vous pouvez le <strong>remplacer</strong> pour tester une marge.</p>
                    <div class="ammo-sim-params ammo-target-params">
                        <label class="ammo-sim-label" for="ammoTargetSlug">Calibre</label>
                        <select id="ammoTargetSlug" class="ammo-sim-select" aria-label="Calibre pour la simulation"></select>
                        <label class="ammo-sim-label" for="ammoTargetMuns">Munitions à fabriquer</label>
                        <input type="number" class="ammo-sim-input ammo-sim-input-muns" id="ammoTargetMuns" min="1" max="9999999" step="1" value="1000" inputmode="numeric">
                        <label class="ammo-sim-label" for="ammoTargetSellPriceMun">Prix vente / mun (optionnel)</label>
                        <input type="number" class="ammo-sim-input" id="ammoTargetSellPriceMun" min="0" step="0.01" placeholder="Tableau" inputmode="decimal" title="Vide = prix du tableau pour ce calibre">
                    </div>
                    <div class="results-table ammo-target-results" id="ammoTargetResults"></div>
                </div>
            </div>
        </div>

        {{-- ============================================ --}}
        {{-- TAB 2: ESPACE MEMBRES --}}
        {{-- ============================================ --}}
        <div class="tab-content" id="tab-membres">

            {{-- Step 1: Select member + enter PIN --}}
            <div class="contract-lock" id="lockMembres">
                <div class="lock-icon">🔒</div>
                <div class="lock-text">Connexion membre — Lost MC</div>
                <select class="lock-input" id="loginMemberSelect" style="width:260px;margin:0 auto 8px;display:block;">
                    <option value="">— Qui êtes-vous ? —</option>
                </select>
                <input type="password" class="lock-input" id="loginPin" placeholder="PIN" autocomplete="off" maxlength="20">
                <button class="lock-btn" id="btnLogin">Se connecter</button>
                <div class="lock-error" id="errLogin"></div>
            </div>

            {{-- Dashboard --}}
            <div id="memberDashboard" style="display:none;">

                <div class="member-bar">
                    <span class="member-bar-name" id="currentMemberName"></span>
                    <span class="member-bar-role" id="currentMemberRole"></span>
                    <button class="member-bar-btn" id="btnLogout">Déconnexion</button>
                    <a class="member-bar-btn admin-btn" href="/armurerie" target="_blank">Admin</a>
                </div>

                <div id="alertBanner" class="alert-banner" style="display:none;"></div>
                <div id="toast" class="toast"></div>

                <div class="sub-tab-bar">
                    <button class="sub-tab active" data-subtab="overview">Vue d'ensemble</button>
                    <button class="sub-tab" data-subtab="actions">Actions</button>
                    <button class="sub-tab" data-subtab="contrats">Contrats</button>
                    <button class="sub-tab" data-subtab="historique">Historique</button>
                    <button class="sub-tab" data-subtab="gestion" id="subTabGestion" style="display:none;">Gestion</button>
                </div>

                {{-- SUB: Overview --}}
                <div class="sub-content active" id="sub-overview">
                    <div class="stats-row" id="statsRow"></div>
                    <div class="sim-section-title">Armes en stock</div>
                    <div class="stock-cards" id="stockWeaponsCards"></div>
                    <div class="sim-section-title">Pièces & Plans</div>
                    <div class="stock-mini-grid" id="stockPiecesGrid"></div>
                    <div class="sim-section-title">Matières premières</div>
                    <div class="stock-mini-grid" id="stockRawGrid"></div>
                </div>

                {{-- SUB: Actions --}}
                <div class="sub-content" id="sub-actions">
                    <div class="action-card">
                        <div class="action-card-title">Déclarer une vente</div>
                        <div class="action-form">
                            <div class="form-row">
                                <div class="form-group"><label>Arme</label><select id="saleWeapon" class="fm-input"></select></div>
                                <div class="form-group sm"><label>Qté</label><input type="number" id="saleQty" class="fm-input" value="1" min="1" max="99"></div>
                                <div class="form-group sm"><label>Prix unit. €</label><input type="number" id="salePrice" class="fm-input" value="0" min="0"></div>
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
                        <p class="action-hint">Coffre, récolte, achat, ajustement...</p>
                        <div class="action-form">
                            <div class="form-row">
                                <div class="form-group"><label>Article</label><select id="mvStock" class="fm-input"></select></div>
                                <div class="form-group sm"><label>Quantité</label><input type="number" id="mvQty" class="fm-input" value="1" min="1" max="999"></div>
                            </div>
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Direction</label>
                                    <div class="direction-toggle">
                                        <button class="dir-btn active" data-dir="in" id="mvDirIn">+ Entrée</button>
                                        <button class="dir-btn" data-dir="out" id="mvDirOut">− Sortie</button>
                                    </div>
                                </div>
                                <div class="form-group"><label>Raison</label><select id="mvReason" class="fm-input"></select></div>
                            </div>
                            <div class="form-row" id="mvCostRow" style="display:none;">
                                <div class="form-group sm"><label>Prix unit. €</label><input type="number" id="mvUnitCost" class="fm-input" value="0" min="0"></div>
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
                    {{-- New contract form --}}
                    <div class="action-card" id="newContractCard">
                        <div class="action-card-title">Nouveau contrat</div>
                        <div class="action-form">
                            <div class="form-row">
                                <div class="form-group"><label>Nom du contrat</label><input type="text" id="ctName" class="fm-input" placeholder="Ex: 3x Cal 50 pour Morana"></div>
                                <div class="form-group"><label>Client</label><input type="text" id="ctClient" class="fm-input" placeholder="Nom du client"></div>
                            </div>
                            <div class="sim-section-title" style="font-size:11px;">Armes commandées</div>
                            <div id="ctItemsContainer">
                                <div class="form-row ct-item-row">
                                    <div class="form-group"><select class="fm-input ct-weapon"></select></div>
                                    <div class="form-group sm"><input type="number" class="fm-input ct-qty" value="1" min="1" max="999" placeholder="Qté"></div>
                                </div>
                            </div>
                            <button type="button" class="action-btn-sm" id="btnAddCtItem">+ Ajouter une arme</button>
                            <div class="form-row" style="margin-top:6px;">
                                <div class="form-group full"><label>Notes <span class="optional">(opt.)</span></label><input type="text" id="ctNotes" class="fm-input" placeholder="..."></div>
                            </div>
                            <button class="action-btn sale-btn" id="btnCreateContract">Créer le contrat</button>
                        </div>
                    </div>

                    <div class="sim-section-title" style="margin-top:12px;">Contrats actifs</div>
                    <div id="contractsList"></div>

                    <div class="sim-section-title" style="margin-top:12px;">À fabriquer (stock déduit)</div>
                    <div class="results-table" id="contractWeaponsToProduce"></div>

                    <div id="contractFullBreakdown" style="display:none;">
                        <div class="sim-section-title">Pièces nécessaires (pour la fabrication)</div>
                        <div class="results-table" id="contractPiecesNeeded"></div>
                        <div class="sim-section-title">Craft de matériaux (table du sud)</div>
                        <div class="results-table" id="contractMaterialCraft"></div>
                        <div class="sim-section-title">Matières premières totales</div>
                        <div class="results-table" id="contractRawMaterials"></div>
                        <div class="sim-section-title">Coût estimé</div>
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
                    <div class="sim-section-title" style="margin-top:10px;">Dernières ventes</div>
                    <div class="movements-list" id="salesList"></div>
                </div>

                {{-- SUB: Gestion (officers only) --}}
                <div class="sub-content" id="sub-gestion">
                    <div class="action-card">
                        <div class="action-card-title">Ajouter un membre</div>
                        <div class="action-form">
                            <div class="form-row">
                                <div class="form-group"><label>Nom RP</label><input type="text" id="newMemberName" class="fm-input" placeholder="Prénom Nom"></div>
                                <div class="form-group sm"><label>PIN</label><input type="text" id="newMemberPin" class="fm-input" placeholder="1234" maxlength="20"></div>
                                <div class="form-group sm"><label>Rôle</label>
                                    <select id="newMemberRole" class="fm-input">
                                        <option value="member">Membre</option>
                                        <option value="officer">Officier</option>
                                    </select>
                                </div>
                            </div>
                            <button class="action-btn sale-btn" id="btnCreateMember">Créer le membre</button>
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
</div>
@endsection

@section('scripts')
<script>
window.WEAPONS = {!! $weaponsJson !!};
window.MEMBERS = {!! $membersJson !!};
</script>
<script src="{{ asset('js/simulateur-armes.js') }}"></script>
@endsection
