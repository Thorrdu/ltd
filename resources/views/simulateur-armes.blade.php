@extends('layouts.app')

@section('title', 'Simulateur Craft Armes - BXL Life')
@section('css')
<link rel="stylesheet" href="{{ asset('css/simulateur-armes.css') }}">
@endsection
@section('body-class', 'simulateur-armes')

@section('content')
<div class="menu-board">
    <div class="inner-board">

        <div class="header">
            <div class="logo-row">
                <div class="station-title">Simulateur Craft Armes</div>
            </div>
            <div class="station-stars">★ ★ ★ ★ ★</div>
            <div class="station-subtitle">BXL Life — Atelier clandestin</div>
        </div>

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

            {{-- Pieces needed per weapon --}}
            <div class="sim-section-title">Pièces par arme</div>
            <div class="results-table" id="piecesTable"></div>

            {{-- Total intermediate materials --}}
            <div class="sim-section-title">Total pièces nécessaires</div>
            <div class="results-table" id="totalPieces"></div>

            {{-- Material crafting --}}
            <div class="sim-section-title">Craft de matériaux (table du sud)</div>
            <div class="results-table" id="materialCraft"></div>

            {{-- Raw materials --}}
            <div class="sim-section-title">Matières premières totales</div>
            <div class="results-table" id="rawMaterials"></div>

            {{-- Cost --}}
            <div class="sim-section-title">Coût estimé</div>
            <div class="results-table" id="costTable"></div>

            {{-- Craft time --}}
            <div class="sim-section-title">Temps de craft total</div>
            <div class="craft-time-display" id="craftTime"></div>
        </div>

    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/simulateur-armes.js') }}"></script>
@endsection
