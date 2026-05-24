<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { type Paginated } from '@/types';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    pagination: Paginated<unknown>;
}>();

const pageLinks = computed(() => props.pagination.links ?? []);
</script>

<template>
    <div
        v-if="pagination.last_page > 1"
        class="flex flex-col items-center justify-between gap-4 border-t px-4 py-3 sm:flex-row"
    >
        <p class="text-sm text-muted-foreground">
            Showing {{ pagination.from ?? 0 }} to {{ pagination.to ?? 0 }} of {{ pagination.total }} results
        </p>
        <nav class="flex flex-wrap items-center justify-center gap-1">
            <template v-for="(link, index) in pageLinks" :key="index">
                <Button
                    v-if="link.url"
                    :variant="link.active ? 'default' : 'outline'"
                    size="sm"
                    as-child
                >
                    <Link :href="link.url" preserve-scroll>
                        <span v-html="link.label" />
                    </Link>
                </Button>
                <span
                    v-else
                    class="inline-flex min-w-9 items-center justify-center px-3 py-2 text-sm text-muted-foreground"
                    v-html="link.label"
                />
            </template>
        </nav>
    </div>
</template>
