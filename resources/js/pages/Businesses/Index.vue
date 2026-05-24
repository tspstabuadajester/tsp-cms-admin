<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem, type Business } from '@/types';
import { Head } from '@inertiajs/vue3';

defineProps<{
    businesses: Business[];
}>();

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
                <HeadingSmall title="Businesses" description="Manage all businesses in the system" />

                <div class="overflow-hidden rounded-lg border">
                    <table class="w-full caption-bottom text-sm">
                        <thead class="border-b bg-muted/50">
                            <tr>
                                <th class="h-10 px-4 text-left align-middle font-medium text-muted-foreground">Name</th>
                                <th class="h-10 px-4 text-left align-middle font-medium text-muted-foreground">Email</th>
                                <th class="h-10 px-4 text-left align-middle font-medium text-muted-foreground">Address</th>
                                <th class="h-10 px-4 text-left align-middle font-medium text-muted-foreground">Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="businesses.length === 0">
                                <td colspan="4" class="p-4 text-center text-muted-foreground">No businesses found.</td>
                            </tr>
                            <tr
                                v-for="business in businesses"
                                :key="business.id"
                                class="border-b transition-colors last:border-0 hover:bg-muted/50"
                            >
                                <td class="p-4 align-middle font-medium">{{ business.name }}</td>
                                <td class="p-4 align-middle">{{ business.email ?? '—' }}</td>
                                <td class="p-4 align-middle">{{ business.address ?? '—' }}</td>
                                <td class="p-4 align-middle text-muted-foreground">{{ formatDate(business.created_at) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
