(function () {
    'use strict';

    var auth = window.McAuth;
    if (!auth) return;

    function $(id) { return document.getElementById(id); }
    function esc(s) { if (s == null) return ''; var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
    function money(n) { return '$' + (parseInt(n, 10) || 0).toLocaleString('fr-FR'); }

    var state = {
        myRequests: [],
        allRequests: [],
        myFilter: 'all',
        allFilter: 'pending'
    };

    // ── SUB-TABS ────────────────────────────────────────────

    function initSubTabs() {
        var tabs = document.querySelectorAll('.sub-tab');
        var contents = document.querySelectorAll('.sub-tab-content');
        tabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                var target = this.getAttribute('data-subtab');
                tabs.forEach(function (t) { t.classList.remove('active'); });
                this.classList.add('active');
                contents.forEach(function (c) {
                    c.style.display = c.getAttribute('data-subtab') === target ? '' : 'none';
                    if (c.getAttribute('data-subtab') === target) c.classList.add('active');
                    else c.classList.remove('active');
                });
                if (target === 'mine') loadMine();
                if (target === 'all') loadAll();
            }.bind(tab));
        });
    }

    // ── STATUS BADGE ────────────────────────────────────────

    function statusBadge(status, label) {
        var cls = 'req-status req-status-' + status;
        return '<span class="' + cls + '">' + esc(label) + '</span>';
    }

    // ── RENDER ROW ──────────────────────────────────────────

    function renderRow(r, showActions) {
        var actions = '';
        if (showActions && r.status === 'pending') {
            actions = '<div class="req-actions">' +
                '<button class="btn-sm btn-approve" data-id="' + r.id + '">Approuver</button>' +
                '<button class="btn-sm btn-reject" data-id="' + r.id + '">Refuser</button>' +
                '</div>';
        }

        var handlerInfo = '';
        if (r.handler_name) {
            handlerInfo = '<div class="req-handler">' +
                (r.status === 'approved' ? 'Approuve' : 'Refuse') +
                ' par <strong>' + esc(r.handler_name) + '</strong>' +
                (r.handled_at ? ' le ' + esc(r.handled_at) : '') +
                (r.handler_notes ? ' &mdash; <em>' + esc(r.handler_notes) + '</em>' : '') +
                '</div>';
        }

        var photoHtml = '';
        if (r.photo_url) {
            photoHtml = '<div class="req-photo"><a href="' + esc(r.photo_url) + '" target="_blank"><img src="' + esc(r.photo_url) + '" alt="Justificatif"></a></div>';
        }

        return '<div class="req-row req-row-' + r.status + '">' +
            '<div class="req-row-header">' +
                '<div class="req-row-left">' +
                    '<span class="req-cat">' + esc(r.category_label) + '</span>' +
                    '<span class="req-amount">' + money(r.amount) + '</span>' +
                    statusBadge(r.status, r.status_label) +
                '</div>' +
                '<div class="req-row-right">' +
                    '<span class="req-user">' + esc(r.user_name) + '</span>' +
                    '<span class="req-date">' + esc(r.created_at) + '</span>' +
                '</div>' +
            '</div>' +
            '<div class="req-desc">' + esc(r.description) + '</div>' +
            photoHtml +
            handlerInfo +
            actions +
            '</div>';
    }

    // ── LOAD MY REQUESTS ────────────────────────────────────

    function loadMine() {
        var url = '/demandes/api/list?scope=mine&status=' + encodeURIComponent(state.myFilter);
        auth.apiGet(url, function (err, data) {
            if (err || !data || data.error) {
                $('reqMyList').innerHTML = '<div class="empty-msg">Erreur de chargement.</div>';
                return;
            }
            state.myRequests = data.requests || [];
            renderStats(data.stats || {});
            renderMine();
        });
    }

    function renderMine() {
        var el = $('reqMyList');
        if (!state.myRequests.length) {
            el.innerHTML = '<div class="empty-msg">Aucune demande trouvee.</div>';
            return;
        }
        el.innerHTML = state.myRequests.map(function (r) { return renderRow(r, false); }).join('');
    }

    // ── LOAD ALL REQUESTS (treasurer+) ──────────────────────

    function loadAll() {
        var url = '/demandes/api/list?scope=all&status=' + encodeURIComponent(state.allFilter);
        auth.apiGet(url, function (err, data) {
            if (err || !data || data.error) {
                $('reqAllList').innerHTML = '<div class="empty-msg">Erreur de chargement.</div>';
                return;
            }
            state.allRequests = data.requests || [];
            renderStats(data.stats || {});
            renderAll();
        });
    }

    function renderAll() {
        var el = $('reqAllList');
        if (!state.allRequests.length) {
            el.innerHTML = '<div class="empty-msg">Aucune demande trouvee.</div>';
            return;
        }
        el.innerHTML = state.allRequests.map(function (r) { return renderRow(r, true); }).join('');
        bindActions(el);
    }

    function bindActions(container) {
        container.querySelectorAll('.btn-approve').forEach(function (btn) {
            btn.addEventListener('click', function () {
                handleRequest(parseInt(this.getAttribute('data-id')), 'approve');
            });
        });
        container.querySelectorAll('.btn-reject').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = parseInt(this.getAttribute('data-id'));
                var notes = prompt('Raison du refus (optionnel) :');
                if (notes === null) return; // cancelled
                handleRequest(id, 'reject', notes);
            });
        });
    }

    function handleRequest(id, action, notes) {
        auth.apiPost('/demandes/api/' + id + '/handle', {
            action: action,
            notes: notes || ''
        }, function (err, data) {
            if (err || !data || data.error) {
                auth.showToast(data?.error || 'Erreur', 'error');
                return;
            }
            auth.showToast(data.message || 'OK', 'success');
            loadAll();
        });
    }

    // ── STATS ───────────────────────────────────────────────

    function renderStats(stats) {
        var el = $('reqStats');
        if (!el) return;
        var html = '';
        if (stats.pending !== undefined) {
            html += '<div class="stat-item"><div class="stat-value">' + stats.pending + '</div><div class="stat-label">En attente</div></div>';
        }
        if (stats.my_pending !== undefined) {
            html += '<div class="stat-item"><div class="stat-value">' + stats.my_pending + '</div><div class="stat-label">Mes demandes en attente</div></div>';
        }
        el.innerHTML = html;
    }

    // ── PHOTO PREVIEW ───────────────────────────────────────

    function initPhotoUpload() {
        var fileInput = $('reqPhoto');
        var preview = $('reqPreview');
        var previewImg = $('reqPreviewImg');
        var label = $('reqUploadLabel');
        var removeBtn = $('reqRemovePhoto');

        fileInput.addEventListener('change', function () {
            var file = this.files[0];
            if (!file) return;
            if (file.size > 5 * 1024 * 1024) {
                auth.showToast('Image trop volumineuse (max 5 Mo)', 'error');
                this.value = '';
                return;
            }
            var reader = new FileReader();
            reader.onload = function (e) {
                previewImg.src = e.target.result;
                preview.style.display = '';
                label.style.display = 'none';
            };
            reader.readAsDataURL(file);
        });

        removeBtn.addEventListener('click', function () {
            fileInput.value = '';
            preview.style.display = 'none';
            label.style.display = '';
        });
    }

    // ── SUBMIT ──────────────────────────────────────────────

    function submitRequest() {
        var category = $('reqCategory').value;
        var amount = parseInt($('reqAmount').value, 10);
        var description = $('reqDescription').value.trim();

        if (!category) { auth.showToast('Choisissez une categorie', 'error'); return; }
        if (!amount || amount < 1) { auth.showToast('Montant invalide', 'error'); return; }
        if (!description) { auth.showToast('Decrivez votre demande', 'error'); return; }

        var fd = new FormData();
        fd.append('category', category);
        fd.append('amount', amount);
        fd.append('description', description);
        var photoFile = $('reqPhoto').files[0];
        if (photoFile) fd.append('photo', photoFile);

        var headers = auth.apiHeaders();
        headers['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        // Do NOT set Content-Type — browser sets multipart boundary automatically

        fetch('/demandes/api/create', { method: 'POST', headers: headers, body: fd })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.error) {
                    auth.showToast(data.error, 'error');
                    return;
                }
                auth.showToast(data.message || 'Demande soumise', 'success');
                $('reqCategory').value = '';
                $('reqAmount').value = '';
                $('reqDescription').value = '';
                $('reqPhoto').value = '';
                $('reqPreview').style.display = 'none';
                $('reqUploadLabel').style.display = '';
            })
            .catch(function () { auth.showToast('Erreur reseau', 'error'); });
    }

    // ── AUTH GATE ───────────────────────────────────────────

    function tryLoad() {
        if (!auth.isLoggedIn) {
            $('reqNotLogged').style.display = '';
            $('reqNoAccess').style.display = 'none';
            $('reqContent').style.display = 'none';
            return;
        }
        $('reqNotLogged').style.display = 'none';

        auth.apiGet('/demandes/api/list?scope=mine&status=all', function (err, data) {
            if (err && err.status === 403) {
                $('reqNoAccess').style.display = '';
                $('reqContent').style.display = 'none';
                return;
            }
            $('reqNoAccess').style.display = 'none';
            $('reqContent').style.display = '';

            // Show "all" tab for treasurer+
            if (auth.isAtLeast('treasurer')) {
                document.querySelectorAll('.sub-tab-treasurer').forEach(function (el) { el.style.display = ''; });
            }

            if (data && !data.error) {
                state.myRequests = data.requests || [];
                renderStats(data.stats || {});
            }
        });
    }

    // ── INIT ────────────────────────────────────────────────

    initSubTabs();
    initPhotoUpload();
    $('reqBtnSubmit').addEventListener('click', submitRequest);
    $('reqMyStatus').addEventListener('change', function () {
        state.myFilter = this.value;
        loadMine();
    });
    $('reqAllStatus').addEventListener('change', function () {
        state.allFilter = this.value;
        loadAll();
    });

    auth.onLogin(tryLoad);
    auth.onLogout(function () {
        $('reqNotLogged').style.display = '';
        $('reqContent').style.display = 'none';
    });

    tryLoad();

})();
