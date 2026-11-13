<?php

declare(strict_types=1);

namespace Raias\Tests;

use Raias\Home\Sample;
use Raias\Home\Statements;

$sample = new Sample();
$ratios = $sample->ratios();
$trends = $sample->trends();
$variance = $sample->budget()->variance($sample->journal(), $sample->kindsList());
$cohorts = $sample->cohorts();
$statements = new Statements($sample->balance(), $sample->income());

return [
    ['the sample posts sixteen lines', static fn () => Assert::same(16, $sample->journal()->count())],
    ['the sample covers eight periods', static fn () => Assert::same(8, count($sample->journal()->periods()))],
    ['net margin divides the result by revenue', static fn () => Assert::same(
        round($sample->income()->result('2026-05') / $sample->income()->revenue('2026-05'), 6),
        round((float) $ratios->margin(), 6)
    )],
    ['the current ratio reads cash and receivables over payables', static fn () => Assert::same(
        round((1595000 + 180000) / (150000 + 500000), 6),
        round((float) $ratios->current(), 6)
    )],
    ['leverage divides liabilities by equity', static fn () => Assert::true(($ratios->leverage() ?? 0) > 0, 'leverage reads above zero')],
    ['the ratios answer with three keys', static fn () => Assert::same(
        ['Net margin', 'Current ratio', 'Leverage'],
        array_keys($ratios->all())
    )],
    ['trends return one row per period', static fn () => Assert::same(8, count($trends->rows()))],
    ['the revenue trend sums the credits on Sales', static fn () => Assert::same(
        480000,
        $trends->rows()[1]['revenue']
    )],
    ['a period with no sales reads zero revenue', static fn () => Assert::same(0, $trends->rows()[0]['revenue'])],
    ['variance is actual minus plan', static fn () => Assert::same(
        $variance[1]['actual'] - $variance[1]['budget'],
        $variance[1]['variance']
    )],
    ['a period with a plan and no sales comes in under it', static fn () => Assert::same(-350000, $variance[6]['variance'])],
    ['the plan covers every period in the books', static fn () => Assert::same(
        $sample->journal()->periods(),
        array_keys($sample->budget()->planned())
    )],
    ['cohorts group by the period a customer first bought', static fn () => Assert::same(
        ['2026-01', '2026-05', '2026-08'],
        array_keys($cohorts->totals())
    )],
    ['a cohort counts its members', static fn () => Assert::same(1, $cohorts->members('2026-05'))],
    ['IFRS leads with assets, liabilities and equity', static fn () => Assert::same(
        ['Assets', 'Liabilities', 'Equity'],
        array_column($statements->balanceSheet(Statements::IFRS), 0)
    )],
    ['GAAP splits current and total', static fn () => Assert::same(
        'Current assets',
        $statements->balanceSheet(Statements::GAAP)[0][0]
    )],
    ['both presentations carry the same total assets', static fn () => Assert::same(
        $statements->balanceSheet(Statements::IFRS)[0][1],
        $statements->balanceSheet(Statements::GAAP)[1][1]
    )],
    ['the GAAP result matches the IFRS result', static fn () => Assert::same(
        $statements->profitAndLoss(Statements::IFRS)[2][1],
        $statements->profitAndLoss(Statements::GAAP)[2][1]
    )],
];
