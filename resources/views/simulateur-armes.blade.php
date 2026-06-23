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

        {{-- Non connecte --}}
        <div id="weaponSimNotLogged" class="contract-lock">
            <div class="lock-text">Connectez-vous via le bouton en haut a droite pour acceder au simulateur.</div>
        </div>

        {{-- Contenu (reserve aux membres connectes) --}}
        <div id="weaponSimContent" style="display:none;">

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
            <p class="ammo-sim-intro">Le tableau distingue deux types de composants : les <strong>composants braquables</strong> (canon, poignee, corp de pistolet, corps de SMG/fusil, crosse) qui peuvent etre <strong>braques</strong> (gratuits) ou <strong>achetes</strong>, et les <strong>matieres craftees uniquement</strong> (pieces de metal, ressorts, polymere). Deux scenarios de cout sont calcules : composants <strong>achetes</strong> ou <strong>braques</strong>. Le <strong>SNS</strong> n'est pas crafte : achat ref. + revente.</p>
            <div class="ammo-sim-params">
                <label class="ammo-sim-label" for="weaponCraftFerPrice">Prix du fer (EUR / unite)</label>
                <input type="number" class="ammo-sim-input" id="weaponCraftFerPrice" min="0" step="0.01" value="30" inputmode="decimal">
            </div>
            <div class="ammo-craft-wrap">
                <table class="ammo-craft-table weapon-craft-table" aria-label="Cout craft arme par composants">
                    <thead>
                        <tr>
                            <th>Arme</th>
                            <th>Tps</th>
                            <th>EUR Comp</th>
                            <th>EUR Mat</th>
                            <th>EUR polym.</th>
                            <th>Cout achat</th>
                            <th>Cout braque</th>
                            <th>Vente</th>
                            <th>M achat</th>
                            <th>M braque</th>
                        </tr>
                    </thead>
                    <tbody id="weaponCraftBody"></tbody>
                    <tfoot>
                        <tr class="ammo-craft-foot">
                            <td colspan="10">EUR Comp = composants braquables (canon, poignee, corp, corps SMG/fusil, crosse). EUR Mat = matieres craftees (metal x 5 + ressort x 8) x prix fer. Cout achat = composants achetes ; Cout braque = composants braques (gratuits). SNS : achat ref. + vente.</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        {{-- Craftable from stock (visible when logged in) --}}
        <div class="sim-section" id="craftableFromStock" style="display:none;"></div>

        </div>{{-- /weaponSimContent --}}

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
