<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import { StatusBadge } from '@/components/StatusBadge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { formatUserStatus, userStatusVariant } from '@/composables/useUserStatus';
import { usePermissions } from '@/composables/usePermissions';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Business, type Website } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Globe, Pencil } from 'lucide-vue-next';

const props = defineProps<{
    website: Pick<
        Website,
        'id' | 'uuid' | 'name' | 'slug' | 'primary_domain' | 'logo' | 'status' | 'published_at' | 'created_at' | 'updated_at'
    > & {
        business?: Pick<Business, 'id' | 'name'> | null;
    };
}>();

const { can } = usePermissions();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Websites',
        href: '/websites',
    },
    {
        title: props.website.name,
        href: `/websites/${props.website.id}`,
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

const formatDate = (date?: string | null) => {
    if (!date) {
        return '—';
    }

    return new Date(date).toLocaleString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

const detailFields = [
    { label: 'Name', value: props.website.name },
    { label: 'Slug', value: props.website.slug },
    { label: 'Primary domain', value: props.website.primary_domain ?? '—' },
    { label: 'Business', value: props.website.business?.name ?? '—' },
    { label: 'UUID', value: props.website.uuid },
    { label: 'Published at', value: formatDate(props.website.published_at) },
    { label: 'Created at', value: formatDate(props.website.created_at) },
    { label: 'Updated at', value: formatDate(props.website.updated_at) },
];
</script>

<template>
    <Head :title="website.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 md:max-w-4xl">
            <div class="flex items-center justify-between gap-4">
                <Button variant="outline" size="sm" as-child>
                    <Link :href="route('websites')">
                        <ArrowLeft class="size-4" />
                        Back to websites
                    </Link>
                </Button>

                <Button v-if="can('websites.manage')" as-child>
                    <Link :href="route('websites.edit', website.id)">
                        <Pencil class="size-4" />
                        Edit website
                    </Link>
                </Button>
            </div>

            <Card class="overflow-hidden">
                <div class="flex flex-col gap-6 p-6 md:flex-row md:items-center">
                    <div
                        class="flex size-28 shrink-0 items-center justify-center overflow-hidden rounded-xl border bg-muted md:size-32"
                    >
                        <img
                            v-if="logoUrl(website.logo)"
                            :src="logoUrl(website.logo)!"
                            :alt="`${website.name} logo`"
                            class="size-full object-cover"
                        />
                        <Globe v-else class="size-12 text-muted-foreground/50" />
                    </div>

                    <div class="min-w-0 flex-1 space-y-3">
                        <div class="flex flex-wrap items-center gap-3">
                            <HeadingSmall
                                :title="website.name"
                                :description="website.primary_domain ?? 'No primary domain set'"
                            />
                            <StatusBadge :variant="userStatusVariant(website.status)">
                                {{ formatUserStatus(website.status) }}
                            </StatusBadge>
                        </div>
                    </div>
                </div>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Website details</CardTitle>
                </CardHeader>
                <Separator />
                <CardContent class="pt-6">
                    <dl class="grid gap-6 sm:grid-cols-2">
                        <div v-for="field in detailFields" :key="field.label" class="space-y-1">
                            <dt class="text-sm font-medium text-muted-foreground">{{ field.label }}</dt>
                            <dd class="text-sm font-medium break-all">{{ field.value }}</dd>
                        </div>
                        <div class="space-y-1">
                            <dt class="text-sm font-medium text-muted-foreground">Status</dt>
                            <dd>
                                <StatusBadge :variant="userStatusVariant(website.status)">
                                    {{ formatUserStatus(website.status) }}
                                </StatusBadge>
                            </dd>
                        </div>
                    </dl>
                </CardContent>
            </Card>
        </div>
    </AppLayout>
</template>
