<?php

declare(strict_types=1);

namespace App\Services\Logging;

/**
 * A body that could not be parsed, so it cannot be masked either: only its
 * size is logged, never its content.
 */
final readonly class BodyNotStored
{
    public function __construct(
        public int $bytes,
    ) {
    }

    public function describe(): string
    {
        return sprintf('[not stored: non-JSON body, %d bytes]', $this->bytes);
    }
}
