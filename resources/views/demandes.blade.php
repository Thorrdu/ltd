@extends('layouts.mc')

@section('title', 'LOST MC -- Demandes')

@section('content')
<div class="menu-board" style="width:1000px;">
    <div class="inner-board">

        <div class="mc-page-header">
            <img src="{{ asset('img/3651.webp') }}" alt="Lost MC">
            <div class="mc-page-title">Demandes</div>
            <div class="mc-page-motto">Le Tout-Puissant pardonne. Pas les Lost.</div>
            <a href="/mc" class="mc-page-back">&larr; Retour a l'accueil</a>
        </div>

        {{-- Non connecte --}}
        <div id="reqNotLogged" class="contract-lock">
            <div class="lock-text">Connectez-vous via le bouton en haut a droite pour acceder a cette section.</div>
        </div>

        {{-- Acces refuse --}}
        <div id="reqNoAccess" class="contract-lock" style="display:none;">
            <div class="lock-text">Acces refuse. Cette page necessite au minimum le role Membre.</div>
        </div>

        {{-- Contenu --}}
        <div id="reqContent" style="display:none;">

            {{-- Stats --}}
            <div class="members-stats" id="reqStats"></div>

            {{-- Sub-tabs --}}
            <div class="sub-tab-bar">
                <button class="sub-tab active" data-subtab="new">Nouvelle demande</button>
                <button class="sub-tab" data-subtab="mine">Mes demandes</button>
                <button class="sub-tab sub-tab-treasurer" data-subtab="all" style="display:none;">Toutes les demandes</button>
            </div>

            {{-- TAB : Nouvelle demande --}}
            <div class="sub-tab-content active" data-subtab="new">
                <div class="req-form">
                    <div class="field-row">
                        <label class="field-label">Categorie</label>
                        <select id="reqCategory" class="lock-input">
                            <option value="">-- Choisir --</option>
                            @foreach($categories as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field-row">
                        <label class="field-label">Montant ($)</label>
                        <input type="number" id="reqAmount" class="lock-input" min="1" placeholder="Ex : 5000">
                    </div>
                    <div class="field-row">
                        <label class="field-label">Motif / description</label>
                        <textarea id="reqDescription" class="lock-input" rows="3" maxlength="1000" placeholder="Decrivez la raison de votre demande..."></textarea>
                    </div>
                    <div class="field-row">
                        <label class="field-label">Justificatif (photo)</label>
                        <div class="req-upload-zone" id="reqUploadZone">
                            <input type="file" id="reqPhoto" accept="image/*" class="req-file-input">
                            <div class="req-upload-label" id="reqUploadLabel">Cliquez ou deposez une image (max 5 Mo)</div>
                            <div class="req-upload-preview" id="reqPreview" style="display:none;">
                                <img id="reqPreviewImg" alt="Apercu">
                                <button type="button" class="req-remove-photo" id="reqRemovePhoto">&times;</button>
                            </div>
                        </div>
                    </div>
                    <button class="btn-primary" id="reqBtnSubmit">Soumettre la demande</button>
                </div>
            </div>

            {{-- TAB : Mes demandes --}}
            <div class="sub-tab-content" data-subtab="mine" style="display:none;">
                <div class="req-filter-bar">
                    <select id="reqMyStatus" class="lock-input lock-input-sm">
                        <option value="all">Tous les statuts</option>
                        <option value="pending">En attente</option>
                        <option value="approved">Approuvees</option>
                        <option value="rejected">Refusees</option>
                    </select>
                </div>
                <div class="members-table" id="reqMyList">
                    <div class="empty-msg">Chargement...</div>
                </div>
            </div>

            {{-- TAB : Toutes (treasurer+) --}}
            <div class="sub-tab-content" data-subtab="all" style="display:none;">
                <div class="req-filter-bar">
                    <select id="reqAllStatus" class="lock-input lock-input-sm">
                        <option value="pending" selected>En attente</option>
                        <option value="all">Tous les statuts</option>
                        <option value="approved">Approuvees</option>
                        <option value="rejected">Refusees</option>
                    </select>
                </div>
                <div class="members-table" id="reqAllList">
                    <div class="empty-msg">Chargement...</div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="{{ asset('js/demandes.js') }}?v={{ filemtime(public_path('js/demandes.js')) }}"></script>
@endsection
