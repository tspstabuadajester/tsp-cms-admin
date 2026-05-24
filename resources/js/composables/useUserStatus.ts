import type { StatusBadgeVariants } from '@/components/StatusBadge';
import type { User } from '@/types';

export function normalizeUserStatus(status?: User['status'] | null): 'active' | 'inactive' {
    return (status ?? 'active') === 'active' ? 'active' : 'inactive';
}

export function isUserActive(status?: User['status'] | null): boolean {
    return normalizeUserStatus(status) === 'active';
}

export function userStatusVariant(status?: User['status'] | null): NonNullable<StatusBadgeVariants['variant']> {
    return isUserActive(status) ? 'green' : 'red';
}

export function formatUserStatus(status?: User['status'] | null): string {
    return isUserActive(status) ? 'Active' : 'Inactive';
}

export function useUserStatus() {
    return {
        normalizeUserStatus,
        isUserActive,
        userStatusVariant,
        formatUserStatus,
    };
}
