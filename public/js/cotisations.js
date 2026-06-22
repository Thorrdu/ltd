(function () {
    'use strict';

    var auth = window.McAuth;
    if (!auth) return;

    function $(id) { return document.getElementById(id); }
    function esc(s) { if (s == null) return ''; var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
    function money(n) { return '$' + (parseInt(n, 10) || 0).toLocaleString('fr-FR'); }

    var state = {
        weekOffset: 0,
        cotisations: [],
        isOfficer: false
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
                if (target === 'historique') loadHistory();
            }.bind(tab));
        });
    }

    // ── LOAD WEEK ───────────────────────────────────────────

    function loadWeek() {
        auth.apiGet('/cotisations/api/list?scope=current&week_offset=' + state.weekOffset, function (err, data) {
            if (err || !data || data.error) {
                $('cotWeekList').innerHTML = '<div class="empty-msg">Erreur de chargement.</div>';
                return;
            }
            state.cotisations = data.cotisations || [];
            state.isOfficer = data.is_officer;
            renderStats(data.stats || {});
            $('cotWeekLabel').textContent = data.stats?.week_label || '';
            renderWeek();

            // Alert for unpaid current week
            if (state.weekOffset === 0) {
                var myId = auth.userId;
                var mine = state.cotisations.find(function (c) { return c.user_id == myId; });
                if (mine && !mine.is_paid && !mine.is_exempt) {
                    $('cotAlert').style.display = '';
                    $('cotAlert').className = 'cot-alert cot-alert-warning';
                    $('cotAlert').innerHTML = 'Votre cotisation de cette semaine n\'est pas encore payee. Montant du : <strong>' + money(mine.remaining) + '</strong>';
                } else {
                    $('cotAlert').style.display = 'none';
                }
            } else {
                $('cotAlert').style.display = 'none';
            }
        });
    }

    function renderStats(stats) {
        var el = $('cotStats');
        if (!el) return;
        el.innerHTML =
            '<div class="stat-item"><div class="stat-value">' + (stats.paid_count || 0) + ' / ' + (stats.total_count || 0) + '</div><div class="stat-label">Payes</div></div>' +
            '<div class="stat-item"><div class="stat-value">' + money(stats.total_paid || 0) + '</div><div class="stat-label">Encaisse</div></div>' +
            '<div class="stat-item"><div class="stat-value">' + money((stats.total_due || 0) - (stats.total_paid || 0)) + '</div><div class="stat-label">Reste a percevoir</div></div>' +
            '<div class="stat-item stat-item-global"><div class="stat-value" style="color:#4ade80">' + money(stats.global_paid || 0) + '</div><div class="stat-label">Total cotisations recues</div></div>' +
            '<div class="stat-item stat-item-global"><div class="stat-value" style="color:#f87171">' + money(stats.global_refunds || 0) + '</div><div class="stat-label">Total remboursements</div></div>' +
            '<div class="stat-item stat-item-perso"><div class="stat-value" style="color:#4ade80">' + money(stats.personal_paid || 0) + '</div><div class="stat-label">Mes cotisations versees</div></div>' +
            '<div class="stat-item stat-item-perso"><div class="stat-value" style="color:#f87171">' + money(stats.personal_refunds || 0) + '</div><div class="stat-label">Mes remboursements recus</div></div>';
    }

    function renderWeek() {
        var el = $('cotWeekList');
        if (!state.cotisations.length) {
            el.innerHTML = '<div class="empty-msg">Aucune cotisation pour cette semaine.</div>';
            return;
        }

        var html = '<table class="profil-table cot-table"><thead><tr>' +
            '<th>Membre</th><th>Role</th><th>Du</th><th>Paye</th><th>Statut</th>' +
            (state.isOfficer ? '<th>Actions</th>' : '') +
            '</tr></thead><tbody>';

        state.cotisations.forEach(function (c) {
            var statusHtml = '';
            if (c.is_exempt) {
                statusHtml = '<span class="cot-badge cot-badge-exempt">Exempte</span>';
            } else if (c.is_paid) {
                statusHtml = '<span class="cot-badge cot-badge-paid">Paye</span>';
            } else if (c.is_partial) {
                statusHtml = '<span class="cot-badge cot-badge-partial">Partiel (' + money(c.remaining) + ' restant)</span>';
            } else {
                statusHtml = '<span class="cot-badge cot-badge-unpaid">Non paye</span>';
            }

            var actions = '';
            if (state.isOfficer) {
                if (c.is_exempt) {
                    actions = '<button class="btn-sm btn-cancel cot-btn-unexempt" data-id="' + c.id + '">Retirer exemption</button>';
                } else if (c.is_paid) {
                    actions = '<button class="btn-sm cot-btn-edit" data-id="' + c.id + '" data-paid="' + c.amount_paid + '">Modifier</button>' +
                        '<button class="btn-sm btn-cancel cot-btn-reset" data-id="' + c.id + '">Annuler</button>';
                } else {
                    actions = '<button class="btn-sm btn-approve cot-btn-pay" data-id="' + c.id + '" data-due="' + c.amount_due + '">Payer</button>' +
                        '<button class="btn-sm cot-btn-edit" data-id="' + c.id + '" data-paid="' + c.amount_paid + '">Montant libre</button>' +
                        '<button class="btn-sm cot-btn-exempt" data-id="' + c.id + '">Exempter</button>';
                }
            }

            var markedInfo = '';
            if (c.marked_by) {
                markedInfo = '<br><span style="font-size:10px;color:rgba(255,255,255,0.4)">par ' + esc(c.marked_by) + (c.paid_at ? ' le ' + esc(c.paid_at) : '') + '</span>';
            }

            var nameHtml = state.isOfficer
                ? '<a href="/membres/' + c.user_id + '/profil" style="color:#fff;text-decoration:underline dotted;text-underline-offset:3px">' + esc(c.user_name) + '</a>'
                : esc(c.user_name);

            html += '<tr class="' + (c.is_exempt ? 'cot-row-exempt' : (c.is_paid ? 'cot-row-paid' : (c.is_partial ? 'cot-row-partial' : 'cot-row-unpaid'))) + '">' +
                '<td><strong>' + nameHtml + '</strong></td>' +
                '<td>' + esc(c.role_label) + '</td>' +
                '<td>' + money(c.amount_due) + '</td>' +
                '<td>' + money(c.amount_paid) + markedInfo + '</td>' +
                '<td>' + statusHtml + '</td>' +
                (state.isOfficer ? '<td class="cot-actions">' + actions + '</td>' : '') +
                '</tr>';
        });

        html += '</tbody></table>';
        el.innerHTML = html;

        // Bind pay buttons (full amount)
        el.querySelectorAll('.cot-btn-pay').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = parseInt(this.getAttribute('data-id'));
                var due = parseInt(this.getAttribute('data-due'));
                if (!confirm('Marquer cotisation payee (' + money(due) + ') ?')) return;
                markPaid(id, due);
            });
        });

        // Bind edit buttons (custom amount)
        el.querySelectorAll('.cot-btn-edit').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = parseInt(this.getAttribute('data-id'));
                var current = parseInt(this.getAttribute('data-paid')) || 0;
                var amount = prompt('Montant paye :', current);
                if (amount === null) return;
                amount = parseInt(amount, 10);
                if (isNaN(amount) || amount < 0) { auth.showToast('Montant invalide', 'error'); return; }
                markPaid(id, amount);
            });
        });

        // Bind reset buttons (cancel payment → back to 0)
        el.querySelectorAll('.cot-btn-reset').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = parseInt(this.getAttribute('data-id'));
                if (!confirm('Annuler le paiement de cette cotisation ?')) return;
                markPaid(id, 0);
            });
        });

        // Bind exempt buttons
        el.querySelectorAll('.cot-btn-exempt').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = parseInt(this.getAttribute('data-id'));
                if (!confirm('Exempter ce membre pour cette semaine ?')) return;
                toggleExempt(id);
            });
        });

        // Bind un-exempt buttons
        el.querySelectorAll('.cot-btn-unexempt').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var id = parseInt(this.getAttribute('data-id'));
                if (!confirm('Retirer l\'exemption pour ce membre ?')) return;
                toggleExempt(id);
            });
        });
    }

    function markPaid(id, amount) {
        var notes = '';
        auth.apiPost('/cotisations/api/' + id + '/pay', { amount: amount, notes: notes }, function (err, data) {
            if (err || !data || data.error) {
                auth.showToast(data?.error || 'Erreur', 'error');
                return;
            }
            auth.showToast(data.message || 'OK', 'success');
            loadWeek();
        });
    }

    function toggleExempt(id) {
        auth.apiPost('/cotisations/api/' + id + '/exempt', {}, function (err, data) {
            if (err || !data || data.error) {
                auth.showToast(data?.error || 'Erreur', 'error');
                return;
            }
            auth.showToast(data.message || 'OK', 'success');
            loadWeek();
        });
    }

    // ── LOAD HISTORY ────────────────────────────────────────

    function loadHistory() {
        var memberId = $('cotHistoryMember') ? $('cotHistoryMember').value : '';
        var url = '/cotisations/api/list?scope=history';
        if (memberId) url += '&member_id=' + encodeURIComponent(memberId);

        auth.apiGet(url, function (err, data) {
            if (err || !data || data.error) {
                $('cotHistoryList').innerHTML = '<div class="empty-msg">Erreur de chargement.</div>';
                return;
            }

            if (data.is_officer) {
                $('cotHistoryFilter').style.display = '';
            }

            renderHistory(data.cotisations || []);
        });
    }

    function renderHistory(items) {
        var el = $('cotHistoryList');
        if (!items.length) {
            el.innerHTML = '<div class="empty-msg">Aucun historique.</div>';
            return;
        }

        var html = '<table class="profil-table cot-table"><thead><tr>' +
            '<th>Membre</th><th>Periode</th><th>Du</th><th>Paye</th><th>Statut</th></tr></thead><tbody>';

        items.forEach(function (c) {
            var statusHtml = c.is_exempt
                ? '<span class="cot-badge cot-badge-exempt">Exempte</span>'
                : (c.is_paid
                    ? '<span class="cot-badge cot-badge-paid">Paye</span>'
                    : (c.is_partial
                        ? '<span class="cot-badge cot-badge-partial">Partiel</span>'
                        : '<span class="cot-badge cot-badge-unpaid">Non paye</span>'));

            html += '<tr class="' + (c.is_paid ? 'cot-row-paid' : 'cot-row-unpaid') + '">' +
                '<td>' + esc(c.user_name) + '</td>' +
                '<td>' + esc(c.period_start) + ' - ' + esc(c.period_end) + '</td>' +
                '<td>' + money(c.amount_due) + '</td>' +
                '<td>' + money(c.amount_paid) + '</td>' +
                '<td>' + statusHtml + '</td>' +
                '</tr>';
        });

        html += '</tbody></table>';
        el.innerHTML = html;
    }

    // ── POPULATE MEMBER FILTER ──────────────────────────────

    function populateMemberFilter() {
        var sel = $('cotHistoryMember');
        if (!sel) return;
        var members = window.MC_MEMBERS || [];
        members.forEach(function (m) {
            var opt = document.createElement('option');
            opt.value = m.id;
            opt.textContent = m.name;
            sel.appendChild(opt);
        });
        sel.addEventListener('change', function () { loadHistory(); });
    }

    // ── AUTH GATE ───────────────────────────────────────────

    function tryLoad() {
        if (!auth.isLoggedIn) {
            $('cotNotLogged').style.display = '';
            $('cotNoAccess').style.display = 'none';
            $('cotContent').style.display = 'none';
            return;
        }
        $('cotNotLogged').style.display = 'none';

        auth.apiGet('/cotisations/api/list?scope=current&week_offset=0', function (err, data) {
            if (err && err.status === 403) {
                $('cotNoAccess').style.display = '';
                $('cotContent').style.display = 'none';
                return;
            }
            if (data && data.error) {
                $('cotNoAccess').style.display = '';
                $('cotContent').style.display = 'none';
                return;
            }
            $('cotNoAccess').style.display = 'none';
            $('cotContent').style.display = '';

            state.cotisations = data.cotisations || [];
            state.isOfficer = data.is_officer;
            renderStats(data.stats || {});
            $('cotWeekLabel').textContent = data.stats?.week_label || '';
            renderWeek();

            // Alert check
            var myId = auth.userId;
            var mine = state.cotisations.find(function (c) { return c.user_id == myId; });
            if (mine && !mine.is_paid && !mine.is_exempt) {
                $('cotAlert').style.display = '';
                $('cotAlert').className = 'cot-alert cot-alert-warning';
                $('cotAlert').innerHTML = 'Votre cotisation de cette semaine n\'est pas encore payee. Montant du : <strong>' + money(mine.remaining) + '</strong>';
            }
        });
    }

    // ── INIT ────────────────────────────────────────────────

    initSubTabs();
    populateMemberFilter();

    $('cotPrevWeek').addEventListener('click', function () {
        state.weekOffset--;
        loadWeek();
    });
    $('cotNextWeek').addEventListener('click', function () {
        if (state.weekOffset < 0) {
            state.weekOffset++;
            loadWeek();
        }
    });

    auth.onLogin(tryLoad);
    auth.onLogout(function () {
        $('cotNotLogged').style.display = '';
        $('cotContent').style.display = 'none';
    });
    tryLoad();

})();
