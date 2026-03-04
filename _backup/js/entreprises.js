var PAGE_PASSWORD = 'ltd2026';

function render() { renderGroups(); }

function checkPassword() {
    var input = document.getElementById('pwInput');
    var error = document.getElementById('pwError');
    if (input.value === PAGE_PASSWORD) {
        document.getElementById('passwordOverlay').classList.add('hidden');
        sessionStorage.setItem('_ent_auth', '1');
    } else {
        error.classList.add('visible');
        input.value = '';
        input.focus();
        setTimeout(function() { error.classList.remove('visible'); }, 2000);
    }
}

(function() {
    if (sessionStorage.getItem('_ent_auth') === '1') {
        document.getElementById('passwordOverlay').classList.add('hidden');
    }
    document.getElementById('pwInput').addEventListener('keydown', function(e) {
        if (e.key === 'Enter') checkPassword();
    });
})();

boot('station-ltd-entreprises', 'data/entreprises.json', {
    containerId: 'enterpriseContainer',
    blockClass: 'enterprise-block',
    headerClass: 'enterprise-header',
    hasColumns: false,
    hasMoveButtons: false,
    uppercaseNames: false,
    addGroupLabel: '+ Ajouter une entreprise',
    newGroupTemplate: function() {
        return { name: "Nouvelle Entreprise", products: [{ name: "Produit exemple", price: "0" }] };
    }
});
