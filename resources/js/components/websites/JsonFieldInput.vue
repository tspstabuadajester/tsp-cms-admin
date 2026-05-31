<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { getXsrfToken, isImageUploadFieldPath } from '@/lib/json-fields';
import { type WebsiteJsonField } from '@/types';
import { LoaderCircle } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
    field: WebsiteJsonField;
    inputId: string;
    websiteId: number;
    error?: string;
}>();

const emit = defineEmits<{
    'update:value': [value: string];
}>();

const uploading = ref(false);
const uploadError = ref<string | null>(null);

const assetPreviewUrl = (value: string): string => {
    if (!value || value.startsWith('http://') || value.startsWith('https://')) {
        return value;
    }

    return route('websites.preview.asset', { website: props.websiteId, path: value });
};

const uploadImage = async (event: Event) => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];

    input.value = '';

    if (!file) {
        return;
    }

    if (!file.type.startsWith('image/')) {
        uploadError.value = 'Only image files are allowed.';

        return;
    }

    uploading.value = true;
    uploadError.value = null;

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

        if (typeof payload.path === 'string') {
            emit('update:value', payload.path);
        }
    } catch (error) {
        uploadError.value = error instanceof Error ? error.message : 'Image upload failed.';
    } finally {
        uploading.value = false;
    }
};
</script>

<template>
    <div class="space-y-2">
        <Label :for="inputId" class="text-sm text-muted-foreground">
            {{ field.key }}
        </Label>

        <div v-if="isImageUploadFieldPath(field.key)" class="space-y-3 rounded-lg border p-4">
            <div v-if="field.value" class="overflow-hidden rounded-md border bg-muted/40">
                <img :src="assetPreviewUrl(field.value)" :alt="field.key" class="max-h-40 w-full object-contain" />
            </div>

            <p v-if="field.value" class="font-mono text-xs text-muted-foreground">
                {{ field.value }}
            </p>

            <div class="flex items-center gap-3">
                <Input
                    :id="inputId"
                    type="file"
                    accept="image/*"
                    class="cursor-pointer"
                    :disabled="uploading"
                    @change="uploadImage"
                />
                <LoaderCircle v-if="uploading" class="size-4 shrink-0 animate-spin text-muted-foreground" />
            </div>

            <InputError :message="uploadError ?? error" />
        </div>

        <template v-else>
            <Input
                :id="inputId"
                :model-value="field.value"
                type="text"
                :class="error ? 'border-destructive focus-visible:ring-destructive' : ''"
                @update:model-value="emit('update:value', $event)"
            />
            <InputError :message="error" />
        </template>
    </div>
</template>
