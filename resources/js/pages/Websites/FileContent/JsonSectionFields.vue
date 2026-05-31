<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { getXsrfToken, isImageUploadFieldPath } from '@/lib/json-fields';
import { type WebsiteJsonField } from '@/types';
import { LoaderCircle } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
    sectionKey: string;
    websiteId: number;
}>();

const fields = defineModel<WebsiteJsonField[]>('fields', { required: true });

const uploadingFieldPath = ref<string | null>(null);
const uploadErrors = ref<Record<string, string>>({});

const fieldId = (fieldPath: string): string =>
    `${props.sectionKey}-${fieldPath}`.replace(/[^a-zA-Z0-9_-]/g, '-');

const assetPreviewUrl = (value: string): string => {
    if (!value || value.startsWith('http://') || value.startsWith('https://')) {
        return value;
    }

    return route('websites.preview.asset', { website: props.websiteId, path: value });
};

const uploadImage = async (fieldPath: string, event: Event) => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];

    input.value = '';

    if (!file) {
        return;
    }

    if (!file.type.startsWith('image/')) {
        uploadErrors.value[fieldPath] = 'Only image files are allowed.';

        return;
    }

    uploadingFieldPath.value = fieldPath;
    delete uploadErrors.value[fieldPath];

    const body = new FormData();
    body.append('file', file);

    try {
        const response = await fetch(route('websites.files.assets.upload', { website: props.websiteId }), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-XSRF-TOKEN': getXsrfToken(),
            },
            body,
        });

        const payload = await response.json().catch(() => ({}));

        if (!response.ok) {
            const message =
                typeof payload.message === 'string'
                    ? payload.message
                    : payload.errors?.file?.[0] ?? 'Image upload failed.';

            throw new Error(message);
        }

        const field = fields.value.find((item) => item.path === fieldPath);

        if (field && typeof payload.path === 'string') {
            field.value = payload.path;
        }
    } catch (error) {
        uploadErrors.value[fieldPath] = error instanceof Error ? error.message : 'Image upload failed.';
    } finally {
        uploadingFieldPath.value = null;
    }
};
</script>

<template>
    <div v-if="fields.length === 0" class="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground">
        No editable fields in this section.
    </div>

    <div v-else class="grid gap-4 sm:grid-cols-1">
        <div v-for="(field, index) in fields" :key="`${sectionKey}.${field.path}`" class="space-y-2">
            <Label :for="fieldId(field.path)" class="text-sm text-muted-foreground">
                {{ field.path }}
            </Label>

            <div v-if="isImageUploadFieldPath(field.path)" class="space-y-3 rounded-lg border p-4">
                <div v-if="field.value" class="overflow-hidden rounded-md border bg-muted/40">
                    <img
                        :src="assetPreviewUrl(field.value)"
                        :alt="field.path"
                        class="max-h-40 w-full object-contain"
                    />
                </div>

                <p v-if="field.value" class="font-mono text-xs text-muted-foreground">
                    {{ field.value }}
                </p>

                <div class="flex items-center gap-3">
                    <Input
                        :id="fieldId(field.path)"
                        type="file"
                        accept="image/*"
                        class="cursor-pointer"
                        :disabled="uploadingFieldPath === field.path"
                        @change="uploadImage(field.path, $event)"
                    />
                    <LoaderCircle
                        v-if="uploadingFieldPath === field.path"
                        class="size-4 shrink-0 animate-spin text-muted-foreground"
                    />
                </div>

                <InputError :message="uploadErrors[field.path]" />
            </div>

            <Input v-else :id="fieldId(field.path)" v-model="fields[index].value" type="text" />
        </div>
    </div>
</template>
