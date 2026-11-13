<?php

declare(strict_types=1);

namespace Raias\Tests;

use Raias\App\Front;
use Raias\App\Router;
use Raias\App\Views;

$router = new Router('/raias');
$page = static fn (string $uri): string
    => (new Front(new Views('/raias')))->handle($uri, new Router('/raias'));

return [
    ['the base prefix comes off the path', static fn () => Assert::same('/', $router->strip('/raias/'))],
    ['a request outside the base keeps its own path', static fn () => Assert::same(
        '/elsewhere',
        $router->strip('/elsewhere')
    )],
    ['the front page answers', static fn () => Assert::same('front', $router->route('/raias/'))],
    ['the status route answers', static fn () => Assert::same('status', $router->route('/raias/status'))],
    ['an unknown path falls through', static fn () => Assert::same('missing', $router->route('/raias/nope'))],
    ['the heading names the product once', static fn () => Assert::same(
        1,
        substr_count($page('/raias/'), '<h1>RAIAS</h1>')
    )],
    ['the page prints the ratios section', static fn () => Assert::true(
        str_contains($page('/raias/'), 'Net margin'),
        'page check'
    )],
    ['the page prints the variance table', static fn () => Assert::true(
        str_contains($page('/raias/'), 'Against the plan'),
        'page check'
    )],
    ['the page prints both presentations', static fn () => Assert::true(
        str_contains($page('/raias/'), 'IFRS') && str_contains($page('/raias/'), 'GAAP'),
        'page check'
    )],
    ['the status route answers with json', static fn () => Assert::true(
        str_contains($page('/raias/status'), '"name": "RAIAS"'),
        'page check'
    )],
    ['the not found page names the path', static fn () => Assert::true(
        str_contains($page('/raias/nope'), '/nope'),
        'page check'
    )],
    ['the stylesheet link carries the base prefix', static fn () => Assert::true(
        str_contains($page('/raias/'), '/raias/assets/site.css'),
        'page check'
    )],
];
