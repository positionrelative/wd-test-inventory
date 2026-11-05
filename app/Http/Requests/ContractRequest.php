<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Contract;
use App\Models\Vendor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Create (POST) and update (PUT/PATCH) of a contract.
 * On update every field is optional; only the given ones change.
 */
final class ContractRequest extends FormRequest
{
    private const DATE_FORMAT = 'Y-m-d';

    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        $contract = $this->contract();
        $presence = $contract === null ? 'required' : 'sometimes';

        return [
            'title' => [$presence, 'string', 'max:100'],
            'contract_number' => [$presence, 'string', 'max:100'],
            'start_date' => [$presence, 'date_format:' . self::DATE_FORMAT, ...$this->startDateBound($contract)],
            'end_date' => [$presence, 'date_format:' . self::DATE_FORMAT, ...$this->endDateBound($contract)],
            'vendor_id' => [$presence, 'integer', Rule::exists(Vendor::class, 'id')],
        ];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [$this->uniqueContractNumberPerVendor(...)];
    }

    /**
     * (vendor_id, contract_number) must be unique. Checked on the pair as it
     * will be after the change, so changing only vendor_id (or only
     * contract_number) is validated too, not left to the DB unique index.
     */
    private function uniqueContractNumberPerVendor(Validator $validator): void
    {
        if ($validator->errors()->hasAny(['contract_number', 'vendor_id'])) {
            return;
        }

        $contract = $this->contract();
        $vendorId = $this->input('vendor_id', $contract?->vendor_id);
        $number = $this->input('contract_number', $contract?->contract_number);

        if ($vendorId === null || $number === null) {
            return;
        }

        $taken = Contract::query()
            ->where('vendor_id', $vendorId)
            ->where('contract_number', $number)
            ->when($contract !== null, fn ($query) => $query->whereKeyNot($contract->getKey()))
            ->exists();

        if ($taken) {
            $validator->errors()->add(
                $this->has('contract_number') ? 'contract_number' : 'vendor_id',
                'The contract number has already been taken for this vendor.',
            );
        }
    }

    private function contract(): ?Contract
    {
        $contract = $this->route('contract');

        return $contract instanceof Contract ? $contract : null;
    }

    /**
     * end_date >= start_date, where start_date comes from the request or,
     * on a partial update, from the stored contract.
     *
     * @return list<string>
     */
    private function endDateBound(?Contract $contract): array
    {
        if ($this->has('start_date')) {
            return ['after_or_equal:start_date'];
        }

        return $contract !== null
            ? ['after_or_equal:' . $contract->start_date->format(self::DATE_FORMAT)]
            : [];
    }

    /**
     * Only needed when start_date changes alone; when both dates are sent,
     * the end_date rule already compares them.
     *
     * @return list<string>
     */
    private function startDateBound(?Contract $contract): array
    {
        return $contract !== null && !$this->has('end_date')
            ? ['before_or_equal:' . $contract->end_date->format(self::DATE_FORMAT)]
            : [];
    }
}
