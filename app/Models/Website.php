<?php

namespace App\Models;

use App\Support\BusinessUserScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Website extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'primary_domain',
        'business_id',
        'status',
        'seo_defaults',
        'logo',
        'settings',
        'published_at',
    ];

    protected $casts = [
        'seo_defaults' => 'array',
        'settings' => 'array',
        'published_at' => 'datetime',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Limit queries to a single business when the authenticated user belongs to one.
     */
    public function scopeForBusiness(Builder $query, ?int $businessId): Builder
    {
        if ($businessId === null) {
            return $query;
        }

        return $query->where('business_id', $businessId);
    }

    public function resolveRouteBinding($value, $field = null): ?self
    {
        $query = static::query()->where($field ?? $this->getRouteKeyName(), $value);

        if (auth()->check()) {
            $query->forBusiness(BusinessUserScope::scopedBusinessId(auth()->user()));
        }

        return $query->first();
    }
}
