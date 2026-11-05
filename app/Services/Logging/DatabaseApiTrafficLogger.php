<?php

declare(strict_types=1);

namespace App\Services\Logging;

use App\Contracts\Logging\ApiTrafficLoggerInterface;
use App\Http\RequestId;
use App\Models\ApiRequestLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Stores API traffic in api_request_logs.
 *
 * Every payload (query, request body, response body) goes through the same
 * pipeline: parse → mask → enforce the size limit.
 */
final class DatabaseApiTrafficLogger implements ApiTrafficLoggerInterface
{
    public function __construct(
        private readonly SensitiveDataMasker $masker,
        private readonly LogPayloadNormalizer $normalizer,
    ) {
    }

    public function log(Request $request, Response $response, float $durationMs): void
    {
        ApiRequestLog::query()->create([
            'request_id' => RequestId::of($request),
            'method' => $request->getMethod(),
            'path' => Str::limit('/' . ltrim($request->path(), '/'), 2048, ''),
            'query' => $this->normalizer->normalize($request->query() ?: null),
            'ip' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 512, '') ?: null,
            'request_headers' => $this->masker->maskHeaders($request->headers->all()),
            'request_body' => $this->normalizer->normalize($request->post() ?: (string) $request->getContent()),
            'response_status' => $response->getStatusCode(),
            'response_headers' => $this->masker->maskHeaders($response->headers->all()),
            'response_body' => $this->normalizer->normalize((string) $response->getContent()),
            'duration_ms' => round($durationMs, 2),
        ]);
    }
}
