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
    var mvItemTs = null;

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
            populateMovementForm();

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
                    '<div class="s-actions">' +
                        (it.is_sellable ? '<a class="btn-xs sell" href="/ventes?stock_item_id=' + it.id + '">Vendre</a>' : '') +
                        '<button class="btn-xs return s-attr-btn" data-id="' + it.id + '" data-name="' + esc(it.name) + '">Attribuer</button>' +
                        '<a class="btn-xs" href="/stocks/' + esc(it.slug) + '">Detail</a>' +
                    '</div>' +
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
        var ext = $('aFromExternal') && $('aFromExternal').checked;
        if (ext && price && qty) {
            hint.innerHTML = 'Ref. valeur (hors coffre) : ' + money(price * qty) + ' — aucune sortie de stock';
        } else if (price && qty) {
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
            notes: notes || null,
            from_external: $('aFromExternal') && $('aFromExternal').checked
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
        var ext = $('aFromExternal');
        if (ext) ext.checked = false;
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
            toggleBulkBar();
            return;
        }
        el.innerHTML = state.attributions.map(renderAttributionRow).join('');

        el.querySelectorAll('[data-action]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = parseInt(btn.getAttribute('data-id'), 10);
                var action = btn.getAttribute('data-action');
                var maxQ = parseInt(btn.getAttribute('data-max'), 10) || 0;
                handleReconcile(id, action, maxQ);
            });
        });

        // Checkbox events
        el.querySelectorAll('.att-check').forEach(function (cb) {
            cb.addEventListener('change', toggleBulkBar);
        });
        var checkAll = $('attCheckAll');
        if (checkAll) {
            checkAll.addEventListener('change', function () {
                el.querySelectorAll('.att-check').forEach(function (cb) { cb.checked = checkAll.checked; });
                toggleBulkBar();
            });
        }
        toggleBulkBar();
    }

    function toggleBulkBar() {
        var bar = $('attBulkBar');
        if (!bar) return;
        var checked = document.querySelectorAll('.att-check:checked');
        bar.style.display = checked.length > 0 ? 'flex' : 'none';
        var countEl = $('attBulkCount');
        if (countEl) countEl.textContent = checked.length + ' selectionnee(s)';
    }

    function renderAttributionRow(a) {
        var statusClass = a.status || 'open';
        var statusLabel = {
            open: 'En cours',
            pending: 'Attente tresorier',
            reconciled: 'Reconciliee',
            rejected: 'Rejetee'
        }[statusClass] || statusClass;

        var isOpen = a.status === 'open' || a.status === 'pending';
        var checkbox = isOpen ? '<input type="checkbox" class="att-check" data-id="' + a.id + '" data-max="' + a.quantity_abs + '">' : '';

        var actions = '';
        if (isOpen) {
            var qs = '?stock_item_id=' + a.stock_item_id + '&quantity=' + a.quantity_abs + '&attribution_id=' + a.id;
            actions =
                '<a class="btn-xs sell" href="/ventes' + qs + '">Vendu</a>' +
                '<button class="btn-xs return" data-action="return" data-id="' + a.id + '" data-max="' + a.quantity_abs + '">Retour</button>' +
                '<button class="btn-xs" style="background:rgba(255,165,0,0.12);color:#ffa500;" data-action="cancel" data-id="' + a.id + '" data-max="' + a.quantity_abs + '">Annuler</button>' +
                '<button class="btn-xs" style="background:rgba(100,200,100,0.12);color:#6c6;" data-action="already" data-id="' + a.id + '" data-max="' + a.quantity_abs + '">Deja en stock</button>' +
                '<button class="btn-xs loss" data-action="loss" data-id="' + a.id + '" data-max="' + a.quantity_abs + '">Perte</button>' +
                '<button class="btn-xs gift" data-action="gift" data-id="' + a.id + '" data-max="' + a.quantity_abs + '">Don</button>';
        }

        var extBadge = a.from_external ? ' <span class="a-status pending">Hors stock</span>' : '';

        return '<div class="att-row">' +
            '<div class="a-check">' + checkbox + '</div>' +
            '<div class="a-item">' + esc(a.item_name) +
                ' <span class="ts-role-badge role-' + esc(a.category || 'misc') + '">' + esc(CATEGORIES[a.category] || a.category) + '</span>' +
                extBadge +
            '</div>' +
            '<div class="a-qty">x' + a.quantity_abs + '</div>' +
            '<div class="a-meta">Vers <strong><a href="/membres/' + a.attributed_to_id + '/profil" style="color:#fff;text-decoration:underline dotted;text-underline-offset:3px">' + esc(a.attributed_to_name || '?') + '</a></strong><br>par ' + esc(a.by_name) + ' &middot; ' + esc(a.date) + '</div>' +
            '<div class="a-meta"><span class="a-status ' + statusClass + '">' + esc(statusLabel) + '</span>' +
                (a.estimated_value ? '<br>' + money(a.estimated_value) : '') +
                (a.notes ? '<br><em>' + esc(a.notes) + '</em>' : '') +
            '</div>' +
            '<div class="att-actions">' + actions + '</div>' +
            '</div>';
    }

    function handleReconcile(id, action, maxQty) {
        // "cancel" = return with auto-notes "Annulation"
        // "already" = zero-stock-change reconcile with auto-notes "Deja en stock"
        if (action === 'cancel') {
            var notes = prompt('Motif d\'annulation (optionnel) :', '') || 'Annulation';
            auth.apiPost('/stocks/api/reconcile/' + id, {
                action: 'return',
                notes: notes,
                quantity: maxQty
            }, function (err, data) {
                if (err || !data || data.error) { auth.showToast((data && data.error) || 'Erreur', 'error'); return; }
                auth.showToast(data.message || 'Annulee', 'success');
                refreshAttributions();
                tryLoad();
            });
            return;
        }

        if (action === 'already') {
            var notes2 = prompt('Notes (optionnel) :', '') || 'Deja de retour en stock (reconciliation administrative)';
            auth.apiPost('/stocks/api/reconcile/' + id, {
                action: 'loss',
                notes: notes2,
                quantity: maxQty
            }, function (err, data) {
                if (err || !data || data.error) { auth.showToast((data && data.error) || 'Erreur', 'error'); return; }
                auth.showToast('Attribution marquee comme deja en stock', 'success');
                refreshAttributions();
                tryLoad();
            });
            return;
        }

        var qPrompt = 'Quantite concernee (max ' + maxQty + ') :';
        var qtyStr = prompt(qPrompt, String(maxQty));
        if (qtyStr === null) return;
        var qty = parseInt(qtyStr, 10);
        if (!qty || qty < 1 || qty > maxQty) {
            auth.showToast('Quantite invalide (1 a ' + maxQty + ')', 'error');
            return;
        }

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
            notes: notes || null,
            quantity: qty
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

    // ── MOVEMENT FORM (Phase 3B.1) ────────────────────────

    function populateMovementForm() {
        var sel = $('mItem');
        if (!sel) return;
        if (mvItemTs) { try { mvItemTs.destroy(); } catch (e) {} mvItemTs = null; }
        sel.innerHTML = '<option value="">-- Choisir un article --</option>';
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
                og.appendChild(opt);
            });
            sel.appendChild(og);
        });
        if (typeof TomSelect !== 'undefined') {
            mvItemTs = new TomSelect(sel, {
                placeholder: 'Rechercher un article...',
                searchField: ['text'],
                maxOptions: 500,
                plugins: ['dropdown_input']
            });
        }
    }

    function initMovementForm() {
        var dirIn = $('mDirIn');
        var dirOut = $('mDirOut');
        var reasonEl = $('mReason');
        var costRow = $('mCostRow');

        if (dirIn) dirIn.addEventListener('click', function () {
            dirIn.classList.add('active'); dirOut.classList.remove('active');
        });
        if (dirOut) dirOut.addEventListener('click', function () {
            dirOut.classList.add('active'); dirIn.classList.remove('active');
        });
        if (reasonEl) reasonEl.addEventListener('change', function () {
            costRow.style.display = this.value === 'purchase' ? '' : 'none';
        });

        var costEl = $('mUnitCost');
        var qtyEl = $('mQty');
        var preview = $('mCostPreview');
        function updateCostPreview() {
            if (!preview) return;
            var uc = parseInt(costEl.value, 10) || 0;
            var q = parseInt(qtyEl.value, 10) || 0;
            preview.innerHTML = uc && q ? 'Total : ' + money(uc * q) : '';
        }
        if (costEl) costEl.addEventListener('input', updateCostPreview);
        if (qtyEl) qtyEl.addEventListener('input', updateCostPreview);

        var btn = $('mBtnSave');
        if (btn) btn.addEventListener('click', function () {
            var itemId = parseInt($('mItem').value, 10);
            var qty = parseInt($('mQty').value, 10);
            var direction = $('mDirIn').classList.contains('active') ? 'in' : 'out';
            var reason = $('mReason').value;
            var unitCost = parseInt($('mUnitCost').value, 10) || 0;
            var notes = ($('mNotes').value || '').trim();

            if (!itemId) { auth.showToast('Choisissez un article', 'error'); return; }
            if (!qty || qty < 1) { auth.showToast('Quantite invalide', 'error'); return; }

            btn.disabled = true;
            btn.textContent = 'Enregistrement...';

            auth.apiPost('/stocks/api/movement', {
                stock_item_id: itemId,
                quantity: qty,
                direction: direction,
                reason: reason,
                unit_cost: reason === 'purchase' ? unitCost : null,
                notes: notes || null
            }, function (err, data) {
                btn.disabled = false;
                btn.textContent = 'Enregistrer le mouvement';
                if (err || !data || data.error) {
                    var msg = (data && data.error) || 'Erreur';
                    if (data && data.messages) msg = Object.values(data.messages).flat().join(' | ');
                    auth.showToast(msg, 'error');
                    return;
                }
                auth.showToast(data.message || 'Mouvement enregistre', 'success');
                if (data.warning) setTimeout(function () { auth.showToast(data.warning, 'error'); }, 900);
                // Reset form
                $('mQty').value = '1';
                $('mNotes').value = '';
                $('mUnitCost').value = '0';
                if (mvItemTs) mvItemTs.clear();
                if (preview) preview.innerHTML = '';
                tryLoad();
            });
        });
    }

    // ── CREATE ITEM (Phase 3B.1) ───────────────────────────

    function initCreateItem() {
        var formEl = $('newItemForm');
        var btnNew = $('btnNewItem');
        var btnCancel = $('niBtnCancel');
        var btnSave = $('niBtnSave');
        if (!formEl || !btnNew) return;

        btnNew.addEventListener('click', function () {
            formEl.style.display = formEl.style.display === 'none' ? '' : 'none';
        });
        if (btnCancel) btnCancel.addEventListener('click', function () {
            formEl.style.display = 'none';
        });
        if (btnSave) btnSave.addEventListener('click', function () {
            var name = ($('niName').value || '').trim();
            var category = $('niCategory').value;
            var qty = parseInt($('niQty').value, 10) || 0;
            var sell = parseInt($('niSellPrice').value, 10) || null;
            var purch = parseInt($('niPurchPrice').value, 10) || null;
            var weight = parseInt($('niWeight').value, 10) || null;
            var notes = ($('niNotes').value || '').trim();

            if (!name) { auth.showToast('Nom requis', 'error'); return; }

            btnSave.disabled = true;
            btnSave.textContent = 'Creation...';

            auth.apiPost('/stocks/api/create-item', {
                name: name,
                category: category,
                quantity: qty > 0 ? qty : 0,
                default_sell_price: sell,
                default_purchase_price: purch,
                unit_weight_g: weight,
                notes: notes || null
            }, function (err, data) {
                btnSave.disabled = false;
                btnSave.textContent = 'Creer l\'article';
                if (err || !data || data.error) {
                    var msg = (data && data.error) || 'Erreur';
                    if (data && data.messages) msg = Object.values(data.messages).flat().join(' | ');
                    auth.showToast(msg, 'error');
                    return;
                }
                auth.showToast(data.message || 'Article cree', 'success');
                formEl.style.display = 'none';
                $('niName').value = ''; $('niQty').value = '0';
                $('niSellPrice').value = '0'; $('niPurchPrice').value = '0';
                $('niWeight').value = '0'; $('niNotes').value = '';
                tryLoad();
            });
        });
    }

    // ── QUICK ATTRIBUTE FROM OVERVIEW ROW ──────────────────

    function initQuickAttribute() {
        var listHost = $('stocksList');
        if (!listHost) return;
        listHost.addEventListener('click', function (e) {
            var btn = e.target.closest('.s-attr-btn');
            if (!btn) return;
            var itemId = parseInt(btn.getAttribute('data-id'), 10);
            var itemName = btn.getAttribute('data-name') || '?';
            // Switch to the Attribuer tab and pre-select the item
            document.querySelectorAll('.sub-tab').forEach(function (b) { b.classList.remove('active'); });
            document.querySelectorAll('.sub-content').forEach(function (c) { c.classList.remove('active'); });
            var tabBtn = document.querySelector('.sub-tab[data-subtab="attribute"]');
            if (tabBtn) tabBtn.classList.add('active');
            var tabContent = $('sub-attribute');
            if (tabContent) tabContent.classList.add('active');
            // Pre-select item in the attribution Tom Select
            if (itemTs) {
                itemTs.setValue(String(itemId), true);
            }
            updateValueHint();
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
                if (target === 'movement') populateMovementForm();
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
        var aExt = $('aFromExternal');
        if (aExt) aExt.addEventListener('change', updateValueHint);
        $('aBtnSave').addEventListener('click', submitAttribution);

        $('attScope').addEventListener('change', function () { state.attScope = this.value; refreshAttributions(); });
        $('attStatus').addEventListener('change', function () { state.attStatus = this.value; refreshAttributions(); });

        // Bulk actions
        function bulkReconcile(action) {
            var checked = document.querySelectorAll('.att-check:checked');
            if (!checked.length) { auth.showToast('Selectionnez au moins une attribution', 'error'); return; }
            var motif = ($('attBulkMotif').value || '').trim();
            if (action === 'loss' && !motif) { auth.showToast('Motif obligatoire pour une perte', 'error'); return; }

            var defaultNotes = {
                'return': motif || 'Retour groupé',
                'cancel': motif || 'Annulation groupée',
                'loss': motif,
                'already': motif || 'Deja en stock (reconciliation groupée)'
            };

            var ids = [];
            checked.forEach(function (cb) {
                ids.push({ id: parseInt(cb.getAttribute('data-id'), 10), max: parseInt(cb.getAttribute('data-max'), 10) });
            });

            var remaining = ids.length;
            var errors = 0;
            ids.forEach(function (item) {
                var apiAction = (action === 'cancel') ? 'return' : (action === 'already' ? 'loss' : action);
                auth.apiPost('/stocks/api/reconcile/' + item.id, {
                    action: apiAction,
                    notes: defaultNotes[action] || motif || null,
                    quantity: item.max
                }, function (err, data) {
                    remaining--;
                    if (err || !data || data.error) errors++;
                    if (remaining <= 0) {
                        var msg = (ids.length - errors) + '/' + ids.length + ' attribution(s) traitee(s)';
                        auth.showToast(msg, errors > 0 ? 'error' : 'success');
                        $('attBulkMotif').value = '';
                        refreshAttributions();
                        tryLoad();
                    }
                });
            });
        }

        var bulkReturn = $('attBulkReturn');
        var bulkCancel = $('attBulkCancel');
        var bulkLoss = $('attBulkLoss');
        var bulkAlready = $('attBulkAlready');
        if (bulkReturn) bulkReturn.addEventListener('click', function () { bulkReconcile('return'); });
        if (bulkCancel) bulkCancel.addEventListener('click', function () { bulkReconcile('cancel'); });
        if (bulkLoss) bulkLoss.addEventListener('click', function () { bulkReconcile('loss'); });
        if (bulkAlready) bulkAlready.addEventListener('click', function () { bulkReconcile('already'); });

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
    initMovementForm();
    initCreateItem();
    initQuickAttribute();
    updateGate();
    auth.onLogin(updateGate);
    auth.onLogout(updateGate);
})();
