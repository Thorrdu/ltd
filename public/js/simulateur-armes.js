(function () {
    'use strict';

    var weaponList = window.WEAPONS || [];
    var memberList = window.MEMBERS || [];
    var weapons = {};
    var pieceKeys = ['plans', 'ressort', 'canon', 'poignee', 'corp', 'crosse', 'corp_smg', 'corp_rifle', 'metal', 'polymere'];
    var pieceNames = {
        plans: 'Plans', ressort: 'Ressort', canon: 'Canon',
        poignee: 'Poign\u00e9e', corp: 'Corp de pistolet',
        crosse: 'Crosse', corp_smg: 'Corps de SMG', corp_rifle: 'Corps de fusil',
        metal: 'Pi\u00e8ce de m\u00e9tal', polymere: 'Polym\u00e8re'
    };

    weaponList.forEach(function (w) {
        var sp = w.sell_price || 0;
        var rpRaw = w.reference_purchase_price;
        var rp = (rpRaw != null && rpRaw !== '' && !isNaN(Number(rpRaw))) ? (Number(rpRaw) || 0) : 0;
        weapons[w.slug] = {
            id: w.id, name: w.name, slug: w.slug,
            craftTime: w.craft_time_seconds,
            sellPrice: sp,
            priceMin: w.price_min || 0,
            priceMax: w.price_max || 0,
            referencePurchasePrice: rp,
            isBoughtWeapon: w.slug === 'sns',
            pieces: {
                plans: w.recipe_plans, ressort: w.recipe_ressort,
                canon: w.recipe_canon, poignee: w.recipe_poignee,
                corp: w.recipe_corp, crosse: w.recipe_crosse,
                corp_smg: w.recipe_corp_smg, corp_rifle: w.recipe_corp_rifle,
                metal: w.recipe_metal, polymere: w.recipe_polymere
            }
        };
    });

    var weaponById = {};
    weaponList.forEach(function (w) { weaponById[w.id] = w; });

    var POLYMERE_PETROLE_RATE = 5;
    var POLYMERE_COST = 4500;
    var WEAPON_CRAFT_CORP_EUR = 15000;
    var WEAPON_CRAFT_CORP_SMG_EUR = 25000;
    var WEAPON_CRAFT_CORP_RIFLE_EUR = 40000;
    var WEAPON_CRAFT_WEAPON_PIECE_EUR = 5000;
    var METAL_MINERAI_RATE = 5;
    var RESSORT_METAL_RATE = 1;
    var RESSORT_MINERAI_RATE = 3;
    var PLANS_PER_ITEM = 4;

    var auth = window.McAuth;
    var cachedData = null;

    function esc(s) { if (s == null) return ''; var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
    function fmt(n) { return Number(n).toLocaleString('fr-FR'); }
    function fmtEuro(n) {
        return Number(n).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' \u20ac';
    }
    function fmtK(n) {
        if (n >= 1000000) {
            var m = n / 1000000;
            return (m % 1 === 0 ? m.toFixed(0) : m.toFixed(1).replace(/\.0$/, '')) + 'M';
        }
        return Math.round(n / 1000) + 'k';
    }
    function fmtPriceRange(wd) {
        if (!wd.priceMin && !wd.priceMax) return '';
        return fmtK(wd.priceMin) + ' \u2013 ' + fmtK(wd.priceMax);
    }
    function $(id) { return document.getElementById(id); }
    function showToast(msg, type) { if (auth && auth.showToast) auth.showToast(msg, type); }

    function makeRow(label, value, cls) {
        return '<div class="result-row"><span class="label">' + label + '</span><span class="dot-leader"></span><span class="value' + (cls ? ' ' + cls : '') + '">' + value + '</span></div>';
    }
    function makeSectionHeader(label) {
        return '<div class="result-row section-header"><span class="label">' + label + '</span></div>';
    }
    function formatTime(s) {
        if (s < 60) return s + ' sec';
        var m = Math.floor(s / 60), r = s % 60;
        return m + ' min' + (r ? ' ' + r + ' sec' : '');
    }

    function ammoBenClass(v) {
        if (v > 0) return 'ammo-ben-pos';
        if (v < 0) return 'ammo-ben-neg';
        return 'ammo-ben-zero';
    }

    // ===== TOM SELECT HELPERS =====
    var HAS_TOM = typeof TomSelect !== 'undefined';

    function stockQtyClass(q) {
        if (q <= 0) return 'zero';
        if (q <= 2) return 'low';
        return '';
    }

    function destroyTomSelect(el) {
        if (el && el.tomselect) {
            el.tomselect.destroy();
        }
    }

    function initTomSelectSingle(el, options) {
        if (!el || !HAS_TOM) return null;
        destroyTomSelect(el);
        var opts = Object.assign({
            maxOptions: 500,
            allowEmptyOption: false,
            create: false,
            plugins: ['dropdown_input']
        }, options || {});
        return new TomSelect(el, opts);
    }

    function populateGroupedStockSelect(sel, stock) {
        destroyTomSelect(sel);
        sel.innerHTML = '';
        var labelMap = window.MC_CATEGORIES || {};
        var grouped = {};
        stock.forEach(function (s) {
            if (!grouped[s.category]) grouped[s.category] = [];
            grouped[s.category].push(s);
        });
        var catKeys = Object.keys(grouped);
        catKeys.sort(function (a, b) {
            var la = labelMap[a] || a;
            var lb = labelMap[b] || b;
            return la.localeCompare(lb, 'fr');
        });
        catKeys.forEach(function (cat) {
            var rows = grouped[cat];
            if (!rows || !rows.length) return;
            var og = document.createElement('optgroup');
            og.label = labelMap[cat] || cat;
            rows.forEach(function (s) {
                var opt = document.createElement('option');
                opt.value = s.id;
                opt.textContent = s.name;
                opt.dataset.qty = s.quantity;
                opt.dataset.category = s.category;
                og.appendChild(opt);
            });
            sel.appendChild(og);
        });
        initTomSelectSingle(sel, {
            placeholder: 'Rechercher un article...',
            searchField: ['text'],
            render: {
                option: function (data, escape) {
                    var qty = data.$option && data.$option.dataset ? (data.$option.dataset.qty || '0') : '0';
                    var cls = stockQtyClass(parseInt(qty, 10) || 0);
                    return '<div>' + escape(data.text) +
                        '<span class="ts-stock-qty ' + cls + '">' + escape(qty) + '</span></div>';
                },
                item: function (data, escape) {
                    var qty = data.$option && data.$option.dataset ? (data.$option.dataset.qty || '') : '';
                    return '<div>' + escape(data.text) +
                        (qty !== '' ? ' <span class="ts-stock-qty">' + escape(qty) + '</span>' : '') +
                        '</div>';
                }
            }
        });
    }

    function populateWeaponSelect(sel) {
        destroyTomSelect(sel);
        sel.innerHTML = '';
        weaponList.forEach(function (w) {
            sel.insertAdjacentHTML('beforeend', '<option value="' + w.id + '">' + esc(w.name) + '</option>');
        });
        initTomSelectSingle(sel, {
            placeholder: 'Choisir une arme...',
            searchField: ['text']
        });
    }

    function populateSaleWeaponSelect(sel) {
        destroyTomSelect(sel);
        sel.innerHTML = '';
        weaponList.forEach(function (w) {
            var st = getWeaponStock(w.id);
            var qty = st ? st.quantity : 0;
            var opt = document.createElement('option');
            opt.value = w.id;
            opt.textContent = w.name;
            opt.dataset.qty = qty;
            opt.dataset.price = w.sell_price || 0;
            sel.appendChild(opt);
        });
        initTomSelectSingle(sel, {
            placeholder: 'Rechercher une arme...',
            searchField: ['text'],
            render: {
                option: function (data, escape) {
                    var qty = data.$option && data.$option.dataset ? (data.$option.dataset.qty || '0') : '0';
                    var cls = stockQtyClass(parseInt(qty, 10) || 0);
                    return '<div>' + escape(data.text) +
                        '<span class="ts-stock-qty ' + cls + '">' + escape(qty) + ' stock</span></div>';
                }
            },
            onChange: function () { onSaleWeaponChange(); }
        });
    }

    // ===== BUILD WEAPON CARDS =====
    var grid = $('weaponsGrid');
    if (grid) {
        weaponList.forEach(function (w) {
            grid.insertAdjacentHTML('beforeend',
                '<div class="weapon-card" data-weapon="' + esc(w.slug) + '">' +
                '<div class="weapon-name">' + esc(w.name) + '</div>' +
                '<div class="weapon-craft-time">\u23f1 ' + (w.craft_time_seconds ? w.craft_time_seconds + 's' : '?') + '</div>' +
                '<div class="weapon-qty-row">' +
                '<button class="qty-btn minus" data-weapon="' + esc(w.slug) + '">\u2212</button>' +
                '<input type="number" class="qty-input" id="qty-' + esc(w.slug) + '" value="0" min="0" max="99">' +
                '<button class="qty-btn plus" data-weapon="' + esc(w.slug) + '">+</button>' +
                '</div></div>'
            );
        });
    }

    // ===== BUILD WEAPON STOCK INPUTS =====
    var compStockGrid = $('weaponCompStockGrid');
    if (compStockGrid) {
        // Plan inputs per weapon
        weaponList.forEach(function (w) {
            if (w.slug === 'sns') return; // SNS is bought, no plan needed
            compStockGrid.insertAdjacentHTML('beforeend',
                '<label class="ammo-sim-label" for="weaponStock-plan_' + esc(w.slug) + '">Plan ' + esc(w.name) + '</label>' +
                '<input type="number" class="ammo-sim-input ammo-sim-input-sm weapon-stock-in" id="weaponStock-plan_' + esc(w.slug) + '" min="0" max="999999" step="1" value="0" inputmode="numeric" autocomplete="off">'
            );
        });
        // Shared component inputs
        var compInputs = [
            { key: 'ressort', label: 'Ressort' },
            { key: 'canon', label: 'Canon' },
            { key: 'poignee', label: 'Poign\u00e9e' },
            { key: 'corp', label: 'Corp de pistolet' },
            { key: 'crosse', label: 'Crosse' },
            { key: 'corp_smg', label: 'Corps de SMG' },
            { key: 'corp_rifle', label: 'Corps de fusil' },
            { key: 'metal', label: 'Pi\u00e8ce de m\u00e9tal' },
            { key: 'polymere', label: 'Polym\u00e8re' }
        ];
        compInputs.forEach(function (c) {
            compStockGrid.insertAdjacentHTML('beforeend',
                '<label class="ammo-sim-label" for="weaponStock-' + c.key + '">' + c.label + '</label>' +
                '<input type="number" class="ammo-sim-input ammo-sim-input-sm weapon-stock-in" id="weaponStock-' + c.key + '" min="0" max="999999" step="1" value="0" inputmode="numeric" autocomplete="off">'
            );
        });
    }
    // Toggle stock fields visibility
    var compStockCb = $('weaponUseCompStock');
    if (compStockCb) {
        compStockCb.addEventListener('change', function () {
            var fields = $('weaponCompStockFields');
            if (fields) fields.style.display = compStockCb.checked ? '' : 'none';
            calculate();
        });
    }

    function weaponCompStockEnabled() { var el = $('weaponUseCompStock'); return el ? el.checked : false; }

    function weaponStockReadComps() {
        var vals = {};
        // plans per weapon
        weaponList.forEach(function (w) {
            if (w.slug === 'sns') return;
            var el = $('weaponStock-plan_' + w.slug);
            var v = el ? parseInt(String(el.value).trim(), 10) : 0;
            vals['plan_' + w.slug] = Math.max(0, isNaN(v) ? 0 : v);
        });
        // shared components
        ['ressort', 'canon', 'poignee', 'corp', 'crosse', 'corp_smg', 'corp_rifle', 'metal', 'polymere'].forEach(function (k) {
            var el = $('weaponStock-' + k);
            var v = el ? parseInt(String(el.value).trim(), 10) : 0;
            vals[k] = Math.max(0, isNaN(v) ? 0 : v);
        });
        return vals;
    }

    function fillWeaponStockFields() {
        if (!cachedData || !cachedData.stock) return;
        var stockMap = {};
        cachedData.stock.forEach(function (s) { stockMap[s.slug] = s.quantity; });
        // plans
        weaponList.forEach(function (w) {
            if (w.slug === 'sns') return;
            var el = $('weaponStock-plan_' + w.slug);
            if (el && stockMap['plan_' + w.slug] != null) el.value = stockMap['plan_' + w.slug];
        });
        // shared components
        ['ressort', 'canon', 'poignee', 'corp', 'crosse', 'corp_smg', 'corp_rifle', 'metal', 'polymere'].forEach(function (k) {
            var el = $('weaponStock-' + k);
            if (el && stockMap[k] != null) el.value = stockMap[k];
        });
    }

    // ===== TAB SWITCHING =====
    document.querySelectorAll('.tab-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.tab-btn').forEach(function (b) { b.classList.remove('active'); });
            document.querySelectorAll('.tab-content').forEach(function (c) { c.classList.remove('active'); });
            btn.classList.add('active');
            var target = $('tab-' + btn.getAttribute('data-tab'));
            if (target) target.classList.add('active');
        });
    });
    document.querySelectorAll('.sub-tab').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.sub-tab').forEach(function (b) { b.classList.remove('active'); });
            document.querySelectorAll('.sub-content').forEach(function (c) { c.classList.remove('active'); });
            btn.classList.add('active');
            var target = $('sub-' + btn.getAttribute('data-subtab'));
            if (target) target.classList.add('active');
        });
    });

    // ===== SIMULATOR =====
    var resultsSection = $('resultsSection');

    function calculate() {
        var orders = {}, hasAny = false;
        Object.keys(weapons).forEach(function (key) {
            var input = $('qty-' + key);
            if (!input) return;
            var qty = Math.max(0, Math.min(99, parseInt(input.value, 10) || 0));
            input.value = qty;
            orders[key] = qty;
            if (qty > 0) hasAny = true;
        });
        document.querySelectorAll('.weapon-card').forEach(function (card) {
            card.classList.toggle('active', (orders[card.getAttribute('data-weapon')] || 0) > 0);
        });
        if (!hasAny) { if (resultsSection) resultsSection.style.display = 'none'; return; }
        if (resultsSection) resultsSection.style.display = '';

        var html = '', totals = {};
        pieceKeys.forEach(function (k) { totals[k] = 0; });

        Object.keys(weapons).forEach(function (key) {
            var qty = orders[key]; if (!qty) return;
            var w = weapons[key];
            html += makeSectionHeader(w.name + ' \u00d7 ' + qty);
            pieceKeys.forEach(function (p) {
                var need = (w.pieces[p] || 0) * qty;
                totals[p] += need;
                if (need) html += makeRow(pieceNames[p], fmt(need));
            });
        });
        $('piecesTable').innerHTML = html;

        html = '';
        pieceKeys.forEach(function (p) { if (totals[p]) html += makeRow(pieceNames[p], fmt(totals[p])); });
        $('totalPieces').innerHTML = html;

        // Stock deduction from form inputs
        var useStock = weaponCompStockEnabled();
        var compStock = useStock ? weaponStockReadComps() : null;

        if (useStock) {
            $('simStockCompare').style.display = '';
            html = '';
            pieceKeys.forEach(function (p) {
                if (!totals[p]) return;
                if (p === 'plans') {
                    Object.keys(weapons).forEach(function (slug) {
                        if (!orders[slug]) return;
                        var planNeed = (weapons[slug].pieces.plans || 0) * orders[slug];
                        var planHave = compStock['plan_' + slug] || 0;
                        var diff = planNeed - planHave;
                        var missing = Math.max(0, diff);
                        html += makeRow('Plan ' + weapons[slug].name + ' \u2014 besoin ' + fmt(planNeed) + ' / stock ' + fmt(planHave),
                            diff <= 0 ? '\u2713 OK' : '\u25b2 manque ' + fmt(missing), diff <= 0 ? 'ok' : 'need');
                    });
                } else {
                    var have = compStock[p] || 0;
                    var diff = totals[p] - have;
                    var missing = Math.max(0, diff);
                    html += makeRow(pieceNames[p] + ' \u2014 besoin ' + fmt(totals[p]) + ' / stock ' + fmt(have),
                        diff <= 0 ? '\u2713 OK' : '\u25b2 manque ' + fmt(missing), diff <= 0 ? 'ok' : 'need');
                }
            });
            $('simStockTable').innerHTML = html;
        } else {
            var ssc = $('simStockCompare');
            if (ssc) ssc.style.display = 'none';
        }

        // Effective totals after stock deduction
        var effTotals = {};
        if (compStock) {
            pieceKeys.forEach(function (k) {
                if (k === 'plans') {
                    var totalPlansNeeded = totals.plans;
                    var totalPlansInStock = 0;
                    Object.keys(weapons).forEach(function (slug) {
                        if (!orders[slug]) return;
                        var planNeed = (weapons[slug].pieces.plans || 0) * orders[slug];
                        var planHave = compStock['plan_' + slug] || 0;
                        totalPlansInStock += Math.min(planNeed, planHave);
                    });
                    effTotals.plans = Math.max(0, totalPlansNeeded - totalPlansInStock);
                } else {
                    effTotals[k] = Math.max(0, totals[k] - (compStock[k] || 0));
                }
            });
        } else {
            pieceKeys.forEach(function (k) { effTotals[k] = totals[k]; });
        }

        var metalForR = effTotals.ressort * RESSORT_METAL_RATE;
        var mineraiForR = effTotals.ressort * RESSORT_MINERAI_RATE;
        var totalMetal = effTotals.metal + metalForR;
        var totalMineraiMetal = totalMetal * METAL_MINERAI_RATE;
        var totalMinerai = totalMineraiMetal + mineraiForR;
        var totalPetrole = effTotals.polymere * POLYMERE_PETROLE_RATE;

        var stockLabel = useStock ? ' (apr\u00e8s stock)' : '';
        html = '';
        html += makeSectionHeader('Craft Ressorts (' + fmt(effTotals.ressort) + ')' + stockLabel);
        html += makeRow('Pi\u00e8ces m\u00e9tal (pour ressorts)', fmt(metalForR));
        html += makeRow('Minerais (pour ressorts)', fmt(mineraiForR));
        html += makeSectionHeader('Craft Pi\u00e8ces m\u00e9tal (' + fmt(totalMetal) + ')' + stockLabel);
        html += makeRow('Directes', fmt(effTotals.metal));
        html += makeRow('Pour ressorts', fmt(metalForR));
        html += makeRow('Minerais n\u00e9cessaires', fmt(totalMineraiMetal));
        html += makeSectionHeader('Craft Polym\u00e8res (' + fmt(effTotals.polymere) + ')' + stockLabel);
        html += makeRow('P\u00e9troles n\u00e9cessaires', fmt(totalPetrole));
        $('materialCraft').innerHTML = html;

        html = '';
        html += makeRow('Minerais de fer' + stockLabel, fmt(totalMinerai), 'highlight');
        html += makeRow('P\u00e9troles' + stockLabel, fmt(totalPetrole), 'highlight');
        if (effTotals.plans) html += makeRow('Plans (utilisations)', fmt(effTotals.plans));
        if (effTotals.canon) html += makeRow('Canons', fmt(effTotals.canon));
        if (effTotals.poignee) html += makeRow('Poign\u00e9es', fmt(effTotals.poignee));
        if (effTotals.corp) html += makeRow('Corps de pistolet', fmt(effTotals.corp));
        if (effTotals.crosse) html += makeRow('Crosses', fmt(effTotals.crosse));
        if (effTotals.corp_smg) html += makeRow('Corps de SMG', fmt(effTotals.corp_smg));
        if (effTotals.corp_rifle) html += makeRow('Corps de fusil', fmt(effTotals.corp_rifle));
        $('rawMaterials').innerHTML = html;

        var cost = effTotals.polymere * POLYMERE_COST;
        $('costTable').innerHTML = makeRow('Polym\u00e8res (' + fmt(effTotals.polymere) + ' \u00d7 ' + fmt(POLYMERE_COST) + '\u20ac)', fmt(cost) + ' \u20ac', 'highlight');

        var totalTime = 0, hasUnknown = false;
        Object.keys(weapons).forEach(function (key) {
            if (!orders[key]) return;
            if (weapons[key].craftTime === null) hasUnknown = true;
            else totalTime += weapons[key].craftTime * orders[key];
        });
        var timeStr = formatTime(totalTime);
        if (hasUnknown) timeStr += ' + temps inconnu';
        $('craftTime').textContent = timeStr;
    }

    if (grid) {
        grid.addEventListener('click', function (e) {
            var btn = e.target.closest('.qty-btn');
            if (!btn) return;
            var w = btn.getAttribute('data-weapon');
            var input = $('qty-' + w);
            var val = parseInt(input.value, 10) || 0;
            input.value = btn.classList.contains('plus') ? Math.min(val + 1, 99) : Math.max(val - 1, 0);
            calculate();
        });
        grid.addEventListener('input', function (e) { if (e.target.classList.contains('qty-input')) calculate(); });
    }

    // Re-calculate when stock inputs change
    if (compStockGrid) {
        compStockGrid.addEventListener('input', function (e) { if (e.target.classList.contains('weapon-stock-in')) calculate(); });
    }

    // ===== WEAPON CRAFT COST =====
    var MINERAI_PER_RESSORT = RESSORT_METAL_RATE * METAL_MINERAI_RATE + RESSORT_MINERAI_RATE;

    function weaponCraftCompCostOne(w) {
        var p = w.pieces;
        return (p.corp || 0) * WEAPON_CRAFT_CORP_EUR
             + (p.corp_smg || 0) * WEAPON_CRAFT_CORP_SMG_EUR
             + (p.corp_rifle || 0) * WEAPON_CRAFT_CORP_RIFLE_EUR
             + ((p.canon || 0) + (p.poignee || 0) + (p.crosse || 0)) * WEAPON_CRAFT_WEAPON_PIECE_EUR;
    }

    function weaponCraftMatCostOne(w, ferPrice) {
        var p = w.pieces;
        var fp = Math.max(0, ferPrice);
        var metalMinerai = (p.metal || 0) * METAL_MINERAI_RATE;
        var ressortMinerai = (p.ressort || 0) * MINERAI_PER_RESSORT;
        return (metalMinerai + ressortMinerai) * fp;
    }

    function weaponCraftCostBreakdownOne(w, planPriceEu, ferPrice, compsInStock) {
        var pp = Math.max(0, planPriceEu);
        var p = w.pieces;
        var costPlans = (p.plans || 0) * pp;
        var costComp = compsInStock ? 0 : weaponCraftCompCostOne(w);
        var costMat = weaponCraftMatCostOne(w, ferPrice);
        var costPoly = (p.polymere || 0) * POLYMERE_COST;
        var totalAch = costPlans + costComp + costMat + costPoly;
        var totalRec = costPlans + costComp + costPoly;
        return { costPlans: costPlans, costComp: costComp, costMat: costMat, costPoly: costPoly, totalAch: totalAch, totalRec: totalRec };
    }

    function weaponCraftTimeLabel(craftTime) {
        if (craftTime === null || craftTime === undefined) return '?';
        return craftTime + ' s';
    }

    function readWeaponCraftFerPrice() {
        var el = $('weaponCraftFerPrice');
        return el ? Math.max(0, parseFloat(el.value) || 0) : 30;
    }

    function readWeaponCraftCompsInStock() {
        var el = $('weaponCraftCompsInStock');
        return el ? el.checked : false;
    }

    function updateWeaponCraftTable() {
        var tbody = $('weaponCraftBody');
        var planIn = $('weaponCraftPlanPrice');
        if (!tbody || !planIn) return;
        var raw = String(planIn.value).trim();
        var planEu = raw === '' ? 0 : Math.max(0, parseFloat(raw) || 0);
        var ferPrice = readWeaponCraftFerPrice();
        var compsInStock = readWeaponCraftCompsInStock();
        var html = '';
        weaponList.forEach(function (w) {
            var wd = weapons[w.slug];
            if (!wd) return;
            var bought = wd.isBoughtWeapon;
            var sell = wd.sellPrice || 0;
            var refBuy = wd.referencePurchasePrice || 0;
            html += '<tr>';
            html += '<td>' + esc(w.name) + '</td>';
            html += '<td>' + esc(weaponCraftTimeLabel(wd.craftTime)) + '</td>';
            var range = fmtPriceRange(wd);
            var sellCell = (sell > 0 ? fmtEuro(sell) : '\u2014') + (range ? '<br><small class="price-range">' + range + '</small>' : '');
            if (bought) {
                html += '<td>\u2014</td><td>\u2014</td><td>\u2014</td><td>\u2014</td><td>\u2014</td><td>\u2014</td>';
                html += '<td>' + sellCell + '</td>';
                var margeRevente = (sell > 0 && refBuy > 0) ? sell - refBuy : null;
                html += '<td class="' + ammoBenClass(margeRevente) + '">' + (margeRevente != null ? fmtEuro(margeRevente) : '\u2014') + '</td>';
                html += '<td class="' + ammoBenClass(margeRevente) + '">' + (margeRevente != null ? fmtEuro(margeRevente) : '\u2014') + '</td>';
            } else {
                var b = weaponCraftCostBreakdownOne(wd, planEu, ferPrice, compsInStock);
                html += '<td>' + fmtEuro(b.costPlans) + '</td>';
                html += '<td>' + (compsInStock ? '\u2014' : fmtEuro(b.costComp)) + '</td>';
                html += '<td>' + fmtEuro(b.costMat) + '</td>';
                html += '<td>' + fmtEuro(b.costPoly) + '</td>';
                html += '<td>' + fmtEuro(b.totalAch) + '</td>';
                html += '<td>' + fmtEuro(b.totalRec) + '</td>';
                html += '<td>' + sellCell + '</td>';
                var margeAch = (sell > 0) ? sell - b.totalAch : null;
                var margeRec = (sell > 0) ? sell - b.totalRec : null;
                html += '<td class="' + ammoBenClass(margeAch) + '">' + (margeAch != null ? fmtEuro(margeAch) : '\u2014') + '</td>';
                html += '<td class="' + ammoBenClass(margeRec) + '">' + (margeRec != null ? fmtEuro(margeRec) : '\u2014') + '</td>';
            }
            html += '</tr>';
        });
        tbody.innerHTML = html;
    }

    function refreshWeaponCraftSims() {
        updateWeaponCraftTable();
        updateCraftableFromStock();
    }

    // ===== CRAFTABLE FROM STOCK (logged-in only) =====
    // Pistol plan slugs: plans that can craft various pistols (9mm to heavy, cal .50 excluded)
    var PISTOL_PLAN_WEAPONS = ['sns', 'wn29', 'ceramic', 'pistol', 'heavy'];

    function updateCraftableFromStock() {
        var el = $('craftableFromStock');
        if (!el) return;
        if (!auth || !auth.isLoggedIn || !cachedData || !cachedData.stock) {
            el.style.display = 'none';
            return;
        }
        el.style.display = '';

        var stockMap = {};
        cachedData.stock.forEach(function (s) { stockMap[s.slug] = s.quantity; });

        var html = '';
        html += '<div class="sim-section-title">Armes craftables avec le stock actuel</div>';
        html += '<p class="ammo-sim-intro ammo-sim-intro-tight">Nombre maximum d\'armes craftables par type, en utilisant tout le stock disponible pour ce type. Les <strong>plans Pistol</strong> couvrent les pistolets (SNS \u2192 Heavy) <em>sauf</em> le Cal .50. Les pi\u00e8ces partag\u00e9es (ressort, canon, m\u00e9tal\u2026) sont compt\u00e9es ind\u00e9pendamment par arme.</p>';
        var hasAny = false;

        weaponList.forEach(function (w) {
            var wd = weapons[w.slug];
            if (!wd || wd.isBoughtWeapon) return;
            var maxCraftable = Infinity;
            var limitingPiece = '';

            pieceKeys.forEach(function (p) {
                var need = wd.pieces[p] || 0;
                if (need <= 0) return;
                var have = 0;
                if (p === 'plans') {
                    have = stockMap['plan_' + w.slug] || 0;
                    if (PISTOL_PLAN_WEAPONS.indexOf(w.slug) >= 0) {
                        have += (stockMap['plan_pistol'] || 0);
                    }
                } else {
                    have = stockMap[p] || 0;
                }
                var canMake = Math.floor(have / need);
                if (canMake < maxCraftable) {
                    maxCraftable = canMake;
                    limitingPiece = pieceNames[p] || p;
                }
            });

            if (maxCraftable === Infinity) maxCraftable = 0;
            hasAny = true;
            var cls = maxCraftable <= 0 ? 'need' : (maxCraftable < 3 ? '' : 'ok');
            var label = maxCraftable <= 0
                ? '0 \u2014 manque ' + limitingPiece
                : fmt(maxCraftable) + ' arme(s)' + (limitingPiece ? ' (limit\u00e9 par ' + limitingPiece + ')' : '');
            html += makeRow(esc(wd.name), label, cls);
        });

        if (!hasAny) {
            html += '<div class="empty-msg">Aucune arme craftable.</div>';
        }

        el.innerHTML = html;
    }

    // ===== WEAPON CRAFT EVENT LISTENERS =====
    var weaponCraftPlanEl = $('weaponCraftPlanPrice');
    if (weaponCraftPlanEl) {
        weaponCraftPlanEl.addEventListener('input', refreshWeaponCraftSims);
        weaponCraftPlanEl.addEventListener('change', refreshWeaponCraftSims);
    }
    var weaponCraftFerEl = $('weaponCraftFerPrice');
    if (weaponCraftFerEl) {
        weaponCraftFerEl.addEventListener('input', refreshWeaponCraftSims);
        weaponCraftFerEl.addEventListener('change', refreshWeaponCraftSims);
    }
    var weaponCraftCompsEl = $('weaponCraftCompsInStock');
    if (weaponCraftCompsEl) {
        weaponCraftCompsEl.addEventListener('change', refreshWeaponCraftSims);
    }
    refreshWeaponCraftSims();

    // ===== MEMBER DASHBOARD =====
    function showDashboard() {
        var dash = $('memberDashboard');
        if (!dash) return;
        dash.style.display = '';

        var nameEl = $('currentMemberName');
        if (nameEl) nameEl.textContent = auth.userName;

        var roleEl = $('currentMemberRole');
        if (roleEl) {
            var labels = {
                president: 'President', treasurer: 'Tresorier',
                vice_president: 'Vice-President', officer: 'Officier',
                member: 'Membre', prospect: 'Prospect'
            };
            roleEl.textContent = labels[auth.userRole] || auth.userRole;
            roleEl.className = 'member-bar-role role-' + auth.userRole;
        }

        loadDashboardData();
    }

    function loadDashboardData() {
        auth.apiGet('/simulateur-armes/api/data', function (err, data) {
            if (err || !data || data.error) return;
            cachedData = data;
            if (data.members) memberList = data.members;
            if (data.assignable_roles) window.MC_ASSIGNABLE_ROLES = data.assignable_roles;
            populateForms();
            renderDashboard(data);
            renderProfile();
            updateCraftableFromStock();
            fillWeaponStockFields();
            calculate();
        });
    }

    function hideDashboard() {
        cachedData = null;
        var dash = $('memberDashboard');
        if (dash) dash.style.display = 'none';
        updateCraftableFromStock();
    }

    auth.onLogin(function () { showDashboard(); });
    auth.onLogout(function () { hideDashboard(); });
    if (auth.isLoggedIn) showDashboard();

    // ===== POPULATE FORMS =====
    function getWeaponStock(weaponId) {
        if (!cachedData || !cachedData.stock) return null;
        var w = weaponById[weaponId];
        if (!w) return null;
        var found = null;
        cachedData.stock.forEach(function (s) {
            if (s.slug === 'weapon_' + w.slug) found = s;
        });
        return found;
    }

    function populateForms() {
        var sw = $('saleWeapon');
        if (sw) {
            populateSaleWeaponSelect(sw);
            onSaleWeaponChange();
        }

        if (cachedData && cachedData.stock) {
            var mvs = $('mvStock');
            if (mvs) populateGroupedStockSelect(mvs, cachedData.stock);
        }

        var mr = $('mvReason');
        if (mr) {
            destroyTomSelect(mr);
            mr.innerHTML = '';
            if (cachedData && cachedData.reasons) {
                Object.keys(cachedData.reasons).forEach(function (k) {
                    mr.insertAdjacentHTML('beforeend', '<option value="' + k + '">' + esc(cachedData.reasons[k]) + '</option>');
                });
            }
            initTomSelectSingle(mr, { placeholder: 'Raison...', searchField: ['text'] });
        }
        updateMvCostVisibility();
        document.querySelectorAll('.ct-weapon').forEach(function (sel) { populateWeaponSelect(sel); });
    }

    function onSaleWeaponChange() {
        var sw = $('saleWeapon');
        if (!sw) return;
        var wid = parseInt(sw.value, 10);
        var w = weaponById[wid];
        if (w && w.sell_price) $('salePrice').value = w.sell_price;
        updateSalePreview();
    }
    var saleWeaponEl = $('saleWeapon');
    if (saleWeaponEl) saleWeaponEl.addEventListener('change', onSaleWeaponChange);

    // ===== SALE =====
    function updateSalePreview() {
        var qtyEl = $('saleQty'), priceEl = $('salePrice'), prevEl = $('salePreview');
        if (!qtyEl || !priceEl || !prevEl) return;
        var qty = parseInt(qtyEl.value, 10) || 0;
        var price = parseFloat(priceEl.value) || 0;
        var total = qty * price;
        prevEl.innerHTML = total > 0 ? '<span class="preview-total">Total : <strong>' + fmt(total) + ' \u20ac</strong></span>' : '';
    }
    var saleQtyEl = $('saleQty');
    if (saleQtyEl) saleQtyEl.addEventListener('input', updateSalePreview);
    var salePriceEl = $('salePrice');
    if (salePriceEl) salePriceEl.addEventListener('input', updateSalePreview);

    var btnSale = $('btnSale');
    if (btnSale) btnSale.addEventListener('click', function () {
        var btn = $('btnSale');
        var buyer = $('saleBuyer').value.trim();
        if (!buyer) { showToast('Indiquez le nom de l\'acheteur', 'error'); return; }
        btn.disabled = true; btn.textContent = '...';
        auth.apiPost('/simulateur-armes/api/sale', {
            weapon_id: parseInt($('saleWeapon').value, 10),
            quantity: parseInt($('saleQty').value, 10) || 1,
            unit_price: parseFloat($('salePrice').value) || 0,
            buyer_name: buyer,
            notes: $('saleNotes').value
        }, function (err, data) {
            btn.disabled = false; btn.textContent = 'Enregistrer la vente';
            if (err || (data && data.error)) { showToast((data && data.error) || 'Erreur', 'error'); return; }
            if (data.warning) showToast(data.warning, 'error');
            else showToast(data.message, 'success');
            $('saleBuyer').value = ''; $('saleNotes').value = ''; $('saleQty').value = 1; $('salePrice').value = 0; $('salePreview').innerHTML = '';
            refreshData();
        });
    });

    // ===== DIRECTION TOGGLE =====
    var mvDirIn = $('mvDirIn'), mvDirOut = $('mvDirOut');
    if (mvDirIn) mvDirIn.addEventListener('click', function () { mvDirIn.classList.add('active'); mvDirOut.classList.remove('active'); });
    if (mvDirOut) mvDirOut.addEventListener('click', function () { mvDirOut.classList.add('active'); mvDirIn.classList.remove('active'); });

    function updateMvCostVisibility() {
        var reasonEl = $('mvReason'), costRow = $('mvCostRow');
        if (!reasonEl || !costRow) return;
        var isPurchase = reasonEl.value === 'purchase';
        costRow.style.display = isPurchase ? '' : 'none';
        updateMvCostPreview();
    }
    function updateMvCostPreview() {
        var qtyEl = $('mvQty'), costEl = $('mvUnitCost'), prevEl = $('mvCostPreview');
        if (!qtyEl || !costEl || !prevEl) return;
        var qty = parseInt(qtyEl.value, 10) || 0;
        var cost = parseFloat(costEl.value) || 0;
        var total = qty * cost;
        prevEl.innerHTML = total > 0 ? '<span class="preview-total">Co\u00fbt total : <strong>' + fmt(total) + ' \u20ac</strong></span>' : '';
    }
    var mvReasonEl = $('mvReason');
    if (mvReasonEl) mvReasonEl.addEventListener('change', updateMvCostVisibility);
    var mvQtyEl = $('mvQty');
    if (mvQtyEl) mvQtyEl.addEventListener('input', updateMvCostPreview);
    var mvUnitCostEl = $('mvUnitCost');
    if (mvUnitCostEl) mvUnitCostEl.addEventListener('input', updateMvCostPreview);

    // ===== MOVEMENT =====
    var btnMv = $('btnMovement');
    if (btnMv) btnMv.addEventListener('click', function () {
        var btn = $('btnMovement');
        var qty = parseInt($('mvQty').value, 10) || 0;
        if (qty <= 0) { showToast('Quantit\u00e9 invalide', 'error'); return; }
        var isOut = $('mvDirOut').classList.contains('active');
        btn.disabled = true; btn.textContent = '...';
        auth.apiPost('/simulateur-armes/api/movement', {
            stock_item_id: parseInt($('mvStock').value, 10),
            quantity_change: isOut ? -qty : qty,
            reason: $('mvReason').value,
            unit_cost: $('mvReason').value === 'purchase' ? (parseFloat($('mvUnitCost').value) || 0) : null,
            notes: $('mvNotes').value
        }, function (err, data) {
            btn.disabled = false; btn.textContent = 'Enregistrer le mouvement';
            if (err || (data && data.error)) { showToast((data && data.error) || 'Erreur', 'error'); return; }
            showToast(data.message, 'success');
            $('mvNotes').value = ''; $('mvQty').value = 1; $('mvUnitCost').value = 0; $('mvCostPreview').innerHTML = '';
            refreshData();
        });
    });

    // ===== CONTRACT CREATE =====
    var btnAddItem = $('btnAddCtItem');
    if (btnAddItem) btnAddItem.addEventListener('click', function () {
        var row = document.createElement('div');
        row.className = 'form-row ct-item-row';
        row.innerHTML = '<div class="form-group"><select class="fm-input ct-weapon"></select></div>' +
            '<div class="form-group sm"><input type="number" class="fm-input ct-qty" value="1" min="1" max="999"></div>' +
            '<div class="form-group xs"><button type="button" class="action-btn-sm rm-btn">\u2715</button></div>';
        $('ctItemsContainer').appendChild(row);
        populateWeaponSelect(row.querySelector('.ct-weapon'));
        row.querySelector('.rm-btn').addEventListener('click', function () { row.remove(); });
    });

    var btnCt = $('btnCreateContract');
    if (btnCt) btnCt.addEventListener('click', function () {
        var btn = $('btnCreateContract');
        var name = $('ctName').value.trim();
        var client = $('ctClient').value.trim();
        if (!name || !client) { showToast('Nom et client requis', 'error'); return; }
        var items = [];
        document.querySelectorAll('.ct-item-row').forEach(function (row) {
            var wid = parseInt(row.querySelector('.ct-weapon').value, 10);
            var qty = parseInt(row.querySelector('.ct-qty').value, 10) || 0;
            if (wid && qty > 0) items.push({ weapon_id: wid, qty_ordered: qty });
        });
        if (!items.length) { showToast('Ajoutez au moins une arme', 'error'); return; }
        btn.disabled = true; btn.textContent = '...';
        auth.apiPost('/simulateur-armes/api/contract', { name: name, client_name: client, notes: $('ctNotes').value, items: items }, function (err, data) {
            btn.disabled = false; btn.textContent = 'Creer le contrat';
            if (err || (data && data.error)) { showToast((data && data.error) || 'Erreur', 'error'); return; }
            showToast(data.message, 'success');
            $('ctName').value = ''; $('ctClient').value = ''; $('ctNotes').value = '';
            var container = $('ctItemsContainer');
            container.innerHTML = '<div class="form-row ct-item-row"><div class="form-group"><select class="fm-input ct-weapon"></select></div><div class="form-group sm"><input type="number" class="fm-input ct-qty" value="1" min="1" max="999"></div></div>';
            populateWeaponSelect(container.querySelector('.ct-weapon'));
            refreshData();
        });
    });

    // ===== MON PROFIL =====
    var ROLE_LABELS_FULL = {
        treasurer: 'Tresorier (superadmin)', president: 'President',
        vice_president: 'Vice-President', officer: 'Officier',
        member: 'Membre', prospect: 'Prospect'
    };

    function renderProfile() {
        var nameEl = $('profileName');
        if (!nameEl) return;
        nameEl.textContent = auth.userName || '--';

        var avatar = $('profileAvatar');
        if (avatar) {
            var initials = (auth.userName || '?').split(/\s+/).map(function (p) { return p.charAt(0); }).slice(0, 2).join('').toUpperCase();
            avatar.textContent = initials || '?';
            avatar.className = 'profile-avatar role-' + (auth.userRole || '');
        }

        var roleBadge = $('profileRoleBadge');
        if (roleBadge) {
            roleBadge.textContent = ROLE_LABELS_FULL[auth.userRole] || auth.userRole || '--';
            roleBadge.className = 'profile-role-badge role-' + (auth.userRole || '');
        }

        if (cachedData && cachedData.sales) {
            var mySales = cachedData.sales.filter(function (s) { return s.user_id === auth.userId || s.sold_by_user_id === auth.userId; });
            var myRevenue = mySales.reduce(function (sum, s) { return sum + (parseInt(s.total_price, 10) || (s.unit_price * s.quantity) || 0); }, 0);
            var myMovements = (cachedData.movements || []).filter(function (m) { return m.user_id === auth.userId; });
            var stats = $('profileStats');
            if (stats) {
                stats.innerHTML =
                    '<div class="profile-stat"><span class="ps-value">' + mySales.length + '</span><span class="ps-label">Ventes</span></div>' +
                    '<div class="profile-stat"><span class="ps-value">' + myRevenue.toLocaleString('fr-FR') + ' EUR</span><span class="ps-label">Revenu genere</span></div>' +
                    '<div class="profile-stat"><span class="ps-value">' + myMovements.length + '</span><span class="ps-label">Mouvements</span></div>';
            }
        }

        var manage = $('profileManageCard');
        if (manage) manage.style.display = (cachedData && cachedData.can_manage_members) ? '' : 'none';
    }

    var btnPin = $('btnChangePin');
    if (btnPin) btnPin.addEventListener('click', function () {
        var btn = $('btnChangePin');
        var cur = $('pinCurrent').value.trim();
        var nw = $('pinNew').value.trim();
        var cf = $('pinConfirm') ? $('pinConfirm').value.trim() : nw;
        if (!cur || !nw) { showToast('Remplissez les deux PINs', 'error'); return; }
        if (nw.length < 4) { showToast('Le nouveau PIN doit faire au moins 4 caracteres', 'error'); return; }
        if (nw !== cf) { showToast('La confirmation ne correspond pas', 'error'); return; }
        btn.disabled = true; btn.textContent = '...';
        auth.apiPost('/simulateur-armes/api/change-pin', { current_pin: cur, new_pin: nw }, function (err, data) {
            btn.disabled = false; btn.textContent = 'Modifier mon PIN';
            if (err || (data && data.error)) { showToast((data && data.error) || 'Erreur', 'error'); return; }
            showToast(data.message, 'success');
            $('pinCurrent').value = ''; $('pinNew').value = '';
            if ($('pinConfirm')) $('pinConfirm').value = '';
        });
    });

    // ===== REFRESH =====
    function refreshData() {
        auth.apiGet('/simulateur-armes/api/data', function (err, data) {
            if (err || !data || data.error) return;
            cachedData = data;
            if (data.members) memberList = data.members;
            renderDashboard(data);
            populateForms();
            calculate();
            updateCraftableFromStock();
        });
    }

    // ===== RENDER DASHBOARD =====
    function renderDashboard(data) {
        renderStats(data);
        renderStockCards(data.stock || []);
        renderContracts(data);
        renderHistory(data);
        renderAlerts(data.alerts || []);
        renderMembers(data.members || []);
    }

    function renderStats(data) {
        var stock = data.stock || [];
        var totalWeapons = 0, totalPieces = 0;
        stock.forEach(function (s) {
            if (s.category === 'weapon_finished') totalWeapons += s.quantity;
            if (s.category === 'weapon_piece' || s.category === 'weapon_plan') totalPieces += s.quantity;
        });
        var revenue = (data.finance && data.finance.total_revenue) || 0;
        var contracts = (data.contracts || []).length;
        var sr = $('statsRow');
        if (sr) sr.innerHTML =
            '<div class="stat-card"><div class="stat-val">' + fmt(totalWeapons) + '</div><div class="stat-label">Armes</div></div>' +
            '<div class="stat-card"><div class="stat-val">' + fmt(totalPieces) + '</div><div class="stat-label">Pi\u00e8ces</div></div>' +
            '<div class="stat-card revenue"><div class="stat-val">' + fmt(revenue) + ' \u20ac</div><div class="stat-label">Revenus</div></div>' +
            '<div class="stat-card"><div class="stat-val">' + contracts + '</div><div class="stat-label">Contrats</div></div>';
    }

    function renderStockCards(stock) {
        var cats = { weapon_finished: [], weapon_plan: [], weapon_piece: [], raw_material: [] };
        stock.forEach(function (s) { if (cats[s.category]) cats[s.category].push(s); });
        var html = '';
        cats.weapon_finished.forEach(function (s) {
            var wid = s.weapon_id || 0;
            html += '<div class="stock-card ' + (s.quantity > 0 ? 'has-stock' : 'no-stock') + '" data-quicksell="' + wid + '" title="Cliquer pour vendre">';
            html += '<div class="stock-card-qty">' + s.quantity + '</div>';
            html += '<div class="stock-card-name">' + esc(s.name) + '</div>';
            if (s.quantity > 0 && wid) html += '<div class="stock-card-action">Vendre</div>';
            html += '</div>';
        });
        var swc = $('stockWeaponsCards');
        if (swc) swc.innerHTML = html || '<div class="empty-msg">Aucune arme</div>';

        html = '';
        cats.weapon_plan.forEach(function (s) {
            var phys = Math.floor(s.quantity / PLANS_PER_ITEM);
            var hint = '';
            if (s.slug === 'plan_pistol') {
                hint = ' <span class="sm-hint">(couvre SNS \u2192 Heavy, sauf Cal .50)</span>';
            }
            html += '<div class="stock-mini"><span class="sm-name">' + esc(s.name) + hint + '</span><span class="sm-val">' + phys + ' plans (' + s.quantity + ' uses)</span></div>';
        });
        cats.weapon_piece.forEach(function (s) {
            html += '<div class="stock-mini' + (s.quantity <= 0 ? ' sm-low' : '') + '"><span class="sm-name">' + esc(s.name) + '</span><span class="sm-val">' + fmt(s.quantity) + '</span></div>';
        });
        var spg = $('stockPiecesGrid');
        if (spg) spg.innerHTML = html || '<div class="empty-msg">\u2014</div>';

        html = '';
        cats.raw_material.forEach(function (s) {
            html += '<div class="stock-mini"><span class="sm-name">' + esc(s.name) + '</span><span class="sm-val highlight">' + fmt(s.quantity) + '</span></div>';
        });
        var srg = $('stockRawGrid');
        if (srg) srg.innerHTML = html || '<div class="empty-msg">\u2014</div>';
    }

    function renderAlerts(alerts) {
        var banner = $('alertBanner');
        if (!banner) return;
        // Stock alerts only visible to officers+
        if (!auth || !auth.isLoggedIn || !auth.isAtLeast('officer')) {
            banner.style.display = 'none';
            return;
        }
        if (!alerts || !alerts.length) { banner.style.display = 'none'; return; }
        var html = 'Stock bas : ';
        alerts.forEach(function (a, i) {
            if (i > 0) html += ', ';
            html += '<strong>' + esc(a.name) + '</strong> (' + a.quantity + ')';
        });
        banner.innerHTML = html;
        banner.style.display = '';
    }

    // Quick-sell card click: redirect to the unified /ventes page, pre-filled for the weapon.
    // The legacy in-page sale form has been removed (Phase 3 cleanup).
    document.addEventListener('click', function (e) {
        var card = e.target.closest('.stock-card[data-quicksell]');
        if (!card) return;
        var wid = parseInt(card.getAttribute('data-quicksell'), 10);
        if (!wid) return;
        var w = weaponById[wid];
        if (!w || !cachedData || !cachedData.stock) { window.location.href = '/ventes'; return; }
        var stockItem = null;
        cachedData.stock.forEach(function (s) {
            if (s.slug === 'weapon_' + w.slug) stockItem = s;
        });
        if (stockItem && stockItem.id) {
            window.location.href = '/ventes?stock_item_id=' + stockItem.id + '&quantity=1';
        } else {
            window.location.href = '/ventes';
        }
    });

    // ===== CONTRACTS =====
    function renderContracts(data) {
        var contracts = data.contracts || [];
        $('contractsList').innerHTML = contracts.length ? renderContractCards(contracts, true) : '<div class="empty-msg">Aucun contrat actif</div>';
        computeContractNeeds(data);
        var all = data.all_contracts || [];
        $('allContractsList').innerHTML = all.length ? renderContractCards(all, false) : '<div class="empty-msg">Aucun contrat</div>';
    }

    function renderContractCards(contracts, showActions) {
        var isOfficer = auth.isOfficer();
        var html = '';
        contracts.forEach(function (c) {
            html += '<div class="contract-card">';
            html += '<div class="contract-card-header"><span class="contract-card-name">' + esc(c.name) + '</span><span class="contract-status-badge status-' + c.status + '">' + esc(c.status_label) + '</span></div>';
            html += '<div class="contract-card-meta">Client: ' + esc(c.client) + ' \u2014 Par: ' + esc(c.created_by) + '</div>';
            html += '<div class="progress-bar-wrap"><div class="progress-bar-fill" style="width:' + Math.min(100, c.progress) + '%"></div></div>';
            c.items.forEach(function (item) {
                var done = item.remaining === 0;
                html += '<div class="ct-item-line">';
                html += '<span class="ct-item-weapon">' + esc(item.weapon) + '</span>';
                if (showActions && !done) {
                    html += '<span class="ct-item-delivery">';
                    html += '<button class="ct-del-btn" data-iid="' + item.id + '" data-delta="-1" title="-1 livr\u00e9">\u2212</button>';
                    html += '<span class="ct-del-count">' + item.qty_delivered + '</span>';
                    html += '<button class="ct-del-btn" data-iid="' + item.id + '" data-delta="1" title="+1 livr\u00e9">+</button>';
                    html += '<span class="ct-del-of"> / </span>';
                    if (isOfficer) {
                        html += '<input type="number" class="ct-ord-input" data-iid="' + item.id + '" value="' + item.qty_ordered + '" min="1" max="999" title="Modifier la commande">';
                    } else {
                        html += '<span class="ct-del-total">' + item.qty_ordered + '</span>';
                    }
                    html += '</span>';
                } else {
                    html += '<span class="ct-item-delivery"><span class="value ' + (done ? 'ok' : 'need') + '">' + item.qty_delivered + ' / ' + item.qty_ordered + (done ? ' \u2713' : '') + '</span></span>';
                }
                html += '</div>';
            });
            if (showActions && isOfficer && cachedData && cachedData.contract_statuses) {
                html += '<div class="contract-actions"><select class="fm-input fm-sm ct-status-sel" data-cid="' + c.id + '">';
                Object.keys(cachedData.contract_statuses).forEach(function (k) {
                    html += '<option value="' + k + '"' + (k === c.status ? ' selected' : '') + '>' + esc(cachedData.contract_statuses[k]) + '</option>';
                });
                html += '</select><button class="action-btn-sm ct-status-btn" data-cid="' + c.id + '">Statut</button></div>';
            }
            html += '</div>';
        });
        return html;
    }

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.ct-del-btn');
        if (!btn) return;
        var iid = btn.getAttribute('data-iid');
        var delta = parseInt(btn.getAttribute('data-delta'), 10);
        var countEl = btn.parentElement.querySelector('.ct-del-count');
        var cur = parseInt(countEl.textContent, 10) || 0;
        var newVal = Math.max(0, cur + delta);
        countEl.textContent = newVal;
        btn.disabled = true;
        auth.apiPut('/simulateur-armes/api/contract-item/' + iid, { qty_delivered: newVal }, function (err, data) {
            btn.disabled = false;
            if (err || (data && data.error)) { showToast((data && data.error) || 'Erreur', 'error'); return; }
            refreshData();
        });
    });

    document.addEventListener('change', function (e) {
        if (!e.target.classList.contains('ct-ord-input')) return;
        var iid = e.target.getAttribute('data-iid');
        var newQty = parseInt(e.target.value, 10);
        if (!newQty || newQty < 1) return;
        auth.apiPut('/simulateur-armes/api/contract-item/' + iid, { qty_ordered: newQty }, function (err, data) {
            if (err || (data && data.error)) { showToast((data && data.error) || 'Erreur', 'error'); return; }
            showToast('Quantit\u00e9 mise \u00e0 jour', 'success');
            refreshData();
        });
    });

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.ct-status-btn');
        if (!btn) return;
        var cid = btn.getAttribute('data-cid');
        var sel = document.querySelector('.ct-status-sel[data-cid="' + cid + '"]');
        if (!sel) return;
        btn.disabled = true; btn.textContent = '...';
        auth.apiPut('/simulateur-armes/api/contract/' + cid, { status: sel.value }, function (err, data) {
            btn.disabled = false; btn.textContent = 'Statut';
            if (err || (data && data.error)) { showToast((data && data.error) || 'Erreur', 'error'); return; }
            showToast(data.message, 'success');
            refreshData();
        });
    });

    function computeContractNeeds(data) {
        var contracts = data.contracts || [];
        var stock = data.stock || [];
        var stockMap = {};
        stock.forEach(function (s) { stockMap[s.slug] = s.quantity; });

        var weaponNeeds = {};
        contracts.forEach(function (c) {
            c.items.forEach(function (item) {
                if (item.remaining > 0) weaponNeeds[item.weapon_slug] = (weaponNeeds[item.weapon_slug] || 0) + item.remaining;
            });
        });

        var html = '', toCraft = {}, hasAny = false;
        Object.keys(weaponNeeds).forEach(function (slug) {
            var needed = weaponNeeds[slug];
            var have = stockMap['weapon_' + slug] || 0;
            var diff = Math.max(0, needed - have);
            toCraft[slug] = diff;
            hasAny = true;
            var wName = weapons[slug] ? weapons[slug].name : slug;
            html += makeRow(wName + ' \u2014 besoin ' + fmt(needed) + ', stock ' + fmt(have),
                diff <= 0 ? '\u2713 En stock' : '\u2699 ' + fmt(diff) + ' \u00e0 fabriquer', diff <= 0 ? 'ok' : 'need');
        });
        $('contractWeaponsToProduce').innerHTML = hasAny ? html : '<div class="empty-msg">Aucun besoin</div>';

        var totals = {};
        pieceKeys.forEach(function (k) { totals[k] = 0; });
        var totalCraftTime = 0, hasUnknown = false;
        Object.keys(toCraft).forEach(function (slug) {
            if (toCraft[slug] <= 0) return;
            var w = weapons[slug]; if (!w) return;
            pieceKeys.forEach(function (p) { totals[p] += (w.pieces[p] || 0) * toCraft[slug]; });
            if (w.craftTime === null) hasUnknown = true;
            else totalCraftTime += w.craftTime * toCraft[slug];
        });

        var anyToCraft = Object.keys(toCraft).some(function (s) { return toCraft[s] > 0; });
        $('contractFullBreakdown').style.display = anyToCraft ? '' : 'none';
        if (!anyToCraft) return;

        html = '';
        pieceKeys.forEach(function (p) {
            if (!totals[p]) return;
            if (p === 'plans') {
                var planNeed = 0, planHave = 0;
                Object.keys(toCraft).forEach(function (slug) {
                    if (toCraft[slug] <= 0) return;
                    planNeed += (weapons[slug].pieces.plans || 0) * toCraft[slug];
                    planHave += stockMap['plan_' + slug] || 0;
                });
                var diff = planNeed - planHave;
                html += makeRow(pieceNames[p] + ' \u2014 ' + fmt(planNeed) + ' utilis.', diff <= 0 ? '\u2713 OK (' + fmt(planHave) + ')' : '\u25b2 ' + fmt(diff), diff <= 0 ? 'ok' : 'need');
            } else {
                var have = stockMap[p] || 0, diff = totals[p] - have;
                html += makeRow(pieceNames[p] + ' \u2014 ' + fmt(totals[p]) + ' n\u00e9cess.', diff <= 0 ? '\u2713 OK (' + fmt(have) + ')' : '\u25b2 ' + fmt(diff), diff <= 0 ? 'ok' : 'need');
            }
        });
        $('contractPiecesNeeded').innerHTML = html;

        var metalForR = totals.ressort * RESSORT_METAL_RATE, mineraiForR = totals.ressort * RESSORT_MINERAI_RATE;
        var totalMetal = totals.metal + metalForR, totalMineraiMetal = totalMetal * METAL_MINERAI_RATE;
        var totalMinerai = totalMineraiMetal + mineraiForR, totalPetrole = totals.polymere * POLYMERE_PETROLE_RATE;

        html = '';
        html += makeSectionHeader('Craft Ressorts (' + fmt(totals.ressort) + ')');
        html += makeRow('Pi\u00e8ces m\u00e9tal', fmt(metalForR)); html += makeRow('Minerais', fmt(mineraiForR));
        html += makeSectionHeader('Craft Pi\u00e8ces m\u00e9tal (' + fmt(totalMetal) + ')');
        html += makeRow('Directes', fmt(totals.metal)); html += makeRow('Pour ressorts', fmt(metalForR)); html += makeRow('Minerais', fmt(totalMineraiMetal));
        html += makeSectionHeader('Craft Polym\u00e8res (' + fmt(totals.polymere) + ')');
        html += makeRow('P\u00e9troles', fmt(totalPetrole));
        $('contractMaterialCraft').innerHTML = html;

        var mineraiHave = stockMap['minerai'] || 0, petroleHave = stockMap['petrole'] || 0;
        html = '';
        var md = totalMinerai - mineraiHave;
        html += makeRow('Minerais \u2014 ' + fmt(totalMinerai), md <= 0 ? '\u2713 OK (' + fmt(mineraiHave) + ')' : '\u25b2 ' + fmt(md) + ' (' + fmt(mineraiHave) + ')', md <= 0 ? 'ok' : 'need highlight');
        var pd = totalPetrole - petroleHave;
        html += makeRow('P\u00e9troles \u2014 ' + fmt(totalPetrole), pd <= 0 ? '\u2713 OK (' + fmt(petroleHave) + ')' : '\u25b2 ' + fmt(pd) + ' (' + fmt(petroleHave) + ')', pd <= 0 ? 'ok' : 'need highlight');
        $('contractRawMaterials').innerHTML = html;

        $('contractCostTable').innerHTML = makeRow('Polym\u00e8res (' + fmt(totals.polymere) + ' \u00d7 ' + fmt(POLYMERE_COST) + '\u20ac)', fmt(totals.polymere * POLYMERE_COST) + ' \u20ac', 'highlight');
        var ts = formatTime(totalCraftTime); if (hasUnknown) ts += ' + ?';
        $('contractCraftTime').textContent = ts;
    }

    function movementHistoryQtyChange(m) {
        if (m.reason === 'attribution' && m.attribution_original_abs != null) {
            return -m.attribution_original_abs;
        }
        return m.quantity_change;
    }

    // ===== HISTORY =====
    function renderHistory(data) {
        var movements = data.movements || [], sales = data.sales || [];
        var html = '';
        movements.forEach(function (m) {
            var qc = movementHistoryQtyChange(m);
            var sign = qc > 0 ? '+' : '', cls = qc > 0 ? 'mv-in' : 'mv-out';
            html += '<div class="movement-row ' + cls + '">';
            html += '<span class="mv-date">' + esc(m.date) + '</span>';
            html += '<span class="mv-stock">' + esc(m.stock_name) + '</span>';
            html += '<span class="mv-qty">' + sign + qc + '</span>';
            html += '<span class="mv-reason">' + esc(m.reason_label) + '</span>';
            html += '<span class="mv-user">' + esc(m.user) + '</span>';
            if (m.notes) html += '<span class="mv-notes">' + esc(m.notes) + '</span>';
            if (m.unit_cost) html += '<span class="mv-notes">' + fmt(m.unit_cost) + ' \u20ac/u (total: ' + fmt(m.unit_cost * Math.abs(qc)) + ' \u20ac)</span>';
            html += '</div>';
        });
        var ml = $('movementsList');
        if (ml) ml.innerHTML = html || '<div class="empty-msg">Aucun mouvement</div>';

        html = '';
        sales.forEach(function (s) {
            html += '<div class="movement-row mv-sale">';
            html += '<span class="mv-date">' + esc(s.date) + '</span>';
            html += '<span class="mv-stock">' + esc(s.weapon) + ' \u00d7' + s.quantity + '</span>';
            html += '<span class="mv-qty">' + fmt(s.total) + '\u20ac</span>';
            html += '<span class="mv-reason">' + esc(s.buyer) + '</span>';
            html += '<span class="mv-user">' + esc(s.user) + '</span>';
            if (s.notes) html += '<span class="mv-notes">' + esc(s.notes) + '</span>';
            html += '</div>';
        });
        var sl = $('salesList');
        if (sl) sl.innerHTML = html || '<div class="empty-msg">Aucune vente</div>';
    }

    // ===== MEMBERS LIST =====
    var ROLE_SHORT = {
        treasurer: 'Tresorier', president: 'President', vice_president: 'Vice-President',
        officer: 'Officier', member: 'Membre', prospect: 'Prospect'
    };

    function renderMembers(members) {
        if (!auth.isOfficer()) return;
        var list = $('membersList');
        if (!list) return;
        var html = '';
        members.forEach(function (m) {
            var label = ROLE_SHORT[m.role] || m.role;
            html += '<div class="member-list-row">';
            html += '<span class="ml-name">' + esc(m.name) + '</span>';
            html += '<span class="member-badge role-' + esc(m.role) + '">' + esc(label) + '</span>';
            html += '</div>';
        });
        html += '<div class="ml-hint">Pour modifier les roles, reinitialiser un PIN ou desactiver un membre, rendez-vous sur <a href="/membres" class="inline-link">la page Gestion des membres</a>.</div>';
        list.innerHTML = html || '<div class="empty-msg">Aucun membre</div>';
    }
})();
