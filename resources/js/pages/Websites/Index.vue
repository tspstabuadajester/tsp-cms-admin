<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import PaginationLinks from '@/components/PaginationLinks.vue';
import { StatusBadge } from '@/components/StatusBadge';
import { Button } from '@/components/ui/button';
import { Card, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { formatUserStatus, userStatusVariant } from '@/composables/useUserStatus';
import { usePermissions } from '@/composables/usePermissions';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Paginated, type Website } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Eye, Globe, Pencil, Plus } from 'lucide-vue-next';

defineProps<{
    websites: Paginated<Website>;
}>();

const { can } = usePermissions();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Websites',
        href: '/websites',
    },
];

const logoUrl = (filename?: string | null): string | undefined => {
    if (!filename) {
        return undefined;
    }

    if (filename.startsWith('/') || filename.startsWith('http://') || filename.startsWith('https://')) {
        return filename;
    }

    return `/storage/logos/${filename}`;
};

const domainUrl = (domain: string): string => {
    if (domain.startsWith('http://') || domain.startsWith('https://')) {
        return domain;
    }

    return `https://${domain}`;
};
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

            <div
                v-if="websites.data.length === 0"
                class="rounded-lg border p-8 text-center text-muted-foreground"
            >
                No websites found.
            </div>

            <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <Card v-for="website in websites.data" :key="website.id" class="overflow-hidden">
                    <div class="aspect-video w-full overflow-hidden bg-muted">
                        <img
                            v-if="logoUrl(website.logo)"
                            :src="logoUrl(website.logo)!"
                            :alt="`${website.name} logo`"
                            class="size-full object-cover"
                        />
                        <div v-else class="flex size-full items-center justify-center">
                            <Globe class="size-10 text-muted-foreground/50" />
                        </div>
                    </div>
                    <CardHeader class="pb-4">
                        <div class="flex items-start justify-between gap-2">
                            <CardTitle class="line-clamp-1 text-base">{{ website.name }}</CardTitle>
                            <StatusBadge :variant="userStatusVariant(website.status)">
                                {{ formatUserStatus(website.status) }}
                            </StatusBadge>
                        </div>
                        <CardDescription v-if="website.primary_domain" class="min-w-0">
                            <a
                                :href="domainUrl(website.primary_domain)"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="flex items-center gap-1.5 min-w-0 hover:underline"
                            >
                                <Globe class="size-3.5 shrink-0" />
                                <span class="truncate">{{ website.primary_domain }}</span>
                            </a>
                        </CardDescription>
                        <div v-if="can('websites.manage')" class="flex gap-2 pt-2">
                            <Button variant="outline" size="sm" as-child>
                                <Link :href="route('websites.show', website.id)">
                                    <Eye class="size-4" />
                                    View
                                </Link>
                            </Button>
                            <Button variant="outline" size="sm" as-child>
                                <Link :href="route('websites.edit', website.id)">
                                    <Pencil class="size-4" />
                                    Edit
                                </Link>
                            </Button>
                        </div>
                    </CardHeader>
                </Card>
            </div>

            <PaginationLinks v-if="websites.data.length > 0" :pagination="websites" />
        </div>
    </AppLayout>
</template>
