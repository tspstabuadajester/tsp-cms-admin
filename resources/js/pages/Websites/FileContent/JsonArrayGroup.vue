<script setup lang="ts">
import JsonFieldInput from '@/components/websites/JsonFieldInput.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { arrayFieldErrorKey } from '@/lib/json-form-errors';
import { type WebsiteJsonArrayGroup, type WebsiteJsonArrayItem } from '@/types';
import { Plus, Trash2 } from 'lucide-vue-next';

const props = defineProps<{
    sectionKey: string;
    sectionIndex: number;
    arrayIndex: number;
    websiteId: number;
    errors: Record<string, string>;
    clearErrors: (field?: string) => void;
}>();

const arrayGroup = defineModel<WebsiteJsonArrayGroup>('arrayGroup', { required: true });

const inputId = (itemIndex: number, fieldIndex: number, fieldKey: string): string =>
    `${props.sectionKey}-${arrayGroup.value.key}-${itemIndex}-${fieldIndex}-${fieldKey}`.replace(/[^a-zA-Z0-9_-]/g, '-');

let itemIdSequence = 0;

const generateItemId = (): string => {
    itemIdSequence += 1;

    return `item-${Date.now().toString(36)}-${itemIdSequence.toString(36)}`;
};

const createItem = (): WebsiteJsonArrayItem => ({
    fields: arrayGroup.value.template.map((field) => ({ ...field })),
    hidden: arrayGroup.value.template_hidden.map((field) => ({
        ...field,
        value: field.key === 'id' ? generateItemId() : '',
    })),
});

const addItem = () => {
    arrayGroup.value.items.push(createItem());
};

const removeItem = (index: number) => {
    arrayGroup.value.items.splice(index, 1);
};

const updateFieldValue = (itemIndex: number, fieldIndex: number, value: string) => {
    arrayGroup.value.items[itemIndex].fields[fieldIndex].value = value;

    props.clearErrors(arrayFieldErrorKey(props.sectionIndex, props.arrayIndex, itemIndex, fieldIndex));
};

const itemFieldError = (itemIndex: number, fieldIndex: number): string | undefined =>
    props.errors[arrayFieldErrorKey(props.sectionIndex, props.arrayIndex, itemIndex, fieldIndex)];
</script>

<template>
    <div class="space-y-4">
        <div class="flex items-center justify-between gap-4">
            <h3 class="text-sm font-semibold capitalize">{{ arrayGroup.key }}</h3>
            <Button type="button" variant="outline" size="sm" @click="addItem">
                <Plus class="size-4" />
                Add {{ arrayGroup.key }}
            </Button>
        </div>

        <div v-if="arrayGroup.items.length === 0" class="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground">
            No {{ arrayGroup.key }} yet. Click add to create one.
        </div>

        <Card v-for="(item, itemIndex) in arrayGroup.items" :key="`${arrayGroup.key}-${itemIndex}`">
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-4">
                <CardTitle class="text-sm font-medium">
                    {{ arrayGroup.key }} #{{ itemIndex + 1 }}
                </CardTitle>
                <Button type="button" variant="ghost" size="icon" class="text-destructive" @click="removeItem(itemIndex)">
                    <Trash2 class="size-4" />
                </Button>
            </CardHeader>
            <CardContent class="grid gap-4 sm:grid-cols-2">
                <JsonFieldInput
                    v-for="(field, fieldIndex) in item.fields"
                    :key="`${itemIndex}-${fieldIndex}-${field.key}`"
                    :field-key="field.key"
                    :model-value="field.value"
                    :input-id="inputId(itemIndex, fieldIndex, field.key)"
                    :website-id="websiteId"
                    :error="itemFieldError(itemIndex, fieldIndex)"
                    @update:model-value="updateFieldValue(itemIndex, fieldIndex, $event)"
                />
            </CardContent>
        </Card>
    </div>
</template>
