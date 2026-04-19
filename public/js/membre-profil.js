(function () {
    'use strict';

    var auth = window.McAuth;
    if (!auth) return;

    var memberId = window.PROFIL_MEMBER_ID;

    function $(id) { return document.getElementById(id); }
    function esc(s) { if (s == null) return ''; var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
    function money(n) { return '$' + (parseInt(n, 10) || 0).toLocaleString('fr-FR'); }

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
            }.bind(tab));
        });
    }

    // ── ROLE COLORS ─────────────────────────────────────────

    var roleColors = {
        prospect: '#94a3b8', member: '#60a5fa', officer: '#fbbf24',
        vice_president: '#fb923c', president: '#f87171', treasurer: '#a78bfa'
    };

    // ── RENDER ──────────────────────────────────────────────

    function render(data) {
        var info = data.info;
        var color = roleColors[info.role] || '#fff';

        $('profilTitle').textContent = info.name;

        $('profilInfo').innerHTML =
            '<div class="profil-info-row">' +
                '<span class="profil-info-label">Role</span>' +
                '<span class="profil-info-value" style="color:' + color + '">' + esc(info.role_label) + '</span>' +
            '</div>' +
            '<div class="profil-info-row">' +
                '<span class="profil-info-label">Statut</span>' +
                '<span class="profil-info-value">' + (info.is_active ? '<span style="color:#4ade80">Actif</span>' : '<span style="color:#f87171">Inactif</span>') + '</span>' +
            '</div>' +
            '<div class="profil-info-row">' +
                '<span class="profil-info-label">Membre depuis</span>' +
                '<span class="profil-info-value">' + esc(info.created_at || '?') + '</span>' +
            '</div>';

        // Stats
        var st = data.sales_totals;
        $('profilStats').innerHTML =
            '<div class="stat-item"><div class="stat-value">' + money(st.total_revenue) + '</div><div class="stat-label">CA total</div></div>' +
            '<div class="stat-item"><div class="stat-value">' + money(st.week_revenue) + '</div><div class="stat-label">Cette semaine</div></div>' +
            '<div class="stat-item"><div class="stat-value">' + money(st.month_revenue) + '</div><div class="stat-label">Ce mois</div></div>' +
            '<div class="stat-item"><div class="stat-value">' + st.total_count + '</div><div class="stat-label">Ventes totales</div></div>';

        // Attributions (en possession)
        renderAttributions(data.attributions);
        renderVentes(data.sales);
        renderMouvements(data.movements);
        renderCotisations(data.cotisations);
        renderDemandes(data.demandes);
    }

    function renderAttributions(items) {
        var el = $('profilAttributions');
        if (!items || !items.length) { el.innerHTML = '<div class="empty-msg">Aucun article en possession.</div>'; return; }
        var html = '<table class="profil-table"><thead><tr><th>Article</th><th>Categorie</th><th>Qte</th><th>Date</th></tr></thead><tbody>';
        items.forEach(function (a) {
            html += '<tr><td>' + esc(a.item_name) + '</td><td>' + esc(a.category) + '</td><td>' + a.quantity + '</td><td>' + esc(a.date) + '</td></tr>';
        });
        html += '</tbody></table>';
        el.innerHTML = html;
    }

    function renderVentes(items) {
        var el = $('profilVentes');
        if (!items || !items.length) { el.innerHTML = '<div class="empty-msg">Aucune vente.</div>'; return; }
        var html = '<table class="profil-table"><thead><tr><th>Article</th><th>Qte</th><th>Total</th><th>Acheteur</th><th>Date</th></tr></thead><tbody>';
        items.forEach(function (s) {
            html += '<tr><td>' + esc(s.item_name) + '</td><td>' + s.quantity + '</td><td>' + money(s.total) + '</td><td>' + esc(s.buyer) + '</td><td>' + esc(s.date) + '</td></tr>';
        });
        html += '</tbody></table>';
        el.innerHTML = html;
    }

    function renderMouvements(items) {
        var el = $('profilMouvements');
        if (!items || !items.length) { el.innerHTML = '<div class="empty-msg">Aucun mouvement.</div>'; return; }
        var html = '<table class="profil-table"><thead><tr><th>Article</th><th>Qte</th><th>Raison</th><th>Notes</th><th>Date</th></tr></thead><tbody>';
        items.forEach(function (m) {
            var qtyClass = m.qty > 0 ? 'profil-qty-pos' : (m.qty < 0 ? 'profil-qty-neg' : '');
            html += '<tr><td>' + esc(m.item_name) + '</td><td class="' + qtyClass + '">' + (m.qty > 0 ? '+' : '') + m.qty + '</td><td>' + esc(m.reason) + '</td><td>' + esc(m.notes || '') + '</td><td>' + esc(m.date) + '</td></tr>';
        });
        html += '</tbody></table>';
        el.innerHTML = html;
    }

    function renderCotisations(items) {
        var el = $('profilCotisations');
        if (!items || !items.length) { el.innerHTML = '<div class="empty-msg">Aucune cotisation.</div>'; return; }
        var html = '<table class="profil-table"><thead><tr><th>Periode</th><th>Du</th><th>Paye</th><th>Statut</th></tr></thead><tbody>';
        items.forEach(function (c) {
            var status = c.is_paid ? '<span style="color:#4ade80">Paye</span>' :
                         (c.remaining > 0 ? '<span style="color:#fbbf24">' + money(c.remaining) + ' restant</span>' : '<span style="color:#f87171">Non paye</span>');
            html += '<tr><td>' + esc(c.period) + '</td><td>' + money(c.amount_due) + '</td><td>' + money(c.amount_paid) + '</td><td>' + status + '</td></tr>';
        });
        html += '</tbody></table>';
        el.innerHTML = html;
    }

    function renderDemandes(items) {
        var el = $('profilDemandes');
        if (!items || !items.length) { el.innerHTML = '<div class="empty-msg">Aucune demande.</div>'; return; }
        var html = '<table class="profil-table"><thead><tr><th>Categorie</th><th>Montant</th><th>Statut</th><th>Date</th></tr></thead><tbody>';
        items.forEach(function (d) {
            var statusCls = 'req-status req-status-' + d.status;
            html += '<tr><td>' + esc(d.category_label) + '</td><td>' + money(d.amount) + '</td><td><span class="' + statusCls + '">' + esc(d.status_label) + '</span></td><td>' + esc(d.date) + '</td></tr>';
        });
        html += '</tbody></table>';
        el.innerHTML = html;
    }

    // ── AUTH GATE ───────────────────────────────────────────

    function tryLoad() {
        if (!auth.isLoggedIn) {
            $('profilNotLogged').style.display = '';
            $('profilNoAccess').style.display = 'none';
            $('profilContent').style.display = 'none';
            return;
        }
        $('profilNotLogged').style.display = 'none';

        auth.apiGet('/membres/api/' + memberId + '/profile', function (err, data) {
            if (err || !data || data.error) {
                $('profilNoAccess').style.display = '';
                $('profilContent').style.display = 'none';
                return;
            }
            $('profilNoAccess').style.display = 'none';
            $('profilContent').style.display = '';
            render(data);
        });
    }

    // ── INIT ────────────────────────────────────────────────

    initSubTabs();
    auth.onLogin(tryLoad);
    auth.onLogout(function () {
        $('profilNotLogged').style.display = '';
        $('profilContent').style.display = 'none';
    });
    tryLoad();

})();
