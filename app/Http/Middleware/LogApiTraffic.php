<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Contracts\Logging\ApiTrafficLoggerInterface;
use Closure;
use Illuminate\Container\Attributes\Config;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Logs API requests and responses.
 *
 * Runs as global middleware so unmatched routes (404/405) are logged too.
 * The write happens in terminate(), after the response has been sent.
 */
final class LogApiTraffic
{
    private const DURATION_ATTRIBUTE = 'api_log.duration_ms';

    /** @param list<string> $paths */
    public function __construct(
        private readonly ApiTrafficLoggerInterface $logger,
        #[Config('api_logging.enabled', true)]
        private readonly bool $enabled = true,
        #[Config('api_logging.paths', ['api', 'api/*'])]
        private readonly array $paths = ['api', 'api/*'],
    ) {
    }

    /** @param Closure(Request): Response $next */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->shouldLog($request)) {
            return $next($request);
        }

        $startedAt = hrtime(true);
        $response = $next($request);
        $request->attributes->set(self::DURATION_ATTRIBUTE, (hrtime(true) - $startedAt) / 1_000_000);

        return $response;
    }

    public function terminate(Request $request, Response $response): void
    {
        $durationMs = $request->attributes->get(self::DURATION_ATTRIBUTE);

        if ($durationMs === null) {
            return;
        }

        try {
            $this->logger->log($request, $response, (float) $durationMs);
        } catch (Throwable $e) {
            // Logging must never break the API: record the failure and move on.
            report($e);
        }
    }

    private function shouldLog(Request $request): bool
    {
        return $this->enabled && $request->is(...$this->paths);
    }
}
