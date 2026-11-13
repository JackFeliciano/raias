<?php

declare(strict_types=1);

namespace Raias\Home;

use Raias\Books\BalanceSheet;
use Raias\Books\IncomeStatement;
use Raias\Books\Journal;

/**
 * The ratios a reader asks for first. Each one reads the same journal the
 * statements read, and each one returns null when the denominator is zero,
 * because a ratio with nothing underneath it is not a number.
 */
final class Ratios
{
    public function __construct(
        private readonly Journal $journal,
        private readonly array $kinds,
    ) {
    }

    public function margin(): ?float
    {
        $income = new IncomeStatement($this->journal, $this->kinds);
        $period = $this->tradingPeriod($income);
        $revenue = $income->revenue($period);
        if ($revenue === 0) {
            return null;
        }

        return $income->result($period) / $revenue;
    }

    private function tradingPeriod(IncomeStatement $income): string
    {
        foreach (array_reverse($this->journal->periods()) as $period) {
            if ($income->revenue($period) > 0) {
                return $period;
            }
        }

        return $income->period();
    }

    public function current(): ?float
    {
        $balance = new BalanceSheet($this->journal, $this->kinds);
        $assets = $balance->byKind('asset');
        $liabilities = $balance->byKind('liability');
        $currentAssets = ($assets['Cash'] ?? 0) + ($assets['Receivables'] ?? 0);
        $currentLiabilities = ($liabilities['Payables'] ?? 0) + ($liabilities['Loan'] ?? 0);
        if ($currentLiabilities === 0) {
            return null;
        }

        return $currentAssets / $currentLiabilities;
    }

    public function leverage(): ?float
    {
        $balance = new BalanceSheet($this->journal, $this->kinds);
        $equity = $balance->equity();
        if ($equity === 0) {
            return null;
        }

        return $balance->total('liability') / $equity;
    }

    /** @return array<string, float|null> */
    public function all(): array
    {
        return [
            'Net margin' => $this->margin(),
            'Current ratio' => $this->current(),
            'Leverage' => $this->leverage(),
        ];
    }
}
