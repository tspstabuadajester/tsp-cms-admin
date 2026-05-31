<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import { Button } from '@/components/ui/button';
import { Card, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Website, type WebsiteFilePage } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, FileCode } from 'lucide-vue-next';

const props = defineProps<{
    website: Pick<Website, 'id' | 'name' | 'slug'>;
    pages: WebsiteFilePage[];
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
</script>

<template>
    <Head :title="`${website.name} — Pages`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-4 md:max-w-4xl">
            <div class="flex items-center justify-between gap-4">
                <Button variant="outline" size="sm" as-child>
                    <Link :href="route('websites')">
                        <ArrowLeft class="size-4" />
                        Back to websites
                    </Link>
                </Button>
            </div>

            <HeadingSmall
                :title="`${website.name} pages`"
                description="HTML files found in this website's template directory"
            />

            <div
                v-if="pages.length === 0"
                class="rounded-lg border p-8 text-center text-muted-foreground"
            >
                No HTML pages found.
            </div>

            <div v-else class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <Card v-for="page in pages" :key="page.path">
                    <CardHeader class="pb-4">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex size-10 shrink-0 items-center justify-center rounded-lg border bg-muted"
                            >
                                <FileCode class="size-5 text-muted-foreground" />
                            </div>
                            <CardTitle class="line-clamp-2 text-sm font-medium">{{ page.name }}</CardTitle>
                        </div>
                    </CardHeader>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
