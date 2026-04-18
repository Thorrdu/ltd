@extends('layouts.mc')

@section('title', 'LOST MC -- Ventes rapides')

@section('content')
<div class="menu-board" style="width:1000px;">
    <div class="inner-board">

        <div class="mc-page-header">
            <img src="{{ asset('img/3651.webp') }}" alt="Lost MC">
            <div class="mc-page-title">Ventes rapides</div>
            <div class="mc-page-motto">Le Tout-Puissant pardonne. Pas les Lost.</div>
            <a href="/mc" class="mc-page-back">&larr; Retour a l'accueil</a>
        </div>

        {{-- Non connecte --}}
        <div id="ventesNotLogged" class="contract-lock">
            <div class="lock-text">Connectez-vous via le bouton en haut a droite pour acceder a cette section.</div>
        </div>

        {{-- Acces refuse --}}
        <div id="ventesNoAccess" class="contract-lock" style="display:none;">
            <div class="lock-text">Acces refuse. Cette page necessite au minimum le role Membre.</div>
        </div>

        {{-- Contenu --}}
        <div id="ventesContent" style="display:none;">

            <div class="sub-tab-bar">
                <button class="sub-tab active" data-subtab="express">Vente Express</button>
                <button class="sub-tab" data-subtab="new">Vente classique</button>
                <button class="sub-tab" data-subtab="history">Historique</button>
            </div>

            {{-- Sous-onglet : vente express --}}
            <div class="sub-content active" id="sub-express">

                {{-- Mes articles attribues (sur moi) --}}
                <div class="action-card" style="margin-bottom:14px;">
                    <div class="action-card-title">Mes articles (attribues sur moi)</div>
                    <p class="action-hint">Articles que vous avez en votre possession. La vente reconcilie automatiquement l'attribution.</p>
                    <div id="veMyAttributions">
                        <div class="empty-msg">Chargement...</div>
                    </div>
                </div>

                <div class="action-card" style="margin-bottom:14px;">
                    <div class="action-card-title">Catalogue vente rapide</div>
                </div>
                <div class="ve-accordions" id="veAccordions">
                    {{-- Accordion panels rendered by JS, grouped by category --}}
                    <div class="empty-msg">Chargement du catalogue...</div>
                </div>

                {{-- Fixed recap bar --}}
                <div class="ve-recap" id="veRecap" style="display:none;">
                    <div class="ve-recap-items" id="veRecapItems"></div>
                    <div class="ve-recap-footer">
                        <div class="ve-recap-total">
                            <span>Total theorique :</span>
                            <strong id="veRecapTotal">$0</strong>
                        </div>
                        <div class="form-row" style="margin:8px 0 0;">
                            <div class="form-group sm">
                                <label>Argent rapportE ($)</label>
                                <input type="number" id="veActual" class="fm-input" placeholder="Encaisse" min="0">
                            </div>
                            <div class="form-group">
                                <label>Acheteur</label>
                                <input type="text" id="veBuyer" class="fm-input" placeholder="Nom, pseudo ou organisation">
                            </div>
                            <div class="form-group">
                                <label>Notes <span class="optional">(opt.)</span></label>
                                <input type="text" id="veNotes" class="fm-input" placeholder="Contexte...">
                            </div>
                        </div>
                        <button class="action-btn sale-btn" id="veBtnSave" style="margin-top:8px;">Valider la vente</button>
                    </div>
                </div>

                <div class="action-card" style="margin-top:14px;">
                    <div class="action-card-title">Mes ventes du jour</div>
                    <div class="members-stats" id="veTodayStats"></div>
                    <div class="members-table" id="veTodayList">
                        <div class="empty-msg">Aucune vente enregistree aujourd'hui.</div>
                    </div>
                </div>
            </div>

            {{-- Sous-onglet : vente classique (ancien formulaire) --}}
            <div class="sub-content" id="sub-new">
                <div class="action-card">
                    <div class="action-card-title">Enregistrer une vente</div>
                    <p class="action-hint">
                        Recherchez l'article dans la liste, indiquez la quantite et le montant total encaisse.
                        Le prix unitaire est calcule automatiquement.
                    </p>
                    <div class="action-form">
                        <div class="form-row">
                            <div class="form-group full">
                                <label style="display:flex; justify-content:space-between; align-items:center;">
                                    <span>Article</span>
                                    <label class="tiny-toggle" style="font-weight:normal; font-size:11px; color:#9ca3af; cursor:pointer;">
                                        <input type="checkbox" id="vAdHocToggle" style="vertical-align:middle; margin-right:4px;">
                                        Vente hors stock (service, info...)
                                    </label>
                                </label>
                                <select id="vItem" class="fm-input"></select>

                                <div id="vAdHocFields" style="display:none; margin-top:6px;">
                                    <div class="form-row" style="margin:0;">
                                        <div class="form-group full">
                                            <input type="text" id="vAdHocName" class="fm-input" placeholder="Description (service, information, etc.)" maxlength="150">
                                        </div>
                                    </div>
                                    <div style="font-size:11px; color:#9ca3af; margin-top:2px;">
                                        Vente enregistree pour la comptabilite sans impact sur le stock.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group sm">
                                <label>Quantite</label>
                                <input type="number" id="vQty" class="fm-input" value="1" min="1" max="999999999">
                            </div>
                            <div class="form-group sm">
                                <label>Total ($)</label>
                                <input type="number" id="vTotal" class="fm-input" placeholder="0" min="0" step="1">
                            </div>
                            <div class="form-group sm">
                                <label>Unitaire ($)</label>
                                <input type="text" id="vUnit" class="fm-input" placeholder="auto" readonly>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Acheteur</label>
                                <input type="text" id="vBuyer" class="fm-input" placeholder="Nom, pseudo ou organisation">
                            </div>
                            <div class="form-group full">
                                <label>Notes <span class="optional">(facultatif)</span></label>
                                <input type="text" id="vNotes" class="fm-input" placeholder="Contexte, remise, etc.">
                            </div>
                        </div>

                        <button class="action-btn sale-btn" id="vBtnSave">Enregistrer la vente</button>
                    </div>
                </div>

                <div class="action-card" style="margin-top:14px;">
                    <div class="action-card-title">Mes ventes du jour</div>
                    <div class="members-stats" id="vTodayStats"></div>
                    <div class="members-table" id="vTodayList">
                        <div class="empty-msg">Aucune vente enregistree aujourd'hui.</div>
                    </div>
                </div>
            </div>

            {{-- Sous-onglet : historique --}}
            <div class="sub-content" id="sub-history">
                <div class="action-card">
                    <div class="action-card-title">Historique</div>
                    <div class="members-toolbar">
                        <select id="vScope" class="fm-input">
                            <option value="mine">Mes ventes</option>
                            <option value="all">Toutes les ventes</option>
                        </select>
                        <select id="vPeriod" class="fm-input">
                            <option value="today">Aujourd'hui</option>
                            <option value="week">Cette semaine</option>
                            <option value="month">Ce mois</option>
                            <option value="all">Tout</option>
                        </select>
                    </div>
                    <div class="members-stats" id="vHistStats"></div>
                    <div class="members-table" id="vHistList">
                        <div class="empty-msg">Chargement...</div>
                    </div>
                </div>
            </div>

        </div>

    </div>
</div>
@endsection

@section('scripts')
<script>
window.MC_VENTES_CATALOG = {!! $catalogJson !!};
</script>
<script src="{{ asset('js/ventes.js') }}?v={{ filemtime(public_path('js/ventes.js')) }}"></script>
@endsection
