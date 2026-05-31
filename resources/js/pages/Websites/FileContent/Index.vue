<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import { Button } from '@/components/ui/button';
import { Card, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Website, type WebsiteTemplateItem, type WebsiteTemplateItemType } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Braces, ExternalLink, FileCode, FileJson, FileText, Folder, Pencil, type LucideIcon } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    website: Pick<Website, 'id' | 'name' | 'slug'>;
    items: WebsiteTemplateItem[];
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
];

const itemMeta: Record<WebsiteTemplateItemType, { label: string; icon: LucideIcon }> = {
    folder: { label: 'Folder', icon: Folder },
    html: { label: 'HTML', icon: FileCode },
    css: { label: 'CSS', icon: FileText },
    javascript: { label: 'JavaScript', icon: Braces },
    json: { label: 'JSON', icon: FileJson },
};

const groupedItems = computed(() =>
    (['folder', 'html', 'css', 'javascript', 'json'] as WebsiteTemplateItemType[]).map((type) => ({
        type,
        label: {
            folder: 'Folders',
            html: 'Pages',
            css: 'Styles',
            javascript: 'Scripts',
            json: 'JSON',
        }[type],
        items: props.items.filter((item) => item.type === type),
    })).filter((group) => group.items.length > 0),
);
</script>

<template>
    <Head :title="`${website.name} — File Manager`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 md:max-w-4xl">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <Button variant="outline" size="sm" as-child>
                    <Link :href="route('websites')">
                        <ArrowLeft class="size-4" />
                        Back to websites
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
                :title="`${website.name} file manager`"
                description="Folders, pages, styles, scripts, and JSON files found in this website's template directory"
            />

            <div
                v-if="items.length === 0"
                class="rounded-lg border p-8 text-center text-muted-foreground"
            >
                No template files found.
            </div>

            <div v-else class="space-y-8">
                <section v-for="group in groupedItems" :key="group.type" class="space-y-3">
                    <h2 class="text-sm font-medium text-muted-foreground">{{ group.label }}</h2>
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        <Card v-for="item in group.items" :key="item.path">
                            <CardHeader class="pb-4">
                                <div class="flex items-start gap-3">
                                    <div
                                        class="flex size-10 shrink-0 items-center justify-center rounded-lg border bg-muted"
                                    >
                                        <component
                                            :is="itemMeta[item.type].icon"
                                            class="size-5 text-muted-foreground"
                                        />
                                    </div>
                                    <div class="min-w-0 flex-1 space-y-1">
                                        <CardTitle class="line-clamp-2 text-sm font-medium">{{ item.name }}</CardTitle>
                                        <p class="text-xs text-muted-foreground">{{ itemMeta[item.type].label }}</p>
                                    </div>
                                    <Button
                                        v-if="item.type === 'json'"
                                        variant="ghost"
                                        size="icon"
                                        class="shrink-0 text-muted-foreground hover:text-foreground"
                                        as-child
                                    >
                                        <Link
                                            :href="route('websites.files.json', { website: website.id, path: item.name })"
                                            :title="`Edit ${item.name}`"
                                        >
                                            <Pencil class="size-4" />
                                        </Link>
                                    </Button>
                                </div>
                            </CardHeader>
                        </Card>
                    </div>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
