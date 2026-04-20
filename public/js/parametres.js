(function () {
    'use strict';
    var auth = window.McAuth;
    var allData = null;
    var activeGroup = null;

    function esc(s) { var d = document.createElement('div'); d.textContent = s; return d.innerHTML; }

    function tryLoad() {
        auth.apiGet('/parametres/api/list', function (err, data) {
            if (err) {
                document.getElementById('setNotLogged').style.display = 'none';
                document.getElementById('setNoAccess').style.display = '';
                document.getElementById('setContent').style.display = 'none';
                return;
            }
            document.getElementById('setNotLogged').style.display = 'none';
            document.getElementById('setNoAccess').style.display = 'none';
            document.getElementById('setContent').style.display = '';
            allData = data;
            renderGroupTabs();
        });
    }

    function renderGroupTabs() {
        var bar = document.getElementById('setGroupBar');
        bar.innerHTML = '';
        var groups = allData.groups;
        var settings = allData.settings;
        var first = null;

        Object.keys(groups).forEach(function (key) {
            if (!settings[key] || !settings[key].length) return;
            if (!first) first = key;
            var btn = document.createElement('button');
            btn.className = 'sub-tab' + (first === key ? ' active' : '');
            btn.textContent = groups[key];
            btn.setAttribute('data-group', key);
            btn.addEventListener('click', function () {
                bar.querySelectorAll('.sub-tab').forEach(function (b) { b.classList.remove('active'); });
                btn.classList.add('active');
                renderGroup(key);
            });
            bar.appendChild(btn);
        });

        if (first) renderGroup(first);
    }

    function renderGroup(groupKey) {
        activeGroup = groupKey;
        var items = allData.settings[groupKey] || [];
        var html = '<table class="mc-table"><thead><tr>' +
            '<th>Parametre</th><th>Valeur</th><th></th>' +
            '</tr></thead><tbody>';

        items.forEach(function (s) {
            html += '<tr data-id="' + s.id + '">' +
                '<td><strong>' + esc(s.label) + '</strong>' +
                (s.description ? '<br><small style="opacity:.6">' + esc(s.description) + '</small>' : '') + '</td>' +
                '<td>' + renderInput(s) + '</td>' +
                '<td><button class="mc-btn mc-btn-sm set-save-btn" data-id="' + s.id + '">Enregistrer</button></td>' +
                '</tr>';
        });

        html += '</tbody></table>';
        document.getElementById('setBody').innerHTML = html;

        document.querySelectorAll('.set-save-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                saveSetting(parseInt(btn.getAttribute('data-id')));
            });
        });
    }

    function renderInput(s) {
        if (s.type === 'boolean') {
            var checked = (s.value === '1' || s.value === 'true') ? ' checked' : '';
            return '<label class="mc-toggle"><input type="checkbox" class="set-input" data-id="' + s.id + '"' + checked + '>' +
                '<span>' + (checked ? 'Oui' : 'Non') + '</span></label>';
        }
        if (s.type === 'json') {
            return '<textarea class="mc-input set-input" data-id="' + s.id + '" rows="3" style="font-family:monospace;font-size:.85em;width:100%">' +
                esc(s.value) + '</textarea>';
        }
        var inputType = (s.type === 'integer' || s.type === 'float') ? 'number' : 'text';
        var step = s.type === 'float' ? ' step="0.01"' : '';
        return '<input type="' + inputType + '" class="mc-input set-input" data-id="' + s.id + '" value="' + esc(s.value) + '"' + step + '>';
    }

    function saveSetting(id) {
        var input = document.querySelector('.set-input[data-id="' + id + '"]');
        if (!input) return;

        var value;
        if (input.type === 'checkbox') {
            value = input.checked ? '1' : '0';
        } else {
            value = input.value;
        }

        auth.apiPost('/parametres/api/' + id, { value: value, _method: 'PUT' }, function (err, data) {
            if (err) {
                auth.showToast(err.message || 'Erreur', 'error');
                return;
            }
            auth.showToast(data.message || 'OK', 'success');
            // Update local data
            if (allData && allData.settings[activeGroup]) {
                allData.settings[activeGroup].forEach(function (s) {
                    if (s.id === id) s.value = value;
                });
            }
        });
    }

    auth.onLogin(function () { tryLoad(); });
    auth.onLogout(function () {
        document.getElementById('setNotLogged').style.display = '';
        document.getElementById('setNoAccess').style.display = 'none';
        document.getElementById('setContent').style.display = 'none';
        allData = null;
    });
})();
