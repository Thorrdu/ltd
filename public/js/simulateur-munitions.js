(function () {
    'use strict';

    var AMMO_GUNPOWDER_PRICE = 100;
    var AMMO_YIELD_PER_CRAFT = 10;
    var DB_SELL_PRICES = window.AMMO_SELL_PRICES || {};
    var DB_COMP_PRICES = window.AMMO_COMP_PRICES || {};

    // Depuis la refonte : chaque craft produit 10 munitions a partir de
    // poudre + 10 corps + 10 tetes (composants braques ou rachetes, pas de craft).
    var AMMO_RECIPES = [
        { name: '9mm',            slug: 'ammo_9mm',     craftSec: 5, poudre: 5,  comp: 10, corpsSlug: 'ammo_corps_9mm',     teteSlug: 'ammo_tete_9mm' },
        { name: '.22 Long Rifle', slug: 'ammo_22lr',    craftSec: 5, poudre: 5,  comp: 10, corpsSlug: 'ammo_corps_22lr',    teteSlug: 'ammo_tete_22lr' },
        { name: '.45 ACP',        slug: 'ammo_45acp',   craftSec: 5, poudre: 5,  comp: 10, corpsSlug: 'ammo_corps_45acp',   teteSlug: 'ammo_tete_45acp' },
        { name: '.50 AE',         slug: 'ammo_50ae',    craftSec: 5, poudre: 10, comp: 10, corpsSlug: 'ammo_corps_50ae',    teteSlug: 'ammo_tete_50ae' },
        { name: '.38 LC',         slug: 'ammo_38lc',    craftSec: 5, poudre: 5,  comp: 10, corpsSlug: 'ammo_corps_38lc',    teteSlug: 'ammo_tete_38lc' },
        { name: '.44 Magnum',     slug: 'ammo_44mag',   craftSec: 5, poudre: 10, comp: 10, corpsSlug: 'ammo_corps_44mag',   teteSlug: 'ammo_tete_44mag' },
        { name: '5.56x45',        slug: 'ammo_556x45',  craftSec: 5, poudre: 20, comp: 10, corpsSlug: 'ammo_corps_556x45',  teteSlug: 'ammo_tete_556x45' },
        { name: '7.62x39',        slug: 'ammo_762x39',  craftSec: 5, poudre: 20, comp: 10, corpsSlug: 'ammo_corps_762x39',  teteSlug: 'ammo_tete_762x39' },
        { name: '7.62x51',        slug: 'ammo_762x51',  craftSec: 5, poudre: 20, comp: 10, corpsSlug: 'ammo_corps_762x51',  teteSlug: 'ammo_tete_762x51' },
        { name: '.50 BMG',        slug: 'ammo_50bmg',   craftSec: 5, poudre: 20, comp: 10, corpsSlug: 'ammo_corps_50bmg',   teteSlug: 'ammo_tete_50bmg' },
        { name: '12 Gauge',       slug: 'ammo_12gauge', craftSec: 5, poudre: 30, comp: 10, corpsSlug: 'ammo_corps_12gauge', teteSlug: 'ammo_tete_12gauge' }
    ];

    var auth = window.McAuth;
    var cachedStockMap = null;

    function $(id) { return document.getElementById(id); }
    function esc(s) { if (s == null) return ''; var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
    function fmt(n) { return Number(n).toLocaleString('fr-FR'); }
    function fmtEuro(n) {
        return Number(n).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' \u20ac';
    }
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

    function poudrePrice() {
        var el = $('ammoPoudrePrice');
        var v = el ? parseFloat(el.value) : AMMO_GUNPOWDER_PRICE;
        return isFinite(v) && v >= 0 ? v : AMMO_GUNPOWDER_PRICE;
    }
    function compUnitPrice(slug) {
        if (slug && DB_COMP_PRICES[slug] != null) return Number(DB_COMP_PRICES[slug]) || 0;
        return 0;
    }
    // Cout des composants (corps + tete) pour 1 craft de la recette.
    function compCostPerCraft(r) {
        return r.comp * (compUnitPrice(r.corpsSlug) + compUnitPrice(r.teteSlug));
    }

    function ammoStockAmmoEnabled() { var el = $('ammoUseAmmoStock'); return el ? el.checked : false; }
    function ammoStockMatEnabled() { var el = $('ammoUseMatStock'); return el ? el.checked : false; }

    function ammoStockReadAmmo() {
        var vals = {};
        AMMO_RECIPES.forEach(function (r) {
            var safe = r.name.replace(/[^a-zA-Z0-9]/g, '_');
            var el = $('ammoStock-' + safe);
            var v = el ? parseInt(String(el.value).trim(), 10) : 0;
            vals[r.name] = Math.max(0, isNaN(v) ? 0 : v);
        });
        return vals;
    }

    function ammoStockReadMat() {
        function iv(id) { var el = $(id); if (!el) return 0; var v = parseInt(String(el.value).trim(), 10); return Math.max(0, isNaN(v) ? 0 : v); }
        return { poudre: iv('ammoStockPoudre') };
    }

    function loadStockData() {
        if (!auth || !auth.isLoggedIn) return;
        auth.apiGet('/stocks/api/list', function (err, data) {
            if (err || !data || !data.catalog) return;
            cachedStockMap = {};
            data.catalog.forEach(function (item) { cachedStockMap[item.slug] = item.quantity; });
            fillStockFields();
        });
    }

    function fillStockFields() {
        if (!cachedStockMap) return;
        AMMO_RECIPES.forEach(function (r) {
            var safe = r.name.replace(/[^a-zA-Z0-9]/g, '_');
            var el = $('ammoStock-' + safe);
            if (el && r.slug && cachedStockMap[r.slug] != null) el.value = cachedStockMap[r.slug];
        });
        var poudreEl = $('ammoStockPoudre');
        if (poudreEl && cachedStockMap['poudre'] != null) poudreEl.value = cachedStockMap['poudre'];
        refreshAll();
    }

    function ammoRecipeByName(name) {
        for (var i = 0; i < AMMO_RECIPES.length; i++) { if (AMMO_RECIPES[i].name === name) return AMMO_RECIPES[i]; }
        return null;
    }
    function ammoSellPriceForRecipe(r) {
        if (r && r.slug && DB_SELL_PRICES[r.slug] != null) return Number(DB_SELL_PRICES[r.slug]) || 0;
        return 0;
    }

    function parseEuroOptionalInput(raw) {
        var t = String(raw == null ? '' : raw).trim().replace(/\s/g, '');
        if (t === '') return { ok: true, empty: true, value: 0 };
        if (!/^\d+([.,]\d+)?$/.test(t)) return { ok: false, empty: false, value: 0 };
        var v = parseFloat(t.replace(',', '.'));
        if (!isFinite(v) || v < 0) return { ok: false, empty: false, value: 0 };
        return { ok: true, empty: false, value: v };
    }

    // ===== CRAFT TABLE =====
    function updateAmmoCraft() {
        var tbody = $('ammoCraftBody');
        if (!tbody) return;
        var prixPoudre = poudrePrice(), html = '';
        AMMO_RECIPES.forEach(function (r) {
            var coutPoudre = r.poudre * prixPoudre;
            var coutComp = compCostPerCraft(r);
            // Composants achetes (rachat) vs braques (gratuits).
            var revAch = coutPoudre + coutComp, revBraq = coutPoudre;
            var cMunAch = revAch / AMMO_YIELD_PER_CRAFT, cMunBraq = revBraq / AMMO_YIELD_PER_CRAFT;
            var pv = ammoSellPriceForRecipe(r), mAch = pv - cMunAch, mBraq = pv - cMunBraq;
            html += '<tr><td>' + esc(r.name) + '</td><td>' + r.craftSec + ' s</td><td>' + fmt(r.poudre) + '</td><td>' + fmt(r.comp) + ' + ' + fmt(r.comp) + '</td>';
            html += '<td>' + fmtEuro(cMunAch) + '</td><td>' + fmtEuro(cMunBraq) + '</td><td>' + fmtEuro(pv) + '</td>';
            html += '<td class="' + ammoBenClass(mAch) + '">' + fmtEuro(mAch) + '</td><td class="' + ammoBenClass(mBraq) + '">' + fmtEuro(mBraq) + '</td></tr>';
        });
        tbody.innerHTML = html;
    }

    function refreshAll() { updateAmmoCraft(); updateAmmoMulti(); }

    // ===== MULTI-CALIBRE GRID =====
    var multiGrid = $('ammoMultiGrid');
    if (multiGrid) {
        AMMO_RECIPES.forEach(function (r) {
            var safe = r.name.replace(/[^a-zA-Z0-9]/g, '_');
            multiGrid.insertAdjacentHTML('beforeend',
                '<div class="weapon-card ammo-multi-card" data-ammo="' + esc(r.name) + '">' +
                '<div class="weapon-name">' + esc(r.name) + '</div>' +
                '<div class="weapon-craft-time">' + r.craftSec + 's &middot; ' + r.poudre + 'p &middot; ' + r.comp + ' corps + ' + r.comp + ' t\u00eate</div>' +
                '<div class="weapon-qty-row">' +
                '<button class="qty-btn minus" data-ammo="' + esc(r.name) + '">\u2212</button>' +
                '<input type="number" class="qty-input ammo-multi-qty" id="ammoMulti-' + safe + '" data-ammo="' + esc(r.name) + '" value="0" min="0" max="9999999" step="10">' +
                '<button class="qty-btn plus" data-ammo="' + esc(r.name) + '">+</button>' +
                '</div></div>'
            );
        });
        multiGrid.addEventListener('click', function (e) {
            var btn = e.target.closest('.qty-btn');
            if (!btn) return;
            var ammoName = btn.getAttribute('data-ammo');
            var safe = ammoName.replace(/[^a-zA-Z0-9]/g, '_');
            var input = $('ammoMulti-' + safe);
            if (!input) return;
            var val = parseInt(input.value, 10) || 0;
            input.value = btn.classList.contains('plus') ? Math.min(val + 100, 9999999) : Math.max(val - 100, 0);
            updateAmmoMulti();
        });
        multiGrid.addEventListener('input', function (e) { if (e.target.classList.contains('ammo-multi-qty')) updateAmmoMulti(); });
    }

    // ===== BUILD AMMO STOCK INPUTS =====
    var ammoStockGrid = $('ammoAmmoStockGrid');
    if (ammoStockGrid) {
        AMMO_RECIPES.forEach(function (r) {
            var safe = r.name.replace(/[^a-zA-Z0-9]/g, '_');
            ammoStockGrid.insertAdjacentHTML('beforeend',
                '<label class="ammo-sim-label" for="ammoStock-' + safe + '">' + esc(r.name) + '</label>' +
                '<input type="number" class="ammo-sim-input ammo-sim-input-sm weapon-stock-in" id="ammoStock-' + safe + '" min="0" max="999999" step="1" value="0" inputmode="numeric" autocomplete="off">'
            );
        });
    }

    function updateAmmoMulti() {
        var out = $('ammoMultiResults'), section = $('ammoMultiSection');
        if (!out) return;
        var prixPoudre = poudrePrice();
        var useAS = ammoStockAmmoEnabled(), ammoSt = useAS ? ammoStockReadAmmo() : {};
        var orders = [], hasAny = false;
        AMMO_RECIPES.forEach(function (r) {
            var safe = r.name.replace(/[^a-zA-Z0-9]/g, '_');
            var input = $('ammoMulti-' + safe);
            var qty = input ? Math.max(0, parseInt(input.value, 10) || 0) : 0;
            orders.push({ recipe: r, munitions: qty });
            if (qty > 0) hasAny = true;
            var card = input ? input.closest('.weapon-card') : null;
            if (card) card.classList.toggle('active', qty > 0);
        });
        if (!hasAny) { if (section) section.style.display = 'none'; return; }
        if (section) section.style.display = '';

        var gP = 0, gCorps = 0, gTete = 0, gCr = 0, gProd = 0, gCA = 0, gCB = 0, gV = 0, gT = 0, gOrd = 0;
        var gCompCost = 0; // cout total composants (achetes) a crafter maintenant
        var gFullCA = 0, gFullCB = 0; // cout reel de TOUTES les mun (y compris stock)
        var html = '';
        orders.forEach(function (o) {
            if (o.munitions <= 0) return;
            var r = o.recipe, ord = o.munitions;
            var inSt = useAS ? (ammoSt[r.name] || 0) : 0;
            var tc = Math.max(0, ord - inSt);
            var cr = Math.ceil(tc / AMMO_YIELD_PER_CRAFT), pr = cr * AMMO_YIELD_PER_CRAFT;
            var coutPoudreCraft = r.poudre * prixPoudre, coutCompCraft = compCostPerCraft(r);
            var cA = (coutPoudreCraft + coutCompCraft) * cr, cB = coutPoudreCraft * cr;
            var pv = ammoSellPriceForRecipe(r), ve = pv * ord;
            var po = r.poudre * cr, corps = r.comp * cr, tete = r.comp * cr, ti = cr * (r.craftSec || 0);
            // cout reel de toutes les mun commandees (stock incl.)
            var crFull = Math.ceil(ord / AMMO_YIELD_PER_CRAFT);
            var fullCA = (coutPoudreCraft + coutCompCraft) * crFull, fullCB = coutPoudreCraft * crFull;
            gP += po; gCorps += corps; gTete += tete; gCr += cr; gProd += pr; gCA += cA; gCB += cB; gV += ve; gT += ti; gOrd += ord;
            gCompCost += coutCompCraft * cr;
            gFullCA += fullCA; gFullCB += fullCB;

            var hdr = esc(r.name) + ' \u2014 ' + fmt(ord) + ' mun.';
            if (useAS && inSt > 0) hdr += ' (stock: ' + fmt(inSt) + ', craft: ' + fmt(tc) + ')';
            if (tc > 0) hdr += ' (' + fmt(cr) + ' crafts \u2192 ' + fmt(pr) + ')';
            html += makeSectionHeader(hdr);
            if (tc > 0) {
                html += makeRow('Poudre', fmt(po));
                html += makeRow('Corps + T\u00eates', fmt(corps) + ' + ' + fmt(tete));
                html += makeRow('Co\u00fbt craft (comp. rachet\u00e9s)', fmtEuro(cA));
            } else {
                html += makeRow('Couvert par le stock', '\u2713', 'ok');
            }
            html += makeRow('Co\u00fbt r\u00e9el total (' + fmt(ord) + ' mun.)', fmtEuro(fullCA));
            html += makeRow('Vente', fmtEuro(ve));
            html += makeRow('Marge r\u00e9elle (comp. rachet\u00e9s)', fmtEuro(ve - fullCA), ammoBenClass(ve - fullCA));
        });

        html += makeSectionHeader('TOTAL COMMANDE');
        html += makeRow('Munitions command\u00e9es', fmt(gOrd), 'highlight');
        if (gProd < gOrd) html += makeRow('Munitions \u00e0 crafter', fmt(gProd), 'highlight');
        html += makeRow('Crafts totaux', fmt(gCr));
        html += makeRow('Temps de craft', formatTime(gT));

        if (gCr > 0) {
            html += makeSectionHeader('Mat\u00e9riaux n\u00e9cessaires');
            html += makeRow('Poudre \u00e0 canon', fmt(gP) + ' unit\u00e9s', 'highlight');
            html += makeRow('   Co\u00fbt poudre', fmtEuro(gP * prixPoudre));
            html += makeRow('Corps de munition', fmt(gCorps) + ' unit\u00e9s', 'highlight');
            html += makeRow('T\u00eates de munition', fmt(gTete) + ' unit\u00e9s', 'highlight');
            html += makeRow('   Co\u00fbt composants (rachet\u00e9s)', fmtEuro(gCompCost));
        }

        html += makeSectionHeader('Bilan financier');
        html += makeRow('Co\u00fbt r\u00e9el total (' + fmt(gOrd) + ' mun., comp. rachet\u00e9s)', fmtEuro(gFullCA), 'highlight');
        html += makeRow('Co\u00fbt r\u00e9el total (comp. braqu\u00e9s)', fmtEuro(gFullCB), 'highlight');
        if (gCA < gFullCA) html += makeRow('dont \u00e0 crafter maintenant (comp. rachet\u00e9s)', fmtEuro(gCA));
        html += makeRow('Chiffre d\u2019affaires', fmtEuro(gV), 'highlight');
        html += makeRow('Marge r\u00e9elle (comp. rachet\u00e9s)', fmtEuro(gV - gFullCA), ammoBenClass(gV - gFullCA));
        html += makeRow('Marge r\u00e9elle (comp. braqu\u00e9s)', fmtEuro(gV - gFullCB), ammoBenClass(gV - gFullCB));

        if (ammoStockMatEnabled() && gCr > 0) {
            var st = ammoStockReadMat();
            var nP = Math.max(0, gP - st.poudre);
            var usedP = Math.min(st.poudre, gP);
            var ncP = nP * prixPoudre;
            html += makeSectionHeader('Stock mat\u00e9riaux disponible');
            html += makeRow('Poudre en stock', fmt(st.poudre));
            html += makeSectionHeader('D\u00e9duction du stock');
            html += makeRow('Poudre n\u00e9cessaire', fmt(gP));
            html += makeRow('Poudre couverte par stock', usedP > 0 ? '\u2212' + fmt(usedP) : '0', usedP > 0 ? 'ammo-ben-pos' : '');
            html += makeRow('\u27a1 Poudre \u00e0 acheter', fmt(nP), nP > 0 ? 'need' : 'ok');
            html += makeSectionHeader('Investissement restant');
            if (nP > 0) html += makeRow('Achat poudre (' + fmt(nP) + ' u.)', fmtEuro(ncP));
            html += makeRow('\u00c0 investir poudre (apr\u00e8s stock)', fmtEuro(ncP), 'highlight');
        }
        out.innerHTML = html;
    }

    // ===== EVENT LISTENERS =====
    var ammoPoudreEl = $('ammoPoudrePrice');
    if (ammoPoudreEl) { ammoPoudreEl.addEventListener('input', refreshAll); ammoPoudreEl.addEventListener('change', refreshAll); }
    var ammoUseAmmoStockEl = $('ammoUseAmmoStock');
    if (ammoUseAmmoStockEl) { ammoUseAmmoStockEl.addEventListener('change', function () { var f = $('ammoAmmoStockFields'); if (f) f.style.display = ammoUseAmmoStockEl.checked ? '' : 'none'; refreshAll(); }); }
    var ammoUseMatStockEl = $('ammoUseMatStock');
    if (ammoUseMatStockEl) { ammoUseMatStockEl.addEventListener('change', function () { var f = $('ammoMatStockFields'); if (f) f.style.display = ammoUseMatStockEl.checked ? '' : 'none'; refreshAll(); }); }
    AMMO_RECIPES.forEach(function (r) { var safe = r.name.replace(/[^a-zA-Z0-9]/g, '_'); var el = $('ammoStock-' + safe); if (el) { el.addEventListener('input', refreshAll); el.addEventListener('change', refreshAll); } });
    var ammoStockPoudreEl = $('ammoStockPoudre');
    if (ammoStockPoudreEl) { ammoStockPoudreEl.addEventListener('input', refreshAll); ammoStockPoudreEl.addEventListener('change', refreshAll); }

    // ===== AUTH =====
    function updateAmmoGate() {
        var notLogged = $('ammoSimNotLogged');
        var content = $('ammoSimContent');
        var loggedIn = !!(auth && auth.isLoggedIn);
        if (notLogged) notLogged.style.display = loggedIn ? 'none' : '';
        if (content) content.style.display = loggedIn ? '' : 'none';
    }
    if (auth) {
        auth.onLogin(function () { updateAmmoGate(); loadStockData(); });
        auth.onLogout(function () { updateAmmoGate(); });
        if (auth.isLoggedIn) loadStockData();
    }
    updateAmmoGate();
    refreshAll();
})();
