<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Asset;
use App\Models\AssetModel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Create (POST) and update (PUT/PATCH) of an asset.
 * On update every field is optional; only the given ones change.
 */
final class AssetRequest extends FormRequest
{
    /** @return array<string, list<mixed>> */
    public function rules(): array
    {
        $asset = $this->route('asset');
        $asset = $asset instanceof Asset ? $asset : null;
        $presence = $asset === null ? 'required' : 'sometimes';

        return [
            'asset_tag' => [$presence, 'string', 'max:100', Rule::unique(Asset::class)->ignore($asset)],
            'serial_number' => ['nullable', 'string', 'max:100', Rule::unique(Asset::class)->ignore($asset)],
            'model_id' => [$presence, 'integer', Rule::exists(AssetModel::class, 'id')],
        ];
    }
}
