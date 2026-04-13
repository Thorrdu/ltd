(function () {
    'use strict';

    // ===== WEAPON DATA =====
    var weapons = {
        wn29: {
            name: 'WN 29 Pistol',
            craftTime: 15,
            pieces: { plans: 1, ressort: 1, canon: 1, poignee: 1, corp: 1, metal: 10, polymere: 5 }
        },
        ceramic: {
            name: 'Ceramic Pistol',
            craftTime: 15,
            pieces: { plans: 1, ressort: 1, canon: 1, poignee: 1, corp: 1, metal: 5, polymere: 5 }
        },
        pistol: {
            name: 'Pistol',
            craftTime: 15,
            pieces: { plans: 1, ressort: 1, canon: 1, poignee: 1, corp: 1, metal: 5, polymere: 10 }
        },
        heavy: {
            name: 'Heavy Pistol',
            craftTime: null,
            pieces: { plans: 1, ressort: 2, canon: 1, poignee: 1, corp: 1, metal: 10, polymere: 10 }
        },
        cal50: {
            name: 'Cal .50',
            craftTime: null,
            pieces: { plans: 1, ressort: 2, canon: 1, poignee: 1, corp: 1, metal: 10, polymere: 15 }
        }
    };

    // Friendly names for pieces
    var pieceNames = {
        plans: 'Plans',
        ressort: 'Ressort',
        canon: 'Canon',
        poignee: 'Poignée',
        corp: 'Corp de pistolet',
        metal: 'Pièce de métal',
        polymere: 'Polymère'
    };

    // ===== MATERIAL CRAFTING RATES =====
    // 1 Polymère = 5 Pétroles (or buy at 4500€ each)
    // 1 Pièce de métal = 5 Minerais de fer
    // 1 Ressort = 1 Pièce de métal + 3 Minerais de fer = 8 Minerais de fer
    // 2 Fragments de métal = 1 Minerai de fer

    var POLYMERE_PETROLE_RATE = 5;
    var POLYMERE_COST = 4500;
    var METAL_MINERAI_RATE = 5;
    var RESSORT_METAL_RATE = 1;   // 1 pièce de métal per ressort
    var RESSORT_MINERAI_RATE = 3; // + 3 minerais de fer per ressort

    // ===== DOM REFS =====
    var resultsSection = document.getElementById('resultsSection');
    var piecesTable = document.getElementById('piecesTable');
    var totalPieces = document.getElementById('totalPieces');
    var materialCraft = document.getElementById('materialCraft');
    var rawMaterials = document.getElementById('rawMaterials');
    var costTable = document.getElementById('costTable');
    var craftTimeEl = document.getElementById('craftTime');

    // ===== HELPERS =====
    function makeRow(label, value, cls) {
        return '<div class="result-row">' +
            '<span class="label">' + label + '</span>' +
            '<span class="dot-leader"></span>' +
            '<span class="value' + (cls ? ' ' + cls : '') + '">' + value + '</span>' +
            '</div>';
    }

    function makeSectionHeader(label) {
        return '<div class="result-row section-header"><span class="label">' + label + '</span></div>';
    }

    function formatNumber(n) {
        return n.toLocaleString('fr-FR');
    }

    function formatTime(seconds) {
        if (seconds < 60) return seconds + ' sec';
        var m = Math.floor(seconds / 60);
        var s = seconds % 60;
        if (s === 0) return m + ' min';
        return m + ' min ' + s + ' sec';
    }

    // ===== CALCULATION =====
    function calculate() {
        var orders = {};
        var hasAny = false;

        Object.keys(weapons).forEach(function (key) {
            var input = document.getElementById('qty-' + key);
            var qty = parseInt(input.value, 10) || 0;
            if (qty < 0) qty = 0;
            if (qty > 99) qty = 99;
            input.value = qty;
            orders[key] = qty;
            if (qty > 0) hasAny = true;
        });

        // Toggle active state on cards
        document.querySelectorAll('.weapon-card').forEach(function (card) {
            var w = card.getAttribute('data-weapon');
            card.classList.toggle('active', orders[w] > 0);
        });

        if (!hasAny) {
            resultsSection.style.display = 'none';
            return;
        }
        resultsSection.style.display = '';

        // --- Per weapon pieces ---
        var html = '';
        var totals = { plans: 0, ressort: 0, canon: 0, poignee: 0, corp: 0, metal: 0, polymere: 0 };

        Object.keys(weapons).forEach(function (key) {
            var qty = orders[key];
            if (qty === 0) return;
            var w = weapons[key];
            html += makeSectionHeader(w.name + ' × ' + qty);
            Object.keys(w.pieces).forEach(function (p) {
                var need = w.pieces[p] * qty;
                totals[p] += need;
                html += makeRow(pieceNames[p], formatNumber(need));
            });
        });
        piecesTable.innerHTML = html;

        // --- Total pieces ---
        html = '';
        Object.keys(pieceNames).forEach(function (p) {
            if (totals[p] > 0) {
                html += makeRow(pieceNames[p], formatNumber(totals[p]));
            }
        });
        totalPieces.innerHTML = html;

        // --- Material crafting ---
        // Ressort needs: totals.ressort ressorts
        //   Each ressort = 1 pièce de métal + 3 minerais de fer
        var metalForRessorts = totals.ressort * RESSORT_METAL_RATE;
        var mineraiForRessorts = totals.ressort * RESSORT_MINERAI_RATE;
        var totalMetalNeeded = totals.metal + metalForRessorts;
        var totalMineraiForMetal = totalMetalNeeded * METAL_MINERAI_RATE;
        var totalMineraiTotal = totalMineraiForMetal + mineraiForRessorts;
        var totalPetrole = totals.polymere * POLYMERE_PETROLE_RATE;

        html = '';
        html += makeSectionHeader('Craft des Ressorts (' + formatNumber(totals.ressort) + ')');
        html += makeRow('Pièces de métal (pour ressorts)', formatNumber(metalForRessorts));
        html += makeRow('Minerais de fer (pour ressorts)', formatNumber(mineraiForRessorts));
        html += makeSectionHeader('Craft des Pièces de métal (' + formatNumber(totalMetalNeeded) + ')');
        html += makeRow('Pièces directes', formatNumber(totals.metal));
        html += makeRow('Pièces pour ressorts', formatNumber(metalForRessorts));
        html += makeRow('Minerais de fer nécessaires', formatNumber(totalMineraiForMetal));
        html += makeSectionHeader('Craft des Polymères (' + formatNumber(totals.polymere) + ')');
        html += makeRow('Pétroles nécessaires', formatNumber(totalPetrole));
        materialCraft.innerHTML = html;

        // --- Raw materials ---
        html = '';
        html += makeRow('Minerais de fer', formatNumber(totalMineraiTotal), 'highlight');
        html += makeRow('Pétroles', formatNumber(totalPetrole), 'highlight');
        html += makeRow('Plans', formatNumber(totals.plans));
        html += makeRow('Canons', formatNumber(totals.canon));
        html += makeRow('Poignées', formatNumber(totals.poignee));
        html += makeRow('Corps de pistolet', formatNumber(totals.corp));
        rawMaterials.innerHTML = html;

        // --- Cost ---
        var polymereCost = totals.polymere * POLYMERE_COST;
        html = '';
        html += makeRow('Polymères achetés au tunnel (' + formatNumber(totals.polymere) + ' × ' + formatNumber(POLYMERE_COST) + '€)', formatNumber(polymereCost) + ' €', 'highlight');
        costTable.innerHTML = html;

        // --- Craft time ---
        var totalTime = 0;
        var hasUnknown = false;
        Object.keys(weapons).forEach(function (key) {
            var qty = orders[key];
            if (qty === 0) return;
            var w = weapons[key];
            if (w.craftTime === null) {
                hasUnknown = true;
            } else {
                totalTime += w.craftTime * qty;
            }
        });
        var timeStr = formatTime(totalTime);
        if (hasUnknown) {
            timeStr += ' + temps inconnu (Heavy/Cal .50)';
        }
        craftTimeEl.textContent = timeStr;
    }

    // ===== EVENT BINDING =====
    document.querySelectorAll('.qty-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var w = this.getAttribute('data-weapon');
            var input = document.getElementById('qty-' + w);
            var val = parseInt(input.value, 10) || 0;
            if (this.classList.contains('plus')) {
                val = Math.min(val + 1, 99);
            } else {
                val = Math.max(val - 1, 0);
            }
            input.value = val;
            calculate();
        });
    });

    document.querySelectorAll('.qty-input').forEach(function (input) {
        input.addEventListener('input', function () {
            calculate();
        });
        input.addEventListener('change', function () {
            calculate();
        });
    });
})();
