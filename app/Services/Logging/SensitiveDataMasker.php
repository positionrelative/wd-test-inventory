<?php

declare(strict_types=1);

namespace App\Services\Logging;

use Illuminate\Container\Attributes\Config;

/**
 * Replaces secrets (passwords, tokens, auth headers) before they are logged.
 */
final class SensitiveDataMasker
{
    public const MASK = '********';

    /** @var array<string, true> */
    private readonly array $fields;

    /** @var array<string, true> */
    private readonly array $headers;

    /**
     * @param list<string> $fields Body / query keys to mask, at any depth (case-insensitive).
     * @param list<string> $headers Header names to mask (case-insensitive).
     */
    public function __construct(
        #[Config('api_logging.masked_fields', [])]
        array $fields = [],
        #[Config('api_logging.masked_headers', [])]
        array $headers = [],
    ) {
        $this->fields = self::lookup($fields);
        $this->headers = self::lookup($headers);
    }

    /**
     * @param array<array-key, mixed> $data
     * @return array<array-key, mixed>
     */
    public function maskFields(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($key) && isset($this->fields[strtolower($key)])) {
                $data[$key] = self::MASK;
            } elseif (is_array($value)) {
                $data[$key] = $this->maskFields($value);
            }
        }

        return $data;
    }

    /**
     * @param array<string, list<string|null>> $headers
     * @return array<string, list<string|null>|string>
     */
    public function maskHeaders(array $headers): array
    {
        foreach ($headers as $name => $values) {
            if (isset($this->headers[strtolower($name)])) {
                $headers[$name] = self::MASK;
            }
        }

        return $headers;
    }

    /**
     * @param list<string> $names
     * @return array<string, true>
     */
    private static function lookup(array $names): array
    {
        return array_fill_keys(array_map('strtolower', $names), true);
    }
}
