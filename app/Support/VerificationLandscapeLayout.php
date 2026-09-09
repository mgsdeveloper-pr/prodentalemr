<?php

namespace App\Support;

class VerificationLandscapeLayout
{
    public static function columns(iterable $sections): array
    {
        $sections = collect($sections)->values();
        if ($sections->count() < 2) {
            return [$sections, collect()];
        }

        // Estimate wrapped rows, then keep section order while balancing column heights.
        $weights = $sections->map(function (array $section): int {
            $headingRows = collect($section['rows'])->contains(fn (array $row): bool => ($row['kind'] ?? null) === 'coverage_matrix') ? 3 : 2;
            return $headingRows + collect($section['rows'])->sum(function (array $row): int {
                $value = ($row['kind'] ?? null) === 'coverage_matrix'
                    ? ($row['deductible'] ?? '-').' | '.($row['percent'] ?? '-')
                    : ($row['value'] ?? '-');

                return max(
                    static::lines((string) ($row['label'] ?? ''), 58),
                    static::lines((string) $value, 58),
                );
            });
        });
        $total = $weights->sum();
        $left = 0;
        $bestHeight = PHP_INT_MAX;
        $split = 1;
        for ($index = 0; $index < $sections->count() - 1; $index++) {
            $left += $weights[$index];
            $height = max($left, $total - $left);
            if ($height < $bestHeight) {
                $bestHeight = $height;
                $split = $index + 1;
            }
        }

        return [$sections->take($split), $sections->slice($split)->values()];
    }

    protected static function lines(string $text, int $width): int
    {
        return collect(preg_split('/\R/u', $text) ?: [''])
            ->sum(fn (string $line): int => max(1, (int) ceil(mb_strwidth($line) / $width)));
    }
}
