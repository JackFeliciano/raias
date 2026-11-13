<?php

declare(strict_types=1);

namespace Raias\App;

use Raias\Books\Entry;
use Raias\Home\Sample;
use Raias\Home\Statements;

/**
 * Every page RAIAS prints. The markup is written by hand: no template engine,
 * no escaping library beyond htmlspecialchars, so a reader can follow a figure
 * from the record to the table.
 */
final class Views
{
    public function __construct(private readonly string $base)
    {
    }

    private function url(string $path): string
    {
        $base = '/' . trim($this->base, '/');

        return ($base === '/' ? '' : $base) . $path;
    }

    private function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    private function money(int $cents): string
    {
        $sign = $cents < 0 ? '-' : '';
        $cents = abs($cents);
        $whole = intdiv($cents, 100);
        $rest = $cents % 100;
        $units = strrev(implode(',', str_split(strrev((string) $whole), 3)));

        return $sign . $units . '.' . str_pad((string) $rest, 2, '0', STR_PAD_LEFT);
    }

    private function ratio(?float $value): string
    {
        return $value === null ? 'not defined' : number_format($value, 2, '.', '');
    }

    private function table(array $rows, string $caption = ''): string
    {
        $html = '<table>';
        if ($caption !== '') {
            $html .= '<caption class="stage">' . $this->e($caption) . '</caption>';
        }
        $html .= '<tbody>';
        foreach ($rows as [$label, $value, $class]) {
            $html .= '<tr><th>' . $this->e((string) $label) . '</th>'
                . '<td class="n ' . $this->e((string) $class) . '">' . $this->e((string) $value) . '</td></tr>';
        }
        $html .= '</tbody></table>';

        return $html;
    }

