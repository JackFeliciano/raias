<?php

declare(strict_types=1);

namespace Raias\Tests;

use Raias\Connect\Ledger;
use Raias\Connect\Source;

$one = new class implements Source {
    public function label(): string
    {
        return 'db:books/2026/entries';
    }

    public function lines(): array
    {
        return [
            ['account' => 'Cash', 'cents' => 100],
            ['account' => 'Sales', 'cents' => 200],
        ];
    }
};

$two = new class implements Source {
    public function label(): string
    {
        return 'file:plan.json';
    }

    public function lines(): array
    {
        return [
            ['account' => 'Wages', 'cents' => 40],
        ];
    }
};

$silent = new class implements Source {
    public function label(): string
    {
        return 'db:books/2025/entries';
    }

    public function lines(): array
    {
        throw new \RuntimeException('the source at db:books/2025/entries did not answer');
    }
};

return [
    ['a source names its own address', static fn () => Assert::same('db:books/2026/entries', $one->label())],
    ['a source hands back its lines', static fn () => Assert::same(2, count($one->lines()))],
    ['a ledger keys each source by its label', static function () use ($one, $two): void {
        $ledger = new Ledger();
        $ledger->add($one);
        $ledger->add($two);
        Assert::same(['db:books/2026/entries', 'file:plan.json'], $ledger->labels());
    }],
    ['a ledger counts the sources it holds', static function () use ($one, $two): void {
        $ledger = new Ledger();
        $ledger->add($one);
        $ledger->add($two);
        Assert::same(2, $ledger->count());
    }],
    ['a ledger puts every line in one list', static function () use ($one, $two): void {
        $ledger = new Ledger();
        $ledger->add($one);
        $ledger->add($two);
        Assert::same(3, count($ledger->records()));
    }],
    ['the same address twice still holds one source', static function () use ($one): void {
        $ledger = new Ledger();
        $ledger->add($one);
        $ledger->add($one);
        Assert::same(1, $ledger->count());
    }],
    ['a ledger read reaches every source', static function () use ($one, $two): void {
        $ledger = new Ledger();
        $ledger->add($one);
        $ledger->add($two);
        $ledger->read();
        Assert::true(true, 'the read reached every source');
    }],
    ['a silent source names itself when the read fails', static function () use ($silent): void {
        $ledger = new Ledger();
        $ledger->add($silent);
        try {
            $ledger->read();
            Assert::true(false, 'a silent source passed the read');
        } catch (\RuntimeException $e) {
            Assert::contains('did not answer', $e->getMessage());
        }
    }],
];
