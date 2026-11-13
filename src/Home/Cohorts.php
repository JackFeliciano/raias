<?php

declare(strict_types=1);

namespace Raias\Home;

/**
 * Customers grouped by the period they first bought in. The page reads the
 * group totals, which tells a reader whether the recent group spends like the
 * first one did.
 */
final class Cohorts
{
    /** @var array<string, array<string, int>> */
    private array $rows = [];

    public function add(string $party, string $period, int $cents): void
    {
        if (!isset($this->rows[$period])) {
            $this->rows[$period] = [];
        }
        $this->rows[$period][$party] = ($this->rows[$period][$party] ?? 0) + $cents;
    }

    /** @return array<string, int> */
    public function totals(): array
    {
        $out = [];
        foreach ($this->rows as $period => $parties) {
            $out[$period] = array_sum($parties);
        }
        ksort($out);

        return $out;
    }

    public function members(string $period): int
    {
        return count($this->rows[$period] ?? []);
    }
}