    public function front(Sample $sample): string
    {
        $journal = $sample->journal();
        $balance = $sample->balance();
        $income = $sample->income();
        $period = $income->period();
        $ratios = $sample->ratios();
        $trends = $sample->trends();
        $variance = $sample->budget()->variance($journal, $sample->kindsList());
        $cohorts = $sample->cohorts();
        $statements = new Statements($balance, $income);
        $parties = $sample->parties();

        $lede = 'A company runs RAIAS beside its own books and reads the figures an accountant would ask for first.';
        $what = 'RAIAS computes the ratios, reads each period against the one before it, reads the plan against what the books produced, and groups customers by the period they first bought in. The statements follow IFRS or GAAP. Every figure comes from the same lines, and any of them opens on the record behind it.';
        $steps = [
            'A company points RAIAS at the books it already keeps.',
            'RAIAS computes the ratios and reads the periods against the plan.',
            'It presents the statements in the standard the reader asks for.',
        ];

        $ratioRows = [];
        foreach ($ratios->all() as $label => $value) {
            $ratioRows[] = [$label, $this->ratio($value), ''];
        }

        $trendRows = [];
        foreach ($trends->rows() as $row) {
            $trendRows[] = [$row['period'], $this->money($row['revenue']) . ' revenue, '
                . $this->money($row['expense']) . ' expense', ''];
        }

        $varianceRows = [];
        foreach ($variance as $row) {
            $class = $row['variance'] < 0 ? 'bad' : 'ok';
            $varianceRows[] = [$row['period'], $this->money($row['variance']), $class];
        }

        $cohortRows = [];
        foreach ($cohorts->totals() as $cohortPeriod => $cents) {
            $cohortRows[] = ['First bought in ' . $cohortPeriod, $this->money($cents) . ' from '
                . $cohorts->members($cohortPeriod) . ' customer', ''];
        }

        $ifrsRows = [];
        foreach ($statements->balanceSheet(Statements::IFRS) as [$label, $cents]) {
            $ifrsRows[] = [$label, $this->money($cents), ''];
        }

        $gaapRows = [];
        foreach ($statements->balanceSheet(Statements::GAAP) as [$label, $cents]) {
            $gaapRows[] = [$label, $this->money($cents), ''];
        }

        $pnlRows = [];
        foreach ($statements->profitAndLoss(Statements::GAAP) as [$label, $cents]) {
            $pnlRows[] = [$label, $this->money($cents), ''];
        }

        $partyRows = [];
        foreach ($parties->totals() as $party => $cents) {
            $partyRows[] = [$party, $this->money($cents), ''];
        }

        $html = '<!doctype html><html lang="en"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<title>RAIAS</title>'
            . '<link rel="stylesheet" href="' . $this->e($this->url('/assets/site.css')) . '">'
            . '</head><body><main>';

        $html .= '<h1>RAIAS</h1>';
        $html .= '<p class="stage">' . $this->e($lede) . '</p>';
        $html .= '<p>' . $this->e($what) . '</p>';
        $html .= '<h2>How a company runs it</h2><ol class="steps">';
        foreach ($steps as $step) {
            $html .= '<li>' . $this->e($step) . '</li>';
        }
        $html .= '</ol>';

        $html .= '<h2>The company on this page</h2>';
        $html .= '<p>Sample company, period ' . $this->e($period) . '. ' . $journal->count()
            . ' lines across ' . count($journal->periods()) . ' periods, and every table below reads them.</p>';

        $html .= '<h2>Ratios</h2>';
        $html .= $this->table($ratioRows);

        $html .= '<h2>Period on period</h2>';
        $html .= $this->table($trendRows);

        $html .= '<h2>Against the plan</h2>';
        $html .= '<p class="stage">Variance reads actual minus plan, so a negative line came in below the figure the company set.</p>';
        $html .= $this->table($varianceRows);

        $html .= '<h2>Customers by cohort</h2>';
        $html .= $this->table($cohortRows);

        $html .= '<h2>Statement of financial position, IFRS</h2>';
        $html .= $this->table($ifrsRows);

        $html .= '<h2>Balance sheet, GAAP</h2>';
        $html .= $this->table($gaapRows);

        $html .= '<h2>Profit and loss, GAAP</h2>';
        $html .= '<p class="stage">Period ' . $this->e($period) . '.</p>';
        $html .= $this->table($pnlRows);

        $html .= '<h2>Related parties</h2>';
        $html .= '<p class="stage">Period 2026-05. The company named these parties, and the '
            . 'section reads the same lines as the statements above.</p>';
        $html .= $this->table($partyRows);

        $html .= '<h2>The lines behind it</h2>';
        $html .= '<table><thead><tr><th>Day</th><th>Account</th><th>Party</th>'
            . '<th class="n">Debit</th><th class="n">Credit</th></tr></thead><tbody>';
        foreach ($journal->entries() as $entry) {
            $debit = $entry->side === Entry::DEBIT ? $this->money($entry->cents) : '';
            $credit = $entry->side === Entry::CREDIT ? $this->money($entry->cents) : '';
            $html .= '<tr><td>' . $this->e($entry->day) . '</td><td>' . $this->e($entry->account)
                . '</td><td>' . $this->e($entry->counterparty) . '</td>'
                . '<td class="n">' . $this->e($debit) . '</td>'
                . '<td class="n">' . $this->e($credit) . '</td></tr>';
        }
        $html .= '</tbody></table>';

        $html .= '<p class="stage">Figures on this page belong to the sample company and carry '
            . 'a fixed set of lines.</p>';
        $html .= '</main></body></html>';

        return $html;
    }

    public function status(Sample $sample): string
    {
        $income = $sample->income();

        return json_encode([
            'name' => 'RAIAS',
            'lines' => $sample->journal()->count(),
            'periods' => $sample->journal()->periods(),
            'period' => $income->period(),
            'ratios' => $sample->ratios()->all(),
            'budgeted' => count($sample->budget()->planned()),
            'cohorts' => count($sample->cohorts()->totals()),
        ], JSON_PRETTY_PRINT) . "\n";
    }

    public function notFound(string $path): string
    {
        return '<!doctype html><html lang="en"><head><meta charset="utf-8">'
            . '<title>Not found</title></head><body><main><h1>Not found</h1>'
            . '<p>' . $this->e($path) . ' is not a page on this site.</p>'
            . '<p><a href="' . $this->e($this->url('/')) . '">Back to the front page</a></p>'
            . '</main></body></html>';
    }
}
