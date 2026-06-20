INSERT IGNORE INTO `roles` (`name`, `guard_name`, `created_at`, `updated_at`) VALUES
('dso_admin', 'web', NOW(), NOW()),
('dso_manager', 'web', NOW(), NOW()),
('dso_viewer', 'web', NOW(), NOW());

INSERT IGNORE INTO `permissions` (`name`, `guard_name`, `created_at`, `updated_at`) VALUES
('dso.dashboard.add', 'web', NOW(), NOW()),
('dso.dashboard.view', 'web', NOW(), NOW()),
('dso.dashboard.update', 'web', NOW(), NOW()),
('dso.dashboard.delete', 'web', NOW(), NOW()),
('dso.clinics.add', 'web', NOW(), NOW()),
('dso.clinics.view', 'web', NOW(), NOW()),
('dso.clinics.update', 'web', NOW(), NOW()),
('dso.clinics.delete', 'web', NOW(), NOW()),
('dso.reports.add', 'web', NOW(), NOW()),
('dso.reports.view', 'web', NOW(), NOW()),
('dso.reports.update', 'web', NOW(), NOW()),
('dso.reports.delete', 'web', NOW(), NOW()),
('dso.users.add', 'web', NOW(), NOW()),
('dso.users.view', 'web', NOW(), NOW()),
('dso.users.update', 'web', NOW(), NOW()),
('dso.users.delete', 'web', NOW(), NOW()),
('dso.roles_permissions.add', 'web', NOW(), NOW()),
('dso.roles_permissions.view', 'web', NOW(), NOW()),
('dso.roles_permissions.update', 'web', NOW(), NOW()),
('dso.roles_permissions.delete', 'web', NOW(), NOW()),
('dso.settings.add', 'web', NOW(), NOW()),
('dso.settings.view', 'web', NOW(), NOW()),
('dso.settings.update', 'web', NOW(), NOW()),
('dso.settings.delete', 'web', NOW(), NOW());

INSERT IGNORE INTO `role_has_permissions` (`permission_id`, `role_id`)
SELECT `permissions`.`id`, `roles`.`id`
FROM `permissions`
JOIN `roles`
WHERE `roles`.`name` = 'dso_admin'
  AND `permissions`.`name` LIKE 'dso.%';

INSERT IGNORE INTO `role_has_permissions` (`permission_id`, `role_id`)
SELECT `permissions`.`id`, `roles`.`id`
FROM `permissions`
JOIN `roles`
WHERE `roles`.`name` = 'dso_manager'
  AND `permissions`.`name` IN (
    'dso.dashboard.view',
    'dso.clinics.view',
    'dso.clinics.update',
    'dso.reports.view',
    'dso.users.view',
    'dso.users.add',
    'dso.users.update'
  );

INSERT IGNORE INTO `role_has_permissions` (`permission_id`, `role_id`)
SELECT `permissions`.`id`, `roles`.`id`
FROM `permissions`
JOIN `roles`
WHERE `roles`.`name` = 'dso_viewer'
  AND `permissions`.`name` IN (
    'dso.dashboard.view',
    'dso.clinics.view',
    'dso.reports.view'
  );

INSERT IGNORE INTO `role_has_permissions` (`permission_id`, `role_id`)
SELECT `permissions`.`id`, `roles`.`id`
FROM `permissions`
JOIN `roles`
WHERE `roles`.`name` = 'saas_admin'
  AND `permissions`.`name` LIKE 'dso.%';
