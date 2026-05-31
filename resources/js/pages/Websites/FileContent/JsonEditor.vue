<script setup lang="ts">
import { Button } from '@/components/ui/button';
import FileContentJsonLayout from '@/layouts/websites/FileContentJsonLayout.vue';
import JsonSectionFields from '@/pages/Websites/FileContent/JsonSectionFields.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Website, type WebsiteJsonSection } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, ExternalLink, Save } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    website: Pick<Website, 'id' | 'name' | 'slug'>;
    file: {
        path: string;
        name: string;
    };
    sections: WebsiteJsonSection[];
    json_error: string | null;
    can_preview: boolean;
}>();

const editableSections = ref<WebsiteJsonSection[]>(
    props.sections.map((section) => ({
        key: section.key,
        fields: section.fields.map((field) => ({ ...field })),
    })),
);

const activeSectionKey = ref(props.sections[0]?.key ?? '');

watch(
    () => props.sections,
    (sections) => {
        editableSections.value = sections.map((section) => ({
            key: section.key,
            fields: section.fields.map((field) => ({ ...field })),
        }));

        if (!editableSections.value.some((section) => section.key === activeSectionKey.value)) {
            activeSectionKey.value = editableSections.value[0]?.key ?? '';
        }
    },
);

const navItems = computed(() =>
    editableSections.value.map((section) => ({
        key: section.key,
        title: section.key,
    })),
);

const activeSectionIndex = computed(() =>
    editableSections.value.findIndex((section) => section.key === activeSectionKey.value),
);

const hasFields = computed(() => editableSections.value.some((section) => section.fields.length > 0));

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
        <div class="flex flex-1 flex-col gap-6 p-4">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <Button variant="outline" size="sm" as-child>
                    <Link :href="route('websites.files', website.id)">
                        <ArrowLeft class="size-4" />
                        Back to file manager
                    </Link>
                </Button>
                <div class="flex flex-wrap items-center gap-2">
                    <Button type="button" size="sm">
                        <Save class="size-4" />
                        Save Changes
                    </Button>
                    <Button v-if="can_preview" size="sm" variant="outline" as-child>
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
            </div>

            <div
                v-if="json_error"
                class="rounded-lg border border-destructive/30 bg-destructive/5 p-4 text-sm text-destructive"
            >
                {{ json_error }}
            </div>

            <div
                v-else-if="editableSections.length === 0 || !hasFields"
                class="rounded-lg border border-dashed p-12 text-center text-muted-foreground"
            >
                This JSON file has no fields to edit.
            </div>

            <FileContentJsonLayout
                v-else
                v-model:active-section="activeSectionKey"
                :title="file.name"
                :description="`Edit JSON content for ${website.name}`"
                :nav-items="navItems"
            >
                <JsonSectionFields
                    v-if="activeSectionIndex >= 0"
                    v-model:fields="editableSections[activeSectionIndex].fields"
                    :section-key="editableSections[activeSectionIndex].key"
                />
            </FileContentJsonLayout>
        </div>
    </AppLayout>
</template>
