<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\AssetModel;
use App\Models\Manufacturer;
use App\Rules\UniqueTogether;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Create (POST) and update (PUT/PATCH) of an asset model.
 * On update every field is optional; only the given ones change.
 */
final class AssetModelRequest extends FormRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        $presence = $this->assetModel() === null ? 'required' : 'sometimes';

        return [
            'name' => [$presence, 'string', 'max:100'],
            /** Unique per manufacturer. */
            'model_number' => [$presence, 'string', 'max:100'],
            'manufacturer_id' => [$presence, 'integer', Rule::exists(Manufacturer::class, 'id')],
        ];
    }

    /** @return list<callable(Validator): void> */
    public function after(): array
    {
        return [new UniqueTogether(
            AssetModel::class,
            'manufacturer_id',
            'model_number',
            $this->assetModel(),
            'The model number has already been taken for this manufacturer.',
        )];
    }

    private function assetModel(): ?AssetModel
    {
        $model = $this->route('assetModel');

        return $model instanceof AssetModel ? $model : null;
    }
}
