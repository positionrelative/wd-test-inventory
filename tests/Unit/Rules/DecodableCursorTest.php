<?php

declare(strict_types=1);

namespace Tests\Unit\Rules;

use App\Rules\DecodableCursor;
use Illuminate\Pagination\Cursor;
use PHPUnit\Framework\TestCase;

final class DecodableCursorTest extends TestCase
{
    public function test_accepts_a_cursor_issued_by_laravel(): void
    {
        $this->assertNull($this->failure((new Cursor(['id' => 15]))->encode()));
    }

    public function test_rejects_garbage(): void
    {
        $this->assertSame(DecodableCursor::MESSAGE, $this->failure('not-a-cursor'));
        $this->assertSame(DecodableCursor::MESSAGE, $this->failure(base64_encode('{"broken"')));
    }

    public function test_rejects_non_strings(): void
    {
        $this->assertSame(DecodableCursor::MESSAGE, $this->failure(['id' => 1]));
    }

    private function failure(mixed $value): ?string
    {
        $message = null;
        (new DecodableCursor())->validate('cursor', $value, function (string $error) use (&$message): void {
            $message = $error;
        });

        return $message;
    }
}
