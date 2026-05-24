<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Support\BusinessUserScope;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    use HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'status',
        'business_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Determine whether the user account is active.
     */
    public function isActive(): bool
    {
        return ($this->status ?? 'active') === 'active';
    }

    public function belongsToBusiness(): bool
    {
        return $this->business_id !== null;
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

    /**
     * @param  array<string, mixed>  $fields
     */
    public function resolveRouteBinding($value, $field = null): ?self
    {
        $query = static::query()->where($field ?? $this->getRouteKeyName(), $value);

        if (auth()->check() && BusinessUserScope::isScoped(auth()->user())) {
            $query->where('business_id', BusinessUserScope::scopedBusinessId(auth()->user()));
        }

        return $query->first();
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
