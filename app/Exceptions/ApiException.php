<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

/**
 * Base class for expected, client-facing domain errors.
 */
abstract class ApiException extends RuntimeException
{
    abstract public function status(): int;

    abstract public function errorCode(): string;
}
