<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['asset_tag', 'serial_number', 'model_id'])]
class Asset extends Model
{
    /** @return BelongsTo<AssetModel, $this> */
    public function assetModel(): BelongsTo
    {
        return $this->belongsTo(AssetModel::class, 'model_id');
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
