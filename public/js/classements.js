(function () {
    'use strict';

    var auth = window.McAuth;
    if (!auth) return;

    function $(id) { return document.getElementById(id); }
    function esc(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
    function money(n) { return (n || 0).toLocaleString('fr-FR') + ' $'; }

    var CATEGORY_LABELS = {
        weapon_finished: 'Armes',
        weapon_plan:     'Plans d\'armes',
        weapon_piece:    'Pieces d\'armes',
        raw_material:    'Matieres premieres',
        ammo:            'Munitions',
        melee:           'Armes blanches',
        drug:            'Drogues',
        drug_raw:        'Drogues (matieres)',
        farm_consumable: 'Consommables ferme',
        tool:            'Outils',
        electronic:      'Electronique',
        argent:          'Argent',
        misc:            'Divers'
    };

    var CRITERIA_LABELS = {
        revenue:  'Chiffre d\'affaires',
        count:    'Nombre de ventes',
        quantity: 'Quantite vendue'
    };

    var ALL_CATEGORIES = window.MC_RANK_CATEGORIES || [];

    var state = {
        period: 'week',
        rankings: [],
        eagle: null,
        criteria: 'revenue',
        configCategories: [],
        configCriteria: 'revenue'
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
                if (target === 'eagles') loadEagleHistory();
                if (target === 'config') loadConfig();
            }.bind(tab));
        });
    }

    // ── RANKINGS ────────────────────────────────────────────

    function loadRankings() {
        var url = '/classements/api/rankings?period=' + encodeURIComponent(state.period);
        auth.apiGet(url, function (err, data) {
            if (err || !data || data.error) {
                $('rankTable').innerHTML = '<div class="empty-msg">Erreur de chargement.</div>';
                return;
            }
            state.rankings = data.rankings || [];
            state.eagle = data.eagle || null;
            state.criteria = data.criteria || 'revenue';
            renderRankings();
            renderEagle();
        });
    }

    function renderRankings() {
        var el = $('rankTable');
        var label = $('rankCriteriaLabel');
        label.textContent = 'Classement par : ' + (CRITERIA_LABELS[state.criteria] || state.criteria);

        if (!state.rankings.length) {
            el.innerHTML = '<div class="empty-msg">Aucune vente sur cette periode.</div>';
            return;
        }

        var myId = parseInt(sessionStorage.getItem('lmc_uid') || '0');
        var myFound = false;

        var html = '<div class="rank-header-row">' +
            '<div class="rank-col-pos">#</div>' +
            '<div class="rank-col-name">Membre</div>' +
            '<div class="rank-col-stat">Ventes</div>' +
            '<div class="rank-col-stat">Quantite</div>' +
            '<div class="rank-col-stat">CA</div>' +
            '</div>';

        state.rankings.forEach(function (r) {
            var isMe = r.user_id === myId;
            if (isMe) myFound = true;
            var medalClass = '';
            if (r.rank === 1) medalClass = 'rank-gold';
            else if (r.rank === 2) medalClass = 'rank-silver';
            else if (r.rank === 3) medalClass = 'rank-bronze';

            var isEagle = state.eagle && state.eagle.user_id === r.user_id && state.period === 'week';

            html += '<div class="rank-row ' + medalClass + (isMe ? ' rank-me' : '') + '">' +
                '<div class="rank-col-pos">' + r.rank + (isEagle ? ' <span class="rank-eagle-mini">&#x1F985;</span>' : '') + '</div>' +
                '<div class="rank-col-name">' +
                    '<span class="rank-member-name">' + esc(r.name) + '</span>' +
                    '<span class="ts-role-badge role-' + esc(r.role) + '">' + esc(r.role) + '</span>' +
                '</div>' +
                '<div class="rank-col-stat">' + r.sale_count + '</div>' +
                '<div class="rank-col-stat">' + r.total_quantity + '</div>' +
                '<div class="rank-col-stat rank-col-revenue">' + money(r.total_revenue) + '</div>' +
                '</div>';
        });

        // If the logged-in user wasn't in the list, show a separator
        if (myId && !myFound) {
            html += '<div class="rank-row rank-separator"><div class="rank-col-pos">...</div>' +
                '<div class="rank-col-name">Vous n\'apparaissez pas dans ce classement</div>' +
                '<div class="rank-col-stat">-</div><div class="rank-col-stat">-</div><div class="rank-col-stat">-</div></div>';
        }

        el.innerHTML = html;
    }

    function renderEagle() {
        var banner = $('rankEagleBanner');
        if (!state.eagle) {
            banner.style.display = 'none';
            return;
        }
        banner.style.display = '';
        $('rankEagleName').textContent = state.eagle.name;
        var scoreLabel = '';
        if (state.eagle.criteria === 'revenue') scoreLabel = money(state.eagle.score);
        else scoreLabel = state.eagle.score + (state.eagle.criteria === 'count' ? ' ventes' : ' unites');
        $('rankEagleScore').textContent = scoreLabel;
    }

    // ── PERIOD SELECTOR ─────────────────────────────────────

    function initPeriodButtons() {
        document.querySelectorAll('.rank-period-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                document.querySelectorAll('.rank-period-btn').forEach(function (b) { b.classList.remove('active'); });
                this.classList.add('active');
                state.period = this.getAttribute('data-period');
                loadRankings();
            });
        });
    }

    // ── EAGLE HISTORY ───────────────────────────────────────

    function loadEagleHistory() {
        auth.apiGet('/classements/api/eagle-history', function (err, data) {
            if (err || !data || data.error) {
                $('rankEagleHistory').innerHTML = '<div class="empty-msg">Erreur de chargement.</div>';
                return;
            }
            var history = data.history || [];
            if (!history.length) {
                $('rankEagleHistory').innerHTML = '<div class="empty-msg">Aucun historique disponible.</div>';
                return;
            }
            var html = '<div class="rank-eagle-table">' +
                '<div class="rank-eagle-hdr"><div>Semaine</div><div>Aigle</div><div>Score</div></div>';
            history.forEach(function (h) {
                var scoreLabel = '';
                if (h.criteria === 'revenue') scoreLabel = money(h.score);
                else scoreLabel = h.score + (h.criteria === 'count' ? ' ventes' : ' unites');
                html += '<div class="rank-eagle-row">' +
                    '<div>' + esc(h.week_label) + '</div>' +
                    '<div><span class="rank-eagle-mini">&#x1F985;</span> ' + esc(h.name) + '</div>' +
                    '<div>' + scoreLabel + '</div></div>';
            });
            html += '</div>';
            $('rankEagleHistory').innerHTML = html;
        });
    }

    // ── CONFIG (officer+) ───────────────────────────────────

    function loadConfig() {
        auth.apiGet('/classements/api/config', function (err, data) {
            if (err || !data || data.error) return;
            state.configCategories = data.eligible_categories || [];
            state.configCriteria = data.criteria || 'revenue';
            renderConfig();
        });
    }

    function renderConfig() {
        // Categories grid
        var grid = $('rankCatGrid');
        var html = '';
        ALL_CATEGORIES.forEach(function (cat) {
            var checked = state.configCategories.indexOf(cat) !== -1 ? ' checked' : '';
            html += '<label class="rank-cat-checkbox">' +
                '<input type="checkbox" value="' + esc(cat) + '"' + checked + '> ' +
                esc(CATEGORY_LABELS[cat] || cat) +
                '</label>';
        });
        grid.innerHTML = html;

        // Criteria radios
        document.querySelectorAll('input[name="rankCriteria"]').forEach(function (r) {
            r.checked = r.value === state.configCriteria;
        });
    }

    function saveConfig() {
        var cats = [];
        $('rankCatGrid').querySelectorAll('input[type="checkbox"]:checked').forEach(function (cb) {
            cats.push(cb.value);
        });
        var criteria = 'revenue';
        document.querySelectorAll('input[name="rankCriteria"]').forEach(function (r) {
            if (r.checked) criteria = r.value;
        });

        auth.apiPost('/classements/api/config', {
            eligible_categories: cats,
            criteria: criteria
        }, function (err, data) {
            if (err || !data || data.error) {
                auth.showToast(data?.error || 'Erreur', 'error');
                return;
            }
            auth.showToast('Configuration enregistree', 'success');
            loadRankings();
        });
    }

    // ── AUTH GATING ─────────────────────────────────────────

    function tryLoad() {
        if (!auth.isLoggedIn) {
            $('rankNotLogged').style.display = '';
            $('rankNoAccess').style.display = 'none';
            $('rankContent').style.display = 'none';
            return;
        }
        $('rankNotLogged').style.display = 'none';

        auth.apiGet('/classements/api/rankings?period=week', function (err, data) {
            if (err && err.status === 403) {
                $('rankNoAccess').style.display = '';
                $('rankContent').style.display = 'none';
                return;
            }
            $('rankNoAccess').style.display = 'none';
            $('rankContent').style.display = '';

            // Show config tab for officers+
            if (auth.isAtLeast('officer')) {
                document.querySelectorAll('.sub-tab-officer').forEach(function (el) { el.style.display = ''; });
            }

            if (data && !data.error) {
                state.rankings = data.rankings || [];
                state.eagle = data.eagle || null;
                state.criteria = data.criteria || 'revenue';
                renderRankings();
                renderEagle();
            }
        });
    }

    // ── INIT ────────────────────────────────────────────────

    initSubTabs();
    initPeriodButtons();

    $('rankSaveConfig').addEventListener('click', saveConfig);

    auth.onLogin(tryLoad);
    auth.onLogout(function () {
        $('rankNotLogged').style.display = '';
        $('rankContent').style.display = 'none';
    });

    tryLoad();

})();
