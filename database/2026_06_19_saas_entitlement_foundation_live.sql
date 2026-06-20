-- Live-safe SQL for migration:
-- 2026_06_19_000001_add_saas_entitlement_foundation
--
-- This script is intentionally idempotent for phpMyAdmin/live use.
-- It adds each column only when missing, then creates the audit table if missing.

SET @db := DATABASE();

DROP PROCEDURE IF EXISTS add_column_if_missing;
DELIMITER $$
CREATE PROCEDURE add_column_if_missing(
    IN table_name_value VARCHAR(128),
    IN column_name_value VARCHAR(128),
    IN alter_sql_value TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = @db
          AND TABLE_NAME = table_name_value
          AND COLUMN_NAME = column_name_value
    ) THEN
        SET @statement_sql = alter_sql_value;
        PREPARE stmt FROM @statement_sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$
DELIMITER ;

CALL add_column_if_missing('subscription_plans', 'plan_code', 'ALTER TABLE `subscription_plans` ADD COLUMN `plan_code` VARCHAR(255) NULL AFTER `name`');
CALL add_column_if_missing('subscription_plans', 'plan_type', 'ALTER TABLE `subscription_plans` ADD COLUMN `plan_type` VARCHAR(255) NOT NULL DEFAULT ''pms_verification'' AFTER `price`');
CALL add_column_if_missing('subscription_plans', 'workspace_mode', 'ALTER TABLE `subscription_plans` ADD COLUMN `workspace_mode` VARCHAR(255) NOT NULL DEFAULT ''choose'' AFTER `plan_type`');
CALL add_column_if_missing('subscription_plans', 'included_features', 'ALTER TABLE `subscription_plans` ADD COLUMN `included_features` JSON NULL AFTER `included_modules`');
CALL add_column_if_missing('subscription_plans', 'plan_limits', 'ALTER TABLE `subscription_plans` ADD COLUMN `plan_limits` JSON NULL AFTER `included_features`');
CALL add_column_if_missing('subscription_plans', 'managed_services_allowed', 'ALTER TABLE `subscription_plans` ADD COLUMN `managed_services_allowed` TINYINT(1) NOT NULL DEFAULT 0 AFTER `plan_limits`');
CALL add_column_if_missing('subscription_plans', 'trial_days', 'ALTER TABLE `subscription_plans` ADD COLUMN `trial_days` INT UNSIGNED NULL AFTER `managed_services_allowed`');
CALL add_column_if_missing('subscription_plans', 'demo_mode_available', 'ALTER TABLE `subscription_plans` ADD COLUMN `demo_mode_available` TINYINT(1) NOT NULL DEFAULT 0 AFTER `trial_days`');

