<?php

declare(strict_types=1);

namespace App\Services\Logging;

use App\Contracts\Logging\ApiTrafficLoggerInterface;
use App\Http\RequestId;
use App\Models\ApiRequestLog;
use Illuminate\Container\Attributes\Config;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class DatabaseApiTrafficLogger implements ApiTrafficLoggerInterface
{
    private const TRUNCATED_SUFFIX = '...[truncated]';

    public function __construct(
        private readonly SensitiveDataMasker $masker,
        #[Config('api_logging.max_body_length', 65535)]
        private readonly int $maxBodyLength = 65535,
    ) {
    }

    public function log(Request $request, Response $response, float $durationMs): void
    {
        ApiRequestLog::query()->create([
            'request_id' => RequestId::of($request),
            'method' => $request->getMethod(),
            'path' => Str::limit('/' . ltrim($request->path(), '/'), 2048, ''),
            'query' => $this->masker->maskFields($request->query()) ?: null,
            'ip' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 512, '') ?: null,
            'request_headers' => $this->masker->maskHeaders($request->headers->all()),
            'request_body' => $this->requestBody($request),
            'response_status' => $response->getStatusCode(),
            'response_headers' => $this->masker->maskHeaders($response->headers->all()),
            'response_body' => $this->content((string) $response->getContent()),
            'duration_ms' => round($durationMs, 2),
        ]);
    }

    /**
     * JSON and form bodies are already parsed by Laravel; anything else is kept raw.
     *
     * @return array<array-key, mixed>|string|null
     */
    private function requestBody(Request $request): array|string|null
    {
        $input = $request->post();

        return $input !== []
            ? $this->masker->maskFields($input)
            : $this->content((string) $request->getContent());
    }

    /**
     * JSON is stored decoded and masked; anything else (or anything too large)
     * is stored as a, possibly truncated, string.
     *
     * @return array<array-key, mixed>|string|null
     */
    private function content(string $content): array|string|null
    {
        if ($content === '') {
            return null;
        }

        if (strlen($content) > $this->maxBodyLength) {
            return Str::limit($content, $this->maxBodyLength, self::TRUNCATED_SUFFIX);
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) ? $this->masker->maskFields($decoded) : $content;
    }
}
