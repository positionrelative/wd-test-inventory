<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Logging\LogPayloadNormalizer;
use App\Services\Logging\SensitiveDataMasker;
use PHPUnit\Framework\TestCase;

final class LogPayloadNormalizerTest extends TestCase
{
    public function test_parsed_input_and_raw_json_are_masked_consistently(): void
    {
        $normalizer = new LogPayloadNormalizer(new SensitiveDataMasker(['token']));
        $input = ['nested' => ['token' => 'secret', 'keep' => 1]];
        $expected = ['nested' => ['token' => SensitiveDataMasker::MASK, 'keep' => 1]];

        $this->assertSame($expected, $normalizer->normalize($input));
        $this->assertSame($expected, $normalizer->normalize(json_encode($input, JSON_THROW_ON_ERROR)));
    }

    public function test_empty_bodies_are_not_stored(): void
    {
        $normalizer = new LogPayloadNormalizer(new SensitiveDataMasker());

        $this->assertNull($normalizer->normalize(null));
        $this->assertNull($normalizer->normalize(''));
        $this->assertSame([], $normalizer->normalize([]));
    }

    public function test_unstructured_or_malformed_bodies_are_replaced_without_exposing_content(): void
    {
        $normalizer = new LogPayloadNormalizer(new SensitiveDataMasker(['token']));

        foreach (['token=secret', '{"token":"secret"', '"secret"'] as $body) {
            $this->assertSame(
                sprintf('[not stored: non-JSON body, %d bytes]', strlen($body)),
                $normalizer->normalize($body),
            );
        }
    }

    public function test_truncation_masks_secrets_first_and_preserves_utf8(): void
    {
        $normalizer = new LogPayloadNormalizer(new SensitiveDataMasker(['token']), 33);
        $input = ['token' => 'raw-secret-token', 'text' => str_repeat("\u{1F600}", 50)];

        foreach ([$input, json_encode($input, JSON_THROW_ON_ERROR)] as $body) {
            $result = $normalizer->normalize($body);

            $this->assertIsString($result);
            $this->assertStringContainsString(SensitiveDataMasker::MASK, $result);
            $this->assertStringNotContainsString('raw-secret-token', $result);
            $this->assertStringEndsWith('...[truncated]', $result);
            $this->assertLessThanOrEqual(33 + strlen('...[truncated]'), strlen($result));
            $this->assertTrue(mb_check_encoding($result, 'UTF-8'));
        }
    }

    public function test_payload_at_the_size_limit_is_kept_structured(): void
    {
        $input = ['name' => 'value'];
        $normalizer = new LogPayloadNormalizer(
            new SensitiveDataMasker(),
            strlen(json_encode($input, JSON_THROW_ON_ERROR)),
        );

        $this->assertSame($input, $normalizer->normalize($input));
    }
}
