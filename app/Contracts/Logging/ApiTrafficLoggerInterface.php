<?php

declare(strict_types=1);

namespace App\Contracts\Logging;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

interface ApiTrafficLoggerInterface
{
    public function log(Request $request, Response $response, float $durationMs): void;
}
