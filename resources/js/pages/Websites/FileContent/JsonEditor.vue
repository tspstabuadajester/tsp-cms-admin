<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Website } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, ExternalLink } from 'lucide-vue-next';

const props = defineProps<{
    website: Pick<Website, 'id' | 'name' | 'slug'>;
    file: {
        path: string;
        name: string;
    };
    can_preview: boolean;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Websites',
        href: '/websites',
    },
    {
        title: props.website.name,
        href: route('websites.files', props.website.id),
    },
    {
        title: props.file.name,
        href: route('websites.files.json', { website: props.website.id, path: props.file.name }),
    },
];
</script>

<template>
    <Head :title="`${website.name} — ${file.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 md:max-w-4xl">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <Button variant="outline" size="sm" as-child>
                    <Link :href="route('websites.files', website.id)">
                        <ArrowLeft class="size-4" />
                        Back to file manager
                    </Link>
                </Button>
                <Button v-if="can_preview" size="sm" as-child>
                    <a
                        :href="route('websites.preview', website.id)"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        <ExternalLink class="size-4" />
                        Preview
                    </a>
                </Button>
            </div>

            <HeadingSmall
                :title="file.name"
                :description="`Edit JSON content for ${website.name}`"
            />

            <div class="rounded-lg border border-dashed p-12 text-center text-muted-foreground">
                JSON editor coming soon.
            </div>
        </div>
    </AppLayout>
</template>
