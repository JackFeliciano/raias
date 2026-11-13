<?php

declare(strict_types=1);

namespace Raias\App;

use Raias\Home\Sample;

final class Front
{
    public function __construct(private readonly Views $views)
    {
    }

    public function handle(string $uri, Router $router): string
    {
        $sample = new Sample();

        return match ($router->route($uri)) {
            'front' => $this->views->front($sample),
            'status' => $this->views->status($sample),
            default => $this->views->notFound($router->strip($uri)),
        };
    }

    public function run(): void
    {
        $router = new Router(getenv('RAIAS_BASE') ?: '/');
        echo $this->handle($_SERVER['REQUEST_URI'] ?? '/', $router);
    }
}
