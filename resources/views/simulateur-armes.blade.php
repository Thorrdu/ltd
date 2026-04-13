@extends('layouts.app')

@section('title', 'Simulateur Craft Armes - BXL Life')
@section('css')
<link rel="stylesheet" href="{{ asset('css/simulateur-armes.css') }}">
@endsection
@section('body-class', 'simulateur-armes')

@section('content')
<div class="menu-board">
    <div class="inner-board">

        <div class="header lost-header">
            <div class="lost-emblem">⚙</div>
            <div class="logo-row">
                <div class="station-title lost-title">LOST MC</div>
            </div>
            <div class="lost-tagline">SIMULATEUR CRAFT ARMES</div>
            <div class="station-subtitle lost-sub">BXL Life — Atelier clandestin</div>
        </div>

        {{-- TAB NAVIGATION --}}
        <div class="tab-bar">
            <button class="tab-btn active" data-tab="simulateur">Simulateur</button>
            <button class="tab-btn" data-tab="contrats">Contrats & Stock</button>
        </div>

        {{-- ============================================ --}}
        {{-- TAB 1: SIMULATEUR (public) --}}
        {{-- ============================================ --}}
        <div class="tab-content active" id="tab-simulateur">

            {{-- Weapon selector --}}
            <div class="sim-section">
                <div class="sim-section-title">Sélection des armes</div>
                <div class="weapons-grid" id="weaponsGrid">
                    <div class="weapon-card" data-weapon="wn29">
                        <div class="weapon-name">WN 29 Pistol</div>
                        <div class="weapon-craft-time">⏱ 15 sec</div>
                        <div class="weapon-qty-row">
                            <button class="qty-btn minus" data-weapon="wn29">−</button>
                            <input type="number" class="qty-input" id="qty-wn29" value="0" min="0" max="99">
                            <button class="qty-btn plus" data-weapon="wn29">+</button>
                        </div>
                    </div>
                    <div class="weapon-card" data-weapon="ceramic">
                        <div class="weapon-name">Ceramic Pistol</div>
                        <div class="weapon-craft-time">⏱ 15 sec</div>
                        <div class="weapon-qty-row">
                            <button class="qty-btn minus" data-weapon="ceramic">−</button>
                            <input type="number" class="qty-input" id="qty-ceramic" value="0" min="0" max="99">
                            <button class="qty-btn plus" data-weapon="ceramic">+</button>
                        </div>
                    </div>
                    <div class="weapon-card" data-weapon="pistol">
                        <div class="weapon-name">Pistol</div>
                        <div class="weapon-craft-time">⏱ 15 sec</div>
                        <div class="weapon-qty-row">
                            <button class="qty-btn minus" data-weapon="pistol">−</button>
                            <input type="number" class="qty-input" id="qty-pistol" value="0" min="0" max="99">
                            <button class="qty-btn plus" data-weapon="pistol">+</button>
                        </div>
                    </div>
                    <div class="weapon-card" data-weapon="heavy">
                        <div class="weapon-name">Heavy Pistol</div>
                        <div class="weapon-craft-time">⏱ N/A</div>
                        <div class="weapon-qty-row">
                            <button class="qty-btn minus" data-weapon="heavy">−</button>
                            <input type="number" class="qty-input" id="qty-heavy" value="0" min="0" max="99">
                            <button class="qty-btn plus" data-weapon="heavy">+</button>
                        </div>
                    </div>
                    <div class="weapon-card" data-weapon="cal50">
                        <div class="weapon-name">Cal .50</div>
                        <div class="weapon-craft-time">⏱ N/A</div>
                        <div class="weapon-qty-row">
                            <button class="qty-btn minus" data-weapon="cal50">−</button>
                            <input type="number" class="qty-input" id="qty-cal50" value="0" min="0" max="99">
                            <button class="qty-btn plus" data-weapon="cal50">+</button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Results --}}
            <div class="sim-section" id="resultsSection" style="display:none;">
                <div class="sim-section-title">Pièces par arme</div>
                <div class="results-table" id="piecesTable"></div>

                <div class="sim-section-title">Total pièces nécessaires</div>
                <div class="results-table" id="totalPieces"></div>

                <div class="sim-section-title">Craft de matériaux (table du sud)</div>
                <div class="results-table" id="materialCraft"></div>

                <div class="sim-section-title">Matières premières totales</div>
                <div class="results-table" id="rawMaterials"></div>

                <div class="sim-section-title">Coût estimé</div>
                <div class="results-table" id="costTable"></div>

                <div class="sim-section-title">Temps de craft total</div>
                <div class="craft-time-display" id="craftTime"></div>
            </div>

        </div>

        {{-- ============================================ --}}
        {{-- TAB 2: CONTRATS & STOCK (password protected) --}}
        {{-- ============================================ --}}
        <div class="tab-content" id="tab-contrats">

            {{-- Password wall --}}
            <div class="contract-lock" id="contractLock">
                <div class="lock-icon">🔒</div>
                <div class="lock-text">Accès réservé — Lost MC</div>
                <input type="password" class="lock-input" id="contractPwInput" placeholder="Mot de passe" autocomplete="off">
                <button class="lock-btn" id="contractPwBtn">Déverrouiller</button>
                <div class="lock-error" id="contractPwError">Mot de passe incorrect</div>
            </div>

            {{-- Contract content (hidden until unlock) --}}
            <div class="contract-content" id="contractContent" style="display:none;">

                {{-- Contract manager --}}
                <div class="sim-section">
                    <div class="sim-section-title">Contrats</div>
                    <div class="contract-form">
                        <input type="text" class="contract-input name" id="contractName" placeholder="Nom du contrat (ex: Contrat Morana)">
                        <div class="contract-weapons-row">
                            <select class="contract-select" id="contractWeaponSelect">
                                <option value="wn29">WN 29 Pistol</option>
                                <option value="ceramic">Ceramic Pistol</option>
                                <option value="pistol">Pistol</option>
                                <option value="heavy">Heavy Pistol</option>
                                <option value="cal50">Cal .50</option>
                            </select>
                            <input type="number" class="contract-input qty" id="contractWeaponQty" value="1" min="1" max="99">
                            <button class="contract-add-weapon-btn" id="contractAddWeapon">+ Ajouter</button>
                        </div>
                        <div class="contract-weapon-list" id="contractWeaponList"></div>
                        <button class="contract-save-btn" id="contractSaveBtn">Créer le contrat</button>
                    </div>

                    <div class="contracts-list" id="contractsList"></div>
                </div>

                {{-- Stock / Inventaire --}}
                <div class="sim-section">
                    <div class="sim-section-title">Stock actuel</div>
                    <div class="stock-info">Plans : 1 plan = 4 utilisations. Indiquez le nombre de plans physiques.</div>
                    <div class="stock-grid" id="stockGrid">
                        <div class="stock-item">
                            <label>Plans (×4 utilisations)</label>
                            <input type="number" class="stock-input" data-stock="plans" value="0" min="0">
                        </div>
                        <div class="stock-item">
                            <label>Ressorts</label>
                            <input type="number" class="stock-input" data-stock="ressort" value="0" min="0">
                        </div>
                        <div class="stock-item">
                            <label>Canons</label>
                            <input type="number" class="stock-input" data-stock="canon" value="0" min="0">
                        </div>
                        <div class="stock-item">
                            <label>Poignées</label>
                            <input type="number" class="stock-input" data-stock="poignee" value="0" min="0">
                        </div>
                        <div class="stock-item">
                            <label>Corps de pistolet</label>
                            <input type="number" class="stock-input" data-stock="corp" value="0" min="0">
                        </div>
                        <div class="stock-item">
                            <label>Pièces de métal</label>
                            <input type="number" class="stock-input" data-stock="metal" value="0" min="0">
                        </div>
                        <div class="stock-item">
                            <label>Polymères</label>
                            <input type="number" class="stock-input" data-stock="polymere" value="0" min="0">
                        </div>
                        <div class="stock-item">
                            <label>Minerais de fer</label>
                            <input type="number" class="stock-input" data-stock="minerai" value="0" min="0">
                        </div>
                        <div class="stock-item">
                            <label>Pétroles</label>
                            <input type="number" class="stock-input" data-stock="petrole" value="0" min="0">
                        </div>
                    </div>
                </div>

                {{-- Contract results --}}
                <div class="sim-section" id="contractResultsSection" style="display:none;">
                    <div class="sim-section-title">Résumé tous contrats</div>
                    <div class="results-table" id="contractTotalNeeded"></div>

                    <div class="sim-section-title">Ce qu'il reste à récupérer</div>
                    <div class="results-table" id="contractRemaining"></div>

                    <div class="sim-section-title">Détail plans</div>
                    <div class="results-table" id="contractPlansDetail"></div>
                </div>

            </div>
        </div>

    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/simulateur-armes.js') }}"></script>
@endsection
