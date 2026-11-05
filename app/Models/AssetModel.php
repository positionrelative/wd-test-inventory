<?php

namespace App\Models;

use Database\Factories\AssetModelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Asset type. The physical pieces are the Assets.
 */
#[Fillable(['name', 'model_number', 'manufacturer_id'])]
class AssetModel extends Model
{
    /** @use HasFactory<AssetModelFactory> */
    use HasFactory;

    /** @return BelongsTo<Manufacturer, $this> */
    public function manufacturer(): BelongsTo
    {
        return $this->belongsTo(Manufacturer::class);
    }

    /** @return HasMany<Asset, $this> */
    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class, 'model_id');
    }
}
