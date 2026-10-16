<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

#[Fillable(['user_id', 'name'])]
class Brand extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function aliases(): HasMany
    {
        return $this->hasMany(BrandAlias::class);
    }

    public function competitors(): HasMany
    {
        // Stable order: plans check the first N competitors.
        return $this->hasMany(Competitor::class)->orderBy('position')->orderBy('id');
    }

    /**
     * The competitors the current plan actually checks: the first N by position.
     * Every screen and export (run page, dashboard, PDF, CSV, fix text) uses this one list.
     */
    public function checkedCompetitors(): Collection
    {
        $limit = $this->user?->subscription?->limits()['competitors'] ?? null;

        return $limit === null ? $this->competitors : $this->competitors->take($limit)->values();
    }

    public function queries(): HasMany
    {
        return $this->hasMany(PromptQuery::class)->orderBy('position');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(Run::class)->latest();
    }
}
