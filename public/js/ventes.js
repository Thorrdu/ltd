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
        electronic:      'Electronique',
        tool:            'Outils',
        farm_consumable: 'Consommables ferme',
        raw_material:    'Matieres premieres',
        weapon_piece:    'Pieces d\'armes',
        weapon_plan:     'Plans d\'armes',
        misc:            'Divers',
        service:         'Hors stock'
    };

    // Ordre d'affichage des categories dans l'accordeon express.
    var CATEGORY_ORDER = ['drug', 'weapon_finished', 'ammo', 'melee', 'drug_raw', 'electronic', 'tool', 'farm_consumable', 'raw_material', 'weapon_piece', 'weapon_plan', 'misc'];

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
        adHoc: false,
        myAttributions: [],
        userRole: null
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
            state.userRole = data.user_role || auth.userRole;
            state.todaySales = data.sales || [];
            renderToday(data.totals || {});
            refreshMyAttributions();
            var isOfficer = auth.isAtLeast('officer');
            // Prospect/member: only see attributed items, not the full catalog.
            if (isOfficer) {
                populateItemSelect();
                buildExpressAccordions();
                showCatalogSections(true);
            } else {
                hideCatalogSections();
            }
            applyPrefill();
            refreshHistory();
        });
    }

    function showCatalogSections(show) {
        var catalogCard = document.querySelector('#sub-express .ve-accordions');
        var catalogTitle = catalogCard ? catalogCard.previousElementSibling : null;
        if (catalogCard) catalogCard.style.display = show ? '' : 'none';
        if (catalogTitle) catalogTitle.style.display = show ? '' : 'none';
        // Ad-hoc toggle: only officers+ can use it (they have access to full catalog)
        var adHocToggle = $('vAdHocToggle');
        if (adHocToggle) {
            adHocToggle.closest('label').style.display = show ? '' : 'none';
        }
    }

    function hideCatalogSections() {
        showCatalogSections(false);
        // In classic sale, only show attributed items in the select.
        populateItemSelectFromAttributions();
    }

    function populateItemSelectFromAttributions() {
        var sel = $('vItem');
        if (!sel) return;
        if (itemTs) { try { itemTs.destroy(); } catch (e) {} itemTs = null; }
        sel.innerHTML = '<option value="">-- Mes articles attribues --</option>';
        if (!state.myAttributions.length) {
            sel.innerHTML = '<option value="">Aucun article attribue</option>';
            return;
        }
        var grouped = {};
        state.myAttributions.forEach(function (a) {
            if (!grouped[a.category]) grouped[a.category] = [];
            grouped[a.category].push(a);
        });
        Object.keys(grouped).forEach(function (cat) {
            var og = document.createElement('optgroup');
            og.label = CATEGORY_LABELS[cat] || cat;
            grouped[cat].forEach(function (a) {
                var opt = document.createElement('option');
                opt.value = a.stock_item_id;
                opt.textContent = a.name + ' (x' + a.quantity + ')' + (a.default_sell_price ? '  (' + money(a.default_sell_price) + ')' : '');
                opt.setAttribute('data-max-qty', a.quantity);
                opt.setAttribute('data-price', a.default_sell_price || 0);
                og.appendChild(opt);
            });
            sel.appendChild(og);
        });
        if (typeof TomSelect !== 'undefined') {
            itemTs = new TomSelect(sel, {
                placeholder: 'Rechercher parmi mes articles...',
                searchField: ['text'],
                maxOptions: 500,
                plugins: ['dropdown_input']
            });
        }
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
        if (state.adHoc) {
            adHocName = ($('vAdHocName').value || '').trim();
            if (!adHocName) { auth.showToast('Description de la vente requise', 'error'); return; }
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
        } else {
            payload.stock_item_id = stockItemId;
        }
        var onBehalfId = getOnBehalfUserId('vOnBehalf');
        if (onBehalfId) payload.on_behalf_of_user_id = onBehalfId;

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
        if ($('veTodayStats')) renderStats($('veTodayStats'), totals);
        var veList = $('veTodayList');
        if (veList) {
            if (!state.todaySales.length) {
                veList.innerHTML = '<div class="empty-msg">Aucune vente aujourd\'hui.</div>';
            } else {
                veList.innerHTML = state.todaySales.slice(0, 5).map(renderRow).join('');
            }
        }
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

    // ── VENTE EXPRESS (Phase 3B.3) ────────────────────────

    var expressCart = {}; // { itemId: quantity }

    function buildExpressAccordions() {
        var el = $('veAccordions');
        if (!el) return;

        // Only show items flagged for quick sale
        var quickItems = state.catalog.filter(function (it) { return it.is_quick_sale; });

        var grouped = {};
        quickItems.forEach(function (it) {
            if (!grouped[it.category]) grouped[it.category] = [];
            grouped[it.category].push(it);
        });

        var html = '';
        CATEGORY_ORDER.forEach(function (cat, idx) {
            if (!grouped[cat] || !grouped[cat].length) return;
            var label = CATEGORY_LABELS[cat] || cat;
            var openClass = idx === 0 ? ' open' : ''; // First category (drug) open by default
            var count = grouped[cat].length;
            html += '<div class="ve-accordion' + openClass + '" data-cat="' + esc(cat) + '">';
            html += '<div class="ve-acc-header">';
            html += '<span class="ve-acc-title">' + esc(label) + '</span>';
            html += '<span class="ve-acc-count">' + count + ' articles</span>';
            html += '<span class="ve-acc-arrow">▼</span>';
            html += '</div>';
            html += '<div class="ve-acc-body"><div class="ve-items-grid">';
            grouped[cat].forEach(function (it) {
                var noStock = it.current_stock <= 0 ? ' no-stock' : '';
                var selClass = expressCart[it.id] ? ' selected' : '';
                var qtyVal = expressCart[it.id] || 0;
                html += '<div class="ve-item' + noStock + selClass + '" data-id="' + it.id + '">';
                html += '<div class="ve-item-name">' + esc(it.name) + '</div>';
                html += '<div class="ve-item-price">' + (it.default_sell_price ? money(it.default_sell_price) : '-') + '</div>';
                html += '<div class="ve-item-stock">Stock: ' + (it.current_stock || 0) + '</div>';
                html += '<div class="ve-item-qty">';
                html += '<button class="qty-btn ve-minus" data-id="' + it.id + '">−</button>';
                html += '<input type="number" class="qty-input ve-qty-input" data-id="' + it.id + '" value="' + qtyVal + '" min="0" max="999999">';
                html += '<button class="qty-btn ve-plus" data-id="' + it.id + '">+</button>';
                html += '</div>';
                html += '</div>';
            });
            html += '</div></div></div>';
        });
        el.innerHTML = html || '<div class="empty-msg">Aucun article dans le catalogue.</div>';

        // Accordion toggle
        el.querySelectorAll('.ve-acc-header').forEach(function (hdr) {
            hdr.addEventListener('click', function () {
                hdr.parentElement.classList.toggle('open');
            });
        });

        // Qty buttons
        el.querySelectorAll('.ve-plus').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                var id = parseInt(btn.getAttribute('data-id'), 10);
                expressCart[id] = (expressCart[id] || 0) + 1;
                updateExpressItem(id);
            });
        });
        el.querySelectorAll('.ve-minus').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                var id = parseInt(btn.getAttribute('data-id'), 10);
                var cur = expressCart[id] || 0;
                if (cur > 0) {
                    expressCart[id] = cur - 1;
                    if (expressCart[id] <= 0) delete expressCart[id];
                }
                updateExpressItem(id);
            });
        });
        el.querySelectorAll('.ve-qty-input').forEach(function (inp) {
            inp.addEventListener('change', function () {
                var id = parseInt(inp.getAttribute('data-id'), 10);
                var val = parseInt(inp.value, 10) || 0;
                if (val > 0) {
                    expressCart[id] = val;
                } else {
                    delete expressCart[id];
                    inp.value = 0;
                }
                updateExpressItem(id);
            });
        });

        // Item card click toggles +1
        el.querySelectorAll('.ve-item').forEach(function (card) {
            card.addEventListener('click', function (e) {
                if (e.target.closest('.qty-btn') || e.target.closest('.qty-input')) return;
                var id = parseInt(card.getAttribute('data-id'), 10);
                expressCart[id] = (expressCart[id] || 0) + 1;
                updateExpressItem(id);
            });
        });

        updateExpressRecap();
    }

    function updateExpressItem(id) {
        var qty = expressCart[id] || 0;
        var inp = document.querySelector('.ve-qty-input[data-id="' + id + '"]');
        if (inp) inp.value = qty;
        var card = document.querySelector('.ve-item[data-id="' + id + '"]');
        if (card) {
            if (qty > 0) card.classList.add('selected');
            else card.classList.remove('selected');
        }
        updateExpressRecap();
    }

    function updateExpressRecap() {
        var recap = $('veRecap');
        var itemsEl = $('veRecapItems');
        var totalEl = $('veRecapTotal');
        if (!recap || !itemsEl) return;

        var keys = Object.keys(expressCart);
        if (!keys.length) {
            recap.style.display = 'none';
            return;
        }
        recap.style.display = '';

        var total = 0;
        var html = '';
        keys.forEach(function (keyStr) {
            var qty = expressCart[keyStr];
            if (!qty) return;

            var isAttr = keyStr.indexOf('attr_item_') === 0;
            var itemName, price, removeKey;

            if (isAttr) {
                var itemId = parseInt(keyStr.replace('attr_item_', ''), 10);
                var a = state.myAttributions.find(function (x) { return x.stock_item_id === itemId; });
                if (!a) return;
                itemName = a.name + ' (attrib.)';
                price = a.default_sell_price || 0;
                removeKey = keyStr;
            } else {
                var id = parseInt(keyStr, 10);
                var it = state.catalogById[id];
                if (!it) return;
                itemName = it.name;
                price = it.default_sell_price || 0;
                removeKey = keyStr;
            }

            var lineTotal = price * qty;
            total += lineTotal;
            html += '<div class="ve-recap-item">' +
                '<span class="ri-name">' + esc(itemName) + '</span>' +
                '<span class="ri-qty">x' + qty + '</span>' +
                '<span class="ri-total">' + money(lineTotal) + '</span>' +
                '<span class="ri-remove" data-key="' + removeKey + '" title="Retirer">✕</span>' +
                '</div>';
        });
        itemsEl.innerHTML = html;
        if (totalEl) totalEl.textContent = money(total);

        // Pre-fill actual amount with theoretical
        var actualEl = $('veActual');
        if (actualEl && !actualEl.value) actualEl.placeholder = String(total);

        // Remove item
        itemsEl.querySelectorAll('.ri-remove').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var key = btn.getAttribute('data-key');
                delete expressCart[key];
                if (key.indexOf('attr_item_') === 0) {
                    updateExpressAttrItem(key);
                } else {
                    updateExpressItem(parseInt(key, 10));
                }
            });
        });
    }

    function submitExpressSale() {
        var keys = Object.keys(expressCart);
        if (!keys.length) { auth.showToast('Selectionnez au moins un article', 'error'); return; }

        var buyer = ($('veBuyer').value || '').trim();
        if (!buyer) { auth.showToast('Indiquez l\'acheteur', 'error'); return; }

        // All items (attributed or catalog) go through the batch endpoint.
        // The backend auto-reconciles attributions FIFO.
        var stockItems = [];
        keys.forEach(function (keyStr) {
            var qty = expressCart[keyStr];
            if (!qty || qty <= 0) return;
            if (keyStr.indexOf('attr_item_') === 0) {
                var itemId = parseInt(keyStr.replace('attr_item_', ''), 10);
                if (itemId > 0) stockItems.push({ stock_item_id: itemId, quantity: qty });
            } else {
                var id = parseInt(keyStr, 10);
                if (id > 0) stockItems.push({ stock_item_id: id, quantity: qty });
            }
        });

        if (!stockItems.length) { auth.showToast('Aucun article avec quantite > 0', 'error'); return; }

        var actual = parseInt($('veActual').value, 10);
        var notes = ($('veNotes').value || '').trim();

        var btn = $('veBtnSave');
        btn.disabled = true;
        btn.textContent = 'Enregistrement...';

        auth.apiPost('/ventes/api/batch', {
            items: stockItems,
            actual_amount: actual || null,
            buyer_name: buyer,
            notes: notes || null,
            on_behalf_of_user_id: getOnBehalfUserId('veOnBehalf') || undefined
        }, function (err, data) {
            btn.disabled = false;
            btn.textContent = 'Valider la vente';
            if (err || !data || data.error) {
                var msg = (data && data.error) || 'Erreur';
                if (data && data.messages) msg = Object.values(data.messages).flat().join(' | ');
                auth.showToast(msg, 'error');
                return;
            }
            auth.showToast(data.message || 'Vente enregistree', 'success');
            if (data.warnings && data.warnings.length) {
                data.warnings.forEach(function (w) {
                    setTimeout(function () { auth.showToast(w, 'error'); }, 500);
                });
            }
            // Reset
            expressCart = {};
            $('veBuyer').value = '';
            $('veNotes').value = '';
            $('veActual').value = '';
            buildExpressAccordions();
            tryLoad();
        });
    }

    // ── MES ATTRIBUTIONS (items sur moi) ──────────────────

    function refreshMyAttributions() {
        var url = '/ventes/api/my-attributions';
        var forUserId = getOnBehalfUserId('veOnBehalf');
        if (forUserId) url += '?for_user_id=' + forUserId;
        auth.apiGet(url, function (err, data) {
            if (err || !data) return;
            state.myAttributions = data.attributions || [];
            renderMyAttributions();
        });
    }

    function renderMyAttributions() {
        var el = $('veMyAttributions');
        if (!el) return;
        if (!state.myAttributions.length) {
            el.innerHTML = '<div class="empty-msg">Aucun article attribue actuellement.</div>';
            return;
        }
        var html = '<div class="ve-items-grid">';
        state.myAttributions.forEach(function (a) {
            var key = 'attr_item_' + a.stock_item_id;
            var selClass = expressCart[key] ? ' selected' : '';
            var qtyVal = expressCart[key] || 0;
            html += '<div class="ve-item ve-item-attr' + selClass + '" data-item-id="' + a.stock_item_id + '">';
            html += '<div class="ve-item-name">' + esc(a.name) + '</div>';
            html += '<div class="ve-item-price">' + (a.default_sell_price ? money(a.default_sell_price) : '-') + '</div>';
            html += '<div class="ve-item-stock">' + (getOnBehalfUserId('veOnBehalf') ? 'Sur lui/elle' : 'Sur moi') + ': ' + a.quantity + '</div>';
            html += '<div class="ve-item-qty">';
            html += '<button class="qty-btn ve-attr-minus" data-key="' + key + '" data-max="' + a.quantity + '">−</button>';
            html += '<input type="number" class="qty-input ve-attr-qty-input" data-key="' + key + '" data-max="' + a.quantity + '" value="' + qtyVal + '" min="0" max="' + a.quantity + '">';
            html += '<button class="qty-btn ve-attr-plus" data-key="' + key + '" data-max="' + a.quantity + '">+</button>';
            html += '</div>';
            html += '</div>';
        });
        html += '</div>';
        el.innerHTML = html;

        // Bind attribution item events
        el.querySelectorAll('.ve-attr-plus').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                var key = btn.getAttribute('data-key');
                var max = parseInt(btn.getAttribute('data-max'), 10);
                var cur = expressCart[key] || 0;
                if (cur < max) {
                    expressCart[key] = cur + 1;
                    updateExpressAttrItem(key);
                }
            });
        });
        el.querySelectorAll('.ve-attr-minus').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.stopPropagation();
                var key = btn.getAttribute('data-key');
                var cur = expressCart[key] || 0;
                if (cur > 0) {
                    expressCart[key] = cur - 1;
                    if (expressCart[key] <= 0) delete expressCart[key];
                }
                updateExpressAttrItem(key);
            });
        });
        el.querySelectorAll('.ve-attr-qty-input').forEach(function (inp) {
            inp.addEventListener('change', function () {
                var key = inp.getAttribute('data-key');
                var max = parseInt(inp.getAttribute('data-max'), 10);
                var val = Math.min(parseInt(inp.value, 10) || 0, max);
                if (val > 0) {
                    expressCart[key] = val;
                } else {
                    delete expressCart[key];
                }
                inp.value = val;
                updateExpressAttrItem(key);
            });
        });
        el.querySelectorAll('.ve-item-attr').forEach(function (card) {
            card.addEventListener('click', function (e) {
                if (e.target.closest('.qty-btn') || e.target.closest('.qty-input')) return;
                var itemId = card.getAttribute('data-item-id');
                var key = 'attr_item_' + itemId;
                var max = 1;
                var a = state.myAttributions.find(function (x) { return String(x.stock_item_id) === itemId; });
                if (a) max = a.quantity;
                var cur = expressCart[key] || 0;
                if (cur < max) expressCart[key] = cur + 1;
                updateExpressAttrItem(key);
            });
        });
    }

    function updateExpressAttrItem(key) {
        var qty = expressCart[key] || 0;
        var inp = document.querySelector('.ve-attr-qty-input[data-key="' + key + '"]');
        if (inp) inp.value = qty;
        var itemId = key.replace('attr_item_', '');
        var card = document.querySelector('.ve-item-attr[data-item-id="' + itemId + '"]');
        if (card) {
            if (qty > 0) card.classList.add('selected');
            else card.classList.remove('selected');
        }
        updateExpressRecap();
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

    // ── "AU NOM DE" (on behalf of) ────────────────────────

    function initOnBehalf() {
        if (!auth.isAtLeast('treasurer')) return;

        var members = window.MC_MEMBERS || [];
        if (!members.length) return;

        // Show both rows
        var classicRow = $('vOnBehalfRow');
        var expressRow = $('veOnBehalfRow');
        if (classicRow) classicRow.style.display = '';
        if (expressRow) expressRow.style.display = '';

        // Populate both selects
        ['vOnBehalf', 'veOnBehalf'].forEach(function (selId) {
            var sel = $(selId);
            if (!sel) return;
            members.forEach(function (m) {
                var opt = document.createElement('option');
                opt.value = m.id;
                opt.textContent = m.name;
                sel.appendChild(opt);
            });
        });

        // When express "on behalf of" changes, refresh attributions for that member
        var veOnBehalf = $('veOnBehalf');
        if (veOnBehalf) {
            veOnBehalf.addEventListener('change', function () {
                refreshMyAttributions();
            });
        }
    }

    function getOnBehalfUserId(selectId) {
        var sel = $(selectId);
        if (!sel) return null;
        var val = parseInt(sel.value, 10);
        return val > 0 ? val : null;
    }

    // ── EVENTS ─────────────────────────────────────────────

    function bindEvents() {
        $('vItem').addEventListener('change', onItemChange);
        $('vQty').addEventListener('input', onQtyChange);
        $('vTotal').addEventListener('input', recomputeUnit);
        $('vBtnSave').addEventListener('click', saveSale);
        var veBtnSave = $('veBtnSave');
        if (veBtnSave) veBtnSave.addEventListener('click', submitExpressSale);
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
    initOnBehalf();
    updateGate();
    auth.onLogin(updateGate);
    auth.onLogout(updateGate);
})();
