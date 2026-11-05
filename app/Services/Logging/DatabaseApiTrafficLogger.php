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

/**
 * Stores API traffic in api_request_logs.
 *
 * Every payload (query, request body, response body) goes through the same
 * pipeline: parse → mask → enforce the size limit.
 */
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
            'query' => $this->payload($request->query() ?: null),
            'ip' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 512, '') ?: null,
            'request_headers' => $this->masker->maskHeaders($request->headers->all()),
            'request_body' => $this->payload($this->requestData($request)),
            'response_status' => $response->getStatusCode(),
            'response_headers' => $this->masker->maskHeaders($response->headers->all()),
            'response_body' => $this->payload($this->decode((string) $response->getContent())),
            'duration_ms' => round($durationMs, 2),
        ]);
    }

    /**
     * JSON and form bodies are already parsed by Laravel; anything else is decoded here.
     *
     * @return array<array-key, mixed>|BodyNotStored|null
     */
    private function requestData(Request $request): array|BodyNotStored|null
    {
        $input = $request->post();

        return $input !== [] ? $input : $this->decode((string) $request->getContent());
    }

    /** @return array<array-key, mixed>|BodyNotStored|null */
    private function decode(string $content): array|BodyNotStored|null
    {
        if ($content === '') {
            return null;
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : new BodyNotStored(strlen($content));
    }

    /**
     * Masks first, then applies the size limit.
     *
     * @param array<array-key, mixed>|BodyNotStored|null $data
     * @return array<array-key, mixed>|string|null
     */
    private function payload(array|BodyNotStored|null $data): array|string|null
    {
        if ($data === null) {
            return null;
        }

        if ($data instanceof BodyNotStored) {
            return $data->describe();
        }

        $masked = $this->masker->maskFields($data);
        $json = (string) json_encode($masked, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);

        if (strlen($json) <= $this->maxBodyLength) {
            return $masked;
        }

        return mb_strcut($json, 0, $this->maxBodyLength, 'UTF-8') . self::TRUNCATED_SUFFIX;
    }
}
