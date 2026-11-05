<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Contract;
use App\Models\Vendor;
use App\Rules\DateRangeBounds;
use App\Rules\UniqueTogether;
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

        $dates = new DateRangeBounds(
            request: $this,
            startField: 'start_date',
            endField: 'end_date',
            storedStart: $contract?->start_date,
            storedEnd: $contract?->end_date,
        );

        return [
            'title' => [$presence, 'string', 'max:100'],
            /** Unique per vendor. */
            'contract_number' => [$presence, 'string', 'max:100'],
            'start_date' => [$presence, 'date_format:' . self::DATE_FORMAT, ...$dates->start()],
            /** Not before `start_date`. */
            'end_date' => [$presence, 'date_format:' . self::DATE_FORMAT, ...$dates->end()],
            'vendor_id' => [$presence, 'integer', Rule::exists(Vendor::class, 'id')],
        ];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [new UniqueTogether(
            Contract::class,
            'vendor_id',
            'contract_number',
            $this->contract(),
            'The contract number has already been taken for this vendor.',
        )];
    }

    private function contract(): ?Contract
    {
        $contract = $this->route('contract');

        return $contract instanceof Contract ? $contract : null;
    }
}
