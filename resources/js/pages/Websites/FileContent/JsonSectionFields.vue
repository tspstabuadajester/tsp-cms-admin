<script setup lang="ts">
import JsonFieldInput from '@/components/websites/JsonFieldInput.vue';
import JsonArrayGroup from '@/pages/Websites/FileContent/JsonArrayGroup.vue';
import { fieldErrorMessage, scalarFieldErrorKeys } from '@/lib/json-form-errors';
import { type WebsiteJsonSection } from '@/types';

const props = defineProps<{
    sectionKey: string;
    sectionIndex: number;
    websiteId: number;
    errors: Record<string, string>;
    clearErrors: (field?: string) => void;
}>();

const section = defineModel<WebsiteJsonSection>('section', { required: true });

const fieldId = (fieldPath: string): string =>
    `${props.sectionKey}-${fieldPath}`.replace(/[^a-zA-Z0-9_-]/g, '-');

const scalarFieldError = (fieldIndex: number): string | undefined =>
    fieldErrorMessage(props.errors, scalarFieldErrorKeys(props.sectionIndex, fieldIndex));

const updateFieldValue = (fieldIndex: number, value: string) => {
    section.value.fields[fieldIndex].value = value;

    for (const key of scalarFieldErrorKeys(props.sectionIndex, fieldIndex)) {
        props.clearErrors(key);
    }
};
</script>

<template>
    <div
        v-if="section.fields.length === 0 && section.arrays.length === 0"
        class="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground"
    >
        No editable fields in this section.
    </div>

    <div v-else class="space-y-8">
        <div v-if="section.fields.length > 0" class="grid gap-4 sm:grid-cols-1">
            <JsonFieldInput
                v-for="(field, index) in section.fields"
                :key="field.path"
                :field="{ key: field.path, value: field.value }"
                :input-id="fieldId(field.path)"
                :website-id="websiteId"
                :error="scalarFieldError(index)"
                @update:value="updateFieldValue(index, $event)"
            />
        </div>

        <JsonArrayGroup
            v-for="(arrayGroup, index) in section.arrays"
            :key="arrayGroup.key"
            v-model:array-group="section.arrays[index]"
            :section-key="sectionKey"
            :section-index="sectionIndex"
            :array-index="index"
            :website-id="websiteId"
            :errors="errors"
            :clear-errors="clearErrors"
        />
    </div>
</template>
