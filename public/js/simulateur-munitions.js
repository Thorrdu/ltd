(function () {
    'use strict';

    var AMMO_GUNPOWDER_PRICE = 100;
    var AMMO_YIELD_PER_CRAFT = 10;
    var AMMO_FRAGMENTS_PER_FER_UNIT = 2;
    // Prix de vente fixes par munition (source : stock_items.default_sell_price en DB).
    // Regle commune : prix vente = 2 x prix sans fer = 2 x (poudre_recette x 100 / 10) = poudre_recette x 20.
    var AMMO_RECIPES = [
        { name: '9mm',      craftSec: 5,  poudre: 5,  fragment: 10, sell: 100 },
        { name: '.38 LC',   craftSec: 10, poudre: 15, fragment: 10, sell: 300 },
        { name: '.45 ACP',  craftSec: 5,  poudre: 5,  fragment: 10, sell: 100 },
        { name: '.50 AE',   craftSec: 5,  poudre: 10, fragment: 10, sell: 200 },
        { name: '5.56x45',  craftSec: 10, poudre: 20, fragment: 25, sell: 400 },
        { name: '7.62x39',  craftSec: 10, poudre: 20, fragment: 25, sell: 400 },
        { name: '12 Gauge', craftSec: 10, poudre: 30, fragment: 20, sell: 600 },
        { name: '7.62x51',  craftSec: 10, poudre: 20, fragment: 30, sell: 400 },
        { name: '.50 BMG',  craftSec: 10, poudre: 20, fragment: 35, sell: 400 }
    ];

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

    function ammoStockEnabled() {
        var el = $('ammoUseStock');
        return el ? el.checked : false;
    }

    function ammoStockRead() {
        function iv(id) {
            var el = $(id);
            if (!el) return 0;
            var v = parseInt(String(el.value).trim(), 10);
            return Math.max(0, isNaN(v) ? 0 : v);
        }
        return {
            poudre: iv('ammoStockPoudre'),
            fragments: iv('ammoStockFragments'),
            minerais: iv('ammoStockMinerais')
        };
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

    function ammoSellPriceForRecipe(r /*, prixFer */) {
        if (!r) return 0;
        return r.sell != null ? r.sell : 0;
    }

    function parseEuroOptionalInput(raw) {
        var t = String(raw == null ? '' : raw).trim().replace(/\s/g, '');
        if (t === '') return { ok: true, empty: true, value: 0 };
        if (!/^\d+([.,]\d+)?$/.test(t)) return { ok: false, empty: false, value: 0 };
        var v = parseFloat(t.replace(',', '.'));
        if (!isFinite(v) || v < 0) return { ok: false, empty: false, value: 0 };
        return { ok: true, empty: false, value: v };
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

    function updateAmmoTargetSim() {
        var sel = $('ammoTargetSlug');
        var munsIn = $('ammoTargetMuns');
        var sellOv = $('ammoTargetSellPriceMun');
        var out = $('ammoTargetResults');
        var priceIn = $('ammoFerPrice');
        if (!sel || !munsIn || !out || !priceIn) return;
        var r = ammoRecipeByName(sel.value);
        if (!r) {
            out.innerHTML = '<div class="result-row"><span class="label">\u2014</span><span class="value">Choisissez un calibre</span></div>';
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
        var sellNote = useOverride ? '(sc\u00e9nario)' : '(tableau)';
        var html = '';
        html += makeRow('Calibre', esc(r.name));
        html += makeRow('Munitions vis\u00e9es', fmt(M));
        if (produced !== M) {
            html += makeRow('Munitions produites (lots de 10)', fmt(produced), 'highlight');
        }
        html += makeRow('Crafts n\u00e9cessaires', fmt(crafts));
        html += makeRow('Temps de craft total', formatTime(timeTotal));
        html += makeSectionHeader('Par munition');
        html += makeRow('Co\u00fbt / mun (fer achet\u00e9)', fmtEuro(coutMunAch));
        html += makeRow('Co\u00fbt / mun (fer r\u00e9colt\u00e9)', fmtEuro(coutMunRec));
        html += makeRow('Prix vente / mun ' + sellNote, fmtEuro(prixVenteMun));
        html += makeRow('Marge / mun (fer achet\u00e9)', fmtEuro(margeMunAch), ammoBenClass(margeMunAch));
        html += makeRow('Marge / mun (fer r\u00e9colt\u00e9)', fmtEuro(margeMunRec), ammoBenClass(margeMunRec));
        html += makeSectionHeader('Sur la production (' + fmt(produced) + ' mun.)');
        html += makeRow('Co\u00fbt total (fer achet\u00e9)', fmtEuro(costAch), 'highlight');
        html += makeRow('Co\u00fbt total (fer r\u00e9colt\u00e9)', fmtEuro(costRec), 'highlight');
        html += makeRow('Chiffre d\u2019affaires (lot vendu)', fmtEuro(venteTotale), 'highlight');
        html += makeRow('Marge totale (fer achet\u00e9)', fmtEuro(margeAch), ammoBenClass(margeAch));
        html += makeRow('Marge totale (fer r\u00e9colt\u00e9)', fmtEuro(margeRec), ammoBenClass(margeRec));

        // Material requirements for the full production
        var totalPoudre = r.poudre * crafts;
        var totalFragments = r.fragment * crafts;
        var totalMinerai = Math.ceil(totalFragments / AMMO_FRAGMENTS_PER_FER_UNIT);
        var coutPoudreTot = totalPoudre * AMMO_GUNPOWDER_PRICE;
        var coutMineraiTot = totalMinerai * prixFer;

        html += makeSectionHeader('Mat\u00e9riaux n\u00e9cessaires (' + fmt(crafts) + ' crafts)');
        html += makeRow('Poudre \u00e0 canon', fmt(totalPoudre) + ' unit\u00e9s', 'highlight');
        html += makeRow('   Co\u00fbt poudre (' + fmt(totalPoudre) + ' \u00d7 ' + fmt(AMMO_GUNPOWDER_PRICE) + '\u20ac)', fmtEuro(coutPoudreTot));
        html += makeRow('Fragments de m\u00e9tal', fmt(totalFragments) + ' fragments', 'highlight');
        html += makeRow('Minerais de fer n\u00e9cessaires', fmt(totalMinerai) + ' minerais (1 minerai = ' + AMMO_FRAGMENTS_PER_FER_UNIT + ' fragments)', 'highlight');
        if (prixFer > 0) {
            html += makeRow('   Co\u00fbt minerai fer (' + fmt(totalMinerai) + ' \u00d7 ' + fmt(prixFer) + '\u20ac)', fmtEuro(coutMineraiTot));
        }

        if (ammoStockEnabled()) {
            var st = ammoStockRead();
            // Convert stock minerais to fragments equivalent
            var stockFragsFromMinerais = st.minerais * AMMO_FRAGMENTS_PER_FER_UNIT;
            var totalStockFrags = st.fragments + stockFragsFromMinerais;

            var netPoudre = Math.max(0, totalPoudre - st.poudre);
            var netFragments = Math.max(0, totalFragments - totalStockFrags);
            var netMinerai = Math.ceil(netFragments / AMMO_FRAGMENTS_PER_FER_UNIT);
            var netCoutPoudre = netPoudre * AMMO_GUNPOWDER_PRICE;
            var netCoutMinerai = netMinerai * prixFer;
            var netCostAch = netCoutPoudre + netCoutMinerai;
            var netCostRec = netCoutPoudre;
            var netMargeAch = venteTotale - netCostAch;
            var netMargeRec = venteTotale - netCostRec;

            html += makeSectionHeader('Apr\u00e8s d\u00e9duction du stock');
            html += makeRow('Poudre en stock', fmt(st.poudre) + ' unit\u00e9s');
            html += makeRow('Fragments en stock', fmt(st.fragments) + ' fragments' + (st.minerais > 0 ? ' + ' + fmt(st.minerais) + ' minerais (\u2192 ' + fmt(stockFragsFromMinerais) + ' frags)' : ''));
            html += makeRow('Poudre restante \u00e0 acqu\u00e9rir', fmt(netPoudre) + ' unit\u00e9s', netPoudre > 0 ? 'need' : 'ok');
            html += makeRow('Fragments restants \u00e0 acqu\u00e9rir', fmt(netFragments) + ' fragments', netFragments > 0 ? 'need' : 'ok');
            html += makeRow('Minerais restants \u00e0 acqu\u00e9rir', fmt(netMinerai) + ' minerais', netMinerai > 0 ? 'need' : 'ok');
            html += makeRow('Co\u00fbt net (fer achet\u00e9)', fmtEuro(netCostAch), 'highlight');
            html += makeRow('Co\u00fbt net (fer r\u00e9colt\u00e9)', fmtEuro(netCostRec), 'highlight');
            html += makeRow('Marge nette (fer achet\u00e9)', fmtEuro(netMargeAch), ammoBenClass(netMargeAch));
            html += makeRow('Marge nette (fer r\u00e9colt\u00e9)', fmtEuro(netMargeRec), ammoBenClass(netMargeRec));
            var ecoAch = costAch - netCostAch;
            var ecoRec = costRec - netCostRec;
            if (ecoAch > 0) html += makeRow('\u00c9conomie stock (fer ach.)', fmtEuro(ecoAch), 'ammo-ben-pos');
            if (ecoRec > 0) html += makeRow('\u00c9conomie stock (fer r\u00e9c.)', fmtEuro(ecoRec), 'ammo-ben-pos');
        }

        out.innerHTML = html;
    }

    function refreshAll() {
        updateAmmoCraft();
        updateAmmoTargetSim();
        updateAmmoMulti();
    }

    // ===== MULTI-CALIBRE GRID =====
    var multiGrid = $('ammoMultiGrid');
    if (multiGrid) {
        AMMO_RECIPES.forEach(function (r) {
            var safe = r.name.replace(/[^a-zA-Z0-9]/g, '_');
            multiGrid.insertAdjacentHTML('beforeend',
                '<div class="weapon-card ammo-multi-card" data-ammo="' + esc(r.name) + '">' +
                '<div class="weapon-name">' + esc(r.name) + '</div>' +
                '<div class="weapon-craft-time">' + r.craftSec + 's &middot; ' + r.poudre + 'p &middot; ' + r.fragment + 'f</div>' +
                '<div class="weapon-qty-row">' +
                '<button class="qty-btn minus" data-ammo="' + esc(r.name) + '">−</button>' +
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
            var step = 100;
            input.value = btn.classList.contains('plus') ? Math.min(val + step, 9999999) : Math.max(val - step, 0);
            updateAmmoMulti();
        });
        multiGrid.addEventListener('input', function (e) {
            if (e.target.classList.contains('ammo-multi-qty')) updateAmmoMulti();
        });
    }

    function updateAmmoMulti() {
        var out = $('ammoMultiResults');
        var section = $('ammoMultiSection');
        if (!out) return;
        var priceIn = $('ammoFerPrice');
        var prixFer = priceIn ? Math.max(0, parseFloat(priceIn.value) || 0) : 30;

        // Gather orders
        var orders = [];
        var hasAny = false;
        AMMO_RECIPES.forEach(function (r) {
            var safe = r.name.replace(/[^a-zA-Z0-9]/g, '_');
            var input = $('ammoMulti-' + safe);
            var qty = input ? Math.max(0, parseInt(input.value, 10) || 0) : 0;
            orders.push({ recipe: r, munitions: qty });
            if (qty > 0) hasAny = true;
            // Toggle card active state
            var card = input ? input.closest('.weapon-card') : null;
            if (card) card.classList.toggle('active', qty > 0);
        });

        if (!hasAny) {
            if (section) section.style.display = 'none';
            return;
        }
        if (section) section.style.display = '';

        var grandTotalPoudre = 0, grandTotalFragments = 0, grandTotalCrafts = 0;
        var grandTotalProduced = 0, grandTotalCostAch = 0, grandTotalCostRec = 0;
        var grandTotalVente = 0, grandTotalTime = 0;

        var html = '';

        // Per-calibre breakdown
        orders.forEach(function (o) {
            if (o.munitions <= 0) return;
            var r = o.recipe;
            var crafts = Math.ceil(o.munitions / AMMO_YIELD_PER_CRAFT);
            var produced = crafts * AMMO_YIELD_PER_CRAFT;
            var achatPoudre = r.poudre * AMMO_GUNPOWDER_PRICE;
            var ferUnits = r.fragment / AMMO_FRAGMENTS_PER_FER_UNIT;
            var costAch = (achatPoudre + ferUnits * prixFer) * crafts;
            var costRec = achatPoudre * crafts;
            var prixVente = ammoSellPriceForRecipe(r);
            var vente = prixVente * produced;
            var poudre = r.poudre * crafts;
            var fragments = r.fragment * crafts;
            var time = crafts * (r.craftSec || 0);

            grandTotalPoudre += poudre;
            grandTotalFragments += fragments;
            grandTotalCrafts += crafts;
            grandTotalProduced += produced;
            grandTotalCostAch += costAch;
            grandTotalCostRec += costRec;
            grandTotalVente += vente;
            grandTotalTime += time;

            html += makeSectionHeader(esc(r.name) + ' \u2014 ' + fmt(o.munitions) + ' mun. (' + fmt(crafts) + ' crafts \u2192 ' + fmt(produced) + ')');
            html += makeRow('Poudre', fmt(poudre));
            html += makeRow('Fragments', fmt(fragments));
            html += makeRow('Co\u00fbt (fer ach.)', fmtEuro(costAch));
            html += makeRow('Vente', fmtEuro(vente));
            html += makeRow('Marge (fer ach.)', fmtEuro(vente - costAch), ammoBenClass(vente - costAch));
        });

        // Grand totals
        var grandTotalMinerai = Math.ceil(grandTotalFragments / AMMO_FRAGMENTS_PER_FER_UNIT);
        var grandMargeAch = grandTotalVente - grandTotalCostAch;
        var grandMargeRec = grandTotalVente - grandTotalCostRec;
        var coutPoudreTotal = grandTotalPoudre * AMMO_GUNPOWDER_PRICE;
        var coutMineraiTotal = grandTotalMinerai * prixFer;

        html += makeSectionHeader('TOTAL COMMANDE');
        html += makeRow('Munitions produites', fmt(grandTotalProduced), 'highlight');
        html += makeRow('Crafts totaux', fmt(grandTotalCrafts));
        html += makeRow('Temps de craft', formatTime(grandTotalTime));

        html += makeSectionHeader('Mat\u00e9riaux n\u00e9cessaires');
        html += makeRow('Poudre \u00e0 canon', fmt(grandTotalPoudre) + ' unit\u00e9s', 'highlight');
        html += makeRow('   Co\u00fbt poudre', fmtEuro(coutPoudreTotal));
        html += makeRow('Fragments de m\u00e9tal', fmt(grandTotalFragments) + ' fragments', 'highlight');
        html += makeRow('Minerais de fer', fmt(grandTotalMinerai) + ' minerais', 'highlight');
        if (prixFer > 0) {
            html += makeRow('   Co\u00fbt minerai', fmtEuro(coutMineraiTotal));
        }

        html += makeSectionHeader('Bilan financier');
        html += makeRow('Co\u00fbt total (fer achet\u00e9)', fmtEuro(grandTotalCostAch), 'highlight');
        html += makeRow('Co\u00fbt total (fer r\u00e9colt\u00e9)', fmtEuro(grandTotalCostRec), 'highlight');
        html += makeRow('Chiffre d\u2019affaires', fmtEuro(grandTotalVente), 'highlight');
        html += makeRow('Marge totale (fer achet\u00e9)', fmtEuro(grandMargeAch), ammoBenClass(grandMargeAch));
        html += makeRow('Marge totale (fer r\u00e9colt\u00e9)', fmtEuro(grandMargeRec), ammoBenClass(grandMargeRec));

        if (ammoStockEnabled()) {
            var st = ammoStockRead();
            var stockFragsFromMinerais = st.minerais * AMMO_FRAGMENTS_PER_FER_UNIT;
            var totalStockFrags = st.fragments + stockFragsFromMinerais;

            var netPoudre = Math.max(0, grandTotalPoudre - st.poudre);
            var netFragments = Math.max(0, grandTotalFragments - totalStockFrags);
            var netMinerai = Math.ceil(netFragments / AMMO_FRAGMENTS_PER_FER_UNIT);
            var netCoutPoudre = netPoudre * AMMO_GUNPOWDER_PRICE;
            var netCoutMinerai = netMinerai * prixFer;
            var netCostAch = netCoutPoudre + netCoutMinerai;
            var netCostRec = netCoutPoudre;
            var netMargeAch = grandTotalVente - netCostAch;
            var netMargeRec = grandTotalVente - netCostRec;

            html += makeSectionHeader('Apr\u00e8s d\u00e9duction du stock');
            html += makeRow('Poudre en stock', fmt(st.poudre) + ' unit\u00e9s');
            html += makeRow('Fragments en stock', fmt(st.fragments) + ' fragments' + (st.minerais > 0 ? ' + ' + fmt(st.minerais) + ' minerais (\u2192 ' + fmt(stockFragsFromMinerais) + ' frags)' : ''));
            html += makeRow('Poudre restante \u00e0 acqu\u00e9rir', fmt(netPoudre) + ' unit\u00e9s', netPoudre > 0 ? 'need' : 'ok');
            html += makeRow('Fragments restants \u00e0 acqu\u00e9rir', fmt(netFragments) + ' fragments', netFragments > 0 ? 'need' : 'ok');
            html += makeRow('Minerais restants \u00e0 acqu\u00e9rir', fmt(netMinerai) + ' minerais', netMinerai > 0 ? 'need' : 'ok');
            html += makeRow('Co\u00fbt net (fer achet\u00e9)', fmtEuro(netCostAch), 'highlight');
            html += makeRow('Co\u00fbt net (fer r\u00e9colt\u00e9)', fmtEuro(netCostRec), 'highlight');
            html += makeRow('Chiffre d\u2019affaires', fmtEuro(grandTotalVente), 'highlight');
            html += makeRow('Marge nette (fer achet\u00e9)', fmtEuro(netMargeAch), ammoBenClass(netMargeAch));
            html += makeRow('Marge nette (fer r\u00e9colt\u00e9)', fmtEuro(netMargeRec), ammoBenClass(netMargeRec));
            var ecoAch = grandTotalCostAch - netCostAch;
            var ecoRec = grandTotalCostRec - netCostRec;
            if (ecoAch > 0) html += makeRow('\u00c9conomie stock (fer ach.)', fmtEuro(ecoAch), 'ammo-ben-pos');
            if (ecoRec > 0) html += makeRow('\u00c9conomie stock (fer r\u00e9c.)', fmtEuro(ecoRec), 'ammo-ben-pos');
        }

        out.innerHTML = html;
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
        ammoFerEl.addEventListener('input', refreshAll);
        ammoFerEl.addEventListener('change', refreshAll);
    }
    var ammoUseStockEl = $('ammoUseStock');
    if (ammoUseStockEl) {
        ammoUseStockEl.addEventListener('change', function () {
            var fields = $('ammoStockFields');
            if (fields) fields.style.display = ammoUseStockEl.checked ? '' : 'none';
            refreshAll();
        });
    }
    ['ammoStockPoudre', 'ammoStockFragments', 'ammoStockMinerais'].forEach(function (sid) {
        var el = $(sid);
        if (el) {
            el.addEventListener('input', refreshAll);
            el.addEventListener('change', refreshAll);
        }
    });
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
    refreshAll();
})();
