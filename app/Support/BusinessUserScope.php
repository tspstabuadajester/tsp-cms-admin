<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;

final class BusinessUserScope
{
    /**
     * Users without a business_id are treated as super admins and may access all users.
     */
    public static function treatsAsSuperAdmin(?Authenticatable $user): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        return $user->business_id === null;
    }

    /**
     * Whether the authenticated user is limited to a single business.
     */
    public static function isScoped(?Authenticatable $user): bool
    {
        return ! self::treatsAsSuperAdmin($user);
    }

    /**
     * The business id used to scope queries, or null when treated as super admin.
     */
    public static function scopedBusinessId(?Authenticatable $user): ?int
    {
        if (! $user instanceof User || self::treatsAsSuperAdmin($user)) {
            return null;
        }

        return $user->business_id;
    }

    public static function userBelongsToScope(User $user, ?Authenticatable $authUser): bool
    {
        if (self::treatsAsSuperAdmin($authUser)) {
            return true;
        }

        return $user->business_id === self::scopedBusinessId($authUser);
    }
}
