<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { collectSaveErrorMessages, hasSaveErrors } from '@/lib/json-form-errors';
import FileContentJsonLayout from '@/layouts/websites/FileContentJsonLayout.vue';
import JsonSectionFields from '@/pages/Websites/FileContent/JsonSectionFields.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Website, type WebsiteJsonSection } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, ExternalLink, LoaderCircle, Save } from 'lucide-vue-next';
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
    status?: string;
}>();

const cloneSection = (section: WebsiteJsonSection): WebsiteJsonSection => ({
    key: section.key,
    fields: section.fields.map((field) => ({ ...field })),
    arrays: section.arrays.map((arrayGroup) => ({
        key: arrayGroup.key,
        template: arrayGroup.template.map((field) => ({ ...field })),
        template_hidden: arrayGroup.template_hidden.map((field) => ({ ...field })),
        items: arrayGroup.items.map((item) => ({
            fields: item.fields.map((field) => ({ ...field })),
            hidden: item.hidden.map((field) => ({ ...field })),
        })),
    })),
});

const form = useForm({
    sections: [] as WebsiteJsonSection[],
});

const editableSections = ref<WebsiteJsonSection[]>(props.sections.map(cloneSection));

const activeSectionKey = ref(props.sections[0]?.key ?? '');

watch(
    () => props.sections,
    (sections) => {
        editableSections.value = sections.map(cloneSection);

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

const sectionHasContent = (section: WebsiteJsonSection): boolean =>
    section.fields.length > 0 || section.arrays.some((arrayGroup) => arrayGroup.items.length > 0);

const hasFields = computed(() => editableSections.value.some(sectionHasContent));

const saveErrorMessages = computed(() => collectSaveErrorMessages(form.errors));

const clearFieldError = (field?: string): void => {
    if (field) {
        (form.clearErrors as (name: string) => void)(field);
    }
};

const save = () => {
    form.sections = editableSections.value.map((section) => ({
        key: section.key,
        fields: section.fields.map((field) => ({
            path: field.path,
            value: field.value,
        })),
        arrays: section.arrays.map((arrayGroup) => ({
            key: arrayGroup.key,
            items: arrayGroup.items.map((item) => ({
                fields: item.fields.map((field) => ({
                    key: field.key,
                    value: field.value,
                })),
                hidden: item.hidden.map((field) => ({
                    key: field.key,
                    value: field.value,
                })),
            })),
        })),
    }));

    form.put(route('websites.files.json.update', { website: props.website.id, path: props.file.name }), {
        preserveScroll: true,
        onSuccess: () => form.clearErrors(),
    });
};

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
                    <Button
                        type="button"
                        size="sm"
                        :disabled="form.processing || !!json_error || !hasFields"
                        @click="save"
                    >
                        <LoaderCircle v-if="form.processing" class="size-4 animate-spin" />
                        <Save v-else class="size-4" />
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
                v-if="status"
                class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-700"
            >
                {{ status }}
            </div>

            <div
                v-if="hasSaveErrors(form.errors)"
                class="rounded-lg border border-destructive/30 bg-destructive/5 p-4 text-sm text-destructive"
            >
                <p class="font-medium">Could not save changes. Please fix the errors below.</p>
                <ul v-if="saveErrorMessages.length > 0" class="mt-2 list-inside list-disc space-y-1">
                    <li v-for="message in saveErrorMessages" :key="message">{{ message }}</li>
                </ul>
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
                    v-model:section="editableSections[activeSectionIndex]"
                    :section-key="editableSections[activeSectionIndex].key"
                    :section-index="activeSectionIndex"
                    :website-id="website.id"
                    :errors="form.errors"
                    :clear-errors="clearFieldError"
                />
            </FileContentJsonLayout>
        </div>
    </AppLayout>
</template>
