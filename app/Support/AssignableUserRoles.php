<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;
use Spatie\Permission\Models\Role;

final class AssignableUserRoles
{
    /**
     * Role names that may be assigned via the user create/edit forms.
     *
     * @var list<string>
     */
    public const NAMES = [
        'business-admin',
        'content-manager',
    ];

    /**
     * @return Collection<int, Role>
     */
    public static function forSelect(): Collection
    {
        return Role::query()
            ->whereIn('name', self::NAMES)
            ->orderBy('name')
            ->get(['name']);
    }

    public static function validationRule(): In
    {
        return Rule::in(self::NAMES);
    }
}
