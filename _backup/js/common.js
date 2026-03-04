var menuData = [];
var editMode = false;
var editingCell = null;
var STORAGE_KEY = '';
var DATA_URL = '';
var PAGE_CONFIG = null;

function saveToStorage() {
    try { localStorage.setItem(STORAGE_KEY, JSON.stringify(menuData)); } catch (e) {}
}

async function loadData() {
    try {
        var saved = localStorage.getItem(STORAGE_KEY);
        if (saved) {
            var data = JSON.parse(saved);
            if (Array.isArray(data) && data.length > 0) { menuData = data; return; }
        }
    } catch (e) {}
    try {
        var resp = await fetch(DATA_URL);
        if (resp.ok) {
            var data = await resp.json();
            if (Array.isArray(data) && data.length > 0) menuData = data;
        }
    } catch (e) {}
}

function toggleEditMode() {
    editMode = !editMode;
    document.getElementById('menuBoard').classList.toggle('edit-mode', editMode);
    document.getElementById('btnToggleEdit').classList.toggle('active', editMode);
    document.getElementById('btnToggleEdit').textContent = editMode ? 'Mode Lecture' : 'Mode Edition';
    render();
}

function exportJSON() {
    var json = JSON.stringify(menuData, null, 2);
    document.getElementById('modalTitle').textContent = 'Exporter les donnees (JSON)';
    document.getElementById('modalTextarea').value = json;
    document.getElementById('modalTextarea').readOnly = true;
    document.getElementById('modalAction').textContent = 'Copier';
    document.getElementById('modalAction').onclick = copyToClipboard;
    document.getElementById('modalOverlay').classList.add('active');
}

function importJSON() {
    document.getElementById('modalTitle').textContent = 'Importer des donnees (JSON)';
    document.getElementById('modalTextarea').value = '';
    document.getElementById('modalTextarea').readOnly = false;
    document.getElementById('modalTextarea').placeholder = 'Collez ici le JSON a importer...';
    document.getElementById('modalAction').textContent = 'Importer';
    document.getElementById('modalAction').onclick = doImport;
    document.getElementById('modalOverlay').classList.add('active');
}

function doImport() {
    try {
        var data = JSON.parse(document.getElementById('modalTextarea').value);
        if (Array.isArray(data)) {
            menuData = data;
            render();
            saveToStorage();
            closeModal();
        } else {
            alert('Le JSON doit etre un tableau.');
        }
    } catch (e) {
        alert('JSON invalide : ' + e.message);
    }
}

function copyToClipboard() {
    var textarea = document.getElementById('modalTextarea');
    textarea.select();
    navigator.clipboard.writeText(textarea.value).then(function() {
        var btn = document.getElementById('modalAction');
        btn.textContent = 'Copie !';
        setTimeout(function() { btn.textContent = 'Copier'; }, 1500);
    });
}

function closeModal() {
    document.getElementById('modalOverlay').classList.remove('active');
}

