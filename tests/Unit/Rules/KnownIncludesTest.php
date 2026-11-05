<?php

declare(strict_types=1);

namespace Tests\Unit\Rules;

use App\Rules\KnownIncludes;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class KnownIncludesTest extends TestCase
{
    /** @return iterable<string, array{string}> */
    public static function validValues(): iterable
    {
        yield 'single' => ['model'];
        yield 'several' => ['model,manufacturer'];
        yield 'spaces and duplicates' => [' model , model ,manufacturer'];
        yield 'empty items' => ['model,,'];
    }

    #[DataProvider('validValues')]
    public function test_accepts_allowed_includes(string $value): void
    {
        $this->assertNull($this->failure($value));
    }

    public function test_rejects_unknown_includes_listing_them(): void
    {
        $this->assertSame(
            'Unknown include: owner, foo. Allowed: model, manufacturer.',
            $this->failure('model,owner,foo'),
        );
    }

    private function failure(string $value): ?string
    {
        $message = null;
        (new KnownIncludes(['model', 'manufacturer']))->validate(
            'include',
            $value,
            function (string $error) use (&$message): void {
                $message = $error;
            },
        );

        return $message;
    }
}
