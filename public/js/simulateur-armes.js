(function () {
    'use strict';

    // ===== DATA =====
    var weaponList = window.WEAPONS || [];
    var memberList = window.MEMBERS || [];
    var weapons = {};
    var pieceKeys = ['plans', 'ressort', 'canon', 'poignee', 'corp', 'metal', 'polymere'];
    var pieceNames = {
        plans: 'Plans', ressort: 'Ressort', canon: 'Canon',
        poignee: 'Poignée', corp: 'Corp de pistolet',
        metal: 'Pièce de métal', polymere: 'Polymère'
    };

    weaponList.forEach(function (w) {
        var sp = w.sell_price || 0;
        var rpRaw = w.reference_purchase_price;
        var rp = (rpRaw != null && rpRaw !== '' && !isNaN(Number(rpRaw))) ? (Number(rpRaw) || 0) : 0;
        weapons[w.slug] = {
            id: w.id, name: w.name, slug: w.slug,
            craftTime: w.craft_time_seconds,
            sellPrice: sp,
            referencePurchasePrice: rp,
            isBoughtWeapon: w.slug === 'sns',
            pieces: {
                plans: w.recipe_plans, ressort: w.recipe_ressort,
                canon: w.recipe_canon, poignee: w.recipe_poignee,
                corp: w.recipe_corp, metal: w.recipe_metal, polymere: w.recipe_polymere
            }
        };
    });

    // Build weapon id->data map for quick lookup
    var weaponById = {};
    weaponList.forEach(function (w) { weaponById[w.id] = w; });

    var POLYMERE_PETROLE_RATE = 5;
    var POLYMERE_COST = 4500;
    /** Coût d’achat simulé : corps de pistolet (recette corp). */
    var WEAPON_CRAFT_CORP_EUR = 15000;
    /** Ressort, canon, poignée, pièce de métal : chaque unité de recette. */
    var WEAPON_CRAFT_WEAPON_PIECE_EUR = 5000;
    var AMMO_GUNPOWDER_PRICE = 100;
    var AMMO_YIELD_PER_CRAFT = 10;
    /** 1 unité de fer craftée en fragments : 2 fragments par unité de fer (fragments de recette = fer crafté). */
    var AMMO_FRAGMENTS_PER_FER_UNIT = 2;
    var AMMO_RECIPES = [
        { name: '9mm', craftSec: 5, poudre: 5, fragment: 10 },
        { name: '.38 LC', craftSec: 10, poudre: 15, fragment: 10 },
        { name: '.45 ACP', craftSec: 5, poudre: 5, fragment: 10 },
        { name: '.50 AE', craftSec: 5, poudre: 10, fragment: 10 },
        { name: '5.56x45', craftSec: 10, poudre: 20, fragment: 25 },
        { name: '7.62x39', craftSec: 10, poudre: 20, fragment: 25 },
        { name: '12 Gauge', craftSec: 10, poudre: 30, fragment: 20 },
        { name: '7.62x51', craftSec: 10, poudre: 20, fragment: 30 },
        { name: '.50 BMG', craftSec: 10, poudre: 20, fragment: 35 }
    ];
    /**
     * Prix de vente €/mun : si coût poudre seule / mun ≤ 50 € → × 2 sur ce coût ; sinon × 1,5 sur le coût fer acheté (poudre + fer).
     * Arrondi multiple de 10 € ; exceptions : 5.56×45 à 350 €, 7.62×39 à 500 €, 12 Gauge à 400 €.
     */
    var AMMO_SELL_POWDER_THRESHOLD_EUR = 50;
    var AMMO_SELL_MARKUP_SMALL = 2;
    var AMMO_SELL_MARKUP_LARGE = 1.5;
    var METAL_MINERAI_RATE = 5;
    var RESSORT_METAL_RATE = 1;
    var RESSORT_MINERAI_RATE = 3;
    var PLANS_PER_ITEM = 4;

    // Auth state
    var currentUserId = sessionStorage.getItem('lmc_uid') ? parseInt(sessionStorage.getItem('lmc_uid'), 10) : null;
    var currentUserName = sessionStorage.getItem('lmc_name') || '';
    var currentUserRole = sessionStorage.getItem('lmc_role') || '';
    var isLoggedIn = false;
    var cachedData = null;

    // ===== HELPERS =====
    function esc(s) { if (s == null) return ''; var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
    function fmt(n) { return Number(n).toLocaleString('fr-FR'); }
    function fmtEuro(n) {
        return Number(n).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' €';
    }
    function $(id) { return document.getElementById(id); }
    function csrfToken() { var el = document.querySelector('meta[name="csrf-token"]'); return el ? el.getAttribute('content') : ''; }

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

    function showToast(msg, type) {
        var t = $('toast');
        t.textContent = msg;
        t.className = 'toast show ' + (type || 'info');
        clearTimeout(t._timer);
        t._timer = setTimeout(function () { t.className = 'toast'; }, 3500);
    }

    // ===== API HELPERS =====
    function apiHeaders() {
        var h = { 'Accept': 'application/json' };
        if (currentUserId) h['X-Sim-User'] = '' + currentUserId;
        return h;
    }

    function apiGet(cb) {
        fetch('/simulateur-armes/api/data', { headers: apiHeaders() })
            .then(function (r) { return r.json(); })
            .then(function (d) { cb(null, d); })
            .catch(function (e) { cb(e); });
    }

    function apiPost(url, body, cb) {
        var h = apiHeaders();
        h['Content-Type'] = 'application/json';
        h['X-CSRF-TOKEN'] = csrfToken();
        fetch(url, { method: 'POST', headers: h, body: JSON.stringify(body) })
            .then(function (r) { return r.json(); })
            .then(function (d) { cb(null, d); })
            .catch(function (e) { cb(e); });
    }

    function apiPut(url, body, cb) {
        var h = apiHeaders();
        h['Content-Type'] = 'application/json';
        h['X-CSRF-TOKEN'] = csrfToken();
        h['X-HTTP-Method-Override'] = 'PUT';
        fetch(url, { method: 'POST', headers: h, body: JSON.stringify(body) })
            .then(function (r) { return r.json(); })
            .then(function (d) { cb(null, d); })
            .catch(function (e) { cb(e); });
    }

    // ===== GROUPED SELECT =====
    function populateGroupedStockSelect(sel, stock) {
        sel.innerHTML = '';
        var cats = { finished_weapon: 'Armes finies', piece: 'Pièces', plan: 'Plans', raw_material: 'Matières premières' };
        var grouped = {};
        stock.forEach(function (s) {
            if (!grouped[s.category]) grouped[s.category] = [];
            grouped[s.category].push(s);
        });
        Object.keys(cats).forEach(function (cat) {
            if (!grouped[cat] || !grouped[cat].length) return;
            var og = document.createElement('optgroup');
            og.label = cats[cat];
            grouped[cat].forEach(function (s) {
                var opt = document.createElement('option');
                opt.value = s.id;
                opt.textContent = s.name + ' (' + s.quantity + ')';
                og.appendChild(opt);
            });
            sel.appendChild(og);
        });
    }

    function populateWeaponSelect(sel) {
        sel.innerHTML = '';
        weaponList.forEach(function (w) {
            sel.insertAdjacentHTML('beforeend', '<option value="' + w.id + '">' + esc(w.name) + '</option>');
        });
    }

    // ===== BUILD WEAPON CARDS =====
    var grid = $('weaponsGrid');
    weaponList.forEach(function (w) {
        grid.insertAdjacentHTML('beforeend',
            '<div class="weapon-card" data-weapon="' + esc(w.slug) + '">' +
            '<div class="weapon-name">' + esc(w.name) + '</div>' +
            '<div class="weapon-craft-time">⏱ ' + (w.craft_time_seconds ? w.craft_time_seconds + 's' : '?') + '</div>' +
            '<div class="weapon-qty-row">' +
            '<button class="qty-btn minus" data-weapon="' + esc(w.slug) + '">−</button>' +
            '<input type="number" class="qty-input" id="qty-' + esc(w.slug) + '" value="0" min="0" max="99">' +
            '<button class="qty-btn plus" data-weapon="' + esc(w.slug) + '">+</button>' +
            '</div></div>'
        );
    });

    // ===== TAB SWITCHING =====
    document.querySelectorAll('.tab-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.tab-btn').forEach(function (b) { b.classList.remove('active'); });
            document.querySelectorAll('.tab-content').forEach(function (c) { c.classList.remove('active'); });
            btn.classList.add('active');
            $('tab-' + btn.getAttribute('data-tab')).classList.add('active');
        });
    });
    document.querySelectorAll('.sub-tab').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.sub-tab').forEach(function (b) { b.classList.remove('active'); });
            document.querySelectorAll('.sub-content').forEach(function (c) { c.classList.remove('active'); });
            btn.classList.add('active');
            $('sub-' + btn.getAttribute('data-subtab')).classList.add('active');
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
        if (!hasAny) { resultsSection.style.display = 'none'; return; }
        resultsSection.style.display = '';

        var html = '', totals = {};
        pieceKeys.forEach(function (k) { totals[k] = 0; });

        Object.keys(weapons).forEach(function (key) {
            var qty = orders[key]; if (!qty) return;
            var w = weapons[key];
            html += makeSectionHeader(w.name + ' × ' + qty);
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

        // Stock comparison (logged in only)
        if (isLoggedIn && cachedData && cachedData.stock) {
            $('simStockCompare').style.display = '';
            var stockMap = {};
            cachedData.stock.forEach(function (s) { stockMap[s.slug] = s.quantity; });
            html = '';
            pieceKeys.forEach(function (p) {
                if (!totals[p]) return;
                if (p === 'plans') {
                    Object.keys(weapons).forEach(function (slug) {
                        if (!orders[slug]) return;
                        var planNeed = (weapons[slug].pieces.plans || 0) * orders[slug];
                        var planHave = stockMap['plan_' + slug] || 0;
                        var diff = planNeed - planHave;
                        html += makeRow('Plan ' + weapons[slug].name + ' — ' + fmt(planNeed) + ' / stock ' + fmt(planHave),
                            diff <= 0 ? '✓ OK' : '▲ ' + fmt(diff), diff <= 0 ? 'ok' : 'need');
                    });
                } else {
                    var have = stockMap[p] || 0;
                    var diff = totals[p] - have;
                    html += makeRow(pieceNames[p] + ' — ' + fmt(totals[p]) + ' / stock ' + fmt(have),
                        diff <= 0 ? '✓ OK' : '▲ ' + fmt(diff), diff <= 0 ? 'ok' : 'need');
                }
            });
            $('simStockTable').innerHTML = html;
        } else {
            $('simStockCompare').style.display = 'none';
        }

        // Material craft chain
        var metalForR = totals.ressort * RESSORT_METAL_RATE;
        var mineraiForR = totals.ressort * RESSORT_MINERAI_RATE;
        var totalMetal = totals.metal + metalForR;
        var totalMineraiMetal = totalMetal * METAL_MINERAI_RATE;
        var totalMinerai = totalMineraiMetal + mineraiForR;
        var totalPetrole = totals.polymere * POLYMERE_PETROLE_RATE;

        html = '';
        html += makeSectionHeader('Craft Ressorts (' + fmt(totals.ressort) + ')');
        html += makeRow('Pièces métal (pour ressorts)', fmt(metalForR));
        html += makeRow('Minerais (pour ressorts)', fmt(mineraiForR));
        html += makeSectionHeader('Craft Pièces métal (' + fmt(totalMetal) + ')');
        html += makeRow('Directes', fmt(totals.metal));
        html += makeRow('Pour ressorts', fmt(metalForR));
        html += makeRow('Minerais nécessaires', fmt(totalMineraiMetal));
        html += makeSectionHeader('Craft Polymères (' + fmt(totals.polymere) + ')');
        html += makeRow('Pétroles nécessaires', fmt(totalPetrole));
        $('materialCraft').innerHTML = html;

        html = '';
        html += makeRow('Minerais de fer', fmt(totalMinerai), 'highlight');
        html += makeRow('Pétroles', fmt(totalPetrole), 'highlight');
        html += makeRow('Plans (utilisations)', fmt(totals.plans));
        html += makeRow('Canons', fmt(totals.canon));
        html += makeRow('Poignées', fmt(totals.poignee));
        html += makeRow('Corps de pistolet', fmt(totals.corp));
        $('rawMaterials').innerHTML = html;

        var cost = totals.polymere * POLYMERE_COST;
        $('costTable').innerHTML = makeRow('Polymères (' + fmt(totals.polymere) + ' × ' + fmt(POLYMERE_COST) + '€)', fmt(cost) + ' €', 'highlight');

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

    function ammoBenClass(v) {
        if (v > 0) return 'ammo-ben-pos';
        if (v < 0) return 'ammo-ben-neg';
        return 'ammo-ben-zero';
    }

    function ammoRecipeByName(name) {
        for (var i = 0; i < AMMO_RECIPES.length; i++) {
            if (AMMO_RECIPES[i].name === name) return AMMO_RECIPES[i];
        }
        return null;
    }

    function ammoCostPerMun(r, prixFer) {
        var pf = Math.max(0, prixFer);
        var achatPoudre = r.poudre * AMMO_GUNPOWDER_PRICE;
        var ferUnits = r.fragment / AMMO_FRAGMENTS_PER_FER_UNIT;
        return (achatPoudre + ferUnits * pf) / AMMO_YIELD_PER_CRAFT;
    }

    /** Coût poudre seule par munition (€), sans fer. */
    function ammoCostPoudrePerMun(r) {
        return (r.poudre * AMMO_GUNPOWDER_PRICE) / AMMO_YIELD_PER_CRAFT;
    }

    /** Montants de vente plus lisibles pour certains calibres. */
    var AMMO_SELL_PRETTY_EUR = { '5.56x45': 350, '7.62x39': 500, '12 Gauge': 400 };

    function ammoSellPriceForRecipe(r, prixFer) {
        if (!r) return 0;
        if (AMMO_SELL_PRETTY_EUR[r.name] != null) return AMMO_SELL_PRETTY_EUR[r.name];
        var sansFer = ammoCostPoudrePerMun(r);
        var c = ammoCostPerMun(r, prixFer);
        var sell = sansFer <= AMMO_SELL_POWDER_THRESHOLD_EUR ? sansFer * AMMO_SELL_MARKUP_SMALL : c * AMMO_SELL_MARKUP_LARGE;
        return Math.round(sell / 10) * 10;
    }

    function updateAmmoTargetSim() {
        var sel = $('ammoTargetSlug');
        var munsIn = $('ammoTargetMuns');
        var sellOv = $('ammoTargetSellPriceMun');
        var out = $('ammoTargetResults');
        var priceIn = $('ammoFerPrice');
        if (!sel || !munsIn || !out || !priceIn) return;
        var r = ammoRecipeByName(sel.value);
        if (!r) {
            out.innerHTML = '<div class="result-row"><span class="label">—</span><span class="value">Choisissez un calibre</span></div>';
            return;
        }
        var Mraw = parseInt(munsIn.value, 10);
        var M = Math.max(1, Math.min(9999999, isNaN(Mraw) ? 1000 : Mraw));
        if (munsIn.value === '' || isNaN(Mraw) || Mraw < 1) munsIn.value = M;
        var crafts = Math.ceil(M / AMMO_YIELD_PER_CRAFT);
        var produced = crafts * AMMO_YIELD_PER_CRAFT;
        var prixFer = Math.max(0, parseFloat(priceIn.value) || 0);
        var achatPoudre = r.poudre * AMMO_GUNPOWDER_PRICE;
        var ferUnits = r.fragment / AMMO_FRAGMENTS_PER_FER_UNIT;
        var coutMetalFerAchete = ferUnits * prixFer;
        var revientFerAcheteCraft = achatPoudre + coutMetalFerAchete;
        var revientFerRecolteCraft = achatPoudre;
        var costAch = revientFerAcheteCraft * crafts;
        var costRec = revientFerRecolteCraft * crafts;
        var refSell = ammoSellPriceForRecipe(r, prixFer);
        var sellParsedAmmo = sellOv ? parseEuroOptionalInput(sellOv.value) : { ok: true, empty: true, value: 0 };
        var useOverride = !!(sellOv && sellParsedAmmo.ok && !sellParsedAmmo.empty);
        var prixVenteMun = useOverride ? sellParsedAmmo.value : refSell;
        var venteTotale = prixVenteMun * produced;
        var margeAch = venteTotale - costAch;
        var margeRec = venteTotale - costRec;
        var coutMunAch = revientFerAcheteCraft / AMMO_YIELD_PER_CRAFT;
        var coutMunRec = revientFerRecolteCraft / AMMO_YIELD_PER_CRAFT;
        var margeMunAch = prixVenteMun - coutMunAch;
        var margeMunRec = prixVenteMun - coutMunRec;
        var timeTotal = crafts * (r.craftSec || 0);
        var sellNote = useOverride ? '(scénario)' : '(tableau)';
        var html = '';
        html += makeRow('Calibre', esc(r.name));
        html += makeRow('Munitions visées', fmt(M));
        if (produced !== M) {
            html += makeRow('Munitions produites (lots de 10)', fmt(produced), 'highlight');
        }
        html += makeRow('Crafts nécessaires', fmt(crafts));
        html += makeRow('Temps de craft total', formatTime(timeTotal));
        html += makeSectionHeader('Par munition');
        html += makeRow('Coût mat. / mun (fer acheté)', fmtEuro(coutMunAch));
        html += makeRow('Coût mat. / mun (fer récolté)', fmtEuro(coutMunRec));
        html += makeRow('Prix vente / mun ' + sellNote, fmtEuro(prixVenteMun));
        html += makeRow('Marge / mun (fer acheté)', fmtEuro(margeMunAch), ammoBenClass(margeMunAch));
        html += makeRow('Marge / mun (fer récolté)', fmtEuro(margeMunRec), ammoBenClass(margeMunRec));
        html += makeSectionHeader('Sur la production (' + fmt(produced) + ' mun.)');
        html += makeRow('Coût total (fer acheté)', fmtEuro(costAch), 'highlight');
        html += makeRow('Coût total (fer récolté)', fmtEuro(costRec), 'highlight');
        html += makeRow('Chiffre d’affaires (lot vendu)', fmtEuro(venteTotale), 'highlight');
        html += makeRow('Marge totale (fer acheté)', fmtEuro(margeAch), ammoBenClass(margeAch));
        html += makeRow('Marge totale (fer récolté)', fmtEuro(margeRec), ammoBenClass(margeRec));
        out.innerHTML = html;
    }

    function updateAmmoCraft() {
        var tbody = $('ammoCraftBody');
        var priceIn = $('ammoFerPrice');
        if (!tbody || !priceIn) return;
        var prixFer = Math.max(0, parseFloat(priceIn.value) || 0);
        var html = '';
        AMMO_RECIPES.forEach(function (r) {
            var achatPoudre = r.poudre * AMMO_GUNPOWDER_PRICE;
            var ferUnits = r.fragment / AMMO_FRAGMENTS_PER_FER_UNIT;
            var coutMetalFerAchete = ferUnits * prixFer;
            var revientFerAcheteCraft = achatPoudre + coutMetalFerAchete;
            var revientFerRecolteCraft = achatPoudre;
            var coutMunAch = revientFerAcheteCraft / AMMO_YIELD_PER_CRAFT;
            var coutMunRec = revientFerRecolteCraft / AMMO_YIELD_PER_CRAFT;
            var prixVenteParMun = ammoSellPriceForRecipe(r, prixFer);
            var margeMunAch = prixVenteParMun - coutMunAch;
            var margeMunRec = prixVenteParMun - coutMunRec;
            html += '<tr>';
            html += '<td>' + esc(r.name) + '</td>';
            html += '<td>' + r.craftSec + ' s</td>';
            html += '<td>' + fmt(r.poudre) + '</td>';
            html += '<td>' + fmt(r.fragment) + '</td>';
            html += '<td>' + fmtEuro(coutMunAch) + '</td>';
            html += '<td>' + fmtEuro(coutMunRec) + '</td>';
            html += '<td>' + fmtEuro(prixVenteParMun) + '</td>';
            html += '<td class="' + ammoBenClass(margeMunAch) + '">' + fmtEuro(margeMunAch) + '</td>';
            html += '<td class="' + ammoBenClass(margeMunRec) + '">' + fmtEuro(margeMunRec) + '</td>';
            html += '</tr>';
        });
        tbody.innerHTML = html;
    }

    function refreshAmmoSimulators() {
        updateAmmoCraft();
        updateAmmoTargetSim();
    }

    function weaponCraftPurchasedPiecesEuro(w) {
        var p = w.pieces;
        var n = (p.ressort || 0) + (p.canon || 0) + (p.poignee || 0) + (p.metal || 0);
        return n * WEAPON_CRAFT_WEAPON_PIECE_EUR;
    }

    /**
     * Coût matière « tout acheté » pour 1 craft arme (€).
     * @param {object} w entrée weapons[slug]
     * @param {number} planPriceEu prix / utilisation de plan (≥ 0)
     */
    function weaponCraftCostBreakdownOne(w, planPriceEu) {
        var pp = Math.max(0, planPriceEu);
        var p = w.pieces;
        var costPlans = (p.plans || 0) * pp;
        var costCorp = (p.corp || 0) * WEAPON_CRAFT_CORP_EUR;
        var costPieces = weaponCraftPurchasedPiecesEuro(w);
        var costPoly = (p.polymere || 0) * POLYMERE_COST;
        return {
            costPlans: costPlans,
            costCorp: costCorp,
            costPieces: costPieces,
            costPoly: costPoly,
            total: costPlans + costCorp + costPieces + costPoly
        };
    }

    /** Coût monétaire si tout est récolté / craft maison : ici seules les utilisations de plan restent payantes. */
    function weaponCraftCostGatheredOne(w, planPriceEu) {
        var b = weaponCraftCostBreakdownOne(w, planPriceEu);
        return b.costPlans;
    }

    function weaponStockPaidUnits(need, stockAvail) {
        var n = Math.max(0, Math.floor(Number(need)) || 0);
        var s = Math.max(0, Math.floor(Number(stockAvail)) || 0);
        return Math.max(0, n - Math.min(s, n));
    }

    /** Coût total € pour Q armes craftées, composants achetés hors stock. */
    function weaponCraftCostBuyOrderTotal(w, planPriceEu, Q, st) {
        var p = w.pieces;
        var pp = Math.max(0, planPriceEu);
        var u = weaponStockPaidUnits;
        return u(Q * (p.plans || 0), st.plans) * pp
            + u(Q * (p.corp || 0), st.corp) * WEAPON_CRAFT_CORP_EUR
            + u(Q * (p.ressort || 0), st.ressort) * WEAPON_CRAFT_WEAPON_PIECE_EUR
            + u(Q * (p.canon || 0), st.canon) * WEAPON_CRAFT_WEAPON_PIECE_EUR
            + u(Q * (p.poignee || 0), st.poignee) * WEAPON_CRAFT_WEAPON_PIECE_EUR
            + u(Q * (p.metal || 0), st.metal) * WEAPON_CRAFT_WEAPON_PIECE_EUR
            + u(Q * (p.polymere || 0), st.polymere) * POLYMERE_COST;
    }

    /** Scénario récolté : seul le plan est en € ; déduction des utilisations de plan en stock. */
    function weaponCraftCostGatheredOrderTotal(w, planPriceEu, Q, st) {
        var p = w.pieces;
        var pp = Math.max(0, planPriceEu);
        return weaponStockPaidUnits(Q * (p.plans || 0), st.plans) * pp;
    }

    function weaponStockReadFromForm() {
        function iv(id) {
            var el = $(id);
            if (!el) return 0;
            var v = parseInt(String(el.value).trim(), 10);
            return Math.max(0, isNaN(v) ? 0 : Math.min(v, 999999));
        }
        return {
            plans: iv('weaponStockPlans'),
            corp: iv('weaponStockCorp'),
            ressort: iv('weaponStockRessort'),
            canon: iv('weaponStockCanon'),
            poignee: iv('weaponStockPoignee'),
            metal: iv('weaponStockMetal'),
            polymere: iv('weaponStockPolymere'),
            sns: iv('weaponStockSns')
        };
    }

    /** Prix vente optionnel : nombre positif uniquement, sinon invalide. */
    function parseEuroOptionalInput(raw) {
        var t = String(raw == null ? '' : raw).trim().replace(/\s/g, '');
        if (t === '') return { ok: true, empty: true, value: 0 };
        if (!/^\d+([.,]\d+)?$/.test(t)) return { ok: false, empty: false, value: 0 };
        var v = parseFloat(t.replace(',', '.'));
        if (!isFinite(v) || v < 0) return { ok: false, empty: false, value: 0 };
        return { ok: true, empty: false, value: v };
    }

    function weaponCraftTimeLabel(craftTime) {
        if (craftTime === null || craftTime === undefined) return '?';
        return craftTime + ' s';
    }

    function updateWeaponCraftTable() {
        var tbody = $('weaponCraftBody');
        var planIn = $('weaponCraftPlanPrice');
        if (!tbody || !planIn) return;
        var raw = String(planIn.value).trim();
        var planEu = raw === '' ? 0 : Math.max(0, parseFloat(raw) || 0);
        var html = '';
        weaponList.forEach(function (w) {
            var wd = weapons[w.slug];
            if (!wd) return;
            var bought = wd.isBoughtWeapon;
            var b = weaponCraftCostBreakdownOne(wd, planEu);
            var g = weaponCraftCostGatheredOne(wd, planEu);
            var sell = wd.sellPrice || 0;
            var refBuy = wd.referencePurchasePrice || 0;
            var margeBuy = (!bought && sell > 0) ? sell - b.total : null;
            var margeGathered = (!bought && sell > 0) ? sell - g : null;
            var margeRevente = (bought && sell > 0 && refBuy > 0) ? sell - refBuy : null;
            html += '<tr>';
            html += '<td>' + esc(w.name) + '</td>';
            html += '<td>' + esc(weaponCraftTimeLabel(wd.craftTime)) + '</td>';
            if (bought) {
                html += '<td>—</td><td>—</td><td>—</td><td>—</td>';
                html += '<td>—</td><td>—</td>';
                html += '<td>' + (refBuy > 0 ? fmtEuro(refBuy) : '—') + '</td>';
            } else {
                html += '<td>' + fmtEuro(b.costPlans) + '</td>';
                html += '<td>' + fmtEuro(b.costCorp) + '</td>';
                html += '<td>' + fmtEuro(b.costPieces) + '</td>';
                html += '<td>' + fmtEuro(b.costPoly) + '</td>';
                html += '<td>' + fmtEuro(b.total) + '</td>';
                html += '<td>' + fmtEuro(g) + '</td>';
                html += '<td>—</td>';
            }
            if (sell > 0) {
                html += '<td>' + fmtEuro(sell) + '</td>';
                if (bought) {
                    html += '<td class="' + ammoBenClass(margeRevente) + '">' + (margeRevente != null ? fmtEuro(margeRevente) : '—') + '</td>';
                    html += '<td>—</td>';
                } else {
                    html += '<td class="' + ammoBenClass(margeBuy) + '">' + (margeBuy != null ? fmtEuro(margeBuy) : '—') + '</td>';
                    html += '<td class="' + ammoBenClass(margeGathered) + '">' + (margeGathered != null ? fmtEuro(margeGathered) : '—') + '</td>';
                }
            } else {
                html += '<td>—</td><td>—</td><td>—</td>';
            }
            html += '</tr>';
        });
        tbody.innerHTML = html;
    }

    function updateWeaponTargetSim() {
        var sel = $('weaponTargetSlug');
        var qtyIn = $('weaponTargetQty');
        var sellOv = $('weaponTargetSellPrice');
        var out = $('weaponTargetResults');
        var planIn = $('weaponCraftPlanPrice');
        if (!sel || !qtyIn || !out || !planIn) return;
        var slug = sel.value;
        var wd = weapons[slug];
        if (!wd) {
            out.innerHTML = '<div class="result-row"><span class="label">—</span><span class="value">Choisissez une arme</span></div>';
            return;
        }
        var Qraw = parseInt(qtyIn.value, 10);
        var Q = Math.max(1, Math.min(9999, isNaN(Qraw) ? 1 : Qraw));
        if (qtyIn.value === '' || isNaN(Qraw) || Qraw < 1) qtyIn.value = Q;
        var rawPlan = String(planIn.value).trim();
        var planEu = rawPlan === '' ? 0 : Math.max(0, parseFloat(rawPlan) || 0);
        var b = weaponCraftCostBreakdownOne(wd, planEu);
        var gatheredOne = weaponCraftCostGatheredOne(wd, planEu);
        var st = weaponStockReadFromForm();
        var bought = wd.isBoughtWeapon;
        var costTotBuyNoStock = bought ? 0 : b.total * Q;
        var costTotGatheredNoStock = bought ? 0 : gatheredOne * Q;
        var costTotBuy = bought ? 0 : weaponCraftCostBuyOrderTotal(wd, planEu, Q, st);
        var costTotGathered = bought ? 0 : weaponCraftCostGatheredOrderTotal(wd, planEu, Q, st);
        var costOneBuy = bought ? 0 : (Q > 0 ? costTotBuy / Q : 0);
        var costOneGathered = bought ? 0 : (Q > 0 ? costTotGathered / Q : 0);
        var baseSell = wd.sellPrice || 0;
        var sellParsed = sellOv ? parseEuroOptionalInput(sellOv.value) : { ok: true, empty: true, value: 0 };
        var sellInvalid = !!(sellOv && String(sellOv.value).trim() !== '' && !sellParsed.ok);
        var useOverride = !!(sellOv && sellParsed.ok && !sellParsed.empty);
        var prixVente = useOverride ? sellParsed.value : baseSell;
        var sellNote = useOverride ? '(scénario)' : (baseSell > 0 ? '(base)' : '(non défini)');
        var venteTotale = prixVente > 0 ? prixVente * Q : 0;
        var refBuyOne = wd.referencePurchasePrice || 0;
        var snsToBuy = bought ? weaponStockPaidUnits(Q, st.sns) : 0;
        var coutAchatArmeTot = (bought && refBuyOne > 0) ? snsToBuy * refBuyOne : 0;
        var coutAchatUnitMoyen = (bought && Q > 0) ? coutAchatArmeTot / Q : 0;
        var margeTotBuy = (!bought && prixVente > 0) ? venteTotale - costTotBuy : null;
        var margeTotGathered = (!bought && prixVente > 0) ? venteTotale - costTotGathered : null;
        var margeTotRevente = (bought && prixVente > 0 && refBuyOne > 0) ? venteTotale - coutAchatArmeTot : null;
        var margeOneBuy = (!bought && prixVente > 0) ? prixVente - costOneBuy : null;
        var margeOneGathered = (!bought && prixVente > 0) ? prixVente - costOneGathered : null;
        var margeOneRevente = (bought && prixVente > 0 && refBuyOne > 0) ? prixVente - coutAchatUnitMoyen : null;
        var timeOne = wd.craftTime;
        var timeTot = (timeOne != null ? timeOne * Q : null);
        var ecoBuy = (!bought && costTotBuyNoStock > costTotBuy) ? costTotBuyNoStock - costTotBuy : 0;
        var ecoGathered = (!bought && costTotGatheredNoStock > costTotGathered) ? costTotGatheredNoStock - costTotGathered : 0;
        var ecoSnsAcq = (bought && refBuyOne > 0 && st.sns > 0) ? Math.min(st.sns, Q) * refBuyOne : 0;
        var stockUsedCraft = !bought && (st.plans + st.corp + st.ressort + st.canon + st.poignee + st.metal + st.polymere) > 0;
        var stockUsedSns = bought && st.sns > 0;
        var html = '';
        html += makeRow('Arme', esc(wd.name || slug));
        html += makeRow('Armes à fabriquer', fmt(Q));
        html += makeRow('Temps de craft total', bought ? '— (arme non craftée)' : (timeTot != null ? formatTime(timeTot) : 'Inconnu'));
        if (sellInvalid) {
            html += makeRow('Prix vente (champ optionnel)', 'Saisie non numérique — prix en base utilisé', '');
        }
        html += makeSectionHeader('Par arme');
        if (bought) {
            html += makeRow('Coût craft (achat comp.)', '— (non applicable)', '');
            html += makeRow('Coût craft (comp. récoltés)', '— (non applicable)', '');
            if (refBuyOne > 0) {
                html += makeRow('Prix achat réf. / unité neuve', fmtEuro(refBuyOne), 'highlight');
                html += makeRow('Coût acquisition (après stock)', fmtEuro(coutAchatUnitMoyen), 'highlight');
            }
        } else {
            html += makeRow('Coût mat. / arme (achat comp., hors stock)', fmtEuro(b.total), '');
            if (stockUsedCraft) {
                html += makeRow('Coût mat. / arme (achat comp., stock déduit)', fmtEuro(costOneBuy), 'highlight');
            } else {
                html += makeRow('Coût mat. / arme (composants achetés)', fmtEuro(costOneBuy), 'highlight');
            }
            html += makeRow('Coût mat. / arme (récolté, € plans)', fmtEuro(costOneGathered), 'highlight');
        }
        html += makeRow('Prix vente / arme ' + sellNote, prixVente > 0 ? fmtEuro(prixVente) : '—');
        if (prixVente > 0) {
            if (bought) {
                html += makeRow('Marge / arme (revente)', margeOneRevente != null ? fmtEuro(margeOneRevente) : '—', ammoBenClass(margeOneRevente));
            } else {
                html += makeRow('Marge / arme (achat comp.)', margeOneBuy != null ? fmtEuro(margeOneBuy) : '—', ammoBenClass(margeOneBuy));
                html += makeRow('Marge / arme (récolté)', margeOneGathered != null ? fmtEuro(margeOneGathered) : '—', ammoBenClass(margeOneGathered));
            }
        }
        if (stockUsedCraft || stockUsedSns) {
            html += makeSectionHeader('Effet du stock sur cette commande');
            if (stockUsedCraft) {
                if (ecoBuy > 0) html += makeRow('Économie (achat comp. vs sans stock)', fmtEuro(ecoBuy), 'ammo-ben-pos');
                if (ecoGathered > 0) html += makeRow('Économie (scénario récolté, plans)', fmtEuro(ecoGathered), 'ammo-ben-pos');
            }
            if (stockUsedSns && refBuyOne > 0) {
                html += makeRow('SNS couverts par le stock', fmt(Math.min(st.sns, Q)) + ' / ' + fmt(Q));
                if (ecoSnsAcq > 0) html += makeRow('Économie (acquisitions évitées)', fmtEuro(ecoSnsAcq), 'ammo-ben-pos');
            }
        }
        html += makeSectionHeader('Sur la commande (' + fmt(Q) + ' armes)');
        if (bought) {
            html += makeRow('Coût total acquisition (réf.)', coutAchatArmeTot > 0 ? fmtEuro(coutAchatArmeTot) : (refBuyOne > 0 ? fmtEuro(0) : '—'), 'highlight');
        } else {
            html += makeRow('Coût total (composants achetés)', fmtEuro(costTotBuy), 'highlight');
            html += makeRow('Coût total (composants récoltés)', fmtEuro(costTotGathered), 'highlight');
        }
        html += makeRow('Chiffre d’affaires', prixVente > 0 ? fmtEuro(venteTotale) : '—', prixVente > 0 ? 'highlight' : '');
        if (prixVente > 0) {
            if (bought) {
                html += makeRow('Marge totale (revente)', margeTotRevente != null ? fmtEuro(margeTotRevente) : '—', ammoBenClass(margeTotRevente));
            } else {
                html += makeRow('Marge totale (achat comp.)', margeTotBuy != null ? fmtEuro(margeTotBuy) : '—', ammoBenClass(margeTotBuy));
                html += makeRow('Marge totale (récolté)', margeTotGathered != null ? fmtEuro(margeTotGathered) : '—', ammoBenClass(margeTotGathered));
            }
        } else {
            html += makeRow('Marge totale', 'Définissez un prix de vente (base ou champ optionnel)', '');
        }
        out.innerHTML = html;
    }

    function refreshWeaponCraftSims() {
        updateWeaponCraftTable();
        updateWeaponTargetSim();
    }

    var ammoTargetSlugEl = $('ammoTargetSlug');
    if (ammoTargetSlugEl && ammoTargetSlugEl.options.length === 0) {
        AMMO_RECIPES.forEach(function (rec) {
            ammoTargetSlugEl.insertAdjacentHTML('beforeend', '<option value="' + esc(rec.name) + '">' + esc(rec.name) + '</option>');
        });
        var pref = '.45 ACP';
        for (var ai = 0; ai < AMMO_RECIPES.length; ai++) {
            if (AMMO_RECIPES[ai].name === pref) {
                ammoTargetSlugEl.value = pref;
                break;
            }
        }
    }

    var ammoFerEl = $('ammoFerPrice');
    if (ammoFerEl) {
        ammoFerEl.addEventListener('input', refreshAmmoSimulators);
        ammoFerEl.addEventListener('change', refreshAmmoSimulators);
    }
    var ammoTargetMunsEl = $('ammoTargetMuns');
    if (ammoTargetMunsEl) {
        ammoTargetMunsEl.addEventListener('input', updateAmmoTargetSim);
        ammoTargetMunsEl.addEventListener('change', updateAmmoTargetSim);
    }
    if (ammoTargetSlugEl) {
        ammoTargetSlugEl.addEventListener('change', updateAmmoTargetSim);
    }
    var ammoTargetSellEl = $('ammoTargetSellPriceMun');
    if (ammoTargetSellEl) {
        ammoTargetSellEl.addEventListener('input', updateAmmoTargetSim);
        ammoTargetSellEl.addEventListener('change', updateAmmoTargetSim);
    }
    refreshAmmoSimulators();

    var weaponCraftPlanEl = $('weaponCraftPlanPrice');
    if (weaponCraftPlanEl) {
        weaponCraftPlanEl.addEventListener('input', refreshWeaponCraftSims);
        weaponCraftPlanEl.addEventListener('change', refreshWeaponCraftSims);
    }
    var weaponTargetSlugEl = $('weaponTargetSlug');
    if (weaponTargetSlugEl && weaponTargetSlugEl.options.length === 0) {
        weaponList.forEach(function (w) {
            weaponTargetSlugEl.insertAdjacentHTML('beforeend', '<option value="' + esc(w.slug) + '">' + esc(w.name) + '</option>');
        });
    }
    var weaponTargetQtyEl = $('weaponTargetQty');
    if (weaponTargetQtyEl) {
        weaponTargetQtyEl.addEventListener('input', updateWeaponTargetSim);
        weaponTargetQtyEl.addEventListener('change', updateWeaponTargetSim);
    }
    if (weaponTargetSlugEl) {
        weaponTargetSlugEl.addEventListener('change', updateWeaponTargetSim);
    }
    var weaponTargetSellEl = $('weaponTargetSellPrice');
    if (weaponTargetSellEl) {
        weaponTargetSellEl.addEventListener('input', updateWeaponTargetSim);
        weaponTargetSellEl.addEventListener('change', updateWeaponTargetSim);
    }
    ['weaponStockPlans', 'weaponStockCorp', 'weaponStockRessort', 'weaponStockCanon', 'weaponStockPoignee', 'weaponStockMetal', 'weaponStockPolymere', 'weaponStockSns'].forEach(function (sid) {
        var el = $(sid);
        if (el) {
            el.addEventListener('input', updateWeaponTargetSim);
            el.addEventListener('change', updateWeaponTargetSim);
        }
    });
    refreshWeaponCraftSims();

    // ===== LOGIN =====
    var loginSel = $('loginMemberSelect');
    memberList.forEach(function (m) {
        loginSel.insertAdjacentHTML('beforeend', '<option value="' + m.id + '">' + esc(m.name) + '</option>');
    });

    $('btnLogin').addEventListener('click', doLogin);
    $('loginPin').addEventListener('keydown', function (e) { if (e.key === 'Enter') doLogin(); });

    function doLogin() {
        var uid = parseInt(loginSel.value, 10);
        var pin = $('loginPin').value;
        if (!uid) { $('errLogin').textContent = 'Sélectionnez votre nom'; $('errLogin').classList.add('visible'); return; }
        if (!pin) { $('errLogin').textContent = 'Entrez votre PIN'; $('errLogin').classList.add('visible'); return; }

        apiPost('/simulateur-armes/api/login', { user_id: uid, pin: pin }, function (err, data) {
            if (err || !data || data.error) {
                $('errLogin').textContent = (data && data.error) || 'Erreur de connexion';
                $('errLogin').classList.add('visible');
                return;
            }
            $('errLogin').classList.remove('visible');
            currentUserId = data.user.id;
            currentUserName = data.user.name;
            currentUserRole = data.user.role;
            sessionStorage.setItem('lmc_uid', '' + currentUserId);
            sessionStorage.setItem('lmc_name', currentUserName);
            sessionStorage.setItem('lmc_role', currentUserRole);
            isLoggedIn = true;
            showDashboard();
        });
    }

    function showDashboard() {
        $('lockMembres').style.display = 'none';
        $('memberDashboard').style.display = '';
        $('currentMemberName').textContent = '👤 ' + currentUserName;
        $('currentMemberRole').textContent = currentUserRole === 'officer' ? '⭐ Officier' : 'Membre';
        $('currentMemberRole').className = 'member-bar-role role-' + currentUserRole;
        $('subTabGestion').style.display = currentUserRole === 'officer' ? '' : 'none';
        loadDashboardData();
    }

    function loadDashboardData() {
        apiGet(function (err, data) {
            if (err || !data || data.error) { doLogout(); return; }
            cachedData = data;
            if (data.members) memberList = data.members;
            populateForms();
            renderDashboard(data);
        });
    }

    $('btnLogout').addEventListener('click', doLogout);

    function doLogout() {
        currentUserId = null;
        currentUserName = '';
        currentUserRole = '';
        isLoggedIn = false;
        cachedData = null;
        sessionStorage.removeItem('lmc_uid');
        sessionStorage.removeItem('lmc_name');
        sessionStorage.removeItem('lmc_role');
        $('lockMembres').style.display = '';
        $('memberDashboard').style.display = 'none';
        $('loginPin').value = '';
    }

    // Auto-login from session
    if (currentUserId) {
        isLoggedIn = true;
        showDashboard();
    }

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
        // Sale weapon select — with stock count
        var sw = $('saleWeapon');
        sw.innerHTML = '';
        weaponList.forEach(function (w) {
            var st = getWeaponStock(w.id);
            var qty = st ? st.quantity : 0;
            var label = w.name + ' [' + qty + ' en stock]';
            sw.insertAdjacentHTML('beforeend', '<option value="' + w.id + '" data-price="' + (w.sell_price || 0) + '">' + esc(label) + '</option>');
        });
        onSaleWeaponChange(); // auto-fill price for first weapon

        if (cachedData && cachedData.stock) {
            populateGroupedStockSelect($('mvStock'), cachedData.stock);
        }

        var mr = $('mvReason');
        mr.innerHTML = '';
        if (cachedData && cachedData.reasons) {
            Object.keys(cachedData.reasons).forEach(function (k) {
                mr.insertAdjacentHTML('beforeend', '<option value="' + k + '">' + esc(cachedData.reasons[k]) + '</option>');
            });
        }
        updateMvCostVisibility();

        document.querySelectorAll('.ct-weapon').forEach(function (sel) { populateWeaponSelect(sel); });
    }

    // Auto-fill sell price when weapon changes
    function onSaleWeaponChange() {
        var wid = parseInt($('saleWeapon').value, 10);
        var w = weaponById[wid];
        if (w && w.sell_price) {
            $('salePrice').value = w.sell_price;
        }
        updateSalePreview();
    }
    $('saleWeapon').addEventListener('change', onSaleWeaponChange);

    // ===== SALE =====
    function updateSalePreview() {
        var qty = parseInt($('saleQty').value, 10) || 0;
        var price = parseFloat($('salePrice').value) || 0;
        var total = qty * price;
        $('salePreview').innerHTML = total > 0 ? '<span class="preview-total">Total : <strong>' + fmt(total) + ' €</strong></span>' : '';
    }
    $('saleQty').addEventListener('input', updateSalePreview);
    $('salePrice').addEventListener('input', updateSalePreview);

    $('btnSale').addEventListener('click', function () {
        var btn = $('btnSale');
        var buyer = $('saleBuyer').value.trim();
        if (!buyer) { showToast('Indiquez le nom de l\'acheteur', 'error'); return; }
        btn.disabled = true; btn.textContent = '⏳ ...';
        apiPost('/simulateur-armes/api/sale', {
            weapon_id: parseInt($('saleWeapon').value, 10),
            quantity: parseInt($('saleQty').value, 10) || 1,
            unit_price: parseFloat($('salePrice').value) || 0,
            buyer_name: buyer,
            notes: $('saleNotes').value
        }, function (err, data) {
            btn.disabled = false; btn.textContent = '💰 Enregistrer la vente';
            if (err || (data && data.error)) { showToast((data && data.error) || 'Erreur', 'error'); return; }
            if (data.warning) showToast(data.warning, 'error');
            else showToast(data.message, 'success');
            $('saleBuyer').value = ''; $('saleNotes').value = ''; $('saleQty').value = 1; $('salePrice').value = 0; $('salePreview').innerHTML = '';
            refreshData();
        });
    });

    // ===== DIRECTION TOGGLE =====
    $('mvDirIn').addEventListener('click', function () { $('mvDirIn').classList.add('active'); $('mvDirOut').classList.remove('active'); });
    $('mvDirOut').addEventListener('click', function () { $('mvDirOut').classList.add('active'); $('mvDirIn').classList.remove('active'); });

    function updateMvCostVisibility() {
        var isPurchase = $('mvReason').value === 'purchase';
        $('mvCostRow').style.display = isPurchase ? '' : 'none';
        updateMvCostPreview();
    }
    function updateMvCostPreview() {
        var qty = parseInt($('mvQty').value, 10) || 0;
        var cost = parseFloat($('mvUnitCost').value) || 0;
        var total = qty * cost;
        $('mvCostPreview').innerHTML = total > 0 ? '<span class="preview-total">Coût total : <strong>' + fmt(total) + ' €</strong></span>' : '';
    }
    $('mvReason').addEventListener('change', updateMvCostVisibility);
    $('mvQty').addEventListener('input', updateMvCostPreview);
    $('mvUnitCost').addEventListener('input', updateMvCostPreview);

    // ===== MOVEMENT =====
    $('btnMovement').addEventListener('click', function () {
        var btn = $('btnMovement');
        var qty = parseInt($('mvQty').value, 10) || 0;
        if (qty <= 0) { showToast('Quantité invalide', 'error'); return; }
        var isOut = $('mvDirOut').classList.contains('active');
        btn.disabled = true; btn.textContent = '⏳ ...';
        apiPost('/simulateur-armes/api/movement', {
            weapon_stock_id: parseInt($('mvStock').value, 10),
            quantity_change: isOut ? -qty : qty,
            reason: $('mvReason').value,
            unit_cost: $('mvReason').value === 'purchase' ? (parseFloat($('mvUnitCost').value) || 0) : null,
            notes: $('mvNotes').value
        }, function (err, data) {
            btn.disabled = false; btn.textContent = '📦 Enregistrer le mouvement';
            if (err || (data && data.error)) { showToast((data && data.error) || 'Erreur', 'error'); return; }
            showToast(data.message, 'success');
            $('mvNotes').value = ''; $('mvQty').value = 1; $('mvUnitCost').value = 0; $('mvCostPreview').innerHTML = '';
            refreshData();
        });
    });

    // ===== CONTRACT CREATE =====
    $('btnAddCtItem').addEventListener('click', function () {
        var row = document.createElement('div');
        row.className = 'form-row ct-item-row';
        row.innerHTML = '<div class="form-group"><select class="fm-input ct-weapon"></select></div>' +
            '<div class="form-group sm"><input type="number" class="fm-input ct-qty" value="1" min="1" max="999"></div>' +
            '<div class="form-group xs"><button type="button" class="action-btn-sm rm-btn">✕</button></div>';
        $('ctItemsContainer').appendChild(row);
        populateWeaponSelect(row.querySelector('.ct-weapon'));
        row.querySelector('.rm-btn').addEventListener('click', function () { row.remove(); });
    });

    $('btnCreateContract').addEventListener('click', function () {
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
        btn.disabled = true; btn.textContent = '⏳ ...';
        apiPost('/simulateur-armes/api/contract', { name: name, client_name: client, notes: $('ctNotes').value, items: items }, function (err, data) {
            btn.disabled = false; btn.textContent = '📝 Créer le contrat';
            if (err || (data && data.error)) { showToast((data && data.error) || 'Erreur', 'error'); return; }
            showToast(data.message, 'success');
            $('ctName').value = ''; $('ctClient').value = ''; $('ctNotes').value = '';
            var container = $('ctItemsContainer');
            container.innerHTML = '<div class="form-row ct-item-row"><div class="form-group"><select class="fm-input ct-weapon"></select></div><div class="form-group sm"><input type="number" class="fm-input ct-qty" value="1" min="1" max="999"></div></div>';
            populateWeaponSelect(container.querySelector('.ct-weapon'));
            refreshData();
        });
    });

    // ===== MEMBER MANAGEMENT (officers) =====
    $('btnCreateMember').addEventListener('click', function () {
        var btn = $('btnCreateMember');
        var name = $('newMemberName').value.trim();
        var pin = $('newMemberPin').value.trim();
        if (!name || !pin) { showToast('Nom et PIN requis', 'error'); return; }
        btn.disabled = true; btn.textContent = '⏳ ...';
        apiPost('/simulateur-armes/api/member', { name: name, pin: pin, role: $('newMemberRole').value }, function (err, data) {
            btn.disabled = false; btn.textContent = '👤 Créer le membre';
            if (err || (data && data.error)) { showToast((data && data.error) || 'Erreur', 'error'); return; }
            showToast(data.message, 'success');
            $('newMemberName').value = ''; $('newMemberPin').value = '';
            if (data.member) {
                loginSel.insertAdjacentHTML('beforeend', '<option value="' + data.member.id + '">' + esc(data.member.name) + '</option>');
            }
            refreshData();
        });
    });

    $('btnChangePin').addEventListener('click', function () {
        var btn = $('btnChangePin');
        var cur = $('pinCurrent').value;
        var nw = $('pinNew').value;
        if (!cur || !nw) { showToast('Remplissez les deux champs', 'error'); return; }
        btn.disabled = true; btn.textContent = '⏳ ...';
        apiPost('/simulateur-armes/api/change-pin', { current_pin: cur, new_pin: nw }, function (err, data) {
            btn.disabled = false; btn.textContent = '🔑 Modifier';
            if (err || (data && data.error)) { showToast((data && data.error) || 'Erreur', 'error'); return; }
            showToast(data.message, 'success');
            $('pinCurrent').value = ''; $('pinNew').value = '';
        });
    });

    // ===== REFRESH =====
    function refreshData() {
        apiGet(function (err, data) {
            if (err || !data || data.error) return;
            cachedData = data;
            if (data.members) memberList = data.members;
            renderDashboard(data);
            populateForms();
            calculate();
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
            if (s.category === 'finished_weapon') totalWeapons += s.quantity;
            if (s.category === 'piece' || s.category === 'plan') totalPieces += s.quantity;
        });
        var revenue = (data.finance && data.finance.total_revenue) || 0;
        var contracts = (data.contracts || []).length;
        $('statsRow').innerHTML =
            '<div class="stat-card"><div class="stat-val">' + fmt(totalWeapons) + '</div><div class="stat-label">Armes</div></div>' +
            '<div class="stat-card"><div class="stat-val">' + fmt(totalPieces) + '</div><div class="stat-label">Pièces</div></div>' +
            '<div class="stat-card revenue"><div class="stat-val">' + fmt(revenue) + ' €</div><div class="stat-label">Revenus</div></div>' +
            '<div class="stat-card"><div class="stat-val">' + contracts + '</div><div class="stat-label">Contrats</div></div>';
    }

    function renderStockCards(stock) {
        var cats = { finished_weapon: [], plan: [], piece: [], raw_material: [] };
        stock.forEach(function (s) { if (cats[s.category]) cats[s.category].push(s); });
        var html = '';
        cats.finished_weapon.forEach(function (s) {
            // Find matching weapon for quick-sell
            var wid = s.weapon_id || 0;
            html += '<div class="stock-card ' + (s.quantity > 0 ? 'has-stock' : 'no-stock') + '" data-quicksell="' + wid + '" title="Cliquer pour vendre">';
            html += '<div class="stock-card-qty">' + s.quantity + '</div>';
            html += '<div class="stock-card-name">' + esc(s.name) + '</div>';
            if (s.quantity > 0 && wid) html += '<div class="stock-card-action">💰 Vendre</div>';
            html += '</div>';
        });
        $('stockWeaponsCards').innerHTML = html || '<div class="empty-msg">Aucune arme</div>';

        html = '';
        cats.plan.forEach(function (s) {
            var phys = Math.floor(s.quantity / PLANS_PER_ITEM);
            html += '<div class="stock-mini"><span class="sm-name">' + esc(s.name) + '</span><span class="sm-val">' + phys + ' plans (' + s.quantity + ' uses)</span></div>';
        });
        cats.piece.forEach(function (s) {
            html += '<div class="stock-mini' + (s.quantity <= 0 ? ' sm-low' : '') + '"><span class="sm-name">' + esc(s.name) + '</span><span class="sm-val">' + fmt(s.quantity) + '</span></div>';
        });
        $('stockPiecesGrid').innerHTML = html || '<div class="empty-msg">—</div>';

        html = '';
        cats.raw_material.forEach(function (s) {
            html += '<div class="stock-mini"><span class="sm-name">' + esc(s.name) + '</span><span class="sm-val highlight">' + fmt(s.quantity) + '</span></div>';
        });
        $('stockRawGrid').innerHTML = html || '<div class="empty-msg">—</div>';
    }

    function renderAlerts(alerts) {
        if (!alerts || !alerts.length) { $('alertBanner').style.display = 'none'; return; }
        var html = '⚠️ Stock bas : ';
        alerts.forEach(function (a, i) {
            if (i > 0) html += ', ';
            html += '<strong>' + esc(a.name) + '</strong> (' + a.quantity + ')';
        });
        $('alertBanner').innerHTML = html;
        $('alertBanner').style.display = '';
    }

    // Quick-sell from stock cards
    document.addEventListener('click', function (e) {
        var card = e.target.closest('.stock-card[data-quicksell]');
        if (!card) return;
        var wid = parseInt(card.getAttribute('data-quicksell'), 10);
        if (!wid) return;
        // Switch to Actions sub-tab and pre-fill sale form
        document.querySelectorAll('.sub-tab').forEach(function (b) { b.classList.remove('active'); });
        document.querySelectorAll('.sub-content').forEach(function (c) { c.classList.remove('active'); });
        document.querySelector('.sub-tab[data-subtab="actions"]').classList.add('active');
        $('sub-actions').classList.add('active');
        $('saleWeapon').value = wid;
        onSaleWeaponChange();
        $('saleQty').value = 1;
        $('saleBuyer').focus();
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
        var isOfficer = currentUserRole === 'officer';
        var html = '';
        contracts.forEach(function (c) {
            html += '<div class="contract-card">';
            html += '<div class="contract-card-header"><span class="contract-card-name">' + esc(c.name) + '</span><span class="contract-status-badge status-' + c.status + '">' + esc(c.status_label) + '</span></div>';
            html += '<div class="contract-card-meta">Client: ' + esc(c.client) + ' — Par: ' + esc(c.created_by) + '</div>';
            html += '<div class="progress-bar-wrap"><div class="progress-bar-fill" style="width:' + Math.min(100, c.progress) + '%"></div></div>';
            c.items.forEach(function (item) {
                var done = item.remaining === 0;
                html += '<div class="ct-item-line">';
                html += '<span class="ct-item-weapon">' + esc(item.weapon) + '</span>';
                // Delivery: inline +/- buttons (active contracts only)
                if (showActions && !done) {
                    html += '<span class="ct-item-delivery">';
                    html += '<button class="ct-del-btn" data-iid="' + item.id + '" data-delta="-1" title="-1 livré">−</button>';
                    html += '<span class="ct-del-count">' + item.qty_delivered + '</span>';
                    html += '<button class="ct-del-btn" data-iid="' + item.id + '" data-delta="1" title="+1 livré">+</button>';
                    html += '<span class="ct-del-of"> / </span>';
                    // qty_ordered: editable by officers
                    if (isOfficer) {
                        html += '<input type="number" class="ct-ord-input" data-iid="' + item.id + '" value="' + item.qty_ordered + '" min="1" max="999" title="Modifier la commande">';
                    } else {
                        html += '<span class="ct-del-total">' + item.qty_ordered + '</span>';
                    }
                    html += '</span>';
                } else {
                    html += '<span class="ct-item-delivery"><span class="value ' + (done ? 'ok' : 'need') + '">' + item.qty_delivered + ' / ' + item.qty_ordered + (done ? ' ✓' : '') + '</span></span>';
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

    // Delivery +/- buttons
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
        apiPut('/simulateur-armes/api/contract-item/' + iid, { qty_delivered: newVal }, function (err, data) {
            btn.disabled = false;
            if (err || (data && data.error)) { showToast((data && data.error) || 'Erreur', 'error'); return; }
            refreshData();
        });
    });

    // qty_ordered inline edit (officers)
    document.addEventListener('change', function (e) {
        if (!e.target.classList.contains('ct-ord-input')) return;
        var iid = e.target.getAttribute('data-iid');
        var newQty = parseInt(e.target.value, 10);
        if (!newQty || newQty < 1) return;
        apiPut('/simulateur-armes/api/contract-item/' + iid, { qty_ordered: newQty }, function (err, data) {
            if (err || (data && data.error)) { showToast((data && data.error) || 'Erreur', 'error'); return; }
            showToast('Quantité mise à jour', 'success');
            refreshData();
        });
    });

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.ct-status-btn');
        if (!btn) return;
        var cid = btn.getAttribute('data-cid');
        var sel = document.querySelector('.ct-status-sel[data-cid="' + cid + '"]');
        if (!sel) return;
        btn.disabled = true; btn.textContent = '⏳';
        apiPut('/simulateur-armes/api/contract/' + cid, { status: sel.value }, function (err, data) {
            btn.disabled = false; btn.textContent = 'Mettre à jour';
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
            html += makeRow(wName + ' — besoin ' + fmt(needed) + ', stock ' + fmt(have),
                diff <= 0 ? '✓ En stock' : '⚙ ' + fmt(diff) + ' à fabriquer', diff <= 0 ? 'ok' : 'need');
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
                html += makeRow(pieceNames[p] + ' — ' + fmt(planNeed) + ' utilis.', diff <= 0 ? '✓ OK (' + fmt(planHave) + ')' : '▲ ' + fmt(diff), diff <= 0 ? 'ok' : 'need');
            } else {
                var have = stockMap[p] || 0, diff = totals[p] - have;
                html += makeRow(pieceNames[p] + ' — ' + fmt(totals[p]) + ' nécess.', diff <= 0 ? '✓ OK (' + fmt(have) + ')' : '▲ ' + fmt(diff), diff <= 0 ? 'ok' : 'need');
            }
        });
        $('contractPiecesNeeded').innerHTML = html;

        var metalForR = totals.ressort * RESSORT_METAL_RATE, mineraiForR = totals.ressort * RESSORT_MINERAI_RATE;
        var totalMetal = totals.metal + metalForR, totalMineraiMetal = totalMetal * METAL_MINERAI_RATE;
        var totalMinerai = totalMineraiMetal + mineraiForR, totalPetrole = totals.polymere * POLYMERE_PETROLE_RATE;

        html = '';
        html += makeSectionHeader('Craft Ressorts (' + fmt(totals.ressort) + ')');
        html += makeRow('Pièces métal', fmt(metalForR)); html += makeRow('Minerais', fmt(mineraiForR));
        html += makeSectionHeader('Craft Pièces métal (' + fmt(totalMetal) + ')');
        html += makeRow('Directes', fmt(totals.metal)); html += makeRow('Pour ressorts', fmt(metalForR)); html += makeRow('Minerais', fmt(totalMineraiMetal));
        html += makeSectionHeader('Craft Polymères (' + fmt(totals.polymere) + ')');
        html += makeRow('Pétroles', fmt(totalPetrole));
        $('contractMaterialCraft').innerHTML = html;

        var mineraiHave = stockMap['minerai'] || 0, petroleHave = stockMap['petrole'] || 0;
        html = '';
        var md = totalMinerai - mineraiHave;
        html += makeRow('Minerais — ' + fmt(totalMinerai), md <= 0 ? '✓ OK (' + fmt(mineraiHave) + ')' : '▲ ' + fmt(md) + ' (' + fmt(mineraiHave) + ')', md <= 0 ? 'ok' : 'need highlight');
        var pd = totalPetrole - petroleHave;
        html += makeRow('Pétroles — ' + fmt(totalPetrole), pd <= 0 ? '✓ OK (' + fmt(petroleHave) + ')' : '▲ ' + fmt(pd) + ' (' + fmt(petroleHave) + ')', pd <= 0 ? 'ok' : 'need highlight');
        $('contractRawMaterials').innerHTML = html;

        $('contractCostTable').innerHTML = makeRow('Polymères (' + fmt(totals.polymere) + ' × ' + fmt(POLYMERE_COST) + '€)', fmt(totals.polymere * POLYMERE_COST) + ' €', 'highlight');
        var ts = formatTime(totalCraftTime); if (hasUnknown) ts += ' + ?';
        $('contractCraftTime').textContent = ts;
    }

    // ===== HISTORY =====
    function renderHistory(data) {
        var movements = data.movements || [], sales = data.sales || [];
        var html = '';
        movements.forEach(function (m) {
            var sign = m.quantity_change > 0 ? '+' : '', cls = m.quantity_change > 0 ? 'mv-in' : 'mv-out';
            html += '<div class="movement-row ' + cls + '">';
            html += '<span class="mv-date">' + esc(m.date) + '</span>';
            html += '<span class="mv-stock">' + esc(m.stock_name) + '</span>';
            html += '<span class="mv-qty">' + sign + m.quantity_change + '</span>';
            html += '<span class="mv-reason">' + esc(m.reason_label) + '</span>';
            html += '<span class="mv-user">' + esc(m.user) + '</span>';
            if (m.notes) html += '<span class="mv-notes">' + esc(m.notes) + '</span>';
            if (m.unit_cost) html += '<span class="mv-notes">💰 ' + fmt(m.unit_cost) + ' €/u (total: ' + fmt(m.unit_cost * Math.abs(m.quantity_change)) + ' €)</span>';
            html += '</div>';
        });
        $('movementsList').innerHTML = html || '<div class="empty-msg">Aucun mouvement</div>';

        html = '';
        sales.forEach(function (s) {
            html += '<div class="movement-row mv-sale">';
            html += '<span class="mv-date">' + esc(s.date) + '</span>';
            html += '<span class="mv-stock">' + esc(s.weapon) + ' ×' + s.quantity + '</span>';
            html += '<span class="mv-qty">' + fmt(s.total) + '€</span>';
            html += '<span class="mv-reason">' + esc(s.buyer) + '</span>';
            html += '<span class="mv-user">' + esc(s.user) + '</span>';
            if (s.notes) html += '<span class="mv-notes">' + esc(s.notes) + '</span>';
            html += '</div>';
        });
        $('salesList').innerHTML = html || '<div class="empty-msg">Aucune vente</div>';
    }

    // ===== MEMBERS LIST =====
    function renderMembers(members) {
        if (currentUserRole !== 'officer') return;
        var html = '';
        members.forEach(function (m) {
            var badge = m.role === 'officer' ? '<span class="member-badge officer">Officier</span>' : '<span class="member-badge">Membre</span>';
            html += '<div class="member-list-row"><span class="ml-name">' + esc(m.name) + '</span>' + badge;
            html += '<button class="action-btn-sm ml-toggle-role" data-mid="' + m.id + '" data-role="' + m.role + '">' + (m.role === 'officer' ? '↓ Membre' : '↑ Officier') + '</button></div>';
        });
        $('membersList').innerHTML = html || '<div class="empty-msg">Aucun membre</div>';
    }

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.ml-toggle-role');
        if (!btn) return;
        var mid = btn.getAttribute('data-mid');
        var newRole = btn.getAttribute('data-role') === 'officer' ? 'member' : 'officer';
        btn.disabled = true; btn.textContent = '⏳';
        apiPut('/simulateur-armes/api/member/' + mid, { role: newRole }, function (err, data) {
            btn.disabled = false;
            if (err || (data && data.error)) { showToast((data && data.error) || 'Erreur', 'error'); return; }
            showToast(data.message, 'success');
            refreshData();
        });
    });
})();
