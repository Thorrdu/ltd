function render() {
    var container = document.getElementById('menusContainer');
    container.innerHTML = '';

    menuData.forEach(function(entry, idx) {
        if (entry.type === 'promo') {
            var banner = document.createElement('div');
            banner.className = 'promo-banner';
            var p = document.createElement('p');
            p.className = 'promo-highlight';
            p.textContent = entry.text;
            banner.appendChild(p);

            if (editMode) {
                banner.style.cursor = 'pointer';
                banner.ondblclick = function() { startEditPromo(banner, idx); };

                var delBtn = document.createElement('button');
                delBtn.className = 'delete-menu-btn';
                delBtn.textContent = 'x';
                delBtn.title = 'Supprimer cette promo';
                delBtn.onclick = (function(i) {
                    return function(e) {
                        e.stopPropagation();
                        menuData.splice(i, 1);
                        render(); saveToStorage();
                    };
                })(idx);
                banner.appendChild(delBtn);
            }

            container.appendChild(banner);
        } else if (entry.type === 'menu') {
            var card = document.createElement('div');
            card.className = 'menu-card';

            var header = document.createElement('div');
            header.className = 'menu-card-header';

            var h3 = document.createElement('h3');
            h3.textContent = entry.name;
            header.appendChild(h3);

            var price = document.createElement('span');
            price.className = 'menu-card-price';
            price.textContent = entry.price + '\u20AC';
            header.appendChild(price);

            if (editMode) {
                var delMenuBtn = document.createElement('button');
                delMenuBtn.className = 'delete-menu-btn';
                delMenuBtn.textContent = 'x';
                delMenuBtn.title = 'Supprimer ce menu';
                delMenuBtn.onclick = (function(i, name) {
                    return function(e) {
                        e.stopPropagation();
                        if (confirm('Supprimer "' + name + '" ?')) {
                            menuData.splice(i, 1);
                            render(); saveToStorage();
                        }
                    };
                })(idx, entry.name);
                header.appendChild(delMenuBtn);

                header.ondblclick = (function(h, i) {
                    return function(e) {
                        if (e.target.classList.contains('delete-menu-btn')) return;
                        startEditMenuHeader(h, i);
                    };
                })(header, idx);
            }

            card.appendChild(header);

            var body = document.createElement('div');
            body.className = 'menu-card-body';

            if (entry.items && entry.items.length > 0) {
                var desc = document.createElement('div');
                desc.className = 'menu-card-desc';
                desc.textContent = entry.items.join(' + ');
                body.appendChild(desc);
            }

            if (editMode) {
                body.ondblclick = (function(b, i) {
                    return function() { startEditMenuItems(b, i); };
                })(body, idx);
            }

            card.appendChild(body);
            container.appendChild(card);
        }
    });

    if (editMode) {
        var addBtn = document.createElement('button');
        addBtn.className = 'add-btn';
        addBtn.textContent = '+ Ajouter un menu';
        addBtn.onclick = function() {
            menuData.push({ type: "menu", name: "Nouveau Menu", price: "0", items: ["Article 1"] });
            render(); saveToStorage();
        };
        container.appendChild(addBtn);

        var addPromoBtn = document.createElement('button');
        addPromoBtn.className = 'add-btn';
        addPromoBtn.textContent = '+ Ajouter une promo';
        addPromoBtn.style.marginTop = '4px';
        addPromoBtn.onclick = function() {
            menuData.push({ type: "promo", text: "Nouvelle promotion" });
            render(); saveToStorage();
        };
        container.appendChild(addPromoBtn);
    }
}

function startEditMenuHeader(header, idx) {
    if (editingCell) return;
    editingCell = true;

    var entry = menuData[idx];
    header.innerHTML = '';

    var nameInput = document.createElement('input');
    nameInput.className = 'edit-input edit-input-name';
    nameInput.value = entry.name;
    nameInput.style.color = '#fff';
    nameInput.style.background = 'rgba(255,255,255,0.15)';

    var priceInput = document.createElement('input');
    priceInput.className = 'edit-input edit-input-price';
    priceInput.value = entry.price;
    priceInput.style.color = '#fff';
    priceInput.style.background = 'rgba(255,255,255,0.15)';

    header.appendChild(nameInput);
    header.appendChild(priceInput);

    var save = function() {
        var n = nameInput.value.trim();
        var p = priceInput.value.trim();
        if (n) entry.name = n;
        if (p) entry.price = p;
        editingCell = null;
        render(); saveToStorage();
    };

    setupEditInputPair(nameInput, priceInput, save);
}

function startEditMenuItems(body, idx) {
    if (editingCell) return;
    editingCell = true;

    var entry = menuData[idx];
    body.innerHTML = '';

    var label = document.createElement('div');
    label.style.cssText = 'font-size:11px;color:#888;margin-bottom:2px;';
    label.textContent = 'Articles (un par ligne) :';
    body.appendChild(label);

    var itemsArea = document.createElement('textarea');
    itemsArea.className = 'edit-input';
    itemsArea.style.cssText = 'width:100%;min-height:80px;resize:vertical;font-size:13px;';
    itemsArea.value = (entry.items || []).join('\n');
    body.appendChild(itemsArea);

    itemsArea.focus();
    itemsArea.select();

    var save = function() {
        entry.items = itemsArea.value.split('\n').map(function(s) { return s.trim(); }).filter(Boolean);
        editingCell = null;
        render(); saveToStorage();
    };

    itemsArea.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') { editingCell = null; render(); }
    });
    body.addEventListener('blur', function() {
        setTimeout(function() { if (!body.contains(document.activeElement)) save(); }, 150);
    }, true);
}

function startEditPromo(banner, idx) {
    if (editingCell) return;
    editingCell = true;

    var entry = menuData[idx];
    banner.innerHTML = '';

    var input = document.createElement('input');
    input.className = 'edit-input';
    input.style.cssText = 'width:100%;text-align:center;font-size:15px;font-weight:800;';
    input.value = entry.text;
    banner.appendChild(input);
    input.focus();
    input.select();

    var save = function() {
        var t = input.value.trim();
        if (t) entry.text = t;
        editingCell = null;
        render(); saveToStorage();
    };

    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') save();
        else if (e.key === 'Escape') { editingCell = null; render(); }
    });
    input.addEventListener('blur', save);
}

boot('station-ltd-menus', 'data/menus.json');
