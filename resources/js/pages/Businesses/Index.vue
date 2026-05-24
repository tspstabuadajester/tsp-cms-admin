<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import { StatusBadge } from '@/components/StatusBadge';
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/composables/usePermissions';
import { formatUserStatus, userStatusVariant } from '@/composables/useUserStatus';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
import { type BreadcrumbItem, type Business, type Paginated } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Pencil, Plus } from 'lucide-vue-next';

defineProps<{
    businesses: Paginated<Business>;
}>();

const { can } = usePermissions();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Businesses',
        href: '/business',
    },
];

const formatDate = (date: string) => {
    return new Date(date).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};
</script>

<template>
    <Head title="Businesses" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <SettingsLayout full-width>
            <div class="flex flex-col gap-4">
                <div class="flex items-center justify-between gap-4">
                    <HeadingSmall title="Businesses" description="Manage all businesses in the system" />
                    <Button v-if="can('business.manage')" as-child>
                        <Link :href="route('business.create')">
                            <Plus class="size-4" />
                            Add New Business
                        </Link>
                    </Button>
                </div>

                <div class="overflow-hidden rounded-lg border">
                    <table class="w-full caption-bottom text-sm">
                        <thead class="border-b bg-muted/50">
                            <tr>
                                <th class="h-10 px-4 text-left align-middle font-medium text-muted-foreground">Name</th>
                                <th class="h-10 px-4 text-left align-middle font-medium text-muted-foreground">Email</th>
                                <th class="h-10 px-4 text-left align-middle font-medium text-muted-foreground">Address</th>
                                <th class="h-10 px-4 text-left align-middle font-medium text-muted-foreground">Status</th>
                                <th class="h-10 px-4 text-left align-middle font-medium text-muted-foreground">Created</th>
                                <th class="h-10 w-24 px-4 text-right align-middle font-medium text-muted-foreground">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="businesses.data.length === 0">
                                <td colspan="6" class="p-4 text-center text-muted-foreground">No businesses found.</td>
                            </tr>
                            <tr
                                v-for="business in businesses.data"
                                :key="business.id"
                                class="border-b transition-colors last:border-0 hover:bg-muted/50"
                            >
                                <td class="p-4 align-middle font-medium">{{ business.name }}</td>
                                <td class="p-4 align-middle">{{ business.email ?? '—' }}</td>
                                <td class="p-4 align-middle">{{ business.address ?? '—' }}</td>
                                <td class="p-4 align-middle">
                                    <StatusBadge :variant="userStatusVariant(business.status)">
                                        {{ formatUserStatus(business.status) }}
                                    </StatusBadge>
                                </td>
                                <td class="p-4 align-middle text-muted-foreground">{{ formatDate(business.created_at) }}</td>
                                <td class="p-4 align-middle text-right">
                                    <Button v-if="can('business.manage')" variant="outline" size="sm" as-child>
                                        <Link :href="route('business.edit', business.id)">
                                            <Pencil class="size-4" />
                                            Edit
                                        </Link>
                                    </Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <PaginationLinks :pagination="businesses" />
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
