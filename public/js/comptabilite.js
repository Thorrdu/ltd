(function () {
    'use strict';

    var auth = window.McAuth;
    if (!auth) return;

    function $(id) { return document.getElementById(id); }
    function esc(s) { if (s == null) return ''; var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
    function money(n) { return '$' + (parseInt(n, 10) || 0).toLocaleString('fr-FR'); }

    var state = { period: 'week' };

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
                if (target === 'semaines') loadWeekly();
                if (target === 'transactions') loadTransactions();
            }.bind(tab));
        });
    }

    // ── DASHBOARD ───────────────────────────────────────────

    function loadDashboard() {
        auth.apiGet('/comptabilite/api/summary?period=' + state.period, function (err, data) {
            if (err || !data || data.error) return;
            renderSoldes(data.soldes || {});
            renderFlux(data);
        });
    }

    function renderSoldes(s) {
        var el = $('compSoldes');
        el.innerHTML =
            '<div class="stat-item"><div class="stat-value" style="color:#4ade80">' + money(s.argent_sale || 0) + '</div><div class="stat-label">Argent sale</div></div>' +
            '<div class="stat-item"><div class="stat-value" style="color:#60a5fa">' + money(s.argent_propre || 0) + '</div><div class="stat-label">Argent propre</div></div>' +
            '<div class="stat-item"><div class="stat-value">' + money((s.argent_sale || 0) + (s.argent_propre || 0)) + '</div><div class="stat-label">Total</div></div>';
    }

    function renderFlux(data) {
        var el = $('compFlux');
        var v = data.ventes || {};
        var c = data.cotisations || {};
        var d = data.depenses || {};
        var balance = (v.revenue || 0) + (c.paid || 0) - (d.approved_total || 0);

        el.innerHTML =
            '<div class="stat-item"><div class="stat-value" style="color:#4ade80">' + money(v.revenue || 0) + '</div><div class="stat-label">Ventes (' + (v.count || 0) + ')</div></div>' +
            '<div class="stat-item"><div class="stat-value" style="color:#60a5fa">' + money(c.paid || 0) + '</div><div class="stat-label">Cotisations</div></div>' +
            '<div class="stat-item"><div class="stat-value" style="color:#f87171">-' + money(d.approved_total || 0) + '</div><div class="stat-label">Depenses (' + (d.approved_count || 0) + ')</div></div>' +
            '<div class="stat-item"><div class="stat-value" style="color:#fbbf24">' + money(d.pending_total || 0) + '</div><div class="stat-label">En attente (' + (d.pending_count || 0) + ')</div></div>' +
            '<div class="stat-item"><div class="stat-value" style="color:' + (balance >= 0 ? '#4ade80' : '#f87171') + '">' + money(balance) + '</div><div class="stat-label">Balance</div></div>';
    }

    // ── WEEKLY ──────────────────────────────────────────────

    function loadWeekly() {
        auth.apiGet('/comptabilite/api/weekly?weeks=12', function (err, data) {
            if (err || !data || data.error) return;
            renderWeekly(data.weeks || []);
        });
    }

    function renderWeekly(weeks) {
        var el = $('compWeeksTable');
        if (!weeks.length) { el.innerHTML = '<div class="empty-msg">Aucune donnee.</div>'; return; }

        var html = '<table class="profil-table comp-table"><thead><tr>' +
            '<th>Semaine</th><th>Ventes</th><th>Cotisations</th><th>Depenses</th><th>Balance</th></tr></thead><tbody>';

        weeks.forEach(function (w) {
            var balColor = w.balance >= 0 ? '#4ade80' : '#f87171';
            html += '<tr>' +
                '<td>' + esc(w.week_label) + '</td>' +
                '<td style="color:#4ade80">' + money(w.ventes) + '</td>' +
                '<td style="color:#60a5fa">' + money(w.cotisations) + '</td>' +
                '<td style="color:#f87171">' + money(w.depenses) + '</td>' +
                '<td style="color:' + balColor + ';font-weight:700">' + money(w.balance) + '</td>' +
                '</tr>';
        });

        html += '</tbody></table>';
        el.innerHTML = html;
    }

    // ── TRANSACTIONS ────────────────────────────────────────

    function loadTransactions() {
        var type = $('compTxType') ? $('compTxType').value : 'all';
        auth.apiGet('/comptabilite/api/transactions?type=' + encodeURIComponent(type) + '&limit=100', function (err, data) {
            if (err || !data || data.error) return;
            renderTransactions(data.transactions || []);
        });
    }

    function renderTransactions(items) {
        var el = $('compTxList');
        if (!items.length) { el.innerHTML = '<div class="empty-msg">Aucune transaction.</div>'; return; }

        var html = '';
        items.forEach(function (tx) {
            var signColor = tx.sign === '+' ? '#4ade80' : '#f87171';
            var typeLabel = tx.type === 'vente' ? 'Vente' : (tx.type === 'depense' ? 'Depense' : 'Cotisation');
            var typeCls = 'comp-tx-type comp-tx-' + tx.type;

            html += '<div class="comp-tx-row">' +
                '<div class="comp-tx-left">' +
                    '<span class="' + typeCls + '">' + typeLabel + '</span>' +
                    '<span class="comp-tx-label">' + esc(tx.label) + '</span>' +
                '</div>' +
                '<div class="comp-tx-right">' +
                    '<span class="comp-tx-amount" style="color:' + signColor + '">' + tx.sign + money(tx.amount) + '</span>' +
                    '<span class="comp-tx-user">' + esc(tx.user) + '</span>' +
                    '<span class="comp-tx-date">' + esc(tx.date) + '</span>' +
                '</div>' +
                '</div>';
        });

        el.innerHTML = html;
    }

    // ── PERIOD BUTTONS ──────────────────────────────────────

    document.querySelectorAll('.comp-period').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.comp-period').forEach(function (b) { b.classList.remove('active'); });
            this.classList.add('active');
            state.period = this.getAttribute('data-period');
            loadDashboard();
        });
    });

    if ($('compTxType')) {
        $('compTxType').addEventListener('change', function () { loadTransactions(); });
    }

    // ── AUTH GATE ───────────────────────────────────────────

    function tryLoad() {
        if (!auth.isLoggedIn) {
            $('compNotLogged').style.display = '';
            $('compNoAccess').style.display = 'none';
            $('compContent').style.display = 'none';
            return;
        }
        $('compNotLogged').style.display = 'none';

        auth.apiGet('/comptabilite/api/summary?period=' + state.period, function (err, data) {
            if ((err && err.status === 403) || (data && data.error)) {
                $('compNoAccess').style.display = '';
                $('compContent').style.display = 'none';
                return;
            }
            $('compNoAccess').style.display = 'none';
            $('compContent').style.display = '';
            renderSoldes(data.soldes || {});
            renderFlux(data);
        });
    }

    // ── INIT ────────────────────────────────────────────────

    initSubTabs();
    auth.onLogin(tryLoad);
    auth.onLogout(function () {
        $('compNotLogged').style.display = '';
        $('compContent').style.display = 'none';
    });
    tryLoad();

})();
