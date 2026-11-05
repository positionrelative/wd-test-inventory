<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name'])]
class Manufacturer extends Model
{
    /** @return HasMany<AssetModel, $this> */
    public function assetModels(): HasMany
    {
        return $this->hasMany(AssetModel::class);
    }
}
