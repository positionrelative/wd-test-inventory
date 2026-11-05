<?php

declare(strict_types=1);

namespace App\Services\Logging;

use Illuminate\Container\Attributes\Config;

/** Parses, masks and bounds payloads before they are stored in traffic logs. */
final class LogPayloadNormalizer
{
    private const TRUNCATED_SUFFIX = '...[truncated]';

    public function __construct(
        private readonly SensitiveDataMasker $masker,
        #[Config('api_logging.max_body_length', 65535)]
        private readonly int $maxBodyLength = 65535,
    ) {
    }

    /**
     * @param array<array-key, mixed>|string|null $data Parsed input or raw body.
     * @return array<array-key, mixed>|string|null
     */
    public function normalize(array|string|null $data): array|string|null
    {
        return $this->payload(is_string($data) ? $this->decode($data) : $data);
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
