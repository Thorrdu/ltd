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

    var pieceNames = {
        plans: 'Plans',
        ressort: 'Ressort',
        canon: 'Canon',
        poignee: 'Poignée',
        corp: 'Corp de pistolet',
        metal: 'Pièce de métal',
        polymere: 'Polymère'
    };

    var POLYMERE_PETROLE_RATE = 5;
    var POLYMERE_COST = 4500;
    var METAL_MINERAI_RATE = 5;
    var RESSORT_METAL_RATE = 1;
    var RESSORT_MINERAI_RATE = 3;
    var PLANS_PER_ITEM = 4; // 1 plan = 4 uses

    // ===== STATE =====
    var password = '';
    var contracts = [];
    var stock = { plans_wn29: 0, plans_ceramic: 0, plans_pistol: 0, plans_heavy: 0, plans_cal50: 0, ressort: 0, canon: 0, poignee: 0, corp: 0, metal: 0, polymere: 0, minerai: 0, petrole: 0 };
    var pendingContractWeapons = []; // weapons being added while creating a contract
    var saveTimer = null;

    // ===== DOM REFS =====
    var resultsSection = document.getElementById('resultsSection');
    var piecesTable = document.getElementById('piecesTable');
    var totalPiecesEl = document.getElementById('totalPieces');
    var materialCraft = document.getElementById('materialCraft');
    var rawMaterials = document.getElementById('rawMaterials');
    var costTable = document.getElementById('costTable');
    var craftTimeEl = document.getElementById('craftTime');

    var contractLock = document.getElementById('contractLock');
    var contractContent = document.getElementById('contractContent');
    var contractPwInput = document.getElementById('contractPwInput');
    var contractPwBtn = document.getElementById('contractPwBtn');
    var contractPwError = document.getElementById('contractPwError');
    var contractsList = document.getElementById('contractsList');
    var contractWeaponList = document.getElementById('contractWeaponList');
    var contractResultsSection = document.getElementById('contractResultsSection');
    var contractTotalNeeded = document.getElementById('contractTotalNeeded');
    var contractRemaining = document.getElementById('contractRemaining');
    var contractPlansDetail = document.getElementById('contractPlansDetail');

    // ===== HELPERS =====
    function esc(s) {
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

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

    function fmt(n) {
        return n.toLocaleString('fr-FR');
    }

    function formatTime(seconds) {
        if (seconds < 60) return seconds + ' sec';
        var m = Math.floor(seconds / 60);
        var s = seconds % 60;
        if (s === 0) return m + ' min';
        return m + ' min ' + s + ' sec';
    }

    function uid() {
        return Date.now().toString(36) + Math.random().toString(36).slice(2, 7);
    }

    function csrfToken() {
        var el = document.querySelector('meta[name="csrf-token"]');
        return el ? el.getAttribute('content') : '';
    }

    // ===== API =====
    function apiGet(cb) {
        fetch('/simulateur-armes/data', {
            headers: { 'X-Sim-Password': password, 'Accept': 'application/json' }
        })
        .then(function (r) { return r.json(); })
        .then(function (data) { cb(null, data); })
        .catch(function (e) { cb(e); });
    }

    function apiSave(cb) {
        fetch('/simulateur-armes/data', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Sim-Password': password,
                'X-CSRF-TOKEN': csrfToken(),
                'Accept': 'application/json'
            },
            body: JSON.stringify({ contracts: contracts, stock: stock })
        })
        .then(function (r) { return r.json(); })
        .then(function (data) { if (cb) cb(null, data); })
        .catch(function (e) { if (cb) cb(e); });
    }

    function debounceSave() {
        if (saveTimer) clearTimeout(saveTimer);
        saveTimer = setTimeout(function () { apiSave(); }, 400);
    }

    // ===== TAB SWITCHING =====
    document.querySelectorAll('.tab-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.tab-btn').forEach(function (b) { b.classList.remove('active'); });
            document.querySelectorAll('.tab-content').forEach(function (c) { c.classList.remove('active'); });
            btn.classList.add('active');
            document.getElementById('tab-' + btn.getAttribute('data-tab')).classList.add('active');
        });
    });

    // ============================================
    // TAB 1: SIMULATOR (public, unchanged logic)
    // ============================================
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

        document.querySelectorAll('.weapon-card').forEach(function (card) {
            var w = card.getAttribute('data-weapon');
            card.classList.toggle('active', orders[w] > 0);
        });

        if (!hasAny) {
            resultsSection.style.display = 'none';
            return;
        }
        resultsSection.style.display = '';

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
                html += makeRow(pieceNames[p], fmt(need));
            });
        });
        piecesTable.innerHTML = html;

        html = '';
        Object.keys(pieceNames).forEach(function (p) {
            if (totals[p] > 0) {
                html += makeRow(pieceNames[p], fmt(totals[p]));
            }
        });
        totalPiecesEl.innerHTML = html;

        var metalForRessorts = totals.ressort * RESSORT_METAL_RATE;
        var mineraiForRessorts = totals.ressort * RESSORT_MINERAI_RATE;
        var totalMetalNeeded = totals.metal + metalForRessorts;
        var totalMineraiForMetal = totalMetalNeeded * METAL_MINERAI_RATE;
        var totalMineraiTotal = totalMineraiForMetal + mineraiForRessorts;
        var totalPetrole = totals.polymere * POLYMERE_PETROLE_RATE;

        html = '';
        html += makeSectionHeader('Craft des Ressorts (' + fmt(totals.ressort) + ')');
        html += makeRow('Pièces de métal (pour ressorts)', fmt(metalForRessorts));
        html += makeRow('Minerais de fer (pour ressorts)', fmt(mineraiForRessorts));
        html += makeSectionHeader('Craft des Pièces de métal (' + fmt(totalMetalNeeded) + ')');
        html += makeRow('Pièces directes', fmt(totals.metal));
        html += makeRow('Pièces pour ressorts', fmt(metalForRessorts));
        html += makeRow('Minerais de fer nécessaires', fmt(totalMineraiForMetal));
        html += makeSectionHeader('Craft des Polymères (' + fmt(totals.polymere) + ')');
        html += makeRow('Pétroles nécessaires', fmt(totalPetrole));
        materialCraft.innerHTML = html;

        html = '';
        html += makeRow('Minerais de fer', fmt(totalMineraiTotal), 'highlight');
        html += makeRow('Pétroles', fmt(totalPetrole), 'highlight');
        html += makeRow('Plans', fmt(totals.plans));
        html += makeRow('Canons', fmt(totals.canon));
        html += makeRow('Poignées', fmt(totals.poignee));
        html += makeRow('Corps de pistolet', fmt(totals.corp));
        rawMaterials.innerHTML = html;

        var polymereCost = totals.polymere * POLYMERE_COST;
        html = '';
        html += makeRow('Polymères achetés au tunnel (' + fmt(totals.polymere) + ' × ' + fmt(POLYMERE_COST) + '€)', fmt(polymereCost) + ' €', 'highlight');
        costTable.innerHTML = html;

        var totalTime = 0;
        var hasUnknown = false;
        Object.keys(weapons).forEach(function (key) {
            var qty = orders[key];
            if (qty === 0) return;
            var w = weapons[key];
            if (w.craftTime === null) hasUnknown = true;
            else totalTime += w.craftTime * qty;
        });
        var timeStr = formatTime(totalTime);
        if (hasUnknown) timeStr += ' + temps inconnu (Heavy/Cal .50)';
        craftTimeEl.textContent = timeStr;
    }

    document.querySelectorAll('.qty-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var w = this.getAttribute('data-weapon');
            var input = document.getElementById('qty-' + w);
            var val = parseInt(input.value, 10) || 0;
            if (this.classList.contains('plus')) val = Math.min(val + 1, 99);
            else val = Math.max(val - 1, 0);
            input.value = val;
            calculate();
        });
    });

    document.querySelectorAll('.qty-input').forEach(function (input) {
        input.addEventListener('input', calculate);
        input.addEventListener('change', calculate);
    });

    // ============================================
    // TAB 2: CONTRACTS & STOCK (password-protected, shared storage)
    // ============================================

    // --- Password ---
    function tryUnlock() {
        var pw = contractPwInput.value;
        password = pw;
        apiGet(function (err, data) {
            if (err || (data && data.error)) {
                contractPwError.classList.add('visible');
                password = '';
                return;
            }
            contractPwError.classList.remove('visible');
            contractLock.style.display = 'none';
            contractContent.style.display = '';
            contracts = data.contracts || [];
            stock = data.stock || { plans_wn29: 0, plans_ceramic: 0, plans_pistol: 0, plans_heavy: 0, plans_cal50: 0, ressort: 0, canon: 0, poignee: 0, corp: 0, metal: 0, polymere: 0, minerai: 0, petrole: 0 };
            renderStock();
            renderContracts();
            calculateContracts();
        });
    }

    contractPwBtn.addEventListener('click', tryUnlock);
    contractPwInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') tryUnlock();
    });

    // --- Stock ---
    function renderStock() {
        document.querySelectorAll('.stock-input').forEach(function (input) {
            var key = input.getAttribute('data-stock');
            input.value = stock[key] || 0;
        });
    }

    document.querySelectorAll('.stock-input').forEach(function (input) {
        input.addEventListener('input', function () {
            var key = this.getAttribute('data-stock');
            stock[key] = Math.max(0, parseInt(this.value, 10) || 0);
            calculateContracts();
            debounceSave();
        });
    });

    // --- Contract creation ---
    function renderPendingWeapons() {
        var html = '';
        pendingContractWeapons.forEach(function (w, i) {
            html += '<span class="contract-weapon-tag">' +
                esc(weapons[w.key].name) + ' ×' + w.qty +
                ' <span class="remove-tag" data-idx="' + i + '">✕</span></span>';
        });
        contractWeaponList.innerHTML = html;

        contractWeaponList.querySelectorAll('.remove-tag').forEach(function (btn) {
            btn.addEventListener('click', function () {
                pendingContractWeapons.splice(parseInt(this.getAttribute('data-idx'), 10), 1);
                renderPendingWeapons();
            });
        });
    }

    document.getElementById('contractAddWeapon').addEventListener('click', function () {
        var key = document.getElementById('contractWeaponSelect').value;
        var qty = Math.max(1, parseInt(document.getElementById('contractWeaponQty').value, 10) || 1);
        pendingContractWeapons.push({ key: key, qty: qty });
        document.getElementById('contractWeaponQty').value = 1;
        renderPendingWeapons();
    });

    document.getElementById('contractSaveBtn').addEventListener('click', function () {
        var name = document.getElementById('contractName').value.trim();
        if (!name) return;
        if (pendingContractWeapons.length === 0) return;

        // Build done object: { weaponKey: 0 } for each weapon in contract
        var done = {};
        pendingContractWeapons.forEach(function (w) {
            done[w.key] = (done[w.key] || 0); // start at 0 done
        });

        contracts.push({
            id: uid(),
            name: name,
            weapons: pendingContractWeapons.slice(),
            done: done
        });

        // Reset form
        document.getElementById('contractName').value = '';
        pendingContractWeapons = [];
        contractWeaponList.innerHTML = '';

        renderContracts();
        calculateContracts();
        apiSave();
    });

    // --- Contracts list ---
    function renderContracts() {
        if (contracts.length === 0) {
            contractsList.innerHTML = '<div style="text-align:center;color:#6a5a40;font-size:11px;padding:12px;">Aucun contrat</div>';
            return;
        }

        var html = '';
        contracts.forEach(function (c) {
            html += '<div class="contract-card" data-id="' + esc(c.id) + '">';
            html += '<div class="contract-card-header">';
            html += '<span class="contract-card-name">' + esc(c.name) + '</span>';
            html += '<div class="contract-card-actions">';
            html += '<button class="contract-action-btn delete" data-id="' + esc(c.id) + '">Supprimer</button>';
            html += '</div></div>';

            // weapons summary
            var wDesc = c.weapons.map(function (w) {
                return (weapons[w.key] ? weapons[w.key].name : w.key) + ' ×' + w.qty;
            }).join(', ');
            html += '<div class="contract-card-weapons">' + esc(wDesc) + '</div>';

            // done inputs per weapon
            html += '<div class="contract-card-done">';
            c.weapons.forEach(function (w) {
                var doneVal = c.done[w.key] || 0;
                var isComplete = doneVal >= w.qty;
                html += '<div class="contract-done-row">';
                html += '<label>' + esc(weapons[w.key] ? weapons[w.key].name : w.key) + '</label>';
                html += '<input type="number" min="0" max="' + w.qty + '" value="' + doneVal + '" ' +
                    'data-contract="' + esc(c.id) + '" data-weapon="' + esc(w.key) + '" class="contract-done-input">';
                html += '<span class="of-total">/ ' + w.qty + '</span>';
                if (isComplete) html += ' <span class="complete">✓</span>';
                html += '</div>';
            });
            html += '</div>';

            html += '</div>';
        });
        contractsList.innerHTML = html;

        // Bind delete
        contractsList.querySelectorAll('.contract-action-btn.delete').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = this.getAttribute('data-id');
                contracts = contracts.filter(function (c) { return c.id !== id; });
                renderContracts();
                calculateContracts();
                apiSave();
            });
        });

        // Bind done inputs
        contractsList.querySelectorAll('.contract-done-input').forEach(function (input) {
            input.addEventListener('input', function () {
                var cid = this.getAttribute('data-contract');
                var wKey = this.getAttribute('data-weapon');
                var val = Math.max(0, parseInt(this.value, 10) || 0);
                contracts.forEach(function (c) {
                    if (c.id === cid) {
                        c.done[wKey] = val;
                    }
                });
                calculateContracts();
                debounceSave();
            });
        });
    }

    // --- Calculate contract totals vs stock ---
    function calculateContracts() {
        if (contracts.length === 0) {
            contractResultsSection.style.display = 'none';
            return;
        }
        contractResultsSection.style.display = '';

        // Total weapons remaining across all contracts
        var totalWeaponsNeeded = {};
        contracts.forEach(function (c) {
            c.weapons.forEach(function (w) {
                var remaining = Math.max(0, w.qty - (c.done[w.key] || 0));
                totalWeaponsNeeded[w.key] = (totalWeaponsNeeded[w.key] || 0) + remaining;
            });
        });

        // Total pieces needed (from remaining weapons)
        var piecesNeeded = { plans: 0, ressort: 0, canon: 0, poignee: 0, corp: 0, metal: 0, polymere: 0 };
        Object.keys(totalWeaponsNeeded).forEach(function (key) {
            var qty = totalWeaponsNeeded[key];
            if (qty <= 0 || !weapons[key]) return;
            var w = weapons[key];
            Object.keys(w.pieces).forEach(function (p) {
                piecesNeeded[p] += w.pieces[p] * qty;
            });
        });

        // Expand to raw materials
        var metalForRessorts = piecesNeeded.ressort * RESSORT_METAL_RATE;
        var mineraiForRessorts = piecesNeeded.ressort * RESSORT_MINERAI_RATE;
        var totalMetalPieces = piecesNeeded.metal + metalForRessorts;
        var totalMinerai = totalMetalPieces * METAL_MINERAI_RATE + mineraiForRessorts;
        var totalPetrole = piecesNeeded.polymere * POLYMERE_PETROLE_RATE;

        // Plans per weapon: 1 plan item = 4 uses, specific to each weapon
        var plansPerWeapon = {};
        Object.keys(weapons).forEach(function (key) {
            var usesNeeded = (totalWeaponsNeeded[key] || 0) * weapons[key].pieces.plans;
            var physical = stock['plans_' + key] || 0;
            var usesAvailable = physical * PLANS_PER_ITEM;
            plansPerWeapon[key] = { usesNeeded: usesNeeded, physical: physical, usesAvailable: usesAvailable };
        });

        // Total needed summary (pieces + raw)
        var html = '';
        html += makeSectionHeader('Armes restantes');
        Object.keys(totalWeaponsNeeded).forEach(function (key) {
            if (totalWeaponsNeeded[key] > 0) {
                html += makeRow(weapons[key].name, fmt(totalWeaponsNeeded[key]));
            }
        });
        html += makeSectionHeader('Pièces intermédiaires');
        Object.keys(pieceNames).forEach(function (p) {
            if (piecesNeeded[p] > 0) {
                var label = pieceNames[p];
                if (p === 'plans') label += ' (utilisations totales)';
                html += makeRow(label, fmt(piecesNeeded[p]));
            }
        });
        html += makeSectionHeader('Matières premières');
        html += makeRow('Minerais de fer', fmt(totalMinerai));
        html += makeRow('Pétroles', fmt(totalPetrole));
        contractTotalNeeded.innerHTML = html;

        // Remaining (needed - stock)
        html = '';
        // Plans per weapon
        Object.keys(weapons).forEach(function (key) {
            var pw = plansPerWeapon[key];
            if (pw.usesNeeded <= 0) return;
            var physNeeded = Math.ceil(pw.usesNeeded / PLANS_PER_ITEM);
            var diff = physNeeded - pw.physical;
            var cls, val;
            if (diff <= 0) {
                cls = 'ok';
                val = '✓ OK' + (pw.physical > physNeeded ? ' (+' + fmt(pw.physical - physNeeded) + ')' : '');
            } else {
                cls = 'need';
                val = '▲ ' + fmt(diff) + ' manquant' + (diff > 1 ? 's' : '');
            }
            html += makeRow('Plans ' + weapons[key].name + ' — besoin ' + fmt(physNeeded) + ' / stock ' + fmt(pw.physical), val, cls);
        });

        var items = [
            { label: 'Ressorts', needed: piecesNeeded.ressort, have: stock.ressort || 0 },
            { label: 'Canons', needed: piecesNeeded.canon, have: stock.canon || 0 },
            { label: 'Poignées', needed: piecesNeeded.poignee, have: stock.poignee || 0 },
            { label: 'Corps de pistolet', needed: piecesNeeded.corp, have: stock.corp || 0 },
            { label: 'Pièces de métal', needed: totalMetalPieces, have: stock.metal || 0 },
            { label: 'Polymères', needed: piecesNeeded.polymere, have: stock.polymere || 0 },
            { label: 'Minerais de fer', needed: totalMinerai, have: stock.minerai || 0 },
            { label: 'Pétroles', needed: totalPetrole, have: stock.petrole || 0 }
        ];

        items.forEach(function (it) {
            var diff = it.needed - it.have;
            var cls, val;
            if (diff <= 0) {
                cls = 'ok';
                val = '✓ OK' + (it.have > it.needed ? ' (+' + fmt(it.have - it.needed) + ')' : '');
            } else {
                cls = 'need';
                val = '▲ ' + fmt(diff) + ' manquant' + (diff > 1 ? 's' : '');
            }
            html += makeRow(it.label + ' — besoin ' + fmt(it.needed) + ' / stock ' + fmt(it.have), val, cls);
        });
        contractRemaining.innerHTML = html;

        // Plans detail per weapon
        html = '';
        Object.keys(weapons).forEach(function (key) {
            var pw = plansPerWeapon[key];
            if (pw.usesNeeded <= 0) return;
            html += makeSectionHeader('Plans ' + weapons[key].name);
            html += makeRow('Plans physiques en stock', fmt(pw.physical));
            html += makeRow('Utilisations disponibles (×4)', fmt(pw.usesAvailable));
            html += makeRow('Utilisations nécessaires', fmt(pw.usesNeeded));
            var remaining = pw.usesAvailable - pw.usesNeeded;
            if (remaining >= 0) {
                html += makeRow('Utilisations restantes après craft', fmt(remaining), 'ok');
            } else {
                var physNeeded = Math.ceil(Math.abs(remaining) / PLANS_PER_ITEM);
                html += makeRow('Plans physiques à récupérer', fmt(physNeeded), 'need');
            }
        });
        contractPlansDetail.innerHTML = html;
    }
})();
