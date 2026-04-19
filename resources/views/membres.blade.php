@extends('layouts.mc')

@section('title', 'LOST MC -- Gestion des membres')

@section('content')
<div class="menu-board mc-board-lg">
    <div class="inner-board">

        <div class="mc-page-header">
            <img src="{{ asset('img/3651.webp') }}" alt="Lost MC">
            <div class="mc-page-title">Gestion des membres</div>
            <div class="mc-page-motto">Le Tout-Puissant pardonne. Pas les Lost.</div>
            <a href="/mc" class="mc-page-back">&larr; Retour a l'accueil</a>
        </div>

        {{-- Acces refuse --}}
        <div id="membresNoAccess" class="contract-lock" style="display:none;">
            <div class="lock-text">
                Acces refuse. Cette page est reservee a partir du role Vice-President.
            </div>
        </div>

        {{-- Non connecte --}}
        <div id="membresNotLogged" class="contract-lock">
            <div class="lock-text">Connectez-vous via le bouton en haut a droite pour acceder a cette section.</div>
        </div>

        {{-- Contenu --}}
        <div id="membresContent" style="display:none;">

            <div class="sub-tab-bar">
                <button class="sub-tab active" data-subtab="list">Membres</button>
                <button class="sub-tab" data-subtab="create">Ajouter</button>
                <button class="sub-tab" data-subtab="matrix" id="subTabMatrix" style="display:none;">Matrice d'acces</button>
            </div>

            {{-- Sous-onglet : liste --}}
            <div class="sub-content active" id="sub-list">
                <div class="members-toolbar">
                    <input type="text" id="memberSearch" class="fm-input" placeholder="Rechercher un membre...">
                    <select id="memberRoleFilter" class="fm-input"><option value="">Tous les roles</option></select>
                </div>

                <div class="members-stats" id="membersStats"></div>

                <div class="members-table" id="membersTable">
                    <div class="empty-msg">Chargement...</div>
                </div>
            </div>

            {{-- Sous-onglet : creation --}}
            <div class="sub-content" id="sub-create">
                <div class="action-card">
                    <div class="action-card-title">Ajouter un membre</div>
                    <p class="action-hint">Le PIN sera defini ici et pourra etre reinitialise depuis la liste.</p>
                    <div class="action-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Nom RP</label>
                                <input type="text" id="gmNewName" class="fm-input" placeholder="Prenom Nom">
                            </div>
                            <div class="form-group sm">
                                <label>PIN</label>
                                <input type="text" id="gmNewPin" class="fm-input" placeholder="1234" maxlength="20">
                            </div>
                            <div class="form-group sm">
                                <label>Role</label>
                                <select id="gmNewRole" class="fm-input"></select>
                            </div>
                        </div>
                        <button class="action-btn sale-btn" id="gmBtnCreate">Creer le membre</button>
                    </div>
                </div>
            </div>

            {{-- Sous-onglet : matrice (superadmin) --}}
            <div class="sub-content" id="sub-matrix">
                <div class="action-card">
                    <div class="action-card-title">Matrice d'acces aux pages</div>
                    <p class="action-hint">
                        Chaque page/module a un role minimum requis. Seul le superadmin peut modifier cette matrice.
                        Le superadmin (tresorier) a toujours acces a toutes les pages.
                    </p>
                </div>
                <div class="matrix-table" id="matrixTable">
                    <div class="empty-msg">Chargement...</div>
                </div>
            </div>

        </div>

    </div>
</div>

<div id="gmPinModal" class="gm-modal" style="display:none;">
    <div class="gm-modal-inner">
        <div class="gm-modal-title">Nouveau PIN genere</div>
        <div class="gm-modal-pin" id="gmPinModalValue"></div>
        <p class="gm-modal-hint">Notez ce PIN et transmettez-le au membre. Il ne sera plus affiche.</p>
        <button class="action-btn sale-btn" id="gmPinModalClose">J'ai note</button>
    </div>
</div>
@endsection

@section('scripts')
<script>
window.MC_MEMBERS_LIST = {!! json_encode($members ?? []) !!};
</script>
<script src="{{ asset('js/membres.js') }}"></script>
@endsection
