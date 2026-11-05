<?php

declare(strict_types=1);

namespace Tests\Feature\Rules;

use App\Models\Contract;
use App\Rules\UniqueTogether;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

final class UniqueTogetherTest extends TestCase
{
    use RefreshDatabase;

    private const MESSAGE = 'Taken for this vendor.';

    public function test_create_fails_when_the_pair_exists(): void
    {
        $taken = Contract::factory()->create();

        $errors = $this->errors(['vendor_id' => $taken->vendor_id, 'contract_number' => $taken->contract_number]);

        $this->assertSame([self::MESSAGE], $errors['contract_number'] ?? null);
    }

    public function test_same_value_in_another_scope_passes(): void
    {
        $taken = Contract::factory()->create();
        $other = Contract::factory()->create();

        $this->assertSame([], $this->errors(['vendor_id' => $other->vendor_id, 'contract_number' => $taken->contract_number]));
    }

    public function test_scope_only_change_is_checked_with_the_stored_value(): void
    {
        $taken = Contract::factory()->create(['contract_number' => 'CN-1']);
        $contract = Contract::factory()->create(['contract_number' => 'CN-1']);

        $errors = $this->errors(['vendor_id' => $taken->vendor_id], $contract);

        $this->assertSame([self::MESSAGE], $errors['vendor_id'] ?? null);
    }

    public function test_the_model_itself_is_ignored_on_update(): void
    {
        $contract = Contract::factory()->create();

        $this->assertSame([], $this->errors(
            ['vendor_id' => $contract->vendor_id, 'contract_number' => $contract->contract_number],
            $contract,
        ));
    }

    public function test_skipped_when_a_field_already_failed(): void
    {
        $taken = Contract::factory()->create();

        $errors = $this->errors(
            ['vendor_id' => $taken->vendor_id, 'contract_number' => $taken->contract_number],
            rules: ['contract_number' => ['max:1']],
        );

        $this->assertCount(1, $errors['contract_number']);
        $this->assertNotSame(self::MESSAGE, $errors['contract_number'][0]);
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $rules
     * @return array<string, list<string>>
     */
    private function errors(array $data, ?Contract $current = null, array $rules = []): array
    {
        $validator = Validator::make($data, $rules);
        $validator->after(new UniqueTogether(Contract::class, 'vendor_id', 'contract_number', $current, self::MESSAGE));
        $validator->passes();

        return $validator->errors()->toArray();
    }
}
