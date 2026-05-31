<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import { getXsrfToken, isImageUploadFieldPath } from '@/lib/json-fields';
import { LoaderCircle } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    fieldKey: string;
    modelValue: string;
    inputId: string;
    websiteId: number;
    error?: string;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const uploading = ref(false);
const uploadError = ref<string | null>(null);

const isImageField = computed(() => isImageUploadFieldPath(props.fieldKey));

watch(
    () => props.inputId,
    () => {
        uploading.value = false;
        uploadError.value = null;
    },
);

const previewUrl = computed((): string => {
    if (!props.modelValue) {
        return '';
    }

    if (props.modelValue.startsWith('http://') || props.modelValue.startsWith('https://')) {
        return props.modelValue;
    }

    return route('websites.preview.asset', { website: props.websiteId, path: props.modelValue });
});

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
            emit('update:modelValue', payload.path);
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
        <label
            v-if="isImageField"
            class="text-sm font-medium leading-none text-muted-foreground"
        >
            {{ fieldKey }}
        </label>
        <label
            v-else
            :for="inputId"
            class="text-sm font-medium leading-none text-muted-foreground"
        >
            {{ fieldKey }}
        </label>

        <div v-if="isImageField" class="space-y-3 rounded-lg border p-4">
            <div v-if="modelValue" class="overflow-hidden rounded-md border bg-muted/40">
                <img
                    :src="previewUrl"
                    :alt="fieldKey"
                    loading="lazy"
                    class="max-h-40 w-full object-contain"
                />
            </div>

            <p v-if="modelValue" class="break-all font-mono text-xs text-muted-foreground">
                {{ modelValue }}
            </p>

            <div class="flex max-w-md items-center gap-3">
                <input
                    :id="inputId"
                    :key="`${inputId}-file`"
                    type="file"
                    accept="image/*"
                    :disabled="uploading"
                    :class="
                        cn(
                            'flex h-10 w-full cursor-pointer rounded-md border border-input bg-background px-3 py-2 text-base ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium file:text-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm',
                        )
                    "
                    @change="uploadImage"
                />
                <LoaderCircle v-if="uploading" class="size-4 shrink-0 animate-spin text-muted-foreground" />
            </div>

            <InputError :message="uploadError ?? error" />
        </div>

        <template v-else>
            <Input
                :id="inputId"
                :model-value="modelValue"
                type="text"
                :class="error ? 'border-destructive focus-visible:ring-destructive' : ''"
                @update:model-value="emit('update:modelValue', String($event))"
            />
            <InputError :message="error" />
        </template>
    </div>
</template>