CALL add_column_if_missing('subscriptions', 'previous_subscription_plan_id', 'ALTER TABLE `subscriptions` ADD COLUMN `previous_subscription_plan_id` BIGINT UNSIGNED NULL AFTER `subscription_plan_id`');
CALL add_column_if_missing('subscriptions', 'change_type', 'ALTER TABLE `subscriptions` ADD COLUMN `change_type` VARCHAR(255) NULL AFTER `previous_subscription_plan_id`');
CALL add_column_if_missing('subscriptions', 'effective_date', 'ALTER TABLE `subscriptions` ADD COLUMN `effective_date` DATE NULL AFTER `change_type`');
CALL add_column_if_missing('subscriptions', 'renewal_date', 'ALTER TABLE `subscriptions` ADD COLUMN `renewal_date` DATE NULL AFTER `effective_date`');
CALL add_column_if_missing('subscriptions', 'cancel_at_period_end', 'ALTER TABLE `subscriptions` ADD COLUMN `cancel_at_period_end` TINYINT(1) NOT NULL DEFAULT 0 AFTER `renewal_date`');
CALL add_column_if_missing('subscriptions', 'cancelled_at', 'ALTER TABLE `subscriptions` ADD COLUMN `cancelled_at` TIMESTAMP NULL AFTER `cancel_at_period_end`');
CALL add_column_if_missing('subscriptions', 'trial_starts_at', 'ALTER TABLE `subscriptions` ADD COLUMN `trial_starts_at` DATE NULL AFTER `cancelled_at`');
CALL add_column_if_missing('subscriptions', 'trial_ends_at', 'ALTER TABLE `subscriptions` ADD COLUMN `trial_ends_at` DATE NULL AFTER `trial_starts_at`');
CALL add_column_if_missing('subscriptions', 'is_demo', 'ALTER TABLE `subscriptions` ADD COLUMN `is_demo` TINYINT(1) NOT NULL DEFAULT 0 AFTER `trial_ends_at`');
CALL add_column_if_missing('subscriptions', 'service_status', 'ALTER TABLE `subscriptions` ADD COLUMN `service_status` VARCHAR(255) NOT NULL DEFAULT ''active'' AFTER `is_demo`');
CALL add_column_if_missing('subscriptions', 'service_status_reason', 'ALTER TABLE `subscriptions` ADD COLUMN `service_status_reason` VARCHAR(255) NULL AFTER `service_status`');
CALL add_column_if_missing('subscriptions', 'proration_mode', 'ALTER TABLE `subscriptions` ADD COLUMN `proration_mode` VARCHAR(255) NOT NULL DEFAULT ''none'' AFTER `service_status_reason`');
CALL add_column_if_missing('subscriptions', 'proration_amount', 'ALTER TABLE `subscriptions` ADD COLUMN `proration_amount` DECIMAL(10,2) NULL AFTER `proration_mode`');
CALL add_column_if_missing('subscriptions', 'entitlement_overrides', 'ALTER TABLE `subscriptions` ADD COLUMN `entitlement_overrides` JSON NULL AFTER `proration_amount`');
CALL add_column_if_missing('subscriptions', 'usage_snapshot', 'ALTER TABLE `subscriptions` ADD COLUMN `usage_snapshot` JSON NULL AFTER `entitlement_overrides`');
CALL add_column_if_missing('subscriptions', 'account_manager_user_id', 'ALTER TABLE `subscriptions` ADD COLUMN `account_manager_user_id` BIGINT UNSIGNED NULL AFTER `usage_snapshot`');
CALL add_column_if_missing('subscriptions', 'internal_notes', 'ALTER TABLE `subscriptions` ADD COLUMN `internal_notes` TEXT NULL AFTER `account_manager_user_id`');
CALL add_column_if_missing('subscriptions', 'billing_notes', 'ALTER TABLE `subscriptions` ADD COLUMN `billing_notes` TEXT NULL AFTER `internal_notes`');

CALL add_column_if_missing('organizations', 'lifecycle_status', 'ALTER TABLE `organizations` ADD COLUMN `lifecycle_status` VARCHAR(255) NOT NULL DEFAULT ''active'' AFTER `status`');
CALL add_column_if_missing('organizations', 'onboarding_status', 'ALTER TABLE `organizations` ADD COLUMN `onboarding_status` VARCHAR(255) NOT NULL DEFAULT ''pending'' AFTER `lifecycle_status`');
CALL add_column_if_missing('organizations', 'account_manager_user_id', 'ALTER TABLE `organizations` ADD COLUMN `account_manager_user_id` BIGINT UNSIGNED NULL AFTER `onboarding_status`');
CALL add_column_if_missing('organizations', 'internal_notes', 'ALTER TABLE `organizations` ADD COLUMN `internal_notes` TEXT NULL AFTER `account_manager_user_id`');

