<script setup lang="ts">
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type WebsiteJsonField } from '@/types';

const props = defineProps<{
    sectionKey: string;
}>();

const fields = defineModel<WebsiteJsonField[]>('fields', { required: true });

const fieldId = (fieldPath: string): string =>
    `${props.sectionKey}-${fieldPath}`.replace(/[^a-zA-Z0-9_-]/g, '-');
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
            <Input :id="fieldId(field.path)" v-model="fields[index].value" type="text" />
        </div>
    </div>
</template>
