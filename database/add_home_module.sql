-- DEPRECADO: use la migración App\Database\Migrations\2026-05-25-120000_AddHomeModule
-- Se aplica automáticamente con:
--   - Config → Tenants → botón "Migrar BD actual" o "Migrar todos los tenants"
--   - o el botón de aprovisionar (servidor) en cada fila de tenant
--
-- Solo ejecute este SQL manualmente si no puede usar las migraciones desde la aplicación.

INSERT IGNORE INTO `dom_modules` (`name_lang_key`, `desc_lang_key`, `sort`, `module_id`) VALUES
('module_home', 'module_home_desc', 1, 'home');

INSERT IGNORE INTO `dom_permissions` (`module_id`, `person_id`)
SELECT 'home', p.person_id
FROM `dom_permissions` p
WHERE NOT EXISTS (
    SELECT 1 FROM `dom_permissions` h
    WHERE h.person_id = p.person_id AND h.module_id = 'home'
);
