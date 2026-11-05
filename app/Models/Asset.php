<?php

namespace App\Models;

use Database\Factories\AssetFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

#[Fillable(['asset_tag', 'serial_number', 'model_id'])]
class Asset extends Model
{
    /** @use HasFactory<AssetFactory> */
    use HasFactory;

    /** @return BelongsTo<AssetModel, $this> */
    public function assetModel(): BelongsTo
    {
        return $this->belongsTo(AssetModel::class, 'model_id');
    }

    /**
     * Manufacturer of the asset's model (assets.model_id → asset_models.manufacturer_id),
     * eager loadable in one query without loading the model.
     *
     * @return HasOneThrough<Manufacturer, AssetModel, $this>
     */
    public function manufacturer(): HasOneThrough
    {
        return $this->hasOneThrough(
            Manufacturer::class,
            AssetModel::class,
            'id',
            'id',
            'model_id',
            'manufacturer_id',
        );
    }

    /** @return HasMany<AssetAssignment, $this> */
    public function assignments(): HasMany
    {
        return $this->hasMany(AssetAssignment::class);
    }

    /** @return HasOne<AssetAssignment, $this> */
    public function currentAssignment(): HasOne
    {
        return $this->hasOne(AssetAssignment::class)->whereNull('return_date');
    }

    /** @return BelongsToMany<Contract, $this> */
    public function contracts(): BelongsToMany
    {
        return $this->belongsToMany(Contract::class)->withTimestamps();
    }
}
