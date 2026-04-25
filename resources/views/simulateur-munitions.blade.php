@extends('layouts.mc')

@section('title', 'LOST MC -- Simulateur Munitions')

@section('content')
<div class="menu-board mc-board-md">
    <div class="inner-board">

        <div class="mc-page-header">
            <img src="{{ asset('img/3651.webp') }}" alt="Lost MC">
            <div class="mc-page-title">Simulateur Munitions</div>
            <div class="mc-page-motto">Le Tout-Puissant pardonne. Pas les Lost.</div>
            <a href="/mc" class="mc-page-back">&larr; Retour a l'accueil</a>
        </div>

        {{-- Selection multi-calibres --}}
        <div class="sim-section">
            <div class="sim-section-title">Selection des munitions</div>
            <p class="ammo-sim-intro ammo-sim-intro-tight">Selectionnez les calibres et quantites de <strong>munitions</strong> souhaitees. Le recapitulatif affiche les materiaux, couts et marges cumules. Cliquez <strong>+</strong> / <strong>&minus;</strong> (pas de 100) ou saisissez directement la quantite.</p>
            <div class="ammo-sim-params" style="margin-bottom:8px;">
                <label class="ammo-sim-label" for="ammoFerPrice">Prix du fer (EUR / unite)</label>
                <input type="number" class="ammo-sim-input" id="ammoFerPrice" min="0" step="0.01" value="30" inputmode="decimal">
            </div>
            <div class="weapons-grid" id="ammoMultiGrid"></div>

            {{-- Stock disponible --}}
            <div class="weapon-stock-block" style="margin-top:16px;">
                <div class="weapon-stock-title">
                    <label class="ammo-sim-label ammo-sim-label-cb" for="ammoUseAmmoStock" style="margin:0;font-size:1em;">
                        <input type="checkbox" id="ammoUseAmmoStock"> Deduire les munitions deja en stock
                    </label>
                </div>
                <div id="ammoAmmoStockFields" style="display:none;">
                    <p class="ammo-sim-intro ammo-sim-intro-tight">Munitions deja disponibles. Le simulateur deduira ces quantites du besoin total avant de calculer les crafts.</p>
                    <div class="ammo-sim-params weapon-stock-grid" id="ammoAmmoStockGrid"></div>
                </div>
            </div>
            <div class="weapon-stock-block" style="margin-top:8px;">
                <div class="weapon-stock-title">
                    <label class="ammo-sim-label ammo-sim-label-cb" for="ammoUseMatStock" style="margin:0;font-size:1em;">
                        <input type="checkbox" id="ammoUseMatStock"> Deduire les materiaux en stock
                    </label>
                </div>
                <div id="ammoMatStockFields" style="display:none;">
                    <p class="ammo-sim-intro ammo-sim-intro-tight">Materiaux deja disponibles. Ils seront deduits des besoins en materiaux dans les recapitulatifs.</p>
                    <div class="ammo-sim-params weapon-stock-grid">
                        <label class="ammo-sim-label" for="ammoStockPoudre">Poudre a canon</label>
                        <input type="number" class="ammo-sim-input ammo-sim-input-sm weapon-stock-in" id="ammoStockPoudre" min="0" max="999999" step="1" value="0" inputmode="numeric" autocomplete="off">
                        <label class="ammo-sim-label" for="ammoStockFragments">Fragments de metal</label>
                        <input type="number" class="ammo-sim-input ammo-sim-input-sm weapon-stock-in" id="ammoStockFragments" min="0" max="999999" step="1" value="0" inputmode="numeric" autocomplete="off">
                        <label class="ammo-sim-label" for="ammoStockMinerais">Minerais de fer</label>
                        <input type="number" class="ammo-sim-input ammo-sim-input-sm weapon-stock-in" id="ammoStockMinerais" min="0" max="999999" step="1" value="0" inputmode="numeric" autocomplete="off">
                    </div>
                </div>
            </div>
        </div>

        <div class="sim-section" id="ammoMultiSection" style="display:none;">
            <div class="sim-section-title">Recapitulatif de la commande</div>
            <div class="results-table ammo-target-results" id="ammoMultiResults"></div>
        </div>

        {{-- Craft table --}}
        <div class="sim-section" id="ammoCraftSection">
            <div class="sim-section-title">Craft munitions (tableau)</div>
            <p class="ammo-sim-intro">Chaque craft produit <strong>10 munitions</strong>. Les couts sont affiches <strong>par munition</strong>. La poudre coute <strong>100 EUR</strong>/u et <strong>1 minerai de fer = 2 fragments</strong>. Les prix de vente proviennent de la base de donnees. Le tableau se met a jour en temps reel si vous modifiez le prix du fer.</p>
            <div class="ammo-craft-wrap">
                <table class="ammo-craft-table" aria-label="Couts et marges des munitions">
                    <thead>
                        <tr>
                            <th>Munition</th>
                            <th>Tps craft</th>
                            <th>Pdr / craft</th>
                            <th>Frag / craft</th>
                            <th>Cout / mun (F ach.)</th>
                            <th>Cout / mun (F rec.)</th>
                            <th>Vente / mun</th>
                            <th>Marge / mun (F ach.)</th>
                            <th>Marge / mun (F rec.)</th>
                        </tr>
                    </thead>
                    <tbody id="ammoCraftBody"></tbody>
                    <tfoot>
                        <tr class="ammo-craft-foot">
                            <td colspan="9">EUR par munition. Prix de vente : base de donnees (stocks). Pdr/Frag = par craft.</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

    </div>
</div>
@endsection

@section('scripts')
<script>
window.AMMO_SELL_PRICES = {!! $ammoPricesJson !!};
</script>
<script src="{{ asset('js/simulateur-munitions.js') }}?v={{ filemtime(public_path('js/simulateur-munitions.js')) }}"></script>
@endsection
