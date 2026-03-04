function render() { renderGroups(); }

boot('station-ltd-menu', 'data/produits.json', {
    containerId: 'categoriesGrid',
    blockClass: 'category-block',
    headerClass: 'category-header',
    hasColumns: true,
    hasMoveButtons: true,
    uppercaseNames: true,
    addGroupLabel: '+ Ajouter une categorie',
    newGroupTemplate: function() {
        return { name: "NOUVELLE CATEGORIE", column: "left", products: [{ name: "Produit exemple", price: "100" }] };
    }
});
