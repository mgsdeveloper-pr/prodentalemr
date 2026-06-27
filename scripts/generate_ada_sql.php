<?php

$root = dirname(__DIR__);
$rows = json_decode(
    (string) file_get_contents($root . '/database/data/ada_procedure_codes_2026.json'),
    true,
    flags: JSON_THROW_ON_ERROR,
);

$escape = static fn ($value): string => str_replace(
    ["\\", "'", "\r", "\n"],
    ["\\\\", "''", ' ', ' '],
    (string) ($value ?? ''),
);

$values = array_map(
    static fn (array $row): string => sprintf(
        "('%s','%s','%s',%d,2026,'055368',%s,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)",
        $escape($row['procedure_code']),
        $escape($row['description']),
        $escape($row['class']),
        ($row['is_active'] ?? true) ? 1 : 0,
        isset($row['source_page']) ? (string) (int) $row['source_page'] : 'NULL',
    ),
    $rows,
);

$sql = <<<'SQL'
-- ADA Procedure Code catalog extracted from 055368.pdf
-- Laravel migration: 2026_06_25_000001_create_ada_procedure_codes_table

CREATE TABLE IF NOT EXISTS `ada_procedure_codes` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `procedure_code` VARCHAR(10) NOT NULL,
  `description` TEXT NOT NULL,
  `class` VARCHAR(150) NOT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `source_year` SMALLINT UNSIGNED NOT NULL DEFAULT 2026,
  `source_document` VARCHAR(255) NOT NULL DEFAULT '055368',
  `source_page` SMALLINT UNSIGNED NULL,
  `created_at` TIMESTAMP NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ada_procedure_codes_procedure_code_unique` (`procedure_code`),
  KEY `ada_procedure_codes_class_index` (`class`),
  KEY `ada_procedure_codes_is_active_index` (`is_active`),
  KEY `ada_procedure_codes_is_active_procedure_code_index` (`is_active`, `procedure_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SQL;

foreach (array_chunk($values, 150) as $chunk) {
    $sql .= <<<'SQL'

INSERT INTO `ada_procedure_codes`
(`procedure_code`,`description`,`class`,`is_active`,`source_year`,`source_document`,`source_page`,`created_at`,`updated_at`) VALUES

SQL;
    $sql .= implode(",\n", $chunk);
    $sql .= <<<'SQL'

ON DUPLICATE KEY UPDATE
  `description`=VALUES(`description`),
  `class`=VALUES(`class`),
  `is_active`=VALUES(`is_active`),
  `source_year`=VALUES(`source_year`),
  `source_document`=VALUES(`source_document`),
  `source_page`=VALUES(`source_page`),
  `updated_at`=CURRENT_TIMESTAMP;

SQL;
}

$sql .= <<<'SQL'

-- Register the migration only when it is not already present.
SET @ada_migration_batch := (SELECT COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`);
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_06_25_000001_create_ada_procedure_codes_table', @ada_migration_batch
WHERE NOT EXISTS (
  SELECT 1 FROM `migrations`
  WHERE `migration` = '2026_06_25_000001_create_ada_procedure_codes_table'
);

-- Verification query
SELECT COUNT(*) AS total_codes,
       SUM(`is_active` = 1) AS active_codes,
       SUM(`is_active` = 0) AS deleted_codes
FROM `ada_procedure_codes`;
SQL;

$outputDirectory = $root . '/database/sql';

if (! is_dir($outputDirectory)) {
    mkdir($outputDirectory, 0777, true);
}

$outputPath = $outputDirectory . '/2026_06_25_000001_create_ada_procedure_codes_table.sql';
file_put_contents($outputPath, $sql);

echo $outputPath . PHP_EOL;
echo count($rows) . ' ADA rows included.' . PHP_EOL;
