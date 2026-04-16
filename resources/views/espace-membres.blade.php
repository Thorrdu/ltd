@extends('layouts.mc')

@section('title', 'LOST MC -- Espace Membres')

@section('content')
<div class="menu-board" style="width:960px;">
    <div class="inner-board">

        <div class="mc-page-header">
            <img src="{{ asset('img/3651.webp') }}" alt="Lost MC">
            <div class="mc-page-title">Espace Membres</div>
            <div class="mc-page-motto">Le Tout-Puissant pardonne. Pas les Lost.</div>
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
                <button class="sub-tab" data-subtab="attributions">Mes attributions</button>
                <button class="sub-tab" data-subtab="actions">Mouvements</button>
                <button class="sub-tab" data-subtab="contrats">Contrats</button>
                <button class="sub-tab" data-subtab="historique">Historique</button>
                <button class="sub-tab" data-subtab="profil">Mon profil</button>
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

            {{-- SUB: Mes attributions --}}
            <div class="sub-content" id="sub-attributions">
                <div class="action-card">
                    <div class="action-card-title">Mes attributions en cours</div>
                    <p class="action-hint">
                        Articles confies par un officier et qui n'ont pas encore ete reconcilies.
                        Quand l'article est vendu, utilisez le bouton "Vendu" (redirige vers la page Ventes).
                        En cas de retour au coffre, perte, saisie ou don, utilisez les autres boutons.
                    </p>
                    <div class="members-toolbar">
                        <select id="emAttStatus" class="fm-input">
                            <option value="open" selected>En cours</option>
                            <option value="reconciled">Reconciliees</option>
                            <option value="rejected">Rejetees</option>
                            <option value="all">Toutes</option>
                        </select>
                    </div>
                    <div class="members-table" id="emAttList">
                        <div class="empty-msg">Chargement...</div>
                    </div>
                </div>
                <div class="action-card" style="margin-top:10px;">
                    <div class="action-card-title">Besoin d'enregistrer une vente ?</div>
                    <p class="action-hint">
                        Pour toute nouvelle vente (sans attribution prealable), rendez-vous sur la page dediee.
                    </p>
                    <a href="/ventes" class="action-btn sale-btn" style="text-decoration:none; display:inline-block;">Aller a la page Ventes</a>
                </div>
            </div>

            {{-- SUB: Mouvements de stock --}}
            <div class="sub-content" id="sub-actions">
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

            {{-- SUB: Mon profil --}}
            <div class="sub-content" id="sub-profil">
                <div class="profile-card">
                    <div class="profile-header">
                        <div class="profile-avatar" id="profileAvatar">?</div>
                        <div class="profile-id">
                            <div class="profile-name" id="profileName">--</div>
                            <div class="profile-meta">
                                <span class="profile-role-badge" id="profileRoleBadge">--</span>
                                <span class="profile-status" id="profileStatus">Actif</span>
                            </div>
                        </div>
                    </div>
                    <div class="profile-stats" id="profileStats"></div>
                </div>

                <div class="action-card" style="margin-top:10px;">
                    <div class="action-card-title">Changer mon PIN</div>
                    <p class="action-hint">Au moins 4 chiffres. A retenir pour vous reconnecter.</p>
                    <div class="action-form">
                        <div class="form-row">
                            <div class="form-group"><label>PIN actuel</label><input type="password" id="pinCurrent" class="fm-input" maxlength="20" autocomplete="current-password"></div>
                            <div class="form-group"><label>Nouveau PIN</label><input type="password" id="pinNew" class="fm-input" maxlength="20" autocomplete="new-password"></div>
                            <div class="form-group"><label>Confirmer</label><input type="password" id="pinConfirm" class="fm-input" maxlength="20" autocomplete="new-password"></div>
                        </div>
                        <button class="action-btn mv-btn" id="btnChangePin">Modifier mon PIN</button>
                    </div>
                </div>

                <div class="action-card" id="profileManageCard" style="display:none;">
                    <div class="action-card-title">Gestion des membres</div>
                    <p class="action-hint">Vous avez les droits pour gerer les utilisateurs. Ajouter des membres, changer des roles, reinitialiser des PIN et modifier la matrice d'acces.</p>
                    <a href="/membres" class="action-btn sale-btn" style="text-decoration:none; display:inline-block;">Acceder a la gestion des membres</a>
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
window.MC_CATEGORIES = @json(\App\Models\StockItem::CATEGORIES);
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
        if (loggedIn) loadMyAttributions();
    }
    toggle();
    if (window.McAuth) {
        window.McAuth.onLogin(toggle);
        window.McAuth.onLogout(toggle);
    }

    // ---- Mes attributions (Phase 3.3) ----
    var auth = window.McAuth;
    var CATEGORIES = window.MC_CATEGORIES || {};
    function esc(s) { if (s == null) return ''; var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
    function money(n) { return '$' + (parseInt(n, 10) || 0).toLocaleString('fr-FR'); }

    function loadMyAttributions() {
        if (!auth || !auth.isLoggedIn) return;
        var status = document.getElementById('emAttStatus').value || 'open';
        auth.apiGet('/stocks/api/attributions?scope=mine&status=' + encodeURIComponent(status), function (err, data) {
            var el = document.getElementById('emAttList');
            if (!el) return;
            if (err || !data || data.error) {
                el.innerHTML = '<div class="empty-msg">Erreur de chargement.</div>';
                return;
            }
            var rows = data.attributions || [];
            if (!rows.length) {
                el.innerHTML = '<div class="empty-msg">Aucune attribution dans ce statut.</div>';
                return;
            }
            el.innerHTML = rows.map(renderAttRow).join('');
            el.querySelectorAll('[data-em-action]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var id = parseInt(btn.getAttribute('data-id'), 10);
                    var action = btn.getAttribute('data-em-action');
                    var maxQ = parseInt(btn.getAttribute('data-max'), 10) || 0;
                    handleReconcile(id, action, maxQ);
                });
            });
        });
    }

    function renderAttRow(a) {
        var statusClass = a.status || 'open';
        var statusLabel = {
            open: 'En cours',
            pending: 'Attente tresorier',
            reconciled: 'Reconciliee',
            rejected: 'Rejetee'
        }[statusClass] || statusClass;

        var actions = '';
        if (a.status === 'open' || a.status === 'pending') {
            var qs = '?stock_item_id=' + a.stock_item_id + '&quantity=' + a.quantity_abs + '&attribution_id=' + a.id;
            actions =
                '<a class="btn-xs sell" href="/ventes' + qs + '">Vendu</a>' +
                '<button class="btn-xs return" data-em-action="return" data-id="' + a.id + '" data-max="' + a.quantity_abs + '">Retour</button>' +
                '<button class="btn-xs loss" data-em-action="loss" data-id="' + a.id + '" data-max="' + a.quantity_abs + '">Perte</button>' +
                '<button class="btn-xs gift" data-em-action="gift" data-id="' + a.id + '" data-max="' + a.quantity_abs + '">Don</button>';
        }

        var extBadge = a.from_external ? ' <span class="a-status pending">Hors stock</span>' : '';

        return '<div class="att-row">' +
            '<div class="a-item">' + esc(a.item_name) +
                ' <span class="ts-role-badge role-' + esc(a.category || 'misc') + '">' + esc(CATEGORIES[a.category] || a.category) + '</span>' +
                extBadge +
            '</div>' +
            '<div class="a-qty">x' + a.quantity_abs + '</div>' +
            '<div class="a-meta">Attribue par <strong>' + esc(a.by_name) + '</strong><br>' + esc(a.date_full) + '</div>' +
            '<div class="a-meta"><span class="a-status ' + statusClass + '">' + esc(statusLabel) + '</span>' +
                (a.estimated_value ? '<br>' + money(a.estimated_value) : '') +
                (a.notes ? '<br><em>' + esc(a.notes) + '</em>' : '') +
            '</div>' +
            '<div class="att-actions">' + actions + '</div>' +
            '</div>';
    }

    function handleReconcile(id, action, maxQty) {
        var qtyStr = prompt('Quantite concernee (max ' + maxQty + ') :', String(maxQty));
        if (qtyStr === null) return;
        var qty = parseInt(qtyStr, 10);
        if (!qty || qty < 1 || qty > maxQty) {
            auth.showToast('Quantite invalide (1 a ' + maxQty + ')', 'error');
            return;
        }

        var notes = '';
        if (action === 'loss') {
            notes = prompt('Motif de la perte (obligatoire) :', '');
            if (!notes) return;
        } else if (action === 'gift') {
            notes = prompt('Beneficiaire du don (obligatoire) :', '');
            if (!notes) return;
        } else if (action === 'return') {
            notes = prompt('Notes (optionnel) :', '') || '';
        }

        auth.apiPost('/stocks/api/reconcile/' + id, {
            action: action,
            notes: notes || null,
            quantity: qty
        }, function (err, data) {
            if (err || !data || data.error) {
                auth.showToast((data && data.error) || 'Erreur', 'error');
                return;
            }
            auth.showToast(data.message || 'Reconciliee', 'success');
            loadMyAttributions();
        });
    }

    var statusEl = document.getElementById('emAttStatus');
    if (statusEl) statusEl.addEventListener('change', loadMyAttributions);

    // Reload when the sub-tab is activated.
    document.querySelectorAll('.sub-tab[data-subtab="attributions"]').forEach(function (b) {
        b.addEventListener('click', loadMyAttributions);
    });
})();
</script>
@endsection
