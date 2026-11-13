<?php

declare(strict_types=1);

namespace Raias\Home;

use Raias\Books\BalanceSheet;
use Raias\Books\IncomeStatement;

/**
 * One set of figures, two presentations. IFRS leads with the statement of
 * financial position and GAAP leads with the current and non-current split.
 * Both read the same journal, so neither can drift from the other.
 */
final class Statements
{
    public const IFRS = 'IFRS';
    public const GAAP = 'GAAP';

    public function __construct(
        private readonly BalanceSheet $balance,
        private readonly IncomeStatement $income,
    ) {
    }

    /** @return list<array{0: string, 1: int}> */
    public function balanceSheet(string $standard): array
    {
        if ($standard === self::GAAP) {
            return [
                ['Current assets', $this->current()],
                ['Total assets', $this->balance->total('asset')],
                ['Current liabilities', $this->balance->byKind('liability')['Payables'] ?? 0],
                ['Total liabilities', $this->balance->total('liability')],
                ['Total equity', $this->balance->equity()],
            ];
        }

        return [
            ['Assets', $this->balance->total('asset')],
            ['Liabilities', $this->balance->total('liability')],
            ['Equity', $this->balance->equity()],
        ];
    }

    /** @return list<array{0: string, 1: int}> */
    public function profitAndLoss(string $standard): array
    {
        $period = $this->income->period();
        $labels = $standard === self::GAAP
            ? ['Revenue', 'Operating expenses', 'Net result']
            : ['Revenue', 'Expenses', 'Result for the period'];

        return [
            [$labels[0], $this->income->revenue($period)],
            [$labels[1], $this->income->expenses($period)],
            [$labels[2], $this->income->result($period)],
        ];
    }

    private function current(): int
    {
        $assets = $this->balance->byKind('asset');

        return ($assets['Cash'] ?? 0) + ($assets['Receivables'] ?? 0);
    }
}
