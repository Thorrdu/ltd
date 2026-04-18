@extends('layouts.mc')

@section('title', 'LOST MC -- Stock ' . $item->name)

@section('content')
<div class="menu-board" style="width:1000px;">
    <div class="inner-board">

        <div class="mc-page-header">
            <img src="{{ asset('img/3651.webp') }}" alt="Lost MC">
            <div class="mc-page-title">{{ $item->name }}</div>
            <div class="mc-page-motto">{{ $categoriesMap[$item->category] ?? $item->category }}</div>
            <a href="/stocks" class="mc-page-back">&larr; Retour aux stocks</a>
        </div>

        <div id="stocksNotLogged" class="contract-lock">
            <div class="lock-text">Connectez-vous via le bouton en haut a droite pour acceder a cette section.</div>
        </div>
        <div id="stocksNoAccess" class="contract-lock" style="display:none;">
            <div class="lock-text">Acces refuse. Cette page necessite au minimum le role Officier.</div>
        </div>

        <div id="stocksContent" style="display:none;">

            <div class="stocks-detail-grid" id="sdGrid"></div>

            <div class="action-card" style="margin-top:12px;">
                <div class="action-card-title">Quantite en stock</div>
                <p class="action-hint">Valeur absolue dans le coffre. Un mouvement d'ajustement est cree.</p>
                <div class="form-row" style="align-items:flex-end;">
                    <div class="form-group sm">
                        <label>Quantite</label>
                        <input type="number" id="sdQtyInput" class="fm-input" min="-999999999" max="999999999" step="1">
                    </div>
                    <div class="form-group">
                        <button type="button" class="action-btn" id="sdQtySave">Appliquer</button>
                    </div>
                </div>
            </div>

            <div class="action-card" style="margin-top:12px;">
                <div class="action-card-title" style="display:flex; justify-content:space-between; align-items:center;">
                    <span>Fiche article</span>
                    <button class="action-btn secondary" id="sdEditToggle" style="padding:4px 12px; font-size:11px;">Modifier</button>
                </div>
                <div id="sdSummary" class="sd-summary"></div>
                <div id="sdEditForm" class="action-form" style="display:none; margin-top:10px;">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Nom</label>
                            <input type="text" id="sdfName" class="fm-input" maxlength="120">
                        </div>
                        <div class="form-group">
                            <label>Categorie</label>
                            <select id="sdfCategory" class="fm-input">
                                @foreach($categoriesMap as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group sm">
                            <label>Prix vente ($)</label>
                            <input type="number" id="sdfSellPrice" class="fm-input" min="0" step="1" placeholder="(vide)">
                        </div>
                        <div class="form-group sm">
                            <label>Prix achat ($)</label>
                            <input type="number" id="sdfPurchasePrice" class="fm-input" min="0" step="1" placeholder="(vide)">
                        </div>
                        <div class="form-group sm">
                            <label>Poids unit. (g)</label>
                            <input type="number" id="sdfWeight" class="fm-input" min="0" step="1" placeholder="(vide)">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group full">
                            <label>Notes</label>
                            <textarea id="sdfNotes" class="fm-input" rows="2" maxlength="1000" style="resize:vertical;"></textarea>
                        </div>
                    </div>
                    <div class="form-row" style="align-items:center;">
                        <div class="form-group" style="flex:0 0 auto;">
                            <label class="cb-inline"><input type="checkbox" id="sdfSellable"> Vendable depuis /ventes</label>
                        </div>
                        <div class="form-group" style="flex:0 0 auto;">
                            <label class="cb-inline"><input type="checkbox" id="sdfQuickSale"> Vente rapide (express)</label>
                        </div>
                        <div class="form-group" style="flex:0 0 auto;">
                            <label class="cb-inline"><input type="checkbox" id="sdfActive"> Article actif</label>
                        </div>
                        <div class="form-group" style="flex:1; text-align:right;">
                            <button class="action-btn" id="sdfSave">Enregistrer</button>
                            <button class="action-btn secondary" id="sdfCancel" style="margin-left:6px;">Annuler</button>
                        </div>
                    </div>
                    <div style="font-size:11px; color:#9ca3af; margin-top:4px;">
                        La quantite se regle dans le bloc ci-dessus, via l'import CSV ou Filament. Les champs de cette fiche generent un mouvement d'ajustement (quantite 0) avec le resume des modifications.
                    </div>
                </div>
            </div>

            <div class="action-card" style="margin-top:12px;">
                <div class="action-card-title">Attributions en cours</div>
                <div class="members-table" id="sdOpenAttr">
                    <div class="empty-msg">Chargement...</div>
                </div>
            </div>

            <div class="action-card" style="margin-top:12px;">
                <div class="action-card-title">Derniers mouvements</div>
                <div class="movements-list" id="sdMovements">
                    <div class="empty-msg">Chargement...</div>
                </div>
            </div>

        </div>

    </div>
</div>
@endsection

@section('scripts')
<script>
window.MC_STOCK_SLUG = @json($item->slug);
window.MC_CATEGORIES = {!! json_encode($categoriesMap) !!};
window.MC_REASONS = {!! json_encode($reasonsMap) !!};
</script>
<script src="{{ asset('js/stocks-detail.js') }}?v={{ filemtime(public_path('js/stocks-detail.js')) }}"></script>
@endsection
