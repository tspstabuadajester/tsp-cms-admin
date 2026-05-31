<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { usePermissions } from '@/composables/usePermissions';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    fullWidth?: boolean;
}

type SettingsNavItem = NavItem & {
    permission?: string;
};

withDefaults(defineProps<Props>(), {
    fullWidth: false,
});

const { can } = usePermissions();

const sidebarNavItems: SettingsNavItem[] = [
    {
        title: 'Profile',
        href: '/settings/profile',
    },
    {
        title: 'Password',
        href: '/settings/password',
    },
    {
        title: 'Users',
        href: '/users',
        permission: 'settings.manage',
    },
    {
        title: 'Businesses',
        href: '/business',
        permission: 'business.manage',
    },
    {
        title: 'Appearance',
        href: '/settings/appearance',
    },
];

const visibleNavItems = computed(() =>
    sidebarNavItems.filter((item) => !item.permission || can(item.permission)),
);

const page = usePage();

const isNavItemActive = (href: string): boolean => {
    if (page.url === href) {
        return true;
    }

    return page.url.startsWith(`${href}/`);
};
</script>

<template>
    <div class="px-4 py-6">
        <Heading title="Settings" description="Manage your profile and account settings" />

        <div class="flex flex-col space-y-8 md:space-y-0 lg:flex-row lg:space-x-12 lg:space-y-0">
            <aside class="w-full max-w-xl lg:w-48">
                <nav class="flex flex-col space-x-0 space-y-1">
                    <Button
                        v-for="item in visibleNavItems"
                        :key="item.href"
                        variant="ghost"
                        :class="['w-full justify-start', { 'bg-muted': isNavItemActive(item.href) }]"
                        as-child
                    >
                        <Link :href="item.href">
                            {{ item.title }}
                        </Link>
                    </Button>
                </nav>
            </aside>

            <Separator class="my-6 md:hidden" />

            <div class="flex-1" :class="fullWidth ? 'max-w-none' : 'md:max-w-2xl'">
                <section class="space-y-12" :class="fullWidth ? 'max-w-none' : 'max-w-xl'">
                    <slot />
                </section>
            </div>
        </div>
    </div>
</template>
