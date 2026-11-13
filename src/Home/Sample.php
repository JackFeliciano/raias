<?php

declare(strict_types=1);

namespace Raias\Home;

use Raias\Books\AnnualReport;
use Raias\Books\BalanceSheet;
use Raias\Books\Entry;
use Raias\Books\IncomeStatement;
use Raias\Books\Journal;
use Raias\Books\RelatedParties;
use Raias\Connect\Ledger;
use Raias\Home\Budget;
use Raias\Home\Cohorts;
use Raias\Home\Trends;

/**
 * A small company with eight periods of books, two customer cohorts and a
 * budget, so every figure on the page comes from one set of lines. The figures
 * are invented and they belong to this demo only.
 */
final class Sample
{
    private Journal $journal;
    /** @var array<string, string> */
    private array $kinds;
    private Budget $budget;
    private Cohorts $cohorts;

    public function __construct()
    {
        $this->journal = new Journal();
        $this->kinds = [
            'Cash' => 'asset',
            'Receivables' => 'asset',
            'Payables' => 'liability',
            'Loan' => 'liability',
            'Capital' => 'equity',
            'Sales' => 'revenue',
            'Wages' => 'expense',
            'Freight' => 'expense',
        ];
        $lines = [
            ['2026-01-08', 'Cash', 900000, Entry::DEBIT, 'Northwind', 'Opening capital'],
            ['2026-01-08', 'Capital', 900000, Entry::CREDIT, 'Northwind', 'Opening capital'],
            ['2026-02-14', 'Cash', 480000, Entry::DEBIT, 'Northwind', 'Two invoices settled'],
            ['2026-02-14', 'Sales', 480000, Entry::CREDIT, 'Northwind', 'Two invoices settled'],
            ['2026-03-03', 'Payables', 150000, Entry::CREDIT, 'Halloran', 'Stock bought on terms'],
            ['2026-03-03', 'Freight', 150000, Entry::DEBIT, 'Halloran', 'Freight on the stock'],
            ['2026-04-21', 'Wages', 210000, Entry::DEBIT, '', 'Monthly payroll'],
            ['2026-04-21', 'Cash', 210000, Entry::CREDIT, '', 'Monthly payroll'],
            ['2026-05-06', 'Receivables', 320000, Entry::DEBIT, 'Halloran', 'Goods delivered'],
            ['2026-05-06', 'Sales', 320000, Entry::CREDIT, 'Halloran', 'Goods delivered'],
            ['2026-06-18', 'Loan', 500000, Entry::CREDIT, 'Meridian Bank', 'Term loan drawn'],
            ['2026-06-18', 'Cash', 500000, Entry::DEBIT, 'Meridian Bank', 'Term loan drawn'],
            ['2026-07-02', 'Wages', 215000, Entry::DEBIT, '', 'Monthly payroll'],
            ['2026-07-02', 'Cash', 215000, Entry::CREDIT, '', 'Monthly payroll'],
            ['2026-08-19', 'Cash', 140000, Entry::DEBIT, 'Northwind', 'Part payment'],
            ['2026-08-19', 'Receivables', 140000, Entry::CREDIT, 'Northwind', 'Part payment'],
        ];
        foreach ($lines as [$day, $account, $cents, $side, $party, $note]) {
            $this->journal->post(new Entry($day, $account, $cents, $side, $party, $note));
        }

        $this->budget = new Budget([
            '2026-01' => 0,
            '2026-02' => 500000,
            '2026-03' => 0,
            '2026-04' => 0,
            '2026-05' => 300000,
            '2026-06' => 0,
            '2026-07' => 350000,
            '2026-08' => 400000,
        ]);

        $this->cohorts = new Cohorts();
        $this->cohorts->add('Northwind', '2026-01', 480000);
        $this->cohorts->add('Halloran', '2026-05', 320000);
        $this->cohorts->add('Northwind', '2026-08', 140000);
    }

    public function journal(): Journal
    {
        return $this->journal;
    }

    public function budget(): Budget
    {
        return $this->budget;
    }

    public function cohorts(): Cohorts
    {
        return $this->cohorts;
    }

    /** @return array<string, string> */
    public function kindsList(): array
    {
        return $this->kinds;
    }

    public function trends(): Trends
    {
        return new Trends($this->journal, $this->kinds);
    }

    public function ratios(): Ratios
    {
        return new Ratios($this->journal, $this->kinds);
    }

    public function balance(): BalanceSheet
    {
        return new BalanceSheet($this->journal, $this->kinds);
    }

    public function income(): IncomeStatement
    {
        return new IncomeStatement($this->journal, $this->kinds);
    }

    public function parties(): RelatedParties
    {
        return new RelatedParties($this->journal, ['Northwind', 'Halloran', 'Meridian Bank'], '2026-05');
    }

    public function report(): AnnualReport
    {
        $balance = $this->balance();
        $income = $this->income();
        $period = $income->period();

        return new AnnualReport(
            'Sample company, period ' . $period,
            [
                'Assets' => $balance->byKind('asset'),
                'Liabilities' => $balance->byKind('liability'),
                'Equity' => ['Equity' => $balance->equity()],
                'Revenue' => ['Revenue' => $income->revenue($period)],
                'Expenses' => ['Expenses' => $income->expenses($period)],
            ],
            ['db:sample/demo/entries'],
        );
    }

    public function ledger(): Ledger
    {
        return new Ledger();
    }
}
