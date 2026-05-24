<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/composables/usePermissions';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';

const { can } = usePermissions();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Websites',
        href: '/websites',
    },
];
</script>

<template>
    <Head title="Websites" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4">
            <div class="flex items-center justify-between gap-4">
                <HeadingSmall title="Websites" description="Manage all websites in the system" />
                <Button v-if="can('websites.manage')" as-child>
                    <Link :href="route('websites.create')">
                        <Plus class="size-4" />
                        Add New Website
                    </Link>
                </Button>
            </div>
        </div>
    </AppLayout>
</template>
