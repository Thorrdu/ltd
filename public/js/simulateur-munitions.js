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

        out.innerHTML = html;
    }

    function refreshAll() {
        updateAmmoCraft();
        updateAmmoTargetSim();
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
