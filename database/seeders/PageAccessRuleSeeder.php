<?php

namespace Database\Seeders;

use App\Models\PageAccessRule;
use Illuminate\Database\Seeder;

class PageAccessRuleSeeder extends Seeder
{
    public function run(): void
    {
        $rules = [
            ['page_key' => 'panel_admin',      'label' => 'Panneau Admin LTD',          'min_role' => 'treasurer',      'description' => 'Acces au panneau Filament catalogue LTD.',        'sort_order' => 10, 'is_system' => true],
            ['page_key' => 'panel_armurerie', 'label' => 'Panneau Armurerie',           'min_role' => 'officer',        'description' => 'Acces au panneau Filament armurerie.',            'sort_order' => 20, 'is_system' => true],
            ['page_key' => 'mc_hub',           'label' => 'Accueil MC',                 'min_role' => 'prospect',       'description' => 'Page d\'accueil du hub MC.',                      'sort_order' => 30, 'is_system' => true],
            ['page_key' => 'simulateur_armes', 'label' => 'Simulateur Armes',           'min_role' => 'member',         'description' => 'Outil de calcul de craft et rentabilite armes.',  'sort_order' => 40, 'is_system' => false],
            ['page_key' => 'simulateur_munitions', 'label' => 'Simulateur Munitions',   'min_role' => 'member',         'description' => 'Outil de calcul de craft munitions.',             'sort_order' => 50, 'is_system' => false],
            ['page_key' => 'espace_membres',   'label' => 'Espace Membres',             'min_role' => 'prospect',       'description' => 'Dashboard membre (stocks, ventes, contrats).',    'sort_order' => 60, 'is_system' => false],
            ['page_key' => 'membres_gestion',  'label' => 'Gestion des membres',        'min_role' => 'vice_president', 'description' => 'Page dediee a la gestion des utilisateurs.',      'sort_order' => 70, 'is_system' => true],
            ['page_key' => 'matrice_acces',    'label' => 'Matrice d\'acces',           'min_role' => 'treasurer',      'description' => 'Edition des regles d\'acces par page.',           'sort_order' => 80, 'is_system' => true],
            ['page_key' => 'ventes_rapides',   'label' => 'Ventes rapides',             'min_role' => 'member',         'description' => 'Saisie rapide des ventes (a venir).',             'sort_order' => 90, 'is_system' => false],
            ['page_key' => 'stocks_generique', 'label' => 'Stocks generiques',          'min_role' => 'officer',        'description' => 'Vue generique des stocks et attribution.',         'sort_order' => 100, 'is_system' => false],
            ['page_key' => 'stocks_validations','label' => 'Validations stock (tresorier)','min_role' => 'treasurer',    'description' => 'Approbation des attributions au-dela du seuil.',   'sort_order' => 101, 'is_system' => false],
            ['page_key' => 'stocks_import',    'label' => 'Import CSV/Excel stock',     'min_role' => 'treasurer',      'description' => 'Import de l\'inventaire physique (tresorier+).',   'sort_order' => 102, 'is_system' => false],
            ['page_key' => 'comptabilite',     'label' => 'Comptabilite MC',            'min_role' => 'treasurer',      'description' => 'Vue des comptes et transactions (a venir).',      'sort_order' => 110, 'is_system' => false],
            ['page_key' => 'classements',      'label' => 'Classements membres',        'min_role' => 'member',         'description' => 'Classements de productivite (a venir).',          'sort_order' => 120, 'is_system' => false],
            ['page_key' => 'demandes',         'label' => 'Demandes de remboursement',  'min_role' => 'member',         'description' => 'Soumission de demandes de remboursement.',        'sort_order' => 125, 'is_system' => false],
            ['page_key' => 'fiches_membres',   'label' => 'Fiches membres',             'min_role' => 'officer',        'description' => 'Fiche detaillee par membre (a venir).',           'sort_order' => 130, 'is_system' => false],
        ];

        foreach ($rules as $r) {
            PageAccessRule::updateOrCreate(
                ['page_key' => $r['page_key']],
                $r
            );
        }
    }
}