CALL add_column_if_missing('clinics', 'service_status', 'ALTER TABLE `clinics` ADD COLUMN `service_status` VARCHAR(255) NOT NULL DEFAULT ''active'' AFTER `clinic_operations_enabled`');
CALL add_column_if_missing('clinics', 'pms_service_status', 'ALTER TABLE `clinics` ADD COLUMN `pms_service_status` VARCHAR(255) NOT NULL DEFAULT ''active'' AFTER `service_status`');
CALL add_column_if_missing('clinics', 'verification_service_status', 'ALTER TABLE `clinics` ADD COLUMN `verification_service_status` VARCHAR(255) NOT NULL DEFAULT ''active'' AFTER `pms_service_status`');
CALL add_column_if_missing('clinics', 'managed_services_status', 'ALTER TABLE `clinics` ADD COLUMN `managed_services_status` VARCHAR(255) NOT NULL DEFAULT ''not_enabled'' AFTER `verification_service_status`');
CALL add_column_if_missing('clinics', 'trial_ends_at', 'ALTER TABLE `clinics` ADD COLUMN `trial_ends_at` DATE NULL AFTER `managed_services_status`');
CALL add_column_if_missing('clinics', 'demo_mode', 'ALTER TABLE `clinics` ADD COLUMN `demo_mode` TINYINT(1) NOT NULL DEFAULT 0 AFTER `trial_ends_at`');
CALL add_column_if_missing('clinics', 'feature_overrides', 'ALTER TABLE `clinics` ADD COLUMN `feature_overrides` JSON NULL AFTER `demo_mode`');
CALL add_column_if_missing('clinics', 'usage_snapshot', 'ALTER TABLE `clinics` ADD COLUMN `usage_snapshot` JSON NULL AFTER `feature_overrides`');
CALL add_column_if_missing('clinics', 'account_manager_user_id', 'ALTER TABLE `clinics` ADD COLUMN `account_manager_user_id` BIGINT UNSIGNED NULL AFTER `usage_snapshot`');
CALL add_column_if_missing('clinics', 'service_notes', 'ALTER TABLE `clinics` ADD COLUMN `service_notes` TEXT NULL AFTER `account_manager_user_id`');

CALL add_column_if_missing('users', 'default_workspace', 'ALTER TABLE `users` ADD COLUMN `default_workspace` VARCHAR(255) NULL AFTER `last_login_at`');
CALL add_column_if_missing('users', 'allowed_workspaces', 'ALTER TABLE `users` ADD COLUMN `allowed_workspaces` JSON NULL AFTER `default_workspace`');
CALL add_column_if_missing('users', 'feature_overrides', 'ALTER TABLE `users` ADD COLUMN `feature_overrides` JSON NULL AFTER `allowed_workspaces`');

CREATE TABLE IF NOT EXISTS `saas_entitlement_audit_logs` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `organization_id` BIGINT UNSIGNED NULL,
    `clinic_id` BIGINT UNSIGNED NULL,
    `subscription_id` BIGINT UNSIGNED NULL,
    `subscription_plan_id` BIGINT UNSIGNED NULL,
    `target_user_id` BIGINT UNSIGNED NULL,
    `actor_user_id` BIGINT UNSIGNED NULL,
    `event_type` VARCHAR(255) NOT NULL,
    `entity_type` VARCHAR(255) NULL,
    `entity_id` BIGINT UNSIGNED NULL,
    `before_values` JSON NULL,
    `after_values` JSON NULL,
    `notes` TEXT NULL,
    `ip_address` VARCHAR(45) NULL,
    `user_agent` TEXT NULL,
    `created_at` TIMESTAMP NULL,
    `updated_at` TIMESTAMP NULL,
    PRIMARY KEY (`id`),
    INDEX `saas_entitlement_entity_index` (`entity_type`, `entity_id`),
    INDEX `saas_entitlement_event_created_index` (`event_type`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP PROCEDURE IF EXISTS add_column_if_missing;

-- Verification query
SELECT 'subscription_plans.plan_code' AS item, COUNT(*) AS present
FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'subscription_plans' AND COLUMN_NAME = 'plan_code'
UNION ALL SELECT 'subscription_plans.plan_type', COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'subscription_plans' AND COLUMN_NAME = 'plan_type'
UNION ALL SELECT 'subscriptions.service_status', COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'subscriptions' AND COLUMN_NAME = 'service_status'
UNION ALL SELECT 'organizations.lifecycle_status', COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'organizations' AND COLUMN_NAME = 'lifecycle_status'
UNION ALL SELECT 'clinics.service_status', COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'clinics' AND COLUMN_NAME = 'service_status'
UNION ALL SELECT 'users.allowed_workspaces', COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'allowed_workspaces'
UNION ALL SELECT 'saas_entitlement_audit_logs table', COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'saas_entitlement_audit_logs';
