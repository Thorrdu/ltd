(function () {
    'use strict';

    var auth = window.McAuth;
    if (!auth) return;

    function $(id) { return document.getElementById(id); }
    function esc(s) { if (s == null) return ''; var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
    function money(n) { return '$' + (parseInt(n, 10) || 0).toLocaleString('fr-FR'); }
    function num(n)   { return (parseInt(n, 10) || 0).toLocaleString('fr-FR'); }

    var CATEGORIES = window.MC_CATEGORIES || {};
    var MEMBERS    = window.MC_MEMBERS || [];

    var state = {
        catalog: [],
        totals: [],
        capacity_kg: 1000,
        current_kg: 0,
        filter: { search: '', category: '' },
        attributions: [],
        attScope: 'all',
        attStatus: 'open',
        validations: [],
        importPreview: null
    };

    var itemTs = null;
    var memberTs = null;

    // ── GATE ───────────────────────────────────────────────

    function updateGate() {
        var notLogged = $('stocksNotLogged');
        var noAccess = $('stocksNoAccess');
        var content = $('stocksContent');

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
        auth.apiGet('/stocks/api/list', function (err, data) {
            if (err || !data || data.error) {
                $('stocksNoAccess').style.display = '';
                $('stocksContent').style.display = 'none';
                return;
            }
            $('stocksNoAccess').style.display = 'none';
            $('stocksContent').style.display = '';

            state.catalog = data.catalog || [];
            state.totals = data.totals || [];
            state.capacity_kg = data.capacity_kg || 1000;
            state.current_kg = data.current_kg || 0;

            renderCapacity();
            renderTotals();
            renderList();
            populateAttributionForm();

            toggleTreasurerTabs();
            refreshAttributions();
        });
    }

    function toggleTreasurerTabs() {
        var isTreasurer = auth.isSuperadmin();
        $('tabValidations').style.display = isTreasurer ? '' : 'none';
        $('tabImport').style.display = isTreasurer ? '' : 'none';
    }

    // ── OVERVIEW ───────────────────────────────────────────

    function renderCapacity() {
        var el = $('stocksCapacity');
        if (!el) return;
        var pct = Math.min(100, Math.round((state.current_kg / Math.max(state.capacity_kg, 1)) * 100));
        el.innerHTML =
            '<div>Capacite coffre</div>' +
            '<div class="cap-bar"><div class="cap-bar-fill" style="width:' + pct + '%"></div></div>' +
            '<div class="cap-label">' + num(state.current_kg) + ' / ' + num(state.capacity_kg) + ' kg (' + pct + '%)</div>';
    }

    function renderTotals() {
        var el = $('stocksTotals');
        if (!el) return;
        if (!state.totals.length) {
            el.innerHTML = '';
            return;
        }
        el.innerHTML = state.totals.map(function (t) {
            var active = state.filter.category === t.category ? ' active' : '';
            return '<div class="stocks-total-card' + active + '" data-cat="' + esc(t.category) + '">' +
                '<div class="cat-label">' + esc(t.category_label) + '</div>' +
                '<div class="cat-qty">' + num(t.quantity) + '</div>' +
                '<div class="cat-meta">' + t.item_count + ' articles' +
                (t.out_attributed ? ' &middot; <span class="out">' + num(t.out_attributed) + ' attrib.</span>' : '') +
                (t.weight_g ? ' &middot; ' + num(Math.round(t.weight_g / 1000)) + ' kg' : '') +
                '</div>' +
                '</div>';
        }).join('');
        el.querySelectorAll('.stocks-total-card').forEach(function (card) {
            card.addEventListener('click', function () {
                var cat = card.getAttribute('data-cat');
                state.filter.category = state.filter.category === cat ? '' : cat;
                $('stockCategory').value = state.filter.category;
                renderTotals();
                renderList();
            });
        });
    }

    function saveRowQuantity(slug, quantity, inputEl, revertVal) {
        inputEl.disabled = true;
        auth.apiPut('/stocks/api/item/' + encodeURIComponent(slug) + '/quantity', { quantity: quantity }, function (err, data) {
            inputEl.disabled = false;
            if (err || !data || data.error) {
                var msg = (data && data.error) || 'Erreur';
                if (data && data.messages) {
                    msg = Object.values(data.messages).flat().join(' | ');
                }
                if (auth.showToast) auth.showToast(msg, 'error');
                inputEl.value = revertVal;
                return;
            }
            if (auth.showToast) auth.showToast(data.message || 'Quantite mise a jour', 'success');
            var cat = state.catalog.filter(function (x) { return x.slug === slug; })[0];
            if (cat) cat.quantity = quantity;
            inputEl.dataset.orig = String(quantity);
            renderTotals();
            var qc = inputEl.parentNode;
            if (qc) {
                qc.classList.remove('zero', 'low');
                var cls = quantity <= 0 ? 'zero' : (quantity < 5 ? 'low' : '');
                if (cls) qc.classList.add(cls);
            }
        });
    }

    function renderList() {
        var el = $('stocksList');
        if (!el) return;
        var search = (state.filter.search || '').toLowerCase().trim();
        var cat = state.filter.category;

        var filtered = state.catalog.filter(function (it) {
            if (cat && it.category !== cat) return false;
            if (search && (it.name.toLowerCase().indexOf(search) < 0 && (it.slug || '').toLowerCase().indexOf(search) < 0)) return false;
            return true;
        });

        if (!filtered.length) {
            el.innerHTML = '<div class="empty-msg">Aucun article correspondant.</div>';
            return;
        }

        // Group by category for visual hierarchy.
        var grouped = {};
        filtered.forEach(function (it) {
            if (!grouped[it.category]) grouped[it.category] = [];
            grouped[it.category].push(it);
        });

        var html = '';
        Object.keys(grouped).forEach(function (catKey) {
            var label = CATEGORIES[catKey] || catKey;
            html += '<div class="cat-header">' + esc(label) + ' &middot; ' + grouped[catKey].length + ' articles</div>';
            html += '<div class="stocks-row stocks-head">' +
                '<div>Article</div>' +
                '<div>Stock</div>' +
                '<div>Exterieur</div>' +
                '<div>Prix vente</div>' +
                '<div>Poids (u)</div>' +
                '<div></div>' +
                '</div>';
            grouped[catKey].forEach(function (it) {
                var qtyClass = it.quantity <= 0 ? 'zero' : (it.quantity < 5 ? 'low' : '');
                var outClass = it.out_attributed > 0 ? '' : 'none';
                html += '<div class="stocks-row">' +
                    '<div class="s-name"><a href="/stocks/' + esc(it.slug) + '">' + esc(it.name) + '</a><span class="s-slug">' + esc(it.slug) + '</span></div>' +
                    '<div class="s-qty ' + qtyClass + '">' +
                    '<input type="number" class="fm-input s-qty-inline" data-slug="' + esc(it.slug) + '" value="' + it.quantity + '" min="-999999999" max="999999999" step="1" title="Entree ou clic hors champ pour enregistrer">' +
                    '</div>' +
                    '<div class="s-out ' + outClass + '">' + (it.out_attributed ? num(it.out_attributed) : '-') + '</div>' +
                    '<div class="s-price">' + (it.default_sell_price ? money(it.default_sell_price) : '-') + '</div>' +
                    '<div class="s-weight">' + (it.unit_weight_g ? num(it.unit_weight_g) + ' g' : '-') + '</div>' +
                    '<div class="s-actions"><a href="/stocks/' + esc(it.slug) + '">Detail</a></div>' +
                    '</div>';
            });
        });

        el.innerHTML = html;
    }

    // ── ATTRIBUTION ────────────────────────────────────────

    function populateAttributionForm() {
        var itemSel = $('aItem');
        if (itemSel) {
            if (itemTs) { try { itemTs.destroy(); } catch (e) {} itemTs = null; }
            itemSel.innerHTML = '<option value="">-- Choisir un article --</option>';
            var grouped = {};
            state.catalog.forEach(function (it) {
                if (!grouped[it.category]) grouped[it.category] = [];
                grouped[it.category].push(it);
            });
            Object.keys(grouped).forEach(function (cat) {
                var og = document.createElement('optgroup');
                og.label = CATEGORIES[cat] || cat;
                grouped[cat].forEach(function (it) {
                    var opt = document.createElement('option');
                    opt.value = it.id;
                    opt.textContent = it.name + ' (stock: ' + it.quantity + ')';
                    opt.setAttribute('data-price', it.default_sell_price || 0);
                    opt.setAttribute('data-qty', it.quantity);
                    og.appendChild(opt);
                });
                itemSel.appendChild(og);
            });
            if (typeof TomSelect !== 'undefined') {
                itemTs = new TomSelect(itemSel, {
                    placeholder: 'Rechercher un article...',
                    searchField: ['text'],
                    maxOptions: 500,
                    plugins: ['dropdown_input']
                });
            }
        }

        var memberSel = $('aMember');
        if (memberSel) {
            if (memberTs) { try { memberTs.destroy(); } catch (e) {} memberTs = null; }
            memberSel.innerHTML = '<option value="">-- Choisir un beneficiaire --</option>';
            MEMBERS.forEach(function (m) {
                var opt = document.createElement('option');
                opt.value = m.id;
                opt.textContent = m.name;
                memberSel.appendChild(opt);
            });
            if (typeof TomSelect !== 'undefined') {
                memberTs = new TomSelect(memberSel, {
                    placeholder: 'Qui recoit cet article ?',
                    searchField: ['text'],
                    maxOptions: 500,
                    plugins: ['dropdown_input']
                });
            }
        }
    }

    function updateValueHint() {
        var sel = $('aItem');
        var qtyEl = $('aQty');
        var hint = $('aValueHint');
        if (!sel || !qtyEl || !hint) return;
        var opt = sel.options[sel.selectedIndex];
        var price = opt ? parseInt(opt.getAttribute('data-price') || '0', 10) : 0;
        var qty = parseInt(qtyEl.value, 10) || 0;
        if (price && qty) {
            hint.innerHTML = 'Valeur estimee : ' + money(price * qty) + ' (' + qty + ' x ' + money(price) + ')';
        } else {
            hint.textContent = '';
        }
    }

    function submitAttribution() {
        var stockItemId = parseInt($('aItem').value, 10);
        var qty = parseInt($('aQty').value, 10);
        var memberId = parseInt($('aMember').value, 10);
        var notes = ($('aNotes').value || '').trim();

        if (!stockItemId) { auth.showToast('Choisissez un article', 'error'); return; }
        if (!qty || qty < 1) { auth.showToast('Quantite invalide', 'error'); return; }
        if (!memberId) { auth.showToast('Choisissez un beneficiaire', 'error'); return; }

        var btn = $('aBtnSave');
        btn.disabled = true;
        btn.textContent = 'Enregistrement...';

        auth.apiPost('/stocks/api/attribute', {
            stock_item_id: stockItemId,
            quantity: qty,
            attributed_to_user_id: memberId,
            notes: notes || null
        }, function (err, data) {
            btn.disabled = false;
            btn.textContent = 'Enregistrer l\'attribution';
            if (err || !data || data.error) {
                auth.showToast((data && data.error) || 'Erreur', 'error');
                return;
            }
            auth.showToast(data.message || 'Attribution enregistree', 'success');
            if (data.warning) setTimeout(function () { auth.showToast(data.warning, 'error'); }, 900);
            resetAttributionForm();
            tryLoad();
        });
    }

    function resetAttributionForm() {
        $('aQty').value = '1';
        $('aNotes').value = '';
        if (itemTs) itemTs.clear();
        if (memberTs) memberTs.clear();
        updateValueHint();
    }

    // ── ATTRIBUTIONS LIST ──────────────────────────────────

    function refreshAttributions() {
        var url = '/stocks/api/attributions?scope=' + encodeURIComponent(state.attScope) + '&status=' + encodeURIComponent(state.attStatus);
        auth.apiGet(url, function (err, data) {
            if (err || !data || data.error) {
                $('attList').innerHTML = '<div class="empty-msg">Erreur de chargement.</div>';
                return;
            }
            state.attributions = data.attributions || [];
            renderAttributions();
        });
    }

    function renderAttributions() {
        var el = $('attList');
        if (!state.attributions.length) {
            el.innerHTML = '<div class="empty-msg">Aucune attribution dans ce scope.</div>';
            return;
        }
        el.innerHTML = state.attributions.map(renderAttributionRow).join('');

        el.querySelectorAll('[data-action]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = parseInt(btn.getAttribute('data-id'), 10);
                var action = btn.getAttribute('data-action');
                handleReconcile(id, action);
            });
        });
    }

    function renderAttributionRow(a) {
        var statusClass = a.status || 'open';
        var statusLabel = {
            open: 'En cours',
            pending: 'Attente tresorier',
            reconciled: 'Reconciliee',
            rejected: 'Rejetee'
        }[statusClass] || statusClass;

        var actions = '';
        if (a.status === 'open' || a.status === 'pending') {
            var qs = '?stock_item_id=' + a.stock_item_id + '&quantity=' + a.quantity_abs + '&attribution_id=' + a.id;
            actions =
                '<a class="btn-xs sell" href="/ventes' + qs + '">Vendu</a>' +
                '<button class="btn-xs return" data-action="return" data-id="' + a.id + '">Retour</button>' +
                '<button class="btn-xs loss" data-action="loss" data-id="' + a.id + '">Perte</button>' +
                '<button class="btn-xs gift" data-action="gift" data-id="' + a.id + '">Don</button>';
        }

        return '<div class="att-row">' +
            '<div class="a-item">' + esc(a.item_name) +
                ' <span class="ts-role-badge role-' + esc(a.category || 'misc') + '">' + esc(CATEGORIES[a.category] || a.category) + '</span>' +
            '</div>' +
            '<div class="a-qty">x' + a.quantity_abs + '</div>' +
            '<div class="a-meta">Vers <strong>' + esc(a.attributed_to_name || '?') + '</strong><br>par ' + esc(a.by_name) + ' &middot; ' + esc(a.date) + '</div>' +
            '<div class="a-meta"><span class="a-status ' + statusClass + '">' + esc(statusLabel) + '</span>' +
                (a.estimated_value ? '<br>' + money(a.estimated_value) : '') +
                (a.notes ? '<br><em>' + esc(a.notes) + '</em>' : '') +
            '</div>' +
            '<div class="att-actions">' + actions + '</div>' +
            '</div>';
    }

    function handleReconcile(id, action) {
        var notes = '';
        if (action === 'loss') {
            notes = prompt('Motif de la perte (obligatoire) :', '');
            if (!notes) return;
        } else if (action === 'gift') {
            notes = prompt('Beneficiaire du don (obligatoire) :', '');
            if (!notes) return;
        } else if (action === 'return') {
            notes = prompt('Notes (optionnel) :', '') || '';
        }

        auth.apiPost('/stocks/api/reconcile/' + id, {
            action: action,
            notes: notes || null
        }, function (err, data) {
            if (err || !data || data.error) {
                auth.showToast((data && data.error) || 'Erreur', 'error');
                return;
            }
            auth.showToast(data.message || 'Reconciliee', 'success');
            refreshAttributions();
            tryLoad();
        });
    }

    // ── VALIDATIONS ────────────────────────────────────────

    function refreshValidations() {
        auth.apiGet('/stocks/api/validations', function (err, data) {
            if (err || !data || data.error) {
                $('valList').innerHTML = '<div class="empty-msg">Acces refuse ou erreur.</div>';
                return;
            }
            state.validations = data.validations || [];
            renderValidations();
        });
    }

    function renderValidations() {
        var el = $('valList');
        if (!state.validations.length) {
            el.innerHTML = '<div class="empty-msg">Aucune validation en attente.</div>';
            return;
        }
        el.innerHTML = state.validations.map(function (v) {
            return '<div class="att-row">' +
                '<div class="a-item">' + esc(v.item_name) +
                    ' <span class="ts-role-badge role-' + esc(v.category || 'misc') + '">' + esc(CATEGORIES[v.category] || v.category) + '</span>' +
                '</div>' +
                '<div class="a-qty">x' + v.quantity_abs + '</div>' +
                '<div class="a-meta">Vers <strong>' + esc(v.attributed_to_name || '?') + '</strong><br>par ' + esc(v.by_name) + ' &middot; ' + esc(v.date) + '</div>' +
                '<div class="a-meta">' + (v.estimated_value ? money(v.estimated_value) : '') +
                    (v.notes ? '<br><em>' + esc(v.notes) + '</em>' : '') +
                '</div>' +
                '<div class="att-actions">' +
                    '<button class="btn-xs approve" data-val-action="approve" data-id="' + v.id + '">Approuver</button>' +
                    '<button class="btn-xs reject" data-val-action="reject" data-id="' + v.id + '">Rejeter</button>' +
                '</div>' +
                '</div>';
        }).join('');

        el.querySelectorAll('[data-val-action]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = parseInt(btn.getAttribute('data-id'), 10);
                var act = btn.getAttribute('data-val-action');
                if (act === 'approve') {
                    auth.apiPost('/stocks/api/validations/' + id + '/approve', {}, function (err, data) {
                        if (err || !data || data.error) { auth.showToast((data && data.error) || 'Erreur', 'error'); return; }
                        auth.showToast(data.message, 'success');
                        refreshValidations();
                    });
                } else {
                    var reason = prompt('Motif du rejet (obligatoire) :', '');
                    if (!reason) return;
                    auth.apiPost('/stocks/api/validations/' + id + '/reject', { reason: reason }, function (err, data) {
                        if (err || !data || data.error) { auth.showToast((data && data.error) || 'Erreur', 'error'); return; }
                        auth.showToast(data.message, 'success');
                        refreshValidations();
                        tryLoad();
                    });
                }
            });
        });
    }

    // ── IMPORT ─────────────────────────────────────────────

    function previewImport() {
        var csv = $('impCsv').value || '';
        if (!csv.trim()) { auth.showToast('Collez un CSV d\'abord', 'error'); return; }
        auth.apiPost('/stocks/api/import/preview', { csv: csv }, function (err, data) {
            if (err || !data || data.error) {
                auth.showToast((data && data.error) || 'Erreur', 'error');
                return;
            }
            state.importPreview = data;
            renderImportPreview();
            $('impBtnCommit').disabled = !data.preview || !data.preview.length;
        });
    }

    function renderImportPreview() {
        var el = $('impPreview');
        var errBox = $('impErrors');
        var data = state.importPreview;
        if (!data) { el.innerHTML = ''; errBox.style.display = 'none'; return; }

        if (data.errors && data.errors.length) {
            errBox.style.display = '';
            errBox.innerHTML = data.errors.map(esc).join('<br>');
        } else {
            errBox.style.display = 'none';
        }

        if (!data.preview || !data.preview.length) {
            el.innerHTML = '<div class="empty-msg">Aucune ligne valide.</div>';
            return;
        }

        var html = '<div class="imp-row imp-head">' +
            '<div>Article</div><div>Categorie</div><div>Stock actuel</div><div>Import</div><div>Delta</div></div>';
        data.preview.forEach(function (r) {
            var deltaClass = r.delta > 0 ? 'pos' : (r.delta < 0 ? 'neg' : 'zero');
            var deltaStr = (r.delta > 0 ? '+' : '') + num(r.delta);
            html += '<div class="imp-row">' +
                '<div>' + esc(r.name) + '<br><small style="color:#666;">' + esc(r.slug) + '</small></div>' +
                '<div>' + esc(r.category_label) + '</div>' +
                '<div>' + num(r.current_qty) + '</div>' +
                '<div>' + num(r.import_qty) + '</div>' +
                '<div class="imp-delta ' + deltaClass + '">' + deltaStr + '</div>' +
                '</div>';
        });
        el.innerHTML = html;
    }

    function commitImport() {
        var csv = $('impCsv').value || '';
        var label = ($('impLabel').value || '').trim();
        if (!csv.trim()) return;
        if (!confirm('Appliquer cet import ? Le stock central sera mis a jour.')) return;

        $('impBtnCommit').disabled = true;
        auth.apiPost('/stocks/api/import/commit', { csv: csv, label: label || null }, function (err, data) {
            if (err || !data || data.error) {
                auth.showToast((data && data.error) || 'Erreur', 'error');
                $('impBtnCommit').disabled = false;
                return;
            }
            auth.showToast(data.message, 'success');
            $('impCsv').value = '';
            $('impLabel').value = '';
            state.importPreview = null;
            renderImportPreview();
            tryLoad();
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
                if (target === 'attributions') refreshAttributions();
                if (target === 'validations') refreshValidations();
            });
        });
    }

    // ── EVENTS ─────────────────────────────────────────────

    function bindEvents() {
        $('stockSearch').addEventListener('input', function () {
            state.filter.search = this.value;
            renderList();
        });
        $('stockCategory').addEventListener('change', function () {
            state.filter.category = this.value;
            renderTotals();
            renderList();
        });

        $('aItem').addEventListener('change', updateValueHint);
        $('aQty').addEventListener('input', updateValueHint);
        $('aBtnSave').addEventListener('click', submitAttribution);

        $('attScope').addEventListener('change', function () { state.attScope = this.value; refreshAttributions(); });
        $('attStatus').addEventListener('change', function () { state.attStatus = this.value; refreshAttributions(); });

        $('impBtnPreview').addEventListener('click', previewImport);
        $('impBtnCommit').addEventListener('click', commitImport);

        var listHost = $('stocksList');
        if (listHost) {
            listHost.addEventListener('keydown', function (e) {
                if (e.target.classList.contains('s-qty-inline') && e.key === 'Enter') {
                    e.preventDefault();
                    e.target.blur();
                }
            });
            listHost.addEventListener('focusin', function (e) {
                if (e.target.classList.contains('s-qty-inline')) {
                    e.target.dataset.orig = e.target.value;
                }
            });
            listHost.addEventListener('focusout', function (e) {
                if (!e.target.classList.contains('s-qty-inline')) return;
                var inp = e.target;
                var slug = inp.getAttribute('data-slug');
                var orig = inp.dataset.orig;
                var n = parseInt(inp.value, 10);
                if (isNaN(n)) {
                    inp.value = orig;
                    return;
                }
                if (String(n) === String(orig)) return;
                saveRowQuantity(slug, n, inp, orig);
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
