<script setup lang="ts">
import NavIcon from '@/components/NavIcon.vue';
import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';

defineProps<{
    items: NavItem[];
}>();

const page = usePage<SharedData>();

const isNavItemActive = (href: string): boolean => {
    if (page.url === href) {
        return true;
    }

    return page.url.startsWith(`${href}/`);
};
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel>Platform</SidebarGroupLabel>
        <SidebarMenu>
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <SidebarMenuButton as-child :is-active="isNavItemActive(item.href)">
                    <Link :href="item.href">
                        <NavIcon v-if="item.iconSrc" :src="item.iconSrc" :alt="item.title" />
                        <component v-else-if="item.icon" :is="item.icon" />
                        <span>{{ item.title }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
