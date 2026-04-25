@extends('layouts.mc')

@section('title', 'LOST MC -- Simulateur Armes')

@section('content')
<div class="menu-board mc-board-md">
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

            {{-- Stock disponible --}}
            <div class="weapon-stock-block" style="margin-top:16px;">
                <div class="weapon-stock-title">
                    <label class="ammo-sim-label ammo-sim-label-cb" for="weaponUseCompStock" style="margin:0;font-size:1em;">
                        <input type="checkbox" id="weaponUseCompStock"> Deduire les composants en stock
                    </label>
                </div>
                <div id="weaponCompStockFields" style="display:none;">
                    <p class="ammo-sim-intro ammo-sim-intro-tight">Composants et matieres deja disponibles. Le simulateur deduira ces quantites du besoin total avant de calculer les crafts et les couts.</p>
                    <div class="ammo-sim-params weapon-stock-grid" id="weaponCompStockGrid"></div>
                </div>
            </div>
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
            <p class="ammo-sim-intro" style="border-left:3px solid #60a5fa;padding-left:10px;margin-top:4px;color:#7db8fc;">
                <strong>Plans Pistol :</strong> les plans pistolet permettent de crafter tous les pistolets (SNS, WN 29, Ceramic, Pistol, Heavy Pistol) <strong>sauf</strong> le Cal .50 qui necessite son propre plan.
            </p>
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
        </div>

        {{-- Craftable from stock (visible when logged in) --}}
        <div class="sim-section" id="craftableFromStock" style="display:none;"></div>

    </div>
</div>
@endsection

@section('scripts')
<script>
window.WEAPONS = {!! $weaponsJson !!};
window.MEMBERS = [];
</script>
<script src="{{ asset('js/simulateur-armes.js') }}?v={{ filemtime(public_path('js/simulateur-armes.js')) }}"></script>
@endsection
