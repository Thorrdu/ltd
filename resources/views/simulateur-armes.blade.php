@extends('layouts.mc')

@section('title', 'LOST MC -- Simulateur Armes')

@section('content')
<div class="menu-board" style="width:960px;">
    <div class="inner-board">

        <div class="mc-page-header">
            <img src="{{ asset('img/3651.webp') }}" alt="Lost MC">
            <div class="mc-page-title">Simulateur Armes</div>
            <div class="mc-page-motto">Le Tout-Puissant pardonne. Pas les Lost.</div>
            <a href="/mc" class="mc-page-back">&larr; Retour a l'accueil</a>
        </div>

        {{-- Selection des armes --}}
        <div class="sim-section">
            <div class="sim-section-title">Selection des armes</div>
            <div class="weapons-grid" id="weaponsGrid"></div>
        </div>

        <div class="sim-section" id="resultsSection" style="display:none;">
            <div class="sim-section-title">Pieces par arme</div>
            <div class="results-table" id="piecesTable"></div>

            <div class="sim-section-title">Total pieces necessaires</div>
            <div class="results-table" id="totalPieces"></div>

            <div id="simStockCompare" style="display:none;">
                <div class="sim-section-title">Comparaison avec le stock</div>
                <div class="results-table" id="simStockTable"></div>
            </div>

            <div class="sim-section-title">Craft de materiaux (table du sud)</div>
            <div class="results-table" id="materialCraft"></div>

            <div class="sim-section-title">Matieres premieres totales</div>
            <div class="results-table" id="rawMaterials"></div>

            <div class="sim-section-title">Cout estime</div>
            <div class="results-table" id="costTable"></div>

            <div class="sim-section-title">Temps de craft total</div>
            <div class="craft-time-display" id="craftTime"></div>
        </div>

        {{-- Craft armes (composants) --}}
        <div class="sim-section" id="weaponCraftSection">
            <div class="sim-section-title">Craft armes (composants)</div>
            <p class="ammo-sim-intro">Le tableau distingue deux types de composants : les <strong>composants achetes</strong> (corp, canon, poignee) et les <strong>matieres craftees</strong> a base de fer (pieces de metal, ressorts). Deux scenarios de cout sont calcules selon que le fer est <strong>achete</strong> ou <strong>recolte</strong>. Cochez <strong>Composants en stock</strong> si vous disposez deja des composants. Le <strong>SNS</strong> n'est pas crafte : achat ref. + revente.</p>
            <div class="ammo-sim-params">
                <label class="ammo-sim-label" for="weaponCraftPlanPrice">Prix du plan (EUR / utilisation)</label>
                <input type="number" class="ammo-sim-input" id="weaponCraftPlanPrice" min="0" step="0.01" value="" placeholder="Ex. 8000" inputmode="decimal">
                <label class="ammo-sim-label" for="weaponCraftFerPrice">Prix du fer (EUR / unite)</label>
                <input type="number" class="ammo-sim-input" id="weaponCraftFerPrice" min="0" step="0.01" value="30" inputmode="decimal">
                <label class="ammo-sim-label ammo-sim-label-cb" for="weaponCraftCompsInStock">
                    <input type="checkbox" id="weaponCraftCompsInStock"> Composants en stock
                </label>
            </div>
            <div class="ammo-craft-wrap">
                <table class="ammo-craft-table weapon-craft-table" aria-label="Cout craft arme par composants">
                    <thead>
                        <tr>
                            <th>Arme</th>
                            <th>Tps</th>
                            <th>EUR plans</th>
                            <th>EUR Comp</th>
                            <th>EUR Mat</th>
                            <th>EUR polym.</th>
                            <th>Tot. ach.</th>
                            <th>Tot. rec.</th>
                            <th>Vente</th>
                            <th>M ach.</th>
                            <th>M rec.</th>
                        </tr>
                    </thead>
                    <tbody id="weaponCraftBody"></tbody>
                    <tfoot>
                        <tr class="ammo-craft-foot">
                            <td colspan="11">EUR Comp = composants achetes (corp, canon, poignee) -- 0 si "en stock". EUR Mat = matieres craftees (metal x 5 + ressort x 8) x prix fer. Tot. ach. = cout total fer achete ; Tot. rec. = cout si fer recolte (Mat = 0). SNS : achat ref. + vente.</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="ammo-target-block">
                <div class="sim-section-title">Objectif en armes</div>
                <p class="ammo-sim-intro ammo-sim-intro-tight">Simulez le cout de production d'un lot d'<strong>armes finies</strong> (craft) ou l'achat d'<strong>unites SNS</strong>. Deux scenarios sont compares : <strong>fer achete</strong> et <strong>fer recolte</strong>.</p>
                <div class="ammo-sim-params ammo-target-params">
                    <label class="ammo-sim-label" for="weaponTargetSlug">Arme</label>
                    <select id="weaponTargetSlug" class="ammo-sim-select" aria-label="Arme pour la simulation"></select>
                    <label class="ammo-sim-label" for="weaponTargetQty">Armes a fabriquer</label>
                    <input type="number" class="ammo-sim-input ammo-sim-input-muns" id="weaponTargetQty" min="1" max="9999" step="1" value="10" inputmode="numeric" autocomplete="off">
                    <label class="ammo-sim-label" for="weaponTargetSellPrice">Prix vente / arme (optionnel)</label>
                    <input type="number" class="ammo-sim-input" id="weaponTargetSellPrice" min="0" step="0.01" placeholder="Base" inputmode="decimal" autocomplete="off">
                </div>
                <div class="weapon-stock-block">
                    <div class="weapon-stock-title">Deja en stock (optionnel)</div>
                    <p class="ammo-sim-intro ammo-sim-intro-tight">Pieces deja disponibles, deduites automatiquement du besoin total.</p>
                    <div class="ammo-sim-params weapon-stock-grid">
                        <label class="ammo-sim-label" for="weaponStockPlans">Plans (util.)</label>
                        <input type="number" class="ammo-sim-input ammo-sim-input-sm weapon-stock-in" id="weaponStockPlans" min="0" max="999999" step="1" value="0" inputmode="numeric" autocomplete="off">
                        <label class="ammo-sim-label" for="weaponStockCorp">Corp</label>
                        <input type="number" class="ammo-sim-input ammo-sim-input-sm weapon-stock-in" id="weaponStockCorp" min="0" max="999999" step="1" value="0" inputmode="numeric" autocomplete="off">
                        <label class="ammo-sim-label" for="weaponStockRessort">Ressort</label>
                        <input type="number" class="ammo-sim-input ammo-sim-input-sm weapon-stock-in" id="weaponStockRessort" min="0" max="999999" step="1" value="0" inputmode="numeric" autocomplete="off">
                        <label class="ammo-sim-label" for="weaponStockCanon">Canon</label>
                        <input type="number" class="ammo-sim-input ammo-sim-input-sm weapon-stock-in" id="weaponStockCanon" min="0" max="999999" step="1" value="0" inputmode="numeric" autocomplete="off">
                        <label class="ammo-sim-label" for="weaponStockPoignee">Poignee</label>
                        <input type="number" class="ammo-sim-input ammo-sim-input-sm weapon-stock-in" id="weaponStockPoignee" min="0" max="999999" step="1" value="0" inputmode="numeric" autocomplete="off">
                        <label class="ammo-sim-label" for="weaponStockMetal">Metal</label>
                        <input type="number" class="ammo-sim-input ammo-sim-input-sm weapon-stock-in" id="weaponStockMetal" min="0" max="999999" step="1" value="0" inputmode="numeric" autocomplete="off">
                        <label class="ammo-sim-label" for="weaponStockPolymere">Polymere</label>
                        <input type="number" class="ammo-sim-input ammo-sim-input-sm weapon-stock-in" id="weaponStockPolymere" min="0" max="999999" step="1" value="0" inputmode="numeric" autocomplete="off">
                        <label class="ammo-sim-label" for="weaponStockSns">SNS (armes)</label>
                        <input type="number" class="ammo-sim-input ammo-sim-input-sm weapon-stock-in" id="weaponStockSns" min="0" max="999999" step="1" value="0" inputmode="numeric" autocomplete="off">
                    </div>
                </div>
                <div class="results-table ammo-target-results" id="weaponTargetResults"></div>
            </div>
        </div>

    </div>
</div>
@endsection

@section('scripts')
<script>
window.WEAPONS = {!! $weaponsJson !!};
window.MEMBERS = [];
</script>
<script src="{{ asset('js/simulateur-armes.js') }}"></script>
@endsection
