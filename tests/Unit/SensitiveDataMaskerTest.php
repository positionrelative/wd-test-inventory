<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Logging\SensitiveDataMasker;
use PHPUnit\Framework\TestCase;

final class SensitiveDataMaskerTest extends TestCase
{
    private SensitiveDataMasker $masker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->masker = new SensitiveDataMasker(['password', 'token'], ['authorization']);
    }

    public function test_masks_fields_at_any_depth_case_insensitively(): void
    {
        $masked = $this->masker->maskFields([
            'name' => 'Jane',
            'Password' => 'secret',
            'nested' => ['token' => 'abc', 'keep' => 1],
            'list' => [['token' => 'x']],
        ]);

        $this->assertSame([
            'name' => 'Jane',
            'Password' => SensitiveDataMasker::MASK,
            'nested' => ['token' => SensitiveDataMasker::MASK, 'keep' => 1],
            'list' => [['token' => SensitiveDataMasker::MASK]],
        ], $masked);
    }

    public function test_masks_headers_case_insensitively(): void
    {
        $masked = $this->masker->maskHeaders([
            'Authorization' => ['Bearer abc'],
            'accept' => ['application/json'],
        ]);

        $this->assertSame([
            'Authorization' => SensitiveDataMasker::MASK,
            'accept' => ['application/json'],
        ], $masked);
    }
}
