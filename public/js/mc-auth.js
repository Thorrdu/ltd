(function () {
    'use strict';

    function $(id) { return document.getElementById(id); }
    function esc(s) { if (s == null) return ''; var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
    function csrfToken() { var el = document.querySelector('meta[name="csrf-token"]'); return el ? el.getAttribute('content') : ''; }

    var ROLE_LABELS = {
        treasurer: 'Tresorier',
        president: 'President',
        vice_president: 'Vice-President',
        officer: 'Officier',
        member: 'Membre',
        prospect: 'Prospect'
    };

    var ROLE_LEVELS = {
        prospect: 1, member: 2, officer: 3,
        vice_president: 4, president: 5, treasurer: 99
    };

    window.McAuth = {
        userId: sessionStorage.getItem('lmc_uid') ? parseInt(sessionStorage.getItem('lmc_uid'), 10) : null,
        userName: sessionStorage.getItem('lmc_name') || '',
        userRole: sessionStorage.getItem('lmc_role') || '',
        isLoggedIn: false,

        roleLevels: ROLE_LEVELS,

        isAtLeast: function (minRole) {
            return (ROLE_LEVELS[this.userRole] || 0) >= (ROLE_LEVELS[minRole] || 0);
        },

        isOfficer: function () { return this.isAtLeast('officer'); },
        isVicePresident: function () { return this.isAtLeast('vice_president'); },
        isPresident: function () { return this.userRole === 'president' || this.userRole === 'treasurer'; },
        isSuperadmin: function () { return this.userRole === 'treasurer'; },

        apiHeaders: function () {
            var h = { 'Accept': 'application/json' };
            if (this.userId) h['X-Sim-User'] = '' + this.userId;
            return h;
        },

        apiGet: function (url, cb) {
            fetch(url, { headers: this.apiHeaders() })
                .then(function (r) { return r.json(); })
                .then(function (d) { cb(null, d); })
                .catch(function (e) { cb(e); });
        },

        apiPost: function (url, body, cb) {
            var h = this.apiHeaders();
            h['Content-Type'] = 'application/json';
            h['X-CSRF-TOKEN'] = csrfToken();
            fetch(url, { method: 'POST', headers: h, body: JSON.stringify(body) })
                .then(function (r) { return r.json(); })
                .then(function (d) { cb(null, d); })
                .catch(function (e) { cb(e); });
        },

        apiPut: function (url, body, cb) {
            var h = this.apiHeaders();
            h['Content-Type'] = 'application/json';
            h['X-CSRF-TOKEN'] = csrfToken();
            fetch(url, { method: 'PUT', headers: h, body: JSON.stringify(body) })
                .then(function (r) { return r.json(); })
                .then(function (d) { cb(null, d); })
                .catch(function (e) { cb(e); });
        },

        apiDelete: function (url, cb) {
            var h = this.apiHeaders();
            h['X-CSRF-TOKEN'] = csrfToken();
            fetch(url, { method: 'DELETE', headers: h })
                .then(function (r) { return r.json(); })
                .then(function (d) { cb(null, d); })
                .catch(function (e) { cb(e); });
        },

        onLoginCallbacks: [],
        onLogoutCallbacks: [],

        onLogin: function (cb) { this.onLoginCallbacks.push(cb); },
        onLogout: function (cb) { this.onLogoutCallbacks.push(cb); },

        _fireLogin: function () {
            var self = this;
            this.onLoginCallbacks.forEach(function (cb) { cb(self); });
        },
        _fireLogout: function () {
            this.onLogoutCallbacks.forEach(function (cb) { cb(); });
        }
    };

    function showToast(msg, type) {
        var t = $('toast');
        if (!t) return;
        t.textContent = msg;
        t.className = 'toast show ' + (type || 'info');
        clearTimeout(t._timer);
        t._timer = setTimeout(function () { t.className = 'toast'; }, 3500);
    }
    window.McAuth.showToast = showToast;

    function updateUI() {
        var auth = window.McAuth;
        if (auth.isLoggedIn) {
            $('mcAuthOut').style.display = 'none';
            $('mcAuthIn').style.display = '';
            $('mcUserName').textContent = auth.userName;
            var roleEl = $('mcUserRole');
            roleEl.textContent = ROLE_LABELS[auth.userRole] || auth.userRole;
            roleEl.className = 'mc-user-role role-' + auth.userRole;
            $('mcLoginPanel').style.display = 'none';
        } else {
            $('mcAuthOut').style.display = '';
            $('mcAuthIn').style.display = 'none';
        }
    }

    function doLogin() {
        var sel = $('loginMemberSelect');
        var uid = parseInt(sel.value, 10);
        var pin = $('loginPin').value;
        if (!uid) { $('errLogin').textContent = 'Selectionnez votre nom'; $('errLogin').classList.add('visible'); return; }
        if (!pin) { $('errLogin').textContent = 'Entrez votre PIN'; $('errLogin').classList.add('visible'); return; }

        window.McAuth.apiPost('/simulateur-armes/api/login', { user_id: uid, pin: pin }, function (err, data) {
            if (err || !data || data.error) {
                $('errLogin').textContent = (data && data.error) || 'Erreur de connexion';
                $('errLogin').classList.add('visible');
                return;
            }
            $('errLogin').classList.remove('visible');
            window.McAuth.userId = data.user.id;
            window.McAuth.userName = data.user.name;
            window.McAuth.userRole = data.user.role;
            sessionStorage.setItem('lmc_uid', '' + data.user.id);
            sessionStorage.setItem('lmc_name', data.user.name);
            sessionStorage.setItem('lmc_role', data.user.role);
            window.McAuth.isLoggedIn = true;
            updateUI();
            window.McAuth._fireLogin();
            showToast('Connecte : ' + data.user.name, 'success');
        });
    }

    function doLogout() {
        window.McAuth.userId = null;
        window.McAuth.userName = '';
        window.McAuth.userRole = '';
        window.McAuth.isLoggedIn = false;
        sessionStorage.removeItem('lmc_uid');
        sessionStorage.removeItem('lmc_name');
        sessionStorage.removeItem('lmc_role');
        updateUI();
        window.McAuth._fireLogout();
    }

    $('mcLoginToggle').addEventListener('click', function () {
        var panel = $('mcLoginPanel');
        panel.style.display = panel.style.display === 'none' ? '' : 'none';
    });

    $('btnLogin').addEventListener('click', doLogin);
    $('loginPin').addEventListener('keydown', function (e) { if (e.key === 'Enter') doLogin(); });
    $('mcLogoutBtn').addEventListener('click', doLogout);

    document.addEventListener('click', function (e) {
        var panel = $('mcLoginPanel');
        if (panel.style.display === 'none') return;
        if (e.target.closest('#mcLoginPanel') || e.target.closest('#mcLoginToggle')) return;
        panel.style.display = 'none';
    });

    if (window.McAuth.userId) {
        window.McAuth.isLoggedIn = true;
        updateUI();
        setTimeout(function () { window.McAuth._fireLogin(); }, 0);
    }
})();
