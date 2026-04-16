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
