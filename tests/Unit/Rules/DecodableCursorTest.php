<?php

declare(strict_types=1);

namespace Tests\Unit\Rules;

use App\Rules\DecodableCursor;
use Illuminate\Pagination\Cursor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DecodableCursorTest extends TestCase
{
    public function test_accepts_cursors_issued_by_laravel(): void
    {
        $this->assertNull($this->failure((new Cursor(['id' => 15]))->encode()));
        $this->assertNull($this->failure((new Cursor(['name' => 'Dell', 'id' => 3], false))->encode()));
    }

    public function test_rejects_garbage(): void
    {
        $this->assertSame(DecodableCursor::MESSAGE, $this->failure('not-a-cursor'));
        $this->assertSame(DecodableCursor::MESSAGE, $this->failure(base64_encode('{"broken"')));
    }

    /** @return iterable<string, array{string}> */
    public static function malformedPayloads(): iterable
    {
        yield 'null sort value' => ['{"id":null,"_pointsToNextItems":true}'];
        yield 'array sort value' => ['{"id":[1],"_pointsToNextItems":true}'];
        yield 'boolean sort value' => ['{"id":true,"_pointsToNextItems":true}'];
        yield 'object sort value' => ['{"id":{"a":1},"_pointsToNextItems":true}'];
        yield 'non-boolean direction' => ['{"id":5,"_pointsToNextItems":"yes"}'];
        yield 'missing direction' => ['{"id":5}'];
        yield 'no sort value' => ['{"_pointsToNextItems":true}'];
        yield 'json list' => ['[5,true]'];
    }

    #[DataProvider('malformedPayloads')]
    public function test_rejects_malformed_payloads(string $json): void
    {
        $this->assertSame(DecodableCursor::MESSAGE, $this->failure(rtrim(strtr(base64_encode($json), '+/', '-_'), '=')));
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
