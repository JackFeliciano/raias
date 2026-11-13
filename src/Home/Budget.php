<?php

declare(strict_types=1);

namespace Raias\Home;

use Raias\Books\IncomeStatement;
use Raias\Books\Journal;

/**
 * The figure a company planned for a period, held beside the figure the books
 * produced. Variance is actual minus budget, in cents, so a positive number
 * means the period came in above plan.
 */
final class Budget
{
    /** @param array<string, int> $planned */
    public function __construct(private array $planned = [])
    {
    }

    public function plan(string $period): int
    {
        return $this->planned[$period] ?? 0;
    }

    /** @return array<string, int> */
    public function planned(): array
    {
        return $this->planned;
    }

    /** @return list<array{period: string, actual: int, budget: int, variance: int}> */
    public function variance(Journal $journal, array $kinds): array
    {
        $income = new IncomeStatement($journal, $kinds);
        $out = [];
        foreach ($journal->periods() as $period) {
            $actual = $income->revenue($period);
            $budget = $this->plan($period);
            $out[] = [
                'period' => $period,
                'actual' => $actual,
                'budget' => $budget,
                'variance' => $actual - $budget,
            ];
        }

        return $out;
    }
}
