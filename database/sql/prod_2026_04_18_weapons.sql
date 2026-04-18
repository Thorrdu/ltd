-- ============================================================
-- Migration 1: Nouvelles colonnes recette + armes + stock_items
-- ============================================================

-- 1. Ajouter les colonnes recette
ALTER TABLE `weapons`
  ADD COLUMN `recipe_crosse` TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER `recipe_corp`,
  ADD COLUMN `recipe_corp_smg` TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER `recipe_crosse`,
  ADD COLUMN `recipe_corp_rifle` TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER `recipe_corp_smg`;

-- 2. Inserer les nouvelles armes
INSERT INTO `weapons` (`name`, `slug`, `craft_time_seconds`, `sell_price`, `reference_purchase_price`, `price_min`, `price_max`, `recipe_plans`, `recipe_ressort`, `recipe_canon`, `recipe_poignee`, `recipe_corp`, `recipe_crosse`, `recipe_corp_smg`, `recipe_corp_rifle`, `recipe_metal`, `recipe_polymere`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES
('Micro SMG', 'micro_smg', 30, 300000, 300000, 210000, 420000, 1, 2, 1, 1, 0, 1, 1, 0, 15, 20, 8, 1, NOW(), NOW()),
('Mini SMG',  'mini_smg',  30, 300000, 300000, 190000, 380000, 1, 2, 1, 1, 0, 1, 1, 0, 15, 20, 9, 1, NOW(), NOW()),
('Tec 9',     'tec9',      30, 325000, 325000, 190000, 380000, 1, 2, 1, 1, 0, 1, 1, 0, 15, 20, 10, 1, NOW(), NOW()),
('AKU',       'aku',       45, 500000, 500000, 450000, 900000, 0, 5, 1, 1, 0, 1, 0, 1, 25, 35, 11, 1, NOW(), NOW()),
('Pompe',     'pompe',     50, 600000, 600000, 450000, 900000, 1, 4, 1, 1, 0, 1, 0, 1, 30, 50, 12, 1, NOW(), NOW());

-- 3. Mettre a jour AK-47
UPDATE `weapons` SET
  `reference_purchase_price` = 800000,
  `recipe_corp` = 0,
  `recipe_crosse` = 1,
  `recipe_corp_rifle` = 1
WHERE `slug` = 'ak47';

-- 4. Creer les stock_items weapon_finished
INSERT INTO `stock_items` (`category`, `slug`, `name`, `weapon_id`, `default_sell_price`, `default_purchase_price`, `price_min`, `price_max`, `is_sellable`, `is_active`, `sort_order`, `created_at`, `updated_at`)
SELECT 'weapon_finished', CONCAT('weapon_', w.slug), w.name, w.id, w.sell_price, w.reference_purchase_price, w.price_min, w.price_max, 1, 1,
  (SELECT COALESCE(MAX(sort_order), 0) FROM `stock_items` WHERE category = 'weapon_finished') + ROW_NUMBER() OVER (ORDER BY w.sort_order),
  NOW(), NOW()
FROM `weapons` w
WHERE w.slug IN ('micro_smg', 'mini_smg', 'tec9', 'aku', 'pompe');

-- 5. Creer plan_micro_smg (nouveau)
INSERT INTO `stock_items` (`category`, `slug`, `name`, `weapon_id`, `default_sell_price`, `is_sellable`, `is_active`, `sort_order`, `created_at`, `updated_at`)
SELECT 'weapon_plan', 'plan_micro_smg', 'Plan Micro SMG', w.id, 10000, 1, 1,
  (SELECT COALESCE(MAX(sort_order), 0) + 1 FROM `stock_items` WHERE category = 'weapon_plan'),
  NOW(), NOW()
FROM `weapons` w WHERE w.slug = 'micro_smg';

-- 6. Renommer et lier les plans existants
UPDATE `stock_items` SET `weapon_id` = (SELECT id FROM `weapons` WHERE slug = 'mini_smg')
WHERE `slug` = 'plan_mini_smg' AND `weapon_id` IS NULL;

UPDATE `stock_items` SET
  `weapon_id` = (SELECT id FROM `weapons` WHERE slug = 'tec9'),
  `slug` = 'plan_tec9',
  `name` = 'Plan Tec 9'
WHERE `slug` = 'plan_machine_pistol';

UPDATE `stock_items` SET
  `weapon_id` = (SELECT id FROM `weapons` WHERE slug = 'pompe'),
  `slug` = 'plan_pompe',
  `name` = 'Plan Pompe'
WHERE `slug` = 'plan_fusil_pompe';

-- 7. Mettre a jour stock_item AK-47
UPDATE `stock_items` SET `default_purchase_price` = 800000 WHERE `slug` = 'weapon_ak47';

-- 8. Enregistrer les migrations
INSERT INTO `migrations` (`migration`, `batch`) VALUES
('2026_04_18_120000_add_smg_rifle_weapons_and_recipe_columns', (SELECT COALESCE(MAX(batch), 0) + 1 FROM (SELECT batch FROM `migrations`) t)),
('2026_04_18_130000_set_sell_prices_for_new_weapons', (SELECT COALESCE(MAX(batch), 0) FROM (SELECT batch FROM `migrations`) t));
