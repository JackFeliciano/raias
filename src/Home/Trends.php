<?php

declare(strict_types=1);

namespace Raias\Home;

use Raias\Books\Journal;

/**
 * Revenue and expense per period, in the order the periods fall. A reader sees
 * which way the company turned without opening a single statement.
 */
final class Trends
{
    public function __construct(
        private readonly Journal $journal,
        private readonly array $kinds,
    ) {
    }

    /** @return list<array{period: string, revenue: int, expense: int}> */
    public function rows(): array
    {
        $out = [];
        foreach ($this->journal->periods() as $period) {
            $revenue = 0;
            $expense = 0;
            foreach ($this->journal->in($period) as $entry) {
                $kind = $this->kinds[$entry->account] ?? '';
                if ($kind === 'revenue' && $entry->side === 'credit') {
                    $revenue += $entry->cents;
                }
                if ($kind === 'expense' && $entry->side === 'debit') {
                    $expense += $entry->cents;
                }
            }
            $out[] = ['period' => $period, 'revenue' => $revenue, 'expense' => $expense];
        }

        return $out;
    }

    public function change(): int
    {
        $rows = array_values(array_filter($this->rows(), static fn (array $r): bool => $r['revenue'] > 0));
        if (count($rows) < 2) {
            return 0;
        }
        $first = $rows[0]['revenue'];
        $last = $rows[count($rows) - 1]['revenue'];

        return $last - $first;
    }
}
