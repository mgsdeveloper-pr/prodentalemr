CREATE TABLE IF NOT EXISTS `dsos` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `legal_name` VARCHAR(255) NULL,
    `account_code` VARCHAR(255) NULL,
    `primary_contact_name` VARCHAR(255) NULL,
    `email` VARCHAR(255) NULL,
    `phone` VARCHAR(255) NULL,
    `address` TEXT NULL,
    `city` VARCHAR(255) NULL,
    `state` VARCHAR(255) NULL,
    `zip_code` VARCHAR(255) NULL,
    `country` VARCHAR(255) NOT NULL DEFAULT 'USA',
    `lifecycle_status` VARCHAR(255) NOT NULL DEFAULT 'active',
    `billing_mode` VARCHAR(255) NOT NULL DEFAULT 'centralized',
    `service_status` VARCHAR(255) NOT NULL DEFAULT 'active',
    `status` TINYINT(1) NOT NULL DEFAULT 1,
    `account_manager_user_id` BIGINT UNSIGNED NULL,
    `internal_notes` TEXT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    `deleted_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `dsos_account_code_unique` (`account_code`),
    KEY `dsos_account_manager_user_id_foreign` (`account_manager_user_id`),
    CONSTRAINT `dsos_account_manager_user_id_foreign`
        FOREIGN KEY (`account_manager_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `organizations`
    ADD COLUMN `dso_id` BIGINT UNSIGNED NULL AFTER `id`,
    ADD KEY `organizations_dso_id_foreign` (`dso_id`),
    ADD CONSTRAINT `organizations_dso_id_foreign`
        FOREIGN KEY (`dso_id`) REFERENCES `dsos` (`id`) ON DELETE SET NULL;

ALTER TABLE `subscriptions`
    ADD COLUMN `dso_id` BIGINT UNSIGNED NULL AFTER `id`,
    ADD COLUMN `clinic_id` BIGINT UNSIGNED NULL AFTER `organization_id`,
    ADD COLUMN `subscription_scope` VARCHAR(255) NOT NULL DEFAULT 'organization' AFTER `clinic_id`,
    ADD KEY `subscriptions_dso_id_foreign` (`dso_id`),
    ADD KEY `subscriptions_clinic_id_foreign` (`clinic_id`),
    ADD CONSTRAINT `subscriptions_dso_id_foreign`
        FOREIGN KEY (`dso_id`) REFERENCES `dsos` (`id`) ON DELETE SET NULL,
    ADD CONSTRAINT `subscriptions_clinic_id_foreign`
        FOREIGN KEY (`clinic_id`) REFERENCES `clinics` (`id`) ON DELETE SET NULL;

ALTER TABLE `users`
    ADD COLUMN `dso_id` BIGINT UNSIGNED NULL AFTER `id`,
    ADD KEY `users_dso_id_foreign` (`dso_id`),
    ADD CONSTRAINT `users_dso_id_foreign`
        FOREIGN KEY (`dso_id`) REFERENCES `dsos` (`id`) ON DELETE SET NULL;

INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_06_19_000002_add_dso_enterprise_accounts', COALESCE(MAX(`batch`), 0) + 1
FROM `migrations`
WHERE NOT EXISTS (
    SELECT 1
    FROM (SELECT `migration` FROM `migrations`) AS existing_migrations
    WHERE existing_migrations.`migration` = '2026_06_19_000002_add_dso_enterprise_accounts'
);
