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
        histPeriod: 'today',
        attributionId: null,
        attributionLocked: false,
        attributionMaxQty: null,
        adHoc: false
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
            applyPrefill();
            refreshHistory();
        });
    }

    function applyPrefill() {
        var params = new URLSearchParams(window.location.search);
        var itemId = parseInt(params.get('stock_item_id'), 10);
        var qty = parseInt(params.get('quantity'), 10);
        var attributionId = parseInt(params.get('attribution_id'), 10);
        if (!itemId) return;

        var item = state.catalogById[itemId];
        if (!item) return;

        if (itemTs) {
            itemTs.setValue(String(itemId), true);
        } else {
            $('vItem').value = String(itemId);
        }
        if (qty && qty > 0) $('vQty').value = qty;
        if (item.default_sell_price) {
            $('vTotal').value = item.default_sell_price * (qty || 1);
        }
        recomputeUnit();

        if (attributionId) {
            state.attributionId = attributionId;
            state.attributionLocked = true;
            state.attributionMaxQty = qty || 1;
            showAttributionBanner(item, qty);
            if (itemTs) itemTs.disable();
            $('vQty').readOnly = false;
            $('vQty').setAttribute('max', String(state.attributionMaxQty));
        }
    }

    function showAttributionBanner(item, qty) {
        var container = document.querySelector('#sub-new .action-card');
        if (!container) return;
        var existing = document.getElementById('vAttrBanner');
        if (existing) existing.remove();
        var banner = document.createElement('div');
        banner.id = 'vAttrBanner';
        banner.className = 'alert-banner';
        banner.style.cssText = 'margin-bottom:10px; padding:8px 12px; background:rgba(96,165,250,0.08); border:1px solid rgba(96,165,250,0.3); border-radius:4px; color:#93c5fd; font-size:12px;';
        banner.innerHTML = '<strong>Reconciliation d\'attribution</strong> : indiquez la quantite vendue (au plus ' +
            esc(String(qty)) + '). Le solde restant restera ouvert tant que tout n\'est pas vendu. ' +
            '<a href="/ventes" style="color:#93c5fd; text-decoration:underline;">Annuler</a>';
        container.insertBefore(banner, container.firstChild.nextSibling);
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

        var adHocName = '';
        var adHocCategory = '';
        if (state.adHoc) {
            adHocName = ($('vAdHocName').value || '').trim();
            adHocCategory = $('vAdHocCategory').value || 'misc';
            if (!adHocName) { auth.showToast('Nom de l\'article requis', 'error'); return; }
        } else {
            if (!stockItemId) { auth.showToast('Selectionnez un article', 'error'); return; }
        }

        if (!qty || qty < 1) { auth.showToast('Quantite invalide', 'error'); return; }
        if (state.attributionLocked && state.attributionMaxQty && qty > state.attributionMaxQty) {
            auth.showToast('Quantite max. pour cette attribution : ' + state.attributionMaxQty, 'error');
            return;
        }
        if (!total || total < 0) { auth.showToast('Montant total invalide', 'error'); return; }
        if (!buyer) { auth.showToast('Indiquez l\'acheteur', 'error'); return; }

        var btn = $('vBtnSave');
        btn.disabled = true;
        btn.textContent = 'Enregistrement...';

        var payload = {
            quantity: qty,
            total_price: total,
            buyer_name: buyer,
            notes: notes || null,
            attribution_id: state.attributionId || null
        };
        if (state.adHoc) {
            payload.ad_hoc_name = adHocName;
            payload.ad_hoc_category = adHocCategory;
        } else {
            payload.stock_item_id = stockItemId;
        }

        auth.apiPost('/ventes/api/create', payload, function (err, data) {
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
            if (state.attributionId) {
                var rem = data.attribution_remaining;
                if (typeof rem === 'number' && rem > 0) {
                    state.attributionMaxQty = rem;
                    var sid = parseInt($('vItem').value, 10);
                    var newQs = '?stock_item_id=' + sid + '&quantity=' + rem + '&attribution_id=' + state.attributionId;
                    if (window.history && window.history.replaceState) {
                        window.history.replaceState({}, '', '/ventes' + newQs);
                    }
                    var it = state.catalogById[sid];
                    if (it) showAttributionBanner(it, rem);
                    auth.showToast('Il reste ' + rem + ' unite(s) sur cette attribution', 'info');
                    $('vBuyer').value = '';
                    $('vNotes').value = '';
                    $('vQty').value = String(rem);
                    $('vQty').setAttribute('max', String(rem));
                    if (it && it.default_sell_price) {
                        $('vTotal').value = it.default_sell_price * rem;
                    } else {
                        $('vTotal').value = '';
                    }
                    recomputeUnit();
                } else {
                    state.attributionId = null;
                    state.attributionLocked = false;
                    state.attributionMaxQty = null;
                    if (itemTs) itemTs.enable();
                    $('vQty').removeAttribute('max');
                    var banner = document.getElementById('vAttrBanner');
                    if (banner) banner.remove();
                    if (window.history && window.history.replaceState) {
                        window.history.replaceState({}, '', '/ventes');
                    }
                    resetForm();
                }
            } else {
                resetForm();
            }
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
        if (state.adHoc) {
            $('vAdHocName').value = '';
            $('vAdHocCategory').value = 'misc';
        }
    }

    function setAdHocMode(on) {
        state.adHoc = !!on;
        var selWrap = $('vItem');
        var adHocFields = $('vAdHocFields');
        if (!adHocFields) return;

        if (on) {
            adHocFields.style.display = '';
            if (itemTs) {
                itemTs.clear();
                itemTs.disable();
                if (itemTs.wrapper) itemTs.wrapper.style.display = 'none';
            } else if (selWrap) {
                selWrap.style.display = 'none';
            }
        } else {
            adHocFields.style.display = 'none';
            if (itemTs) {
                itemTs.enable();
                if (itemTs.wrapper) itemTs.wrapper.style.display = '';
            } else if (selWrap) {
                selWrap.style.display = '';
            }
        }
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

        var toggle = $('vAdHocToggle');
        if (toggle) {
            toggle.addEventListener('change', function () {
                if (state.attributionLocked && this.checked) {
                    auth.showToast('Impossible en mode reconciliation d\'attribution', 'error');
                    this.checked = false;
                    return;
                }
                setAdHocMode(this.checked);
            });
        }
    }

    // ── INIT ───────────────────────────────────────────────

    initSubTabs();
    bindEvents();
    updateGate();
    auth.onLogin(updateGate);
    auth.onLogout(updateGate);
})();
