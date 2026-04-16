(function () {
    'use strict';

    var auth = window.McAuth;
    if (!auth) return;

    function $(id) { return document.getElementById(id); }
    function esc(s) { if (s == null) return ''; var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
    function money(n) { return '$' + (parseInt(n, 10) || 0).toLocaleString('fr-FR'); }
    function num(n)   { return (parseInt(n, 10) || 0).toLocaleString('fr-FR'); }

    var SLUG = window.MC_STOCK_SLUG;
    var CATEGORIES = window.MC_CATEGORIES || {};
    var REASONS = window.MC_REASONS || {};

    var currentItem = null;

    function updateGate() {
        var notLogged = $('stocksNotLogged');
        var noAccess = $('stocksNoAccess');
        var content = $('stocksContent');
        if (!auth.isLoggedIn) {
            notLogged.style.display = '';
            noAccess.style.display = 'none';
            content.style.display = 'none';
            return;
        }
        notLogged.style.display = 'none';
        tryLoad();
    }

    function tryLoad() {
        auth.apiGet('/stocks/api/item/' + encodeURIComponent(SLUG), function (err, data) {
            if (err || !data || data.error) {
                $('stocksNoAccess').style.display = '';
                $('stocksContent').style.display = 'none';
                return;
            }
            $('stocksNoAccess').style.display = 'none';
            $('stocksContent').style.display = '';
            currentItem = data.item;
            renderGrid(data.item, data.sales_total, data.sales_count);
            var qIn = $('sdQtyInput');
            if (qIn) qIn.value = data.item.quantity;
            renderSummary(data.item);
            renderOpenAttributions(data.open_attributions || []);
            renderMovements(data.movements || []);
        });
    }

    function renderSummary(item) {
        var el = $('sdSummary');
        if (!el) return;
        var parts = [];
        parts.push('<div class="sd-sum-row"><span>Slug</span><code>' + esc(item.slug) + '</code></div>');
        parts.push('<div class="sd-sum-row"><span>Categorie</span><strong>' + esc(CATEGORIES[item.category] || item.category) + '</strong></div>');
        parts.push('<div class="sd-sum-row"><span>Prix de vente</span><strong>' + (item.default_sell_price ? money(item.default_sell_price) : '-') + '</strong></div>');
        parts.push('<div class="sd-sum-row"><span>Prix d\'achat</span><strong>' + (item.default_purchase_price ? money(item.default_purchase_price) : '-') + '</strong></div>');
        parts.push('<div class="sd-sum-row"><span>Poids unitaire</span><strong>' + (item.unit_weight_g ? num(item.unit_weight_g) + ' g' : '-') + '</strong></div>');
        parts.push('<div class="sd-sum-row"><span>Vendable</span><strong>' + (item.is_sellable ? 'Oui' : 'Non') + '</strong></div>');
        parts.push('<div class="sd-sum-row"><span>Actif</span><strong>' + (item.is_active ? 'Oui' : 'Non') + '</strong></div>');
        if (item.notes) {
            parts.push('<div class="sd-sum-row" style="grid-column:1/-1;"><span>Notes</span><em>' + esc(item.notes) + '</em></div>');
        }
        el.innerHTML = parts.join('');
    }

    function openEditForm() {
        if (!currentItem) return;
        $('sdfName').value = currentItem.name || '';
        $('sdfCategory').value = currentItem.category || 'misc';
        $('sdfSellPrice').value = currentItem.default_sell_price != null ? currentItem.default_sell_price : '';
        $('sdfPurchasePrice').value = currentItem.default_purchase_price != null ? currentItem.default_purchase_price : '';
        $('sdfWeight').value = currentItem.unit_weight_g != null ? currentItem.unit_weight_g : '';
        $('sdfNotes').value = currentItem.notes || '';
        $('sdfSellable').checked = !!currentItem.is_sellable;
        $('sdfActive').checked = !!currentItem.is_active;
        $('sdEditForm').style.display = '';
        $('sdEditToggle').textContent = 'Masquer';
    }

    function closeEditForm() {
        $('sdEditForm').style.display = 'none';
        $('sdEditToggle').textContent = 'Modifier';
    }

    function saveEditForm() {
        if (!currentItem) return;
        var name = ($('sdfName').value || '').trim();
        if (!name) { auth.showToast('Le nom est obligatoire', 'error'); return; }

        var sell = $('sdfSellPrice').value;
        var purchase = $('sdfPurchasePrice').value;
        var weight = $('sdfWeight').value;

        var payload = {
            name: name,
            category: $('sdfCategory').value,
            default_sell_price: sell === '' ? null : parseInt(sell, 10),
            default_purchase_price: purchase === '' ? null : parseInt(purchase, 10),
            unit_weight_g: weight === '' ? null : parseInt(weight, 10),
            is_sellable: $('sdfSellable').checked,
            is_active: $('sdfActive').checked,
            notes: ($('sdfNotes').value || '').trim() || null
        };

        var btn = $('sdfSave');
        btn.disabled = true;
        btn.textContent = 'Enregistrement...';

        auth.apiPut('/stocks/api/item/' + encodeURIComponent(SLUG), payload, function (err, data) {
            btn.disabled = false;
            btn.textContent = 'Enregistrer';
            if (err || !data || data.error) {
                var msg = (data && data.error) || 'Erreur';
                if (data && data.messages) {
                    msg = Object.values(data.messages).flat().join(' | ');
                }
                auth.showToast(msg, 'error');
                return;
            }
            auth.showToast(data.message || 'Article mis a jour', 'success');
            closeEditForm();
            tryLoad();
        });
    }

    function renderGrid(item, salesTotal, salesCount) {
        var el = $('sdGrid');
        var qtyClass = item.quantity <= 0 ? 'bad' : (item.quantity < 5 ? 'warn' : 'ok');
        var outClass = item.out_attributed > 0 ? 'warn' : '';
        el.innerHTML =
            '<div class="sd-stat ' + qtyClass + '"><div class="sd-label">Stock central</div><div class="sd-value">' + num(item.quantity) + '</div></div>' +
            '<div class="sd-stat ' + outClass + '"><div class="sd-label">Exterieur</div><div class="sd-value">' + num(item.out_attributed || 0) + '</div></div>' +
            '<div class="sd-stat"><div class="sd-label">Prix vente unit.</div><div class="sd-value">' + (item.default_sell_price ? money(item.default_sell_price) : '-') + '</div></div>' +
            '<div class="sd-stat"><div class="sd-label">Prix achat unit.</div><div class="sd-value">' + (item.default_purchase_price ? money(item.default_purchase_price) : '-') + '</div></div>' +
            '<div class="sd-stat"><div class="sd-label">Poids unit.</div><div class="sd-value">' + (item.unit_weight_g ? num(item.unit_weight_g) + ' g' : '-') + '</div></div>' +
            '<div class="sd-stat"><div class="sd-label">Ventes totales</div><div class="sd-value">' + money(salesTotal) + '</div><div style="color:#888;font-size:10px;">' + salesCount + ' vente(s)</div></div>';
    }

    function renderOpenAttributions(rows) {
        var el = $('sdOpenAttr');
        if (!rows.length) {
            el.innerHTML = '<div class="empty-msg">Aucune attribution en cours.</div>';
            return;
        }
        el.innerHTML = rows.map(function (a) {
            var statusClass = a.status || 'open';
            var extB = a.from_external ? ' <span class="a-status pending">Hors stock</span>' : '';
            return '<div class="att-row">' +
                '<div class="a-item">' + esc(a.attributed_to_name || '?') + extB + '</div>' +
                '<div class="a-qty">x' + a.quantity_abs + '</div>' +
                '<div class="a-meta">par ' + esc(a.by_name) + '<br>' + esc(a.date_full) + '</div>' +
                '<div class="a-meta"><span class="a-status ' + statusClass + '">' + (statusClass === 'pending' ? 'Attente' : 'En cours') + '</span>' +
                (a.notes ? '<br><em>' + esc(a.notes) + '</em>' : '') + '</div>' +
                '<div class="att-actions"></div>' +
                '</div>';
        }).join('');
    }

    function movementHistoryQtyChange(m) {
        if (m.reason === 'attribution' && m.attribution_original_abs != null) {
            return -m.attribution_original_abs;
        }
        return m.quantity_change;
    }

    function renderMovements(rows) {
        var el = $('sdMovements');
        if (!rows.length) {
            el.innerHTML = '<div class="empty-msg">Aucun mouvement.</div>';
            return;
        }
        el.innerHTML = rows.map(function (m) {
            var qc = movementHistoryQtyChange(m);
            var dir = qc > 0 ? '+' : (qc < 0 ? '-' : '0');
            var dirClass = qc > 0 ? 'ok' : (qc < 0 ? 'bad' : '');
            return '<div class="member-row">' +
                '<div class="member-info">' +
                    '<div class="member-name">' + esc(REASONS[m.reason] || m.reason) +
                    (m.attributed_to_name ? ' <span class="sale-qty">-> ' + esc(m.attributed_to_name) + '</span>' : '') +
                    '</div>' +
                    '<div class="member-meta">par ' + esc(m.by_name) + ' &middot; ' + esc(m.date_full) +
                        (m.notes ? ' &middot; <em>' + esc(m.notes) + '</em>' : '') +
                    '</div>' +
                '</div>' +
                '<div class="member-actions sale-totals">' +
                    '<div class="sale-total ' + dirClass + '">' + dir + Math.abs(qc) + '</div>' +
                    (m.unit_cost ? '<div class="sale-unit">' + money(m.unit_cost) + ' / u</div>' : '') +
                '</div>' +
                '</div>';
        }).join('');
    }

    function applyQtyChange() {
        if (!currentItem) return;
        var q = $('sdQtyInput');
        if (!q) return;
        var n = parseInt(q.value, 10);
        if (isNaN(n)) {
            if (auth.showToast) auth.showToast('Quantite invalide', 'error');
            return;
        }
        var btn = $('sdQtySave');
        btn.disabled = true;
        btn.textContent = '...';
        auth.apiPut('/stocks/api/item/' + encodeURIComponent(SLUG) + '/quantity', { quantity: n }, function (err, data) {
            btn.disabled = false;
            btn.textContent = 'Appliquer';
            if (err || !data || data.error) {
                var msg = (data && data.error) || 'Erreur';
                if (data && data.messages) {
                    msg = Object.values(data.messages).flat().join(' | ');
                }
                if (auth.showToast) auth.showToast(msg, 'error');
                return;
            }
            if (auth.showToast) auth.showToast(data.message || 'Quantite mise a jour', 'success');
            tryLoad();
        });
    }

    function bindEvents() {
        var qtyBtn = $('sdQtySave');
        if (qtyBtn) qtyBtn.addEventListener('click', applyQtyChange);
        var toggle = $('sdEditToggle');
        if (toggle) {
            toggle.addEventListener('click', function () {
                var form = $('sdEditForm');
                if (!form) return;
                if (form.style.display === 'none') {
                    openEditForm();
                } else {
                    closeEditForm();
                }
            });
        }
        var save = $('sdfSave');
        if (save) save.addEventListener('click', saveEditForm);
        var cancel = $('sdfCancel');
        if (cancel) cancel.addEventListener('click', closeEditForm);
    }

    bindEvents();
    updateGate();
    auth.onLogin(updateGate);
    auth.onLogout(updateGate);
})();
