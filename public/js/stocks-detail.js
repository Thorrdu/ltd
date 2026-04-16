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
            renderGrid(data.item, data.sales_total, data.sales_count);
            renderOpenAttributions(data.open_attributions || []);
            renderMovements(data.movements || []);
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
            return '<div class="att-row">' +
                '<div class="a-item">' + esc(a.attributed_to_name || '?') + '</div>' +
                '<div class="a-qty">x' + a.quantity_abs + '</div>' +
                '<div class="a-meta">par ' + esc(a.by_name) + '<br>' + esc(a.date_full) + '</div>' +
                '<div class="a-meta"><span class="a-status ' + statusClass + '">' + (statusClass === 'pending' ? 'Attente' : 'En cours') + '</span>' +
                (a.notes ? '<br><em>' + esc(a.notes) + '</em>' : '') + '</div>' +
                '<div class="att-actions"></div>' +
                '</div>';
        }).join('');
    }

    function renderMovements(rows) {
        var el = $('sdMovements');
        if (!rows.length) {
            el.innerHTML = '<div class="empty-msg">Aucun mouvement.</div>';
            return;
        }
        el.innerHTML = rows.map(function (m) {
            var dir = m.quantity_change > 0 ? '+' : (m.quantity_change < 0 ? '-' : '0');
            var dirClass = m.quantity_change > 0 ? 'ok' : (m.quantity_change < 0 ? 'bad' : '');
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
                    '<div class="sale-total ' + dirClass + '">' + dir + Math.abs(m.quantity_change) + '</div>' +
                    (m.unit_cost ? '<div class="sale-unit">' + money(m.unit_cost) + ' / u</div>' : '') +
                '</div>' +
                '</div>';
        }).join('');
    }

    updateGate();
    auth.onLogin(updateGate);
    auth.onLogout(updateGate);
})();