function escapeHtml(text) {
    var div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function setupEditInputPair(nameInput, priceInput, saveFn) {
    nameInput.focus();
    nameInput.select();

    var handleKey = function(e) {
        if (e.key === 'Enter') {
            saveFn();
        } else if (e.key === 'Escape') {
            editingCell = null;
            render();
        } else if (e.key === 'Tab') {
            e.preventDefault();
            if (document.activeElement === nameInput) {
                priceInput.focus();
                priceInput.select();
            } else {
                saveFn();
            }
        }
    };

    nameInput.addEventListener('keydown', handleKey);
    priceInput.addEventListener('keydown', handleKey);
    nameInput.addEventListener('blur', function(e) {
        if (e.relatedTarget !== priceInput) {
            setTimeout(function() { if (document.activeElement !== priceInput) saveFn(); }, 100);
        }
    });
    priceInput.addEventListener('blur', function(e) {
        if (e.relatedTarget !== nameInput) {
            setTimeout(function() { if (document.activeElement !== nameInput) saveFn(); }, 100);
        }
    });
}

/* ============================================
   RENDU GENERIQUE : GROUPES + PRODUITS
   Utilise par produits.js et entreprises.js
   ============================================ */

function renderGroups() {
    var cfg = PAGE_CONFIG;
    var container = document.getElementById(cfg.containerId);
    container.innerHTML = '';

    menuData.forEach(function(group, gIdx) {
        var block = document.createElement('div');
        block.className = cfg.blockClass;
        if (cfg.hasColumns && group.column) block.setAttribute('data-col', group.column);

        var header = document.createElement('div');
        header.className = cfg.headerClass;

        if (editMode) {
            var inner = document.createElement('div');
            inner.className = cfg.headerClass + '-inner';

            var h3 = document.createElement('h3');
            h3.textContent = group.name;
            inner.appendChild(h3);

            var delBtn = document.createElement('button');
            delBtn.className = 'delete-btn';
            delBtn.textContent = 'x';
            delBtn.title = 'Supprimer';
            delBtn.onclick = (function(i, name) {
                return function(e) {
                    e.stopPropagation();
                    if (confirm('Supprimer "' + name + '" et tous ses produits ?')) {
                        menuData.splice(i, 1);
                        render(); saveToStorage();
                    }
                };
            })(gIdx, group.name);
            inner.appendChild(delBtn);
            header.appendChild(inner);

            header.ondblclick = (function(h, i) {
                return function() { startEditGroupHeader(h, i); };
            })(header, gIdx);
        } else {
            header.innerHTML = '<h3>' + escapeHtml(group.name) + '</h3>';
        }

        block.appendChild(header);

        var list = document.createElement('ul');
        list.className = 'product-list';

        group.products.forEach(function(product, pIdx) {
            var row = document.createElement('li');
            row.className = 'product-row';

            var nameSpan = document.createElement('span');
            nameSpan.className = 'product-name';
            nameSpan.textContent = product.name;

            var priceSpan = document.createElement('span');
            priceSpan.className = 'product-price';
            priceSpan.textContent = product.price + '\u20AC';

            var dots = document.createElement('span');
            dots.className = 'dot-leader';

            row.appendChild(nameSpan);
            row.appendChild(dots);
            row.appendChild(priceSpan);

            if (editMode) {
                row.ondblclick = (function(r, gi, pi) {
                    return function(e) {
                        if (e.target.classList.contains('row-action-btn')) return;
                        startEditGroupProduct(r, gi, pi);
                    };
                })(row, gIdx, pIdx);

                var actions = document.createElement('div');
                actions.className = 'row-actions';

                if (cfg.hasMoveButtons) {
                    var isFirst = pIdx === 0;
                    var isLast = pIdx === group.products.length - 1;

                    var upBtn = document.createElement('button');
                    upBtn.className = 'row-action-btn move' + (isFirst ? ' disabled' : '');
                    upBtn.innerHTML = '&#9650;';
                    upBtn.title = 'Monter';
                    if (!isFirst) {
                        upBtn.onclick = (function(gi, pi) {
                            return function(e) {
                                e.stopPropagation();
                                var arr = menuData[gi].products;
                                var tmp = arr[pi - 1]; arr[pi - 1] = arr[pi]; arr[pi] = tmp;
                                render(); saveToStorage();
                            };
                        })(gIdx, pIdx);
                    }
                    actions.appendChild(upBtn);

                    var downBtn = document.createElement('button');
                    downBtn.className = 'row-action-btn move' + (isLast ? ' disabled' : '');
                    downBtn.innerHTML = '&#9660;';
                    downBtn.title = 'Descendre';
                    if (!isLast) {
                        downBtn.onclick = (function(gi, pi) {
                            return function(e) {
                                e.stopPropagation();
                                var arr = menuData[gi].products;
                                var tmp = arr[pi]; arr[pi] = arr[pi + 1]; arr[pi + 1] = tmp;
                                render(); saveToStorage();
                            };
                        })(gIdx, pIdx);
                    }
                    actions.appendChild(downBtn);
                }

                var delProdBtn = document.createElement('button');
                delProdBtn.className = 'row-action-btn delete';
                delProdBtn.textContent = 'x';
                delProdBtn.title = 'Supprimer';
                delProdBtn.onclick = (function(gi, pi) {
                    return function(e) {
                        e.stopPropagation();
                        menuData[gi].products.splice(pi, 1);
                        render(); saveToStorage();
                    };
                })(gIdx, pIdx);
                actions.appendChild(delProdBtn);
                row.appendChild(actions);
            }

            list.appendChild(row);
        });

        block.appendChild(list);

        if (editMode) {
            var addBtn = document.createElement('button');
            addBtn.className = 'add-btn';
            addBtn.textContent = '+ Ajouter un produit';
            addBtn.onclick = (function(gi) {
                return function() {
                    menuData[gi].products.push({ name: "Nouveau produit", price: "0" });
                    render(); saveToStorage();
                };
            })(gIdx);
            block.appendChild(addBtn);
        }

        container.appendChild(block);
    });

    if (editMode) {
        var addGroupBtn = document.createElement('button');
        addGroupBtn.className = 'add-btn large';
        addGroupBtn.textContent = cfg.addGroupLabel;
        addGroupBtn.onclick = function() {
            menuData.push(cfg.newGroupTemplate());
            render(); saveToStorage();
        };
        container.appendChild(addGroupBtn);
    }
}

function startEditGroupProduct(row, groupIdx, prodIdx) {
    if (editingCell) return;
    editingCell = true;

    var product = menuData[groupIdx].products[prodIdx];
    row.innerHTML = '';

    var nameInput = document.createElement('input');
    nameInput.className = 'edit-input edit-input-name';
    nameInput.type = 'text';
    nameInput.value = product.name;

    var priceInput = document.createElement('input');
    priceInput.className = 'edit-input edit-input-price';
    priceInput.type = 'text';
    priceInput.value = product.price;

    row.appendChild(nameInput);
    row.appendChild(priceInput);

    var save = function() {
        var n = nameInput.value.trim();
        var p = priceInput.value.trim();
        if (n) product.name = n;
        if (p) product.price = p;
        editingCell = null;
        render(); saveToStorage();
    };

    setupEditInputPair(nameInput, priceInput, save);
}

function startEditGroupHeader(header, groupIdx) {
    if (editingCell) return;
    editingCell = true;

    var group = menuData[groupIdx];
    var h3 = header.querySelector('h3');

    var input = document.createElement('input');
    input.className = 'edit-input-cat';
    input.type = 'text';
    input.value = group.name;
    h3.textContent = '';
    h3.appendChild(input);
    input.focus();
    input.select();

    var save = function() {
        var newName = input.value.trim();
        if (PAGE_CONFIG && PAGE_CONFIG.uppercaseNames) newName = newName.toUpperCase();
        if (newName) group.name = newName;
        editingCell = null;
        render(); saveToStorage();
    };

    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') save();
        else if (e.key === 'Escape') { editingCell = null; render(); }
    });
    input.addEventListener('blur', save);
}

/* ============================================
   BOOT
   ============================================ */

function boot(storageKey, dataUrl, config) {
    STORAGE_KEY = storageKey;
    DATA_URL = dataUrl;
    if (config) PAGE_CONFIG = config;

    var _h = location.hash.substring(1);
    var _s = sessionStorage;
    var _k = '_rc';
    if (_h === atob('Y2Zn')) {
        _s.setItem(_k, '1');
        history.replaceState(null, '', location.pathname + location.search);
    }
    if (_s.getItem(_k) === '1') {
        document.querySelector('.toolbar').classList.add('rendered');
        document.querySelector('.menu-board').style.marginTop = '56px';
    }

    if (_h === 'reset') {
        localStorage.removeItem(storageKey);
        history.replaceState(null, '', location.pathname + location.search);
    }

    if (_h === 'clean') document.body.classList.add('clean-mode');
    var params = new URLSearchParams(window.location.search);
    if (params.has('clean')) document.body.classList.add('clean-mode');

    document.getElementById('modalOverlay').addEventListener('click', function(e) {
        if (e.target === e.currentTarget) closeModal();
    });

    loadData().then(function() { render(); });
}
