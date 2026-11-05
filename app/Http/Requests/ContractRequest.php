<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Contract;
use App\Models\Vendor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        $contract = $this->route('contract');
        $contract = $contract instanceof Contract ? $contract : null;
        $presence = $contract === null ? 'required' : 'sometimes';

        return [
            'title' => [$presence, 'string', 'max:100'],
            'contract_number' => [
                $presence,
                'string',
                'max:100',
                Rule::unique(Contract::class)
                    ->where('vendor_id', $this->input('vendor_id', $contract?->vendor_id))
                    ->ignore($contract),
            ],
            'start_date' => [$presence, 'date_format:' . self::DATE_FORMAT, ...$this->startDateBound($contract)],
            'end_date' => [$presence, 'date_format:' . self::DATE_FORMAT, ...$this->endDateBound($contract)],
            'vendor_id' => [$presence, 'integer', Rule::exists(Vendor::class, 'id')],
        ];
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
