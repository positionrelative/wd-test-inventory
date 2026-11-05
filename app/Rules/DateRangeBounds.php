<?php

declare(strict_types=1);

namespace App\Rules;

use DateTimeInterface;
use Illuminate\Http\Request;

final readonly class DateRangeBounds
{
    public function __construct(
        private Request $request,
        private string $startField,
        private string $endField,
        private ?DateTimeInterface $storedStart = null,
        private ?DateTimeInterface $storedEnd = null,
        private string $format = 'Y-m-d',
    ) {
    }

    /** @return list<string> */
    public function start(): array
    {
        if ($this->storedEnd === null || $this->request->has($this->endField)) {
            return [];
        }

        return [
            'before_or_equal:' . $this->storedEnd->format($this->format),
        ];
    }

    /** @return list<string> */
    public function end(): array
    {
        if ($this->request->has($this->startField)) {
            return ['after_or_equal:' . $this->startField];
        }

        if ($this->storedStart === null) {
            return [];
        }

        return [
            'after_or_equal:' . $this->storedStart->format($this->format),
        ];
    }
}
