import type { SharedData } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export function usePermissions() {
    const page = usePage<Pick<SharedData, 'auth'>>();

    const permissions = computed(() => page.props.auth.permissions ?? []);
    const roles = computed(() => page.props.auth.roles ?? []);

    const can = (permission: string): boolean => permissions.value.includes(permission);

    const hasRole = (role: string): boolean => roles.value.includes(role);

    return {
        permissions,
        roles,
        can,
        hasRole,
    };
}
