<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// open_asset_id is a DB-generated column: never write it, don't expose it
#[Fillable(['asset_id', 'user_id', 'assign_date', 'return_date'])]
#[Hidden(['open_asset_id'])]
class AssetAssignment extends Model
{
    protected function casts(): array
    {
        return [
            'assign_date' => 'date:Y-m-d',
            'return_date' => 'date:Y-m-d',
        ];
    }

    /** @return BelongsTo<Asset, $this> */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Assignments not yet returned. */
    #[Scope]
    protected function open(Builder $query): void
    {
        $query->whereNull('return_date');
    }
}
