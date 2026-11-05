<?php

declare(strict_types=1);

namespace App\Rules;

use App\Models\Contract;
use Illuminate\Validation\Validator;

/**
 * (vendor_id, contract_number) must be unique.
 *
 * Checked on the pair as it will be after the change (request value, or the
 * stored one on a partial update), so changing only vendor_id or only
 * contract_number is validated too. It spans two fields, so it is an
 * after-validation hook (FormRequest::after()), not a single-field rule:
 * a field rule would not run when its field is absent from the request.
 */
final class UniqueContractNumberPerVendor
{
    public const MESSAGE = 'The contract number has already been taken for this vendor.';

    /** @param Contract|null $contract The contract being updated; null on create. */
    public function __construct(
        private readonly ?Contract $contract = null,
    ) {
    }

    public function __invoke(Validator $validator): void
    {
        // Field errors (missing, wrong type, unknown vendor) come first.
        if ($validator->errors()->hasAny(['contract_number', 'vendor_id'])) {
            return;
        }

        $data = $validator->getData();
        $vendorId = $data['vendor_id'] ?? $this->contract?->vendor_id;
        $number = $data['contract_number'] ?? $this->contract?->contract_number;

        if ($vendorId === null || $number === null || !$this->taken($vendorId, $number)) {
            return;
        }

        // Report it on the field the client changed.
        $validator->errors()->add(
            array_key_exists('contract_number', $data) ? 'contract_number' : 'vendor_id',
            self::MESSAGE,
        );
    }

    private function taken(mixed $vendorId, mixed $number): bool
    {
        return Contract::query()
            ->where('vendor_id', $vendorId)
            ->where('contract_number', $number)
            ->when($this->contract !== null, fn ($query) => $query->whereKeyNot($this->contract->getKey()))
            ->exists();
    }
}
