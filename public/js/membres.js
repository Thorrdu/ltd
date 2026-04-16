(function () {
    'use strict';

    var auth = window.McAuth;
    if (!auth) return;

    function $(id) { return document.getElementById(id); }
    function esc(s) { if (s == null) return ''; var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

    var state = {
        members: [],
        roles: [],
        assignable: [],
        currentUser: null,
        rules: [],
        searchTerm: '',
        roleFilter: ''
    };

    var tomSelectInstances = {};

    function destroyTs(id) {
        if (tomSelectInstances[id]) {
            try { tomSelectInstances[id].destroy(); } catch (e) {}
            delete tomSelectInstances[id];
        }
    }

    function initTs(id, opts) {
        var el = $(id);
        if (!el || typeof TomSelect === 'undefined') return null;
        destroyTs(id);
        tomSelectInstances[id] = new TomSelect(el, Object.assign({
            maxOptions: 500,
            allowEmptyOption: true,
            plugins: ['dropdown_input']
        }, opts || {}));
        return tomSelectInstances[id];
    }

    // ── VISIBILITY GATE ────────────────────────────────────

    function updateGate() {
        var notLogged = $('membresNotLogged');
        var noAccess = $('membresNoAccess');
        var content = $('membresContent');

        if (!auth.isLoggedIn) {
            notLogged.style.display = '';
            noAccess.style.display = 'none';
            content.style.display = 'none';
            return;
        }
        notLogged.style.display = 'none';
        // Acces verifie cote serveur via apiList : on tente et on adapte
        loadData();
    }

    function loadData() {
        auth.apiGet('/membres/api/list', function (err, data) {
            if (err || !data || data.error) {
                $('membresNoAccess').style.display = '';
                $('membresContent').style.display = 'none';
                return;
            }
            $('membresNoAccess').style.display = 'none';
            $('membresContent').style.display = '';
            state.members = data.members || [];
            state.roles = data.roles || [];
            state.assignable = data.assignable_roles || [];
            state.currentUser = data.current_user || null;

            populateRoleFilter();
            populateAssignableSelect();
            renderMembers();
            renderStats();

            if (state.currentUser && state.currentUser.is_superadmin) {
                $('subTabMatrix').style.display = '';
                loadMatrix();
            } else {
                $('subTabMatrix').style.display = 'none';
            }
        });
    }

    function loadMatrix() {
        auth.apiGet('/membres/api/matrix', function (err, data) {
            if (err || !data || data.error) return;
            state.rules = data.rules || [];
            renderMatrix();
        });
    }

    // ── RENDERING ──────────────────────────────────────────

    function populateRoleFilter() {
        var sel = $('memberRoleFilter');
        destroyTs('memberRoleFilter');
        sel.innerHTML = '<option value="">Tous les roles</option>';
        state.roles.forEach(function (r) {
            sel.insertAdjacentHTML('beforeend', '<option value="' + esc(r.key) + '">' + esc(r.label) + '</option>');
        });
        initTs('memberRoleFilter', { placeholder: 'Filtrer par role...', searchField: ['text'] });
        sel.addEventListener('change', function () { state.roleFilter = sel.value; renderMembers(); });
    }

    function populateAssignableSelect() {
        var sel = $('gmNewRole');
        if (!sel) return;
        destroyTs('gmNewRole');
        sel.innerHTML = '';
        state.assignable.forEach(function (r) {
            var opt = document.createElement('option');
            opt.value = r.key;
            opt.textContent = r.label;
            opt.dataset.role = r.key;
            sel.appendChild(opt);
        });
        initTs('gmNewRole', {
            placeholder: 'Role...', allowEmptyOption: false, searchField: ['text'],
            render: {
                option: function (data, escape) {
                    var role = data.$option && data.$option.dataset ? (data.$option.dataset.role || '') : '';
                    return '<div>' + escape(data.text) +
                        '<span class="ts-role-badge role-' + role + '">' + escape(role) + '</span></div>';
                }
            }
        });
    }

    function filteredMembers() {
        var term = (state.searchTerm || '').toLowerCase();
        return state.members.filter(function (m) {
            if (state.roleFilter && m.role !== state.roleFilter) return false;
            if (term && (m.name + ' ' + m.role_label).toLowerCase().indexOf(term) === -1) return false;
            return true;
        });
    }

    function renderStats() {
        var byRole = {};
        state.members.forEach(function (m) {
            byRole[m.role] = (byRole[m.role] || 0) + 1;
        });
        var html = '<span class="stats-chip">Total : <strong>' + state.members.length + '</strong></span>';
        state.roles.forEach(function (r) {
            var n = byRole[r.key] || 0;
            if (n === 0) return;
            html += '<span class="stats-chip role-' + r.key + '">' + esc(r.label) + ' : <strong>' + n + '</strong></span>';
        });
        $('membersStats').innerHTML = html;
    }

    function renderMembers() {
        var list = filteredMembers();
        if (!list.length) {
            $('membersTable').innerHTML = '<div class="empty-msg">Aucun membre ne correspond aux filtres.</div>';
            return;
        }
        var canEdit = state.assignable.length > 0;
        var isSuperadmin = state.currentUser && state.currentUser.is_superadmin;
        var html = '<div class="members-head">'
            + '<div class="mh-name">Membre</div>'
            + '<div class="mh-role">Role</div>'
            + '<div class="mh-status">Statut</div>'
            + '<div class="mh-created">Cree le</div>'
            + '<div class="mh-actions">Actions</div>'
            + '</div>';
        list.forEach(function (m) {
            html += '<div class="members-row" data-mid="' + m.id + '">';
            html += '<div class="mh-name"><span class="ml-name">' + esc(m.name) + '</span>';
            html += '<span class="ml-email">' + esc(m.email || '') + '</span></div>';

            if (canEdit) {
                html += '<div class="mh-role"><select class="fm-input fm-sm member-role-sel" data-mid="' + m.id + '">';
                state.assignable.forEach(function (r) {
                    var sel = r.key === m.role ? ' selected' : '';
                    html += '<option value="' + esc(r.key) + '"' + sel + '>' + esc(r.label) + '</option>';
                });
                if (!state.assignable.some(function (r) { return r.key === m.role; })) {
                    html += '<option value="' + esc(m.role) + '" selected disabled>' + esc(m.role_label) + ' (non modifiable)</option>';
                }
                html += '</select></div>';
            } else {
                html += '<div class="mh-role"><span class="member-badge role-' + m.role + '">' + esc(m.role_label) + '</span></div>';
            }

            html += '<div class="mh-status">';
            if (m.is_active) {
                html += '<span class="status-pill status-active">Actif</span>';
            } else {
                html += '<span class="status-pill status-inactive">Desactive</span>';
            }
            html += '</div>';

            html += '<div class="mh-created">' + esc(m.created_at || '') + '</div>';

            html += '<div class="mh-actions">';
            html += '<button class="action-btn-sm btn-reset-pin" data-mid="' + m.id + '" title="Reinitialiser le PIN">PIN</button>';
            var toggleLabel = m.is_active ? 'Desactiver' : 'Reactiver';
            html += '<button class="action-btn-sm btn-toggle-active" data-mid="' + m.id + '" data-active="' + (m.is_active ? 1 : 0) + '">' + toggleLabel + '</button>';
            if (isSuperadmin && m.id !== state.currentUser.id) {
                html += '<button class="action-btn-sm btn-delete-member" data-mid="' + m.id + '">Supprimer</button>';
            }
            html += '</div>';
            html += '</div>';
        });
        $('membersTable').innerHTML = html;

        // init TomSelect sur chaque role select
        document.querySelectorAll('.member-role-sel').forEach(function (sel) {
            if (typeof TomSelect === 'undefined') return;
            new TomSelect(sel, { maxOptions: 100, searchField: ['text'], plugins: ['dropdown_input'] });
        });
    }

    function renderMatrix() {
        var list = state.rules;
        if (!list.length) {
            $('matrixTable').innerHTML = '<div class="empty-msg">Aucune regle. Relancez le seeder.</div>';
            return;
        }
        var roles = state.roles;
        var html = '<div class="matrix-head">'
            + '<div class="mx-page">Page / Module</div>'
            + '<div class="mx-role">Role minimum</div>'
            + '<div class="mx-desc">Description</div>'
            + '</div>';
        list.forEach(function (r) {
            html += '<div class="matrix-row' + (r.is_system ? ' is-system' : '') + '" data-rid="' + r.id + '">';
            html += '<div class="mx-page"><strong>' + esc(r.label) + '</strong><span class="mx-key">' + esc(r.page_key) + '</span></div>';
            html += '<div class="mx-role"><select class="fm-input fm-sm matrix-role-sel" data-rid="' + r.id + '">';
            roles.forEach(function (role) {
                var sel = role.key === r.min_role ? ' selected' : '';
                html += '<option value="' + esc(role.key) + '"' + sel + '>' + esc(role.label) + '</option>';
            });
            html += '</select></div>';
            html += '<div class="mx-desc">' + esc(r.description || '') + '</div>';
            html += '</div>';
        });
        $('matrixTable').innerHTML = html;

        document.querySelectorAll('.matrix-role-sel').forEach(function (sel) {
            if (typeof TomSelect === 'undefined') return;
            new TomSelect(sel, { maxOptions: 100, searchField: ['text'], plugins: ['dropdown_input'] });
        });
    }

    // ── EVENTS ─────────────────────────────────────────────

    document.addEventListener('input', function (e) {
        if (e.target.id === 'memberSearch') {
            state.searchTerm = e.target.value;
            renderMembers();
        }
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

    // Role change
    document.addEventListener('change', function (e) {
        if (e.target.classList.contains('member-role-sel')) {
            var mid = e.target.getAttribute('data-mid');
            var newRole = e.target.value;
            e.target.disabled = true;
            auth.apiPut('/membres/api/' + mid, { role: newRole }, function (err, data) {
                e.target.disabled = false;
                if (err || (data && data.error)) {
                    auth.showToast((data && data.error) || 'Erreur', 'error');
                    loadData();
                    return;
                }
                auth.showToast(data.message, 'success');
                loadData();
            });
        }
        if (e.target.classList.contains('matrix-role-sel')) {
            var rid = e.target.getAttribute('data-rid');
            var newMinRole = e.target.value;
            e.target.disabled = true;
            auth.apiPut('/membres/api/matrix/' + rid, { min_role: newMinRole }, function (err, data) {
                e.target.disabled = false;
                if (err || (data && data.error)) {
                    auth.showToast((data && data.error) || 'Erreur', 'error');
                    loadMatrix();
                    return;
                }
                auth.showToast(data.message, 'success');
            });
        }
    });

    // Reset PIN
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.btn-reset-pin');
        if (!btn) return;
        var mid = btn.getAttribute('data-mid');
        if (!confirm('Reinitialiser le PIN de ce membre ?')) return;
        btn.disabled = true;
        auth.apiPost('/membres/api/' + mid + '/reset-pin', {}, function (err, data) {
            btn.disabled = false;
            if (err || (data && data.error)) {
                auth.showToast((data && data.error) || 'Erreur', 'error');
                return;
            }
            $('gmPinModalValue').textContent = data.new_pin;
            $('gmPinModal').style.display = '';
            auth.showToast(data.message, 'success');
        });
    });

    // Toggle actif
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.btn-toggle-active');
        if (!btn) return;
        var mid = btn.getAttribute('data-mid');
        var currentlyActive = btn.getAttribute('data-active') === '1';
        btn.disabled = true;
        auth.apiPut('/membres/api/' + mid, { is_active: !currentlyActive }, function (err, data) {
            btn.disabled = false;
            if (err || (data && data.error)) {
                auth.showToast((data && data.error) || 'Erreur', 'error');
                return;
            }
            auth.showToast(data.message, 'success');
            loadData();
        });
    });

    // Delete member
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.btn-delete-member');
        if (!btn) return;
        var mid = btn.getAttribute('data-mid');
        if (!confirm('Supprimer definitivement ce membre ? Action irreversible.')) return;
        btn.disabled = true;
        auth.apiDelete('/membres/api/' + mid, function (err, data) {
            btn.disabled = false;
            if (err || (data && data.error)) {
                auth.showToast((data && data.error) || 'Erreur', 'error');
                return;
            }
            auth.showToast(data.message, 'success');
            loadData();
        });
    });

    // Create
    var btnCreate = $('gmBtnCreate');
    if (btnCreate) {
        btnCreate.addEventListener('click', function () {
            var name = $('gmNewName').value.trim();
            var pin = $('gmNewPin').value.trim();
            var role = $('gmNewRole').value;
            if (!name || !pin || !role) {
                auth.showToast('Nom, PIN et role requis', 'error');
                return;
            }
            btnCreate.disabled = true;
            auth.apiPost('/membres/api/create', { name: name, pin: pin, role: role }, function (err, data) {
                btnCreate.disabled = false;
                if (err || (data && data.error)) {
                    auth.showToast((data && data.error) || 'Erreur', 'error');
                    return;
                }
                auth.showToast(data.message, 'success');
                $('gmNewName').value = '';
                $('gmNewPin').value = '';
                loadData();
            });
        });
    }

    // PIN modal close
    var pinModalClose = $('gmPinModalClose');
    if (pinModalClose) {
        pinModalClose.addEventListener('click', function () {
            $('gmPinModal').style.display = 'none';
            $('gmPinModalValue').textContent = '';
        });
    }

    // ── INIT ───────────────────────────────────────────────
    updateGate();
    auth.onLogin(updateGate);
    auth.onLogout(updateGate);
})();
