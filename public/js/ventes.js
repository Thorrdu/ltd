(function () {
    'use strict';

    var auth = window.McAuth;
    if (!auth) return;

    function $(id) { return document.getElementById(id); }
    function esc(s) { if (s == null) return ''; var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
    function money(n) { return '$' + (parseInt(n, 10) || 0).toLocaleString('fr-FR'); }

    // Labels de categories affiches dans les optgroups et les badges.
    var CATEGORY_LABELS = {
        weapon_finished: 'Armes',
        ammo:            'Munitions',
        melee:           'Armes blanches',
        drug:            'Drogues',
        drug_raw:        'Drogues (matieres)',
        misc:            'Divers'
    };

    // Ordre d'affichage des optgroups.
    var CATEGORY_ORDER = ['weapon_finished', 'ammo', 'melee', 'drug', 'drug_raw', 'misc'];

    var state = {
        catalog: window.MC_VENTES_CATALOG || [],
        catalogById: {},
        todaySales: [],
        histSales: [],
        histScope: 'mine',
        histPeriod: 'today'
    };

    state.catalog.forEach(function (it) { state.catalogById[it.id] = it; });

    var itemTs = null;

    // ── VISIBILITY GATE ────────────────────────────────────

    function updateGate() {
        var notLogged = $('ventesNotLogged');
        var noAccess = $('ventesNoAccess');
        var content = $('ventesContent');

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
        auth.apiGet('/ventes/api/list?scope=mine&period=today', function (err, data) {
            if (err || !data || data.error) {
                $('ventesNoAccess').style.display = '';
                $('ventesContent').style.display = 'none';
                return;
            }
            $('ventesNoAccess').style.display = 'none';
            $('ventesContent').style.display = '';
            state.todaySales = data.sales || [];
            renderToday(data.totals || {});
            populateItemSelect();
            refreshHistory();
        });
    }

    // ── ITEM SELECT ────────────────────────────────────────

    function populateItemSelect() {
        var sel = $('vItem');
        if (!sel) return;
        if (itemTs) { try { itemTs.destroy(); } catch (e) {} itemTs = null; }

        // Groupement par category.
        var grouped = {};
        state.catalog.forEach(function (it) {
            if (!grouped[it.category]) grouped[it.category] = [];
            grouped[it.category].push(it);
        });

        sel.innerHTML = '<option value="">-- Rechercher un article --</option>';
        CATEGORY_ORDER.forEach(function (cat) {
            if (!grouped[cat] || !grouped[cat].length) return;
            var og = document.createElement('optgroup');
            og.label = CATEGORY_LABELS[cat] || cat;
            grouped[cat].forEach(function (it) {
                var opt = document.createElement('option');
                opt.value = it.id;
                var label = it.name;
                if (it.default_sell_price) label += '  (' + money(it.default_sell_price) + ')';
                opt.textContent = label;
                opt.setAttribute('data-category', it.category);
                opt.setAttribute('data-price', it.default_sell_price || 0);
                og.appendChild(opt);
            });
            sel.appendChild(og);
        });

        if (typeof TomSelect !== 'undefined') {
            itemTs = new TomSelect(sel, {
                placeholder: 'Rechercher un article (arme, munition, drogue, arme blanche...)',
                searchField: ['text'],
                maxOptions: 500,
                plugins: ['dropdown_input'],
                render: {
                    option: function (data, escape) {
                        var it = state.catalogById[data.value];
                        if (!it) return '<div>' + escape(data.text) + '</div>';
                        var price = it.default_sell_price ? '<span class="ts-stock-qty">' + money(it.default_sell_price) + '</span>' : '';
                        return '<div>' + escape(it.name) + price + '</div>';
                    },
                    item: function (data, escape) {
                        return '<div>' + escape(data.text) + '</div>';
                    }
                }
            });
        }
    }

    function onItemChange() {
        var sel = $('vItem');
        var id = parseInt(sel.value, 10);
        if (!id) return;
        var it = state.catalogById[id];
        if (!it) return;
        var qty = parseInt($('vQty').value, 10) || 1;
        if (it.default_sell_price) {
            $('vTotal').value = it.default_sell_price * qty;
        }
        recomputeUnit();
    }

    function recomputeUnit() {
        var qty = parseInt($('vQty').value, 10) || 1;
        var total = parseInt($('vTotal').value, 10) || 0;
        var unit = qty > 0 ? Math.round(total / qty) : 0;
        $('vUnit').value = unit ? money(unit) : '';
    }

    function onQtyChange() {
        var sel = $('vItem');
        var id = parseInt(sel.value, 10);
        if (id) {
            var it = state.catalogById[id];
            if (it && it.default_sell_price) {
                var qty = parseInt($('vQty').value, 10) || 1;
                var current = parseInt($('vTotal').value, 10) || 0;
                var previousQty = Math.round(current / it.default_sell_price);
                if (current === 0 || Math.abs(previousQty * it.default_sell_price - current) < it.default_sell_price) {
                    $('vTotal').value = it.default_sell_price * qty;
                }
            }
        }
        recomputeUnit();
    }

    // ── SAVE ───────────────────────────────────────────────

    function saveSale() {
        var sel = $('vItem');
        var stockItemId = parseInt(sel.value, 10);
        var qty = parseInt($('vQty').value, 10);
        var total = parseInt($('vTotal').value, 10);
        var buyer = ($('vBuyer').value || '').trim();
        var notes = ($('vNotes').value || '').trim();

        if (!stockItemId) { auth.showToast('Selectionnez un article', 'error'); return; }
        if (!qty || qty < 1) { auth.showToast('Quantite invalide', 'error'); return; }
        if (!total || total < 0) { auth.showToast('Montant total invalide', 'error'); return; }
        if (!buyer) { auth.showToast('Indiquez l\'acheteur', 'error'); return; }

        var btn = $('vBtnSave');
        btn.disabled = true;
        btn.textContent = 'Enregistrement...';

        auth.apiPost('/ventes/api/create', {
            stock_item_id: stockItemId,
            quantity: qty,
            total_price: total,
            buyer_name: buyer,
            notes: notes || null
        }, function (err, data) {
            btn.disabled = false;
            btn.textContent = 'Enregistrer la vente';
            if (err || !data || data.error) {
                var msg = (data && data.error) || 'Erreur';
                if (data && data.messages) {
                    msg = Object.values(data.messages).flat().join(' | ');
                }
                auth.showToast(msg, 'error');
                return;
            }
            auth.showToast(data.message || 'Vente enregistree', 'success');
            if (data.warning) {
                setTimeout(function () { auth.showToast(data.warning, 'error'); }, 900);
            }
            resetForm();
            tryLoad();
        });
    }

    function resetForm() {
        $('vQty').value = '1';
        $('vTotal').value = '';
        $('vUnit').value = '';
        $('vBuyer').value = '';
        $('vNotes').value = '';
        if (itemTs) itemTs.clear();
    }

    // ── RENDERING ──────────────────────────────────────────

    function renderStats(el, totals) {
        var count = totals.count || 0;
        var revenue = totals.revenue || 0;
        var qty = totals.quantity || 0;
        el.innerHTML =
            '<div class="members-stat"><span class="stat-label">Ventes</span><span class="stat-value">' + count + '</span></div>' +
            '<div class="members-stat"><span class="stat-label">Articles</span><span class="stat-value">' + qty + '</span></div>' +
            '<div class="members-stat"><span class="stat-label">Chiffre</span><span class="stat-value">' + money(revenue) + '</span></div>';
    }

    function renderToday(totals) {
        renderStats($('vTodayStats'), totals);
        var listEl = $('vTodayList');
        if (!state.todaySales.length) {
            listEl.innerHTML = '<div class="empty-msg">Aucune vente enregistree aujourd\'hui.</div>';
            return;
        }
        listEl.innerHTML = state.todaySales.map(renderRow).join('');
    }

    function renderHistory(totals) {
        renderStats($('vHistStats'), totals);
        var listEl = $('vHistList');
        if (!state.histSales.length) {
            listEl.innerHTML = '<div class="empty-msg">Aucune vente sur cette periode.</div>';
            return;
        }
        listEl.innerHTML = state.histSales.map(renderRow).join('');
    }

    function renderRow(s) {
        // La categorie de base (weapon_finished, ammo, melee, drug...) sert de classe de badge.
        var badgeClass = 'ts-role-badge role-' + esc(s.category || 'misc');
        var typeBadge = '<span class="' + badgeClass + '">' + esc(s.type_short || s.category || '?') + '</span>';
        return '' +
            '<div class="member-row">' +
                '<div class="member-info">' +
                    '<div class="member-name">' + esc(s.item_name) + ' <span class="sale-qty">x' + s.quantity + '</span></div>' +
                    '<div class="member-meta">' + typeBadge + ' &middot; vers <strong>' + esc(s.buyer) + '</strong> &middot; par ' + esc(s.sold_by) + ' &middot; ' + esc(s.date) +
                    (s.notes ? ' &middot; <em>' + esc(s.notes) + '</em>' : '') +
                    '</div>' +
                '</div>' +
                '<div class="member-actions sale-totals">' +
                    '<div class="sale-unit">' + money(s.unit_price) + ' / u</div>' +
                    '<div class="sale-total">' + money(s.total_price) + '</div>' +
                '</div>' +
            '</div>';
    }

    function refreshHistory() {
        var url = '/ventes/api/list?scope=' + encodeURIComponent(state.histScope) + '&period=' + encodeURIComponent(state.histPeriod);
        auth.apiGet(url, function (err, data) {
            if (err || !data || data.error) return;
            state.histSales = data.sales || [];
            renderHistory(data.totals || {});
        });
    }

    // ── SUB-TABS ───────────────────────────────────────────

    function initSubTabs() {
        document.querySelectorAll('.sub-tab').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var target = btn.getAttribute('data-subtab');
                document.querySelectorAll('.sub-tab').forEach(function (b) { b.classList.remove('active'); });
                document.querySelectorAll('.sub-content').forEach(function (c) { c.classList.remove('active'); });
                btn.classList.add('active');
                var el = $('sub-' + target);
                if (el) el.classList.add('active');
                if (target === 'history') refreshHistory();
            });
        });
    }

    // ── EVENTS ─────────────────────────────────────────────

    function bindEvents() {
        $('vItem').addEventListener('change', onItemChange);
        $('vQty').addEventListener('input', onQtyChange);
        $('vTotal').addEventListener('input', recomputeUnit);
        $('vBtnSave').addEventListener('click', saveSale);
        $('vScope').addEventListener('change', function () { state.histScope = this.value; refreshHistory(); });
        $('vPeriod').addEventListener('change', function () { state.histPeriod = this.value; refreshHistory(); });
    }

    // ── INIT ───────────────────────────────────────────────

    initSubTabs();
    bindEvents();
    updateGate();
    auth.onLogin(updateGate);
    auth.onLogout(updateGate);
})();
