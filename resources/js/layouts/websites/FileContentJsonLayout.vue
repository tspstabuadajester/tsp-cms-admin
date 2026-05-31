<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';

export type JsonEditorNavItem = {
    key: string;
    title: string;
};

interface Props {
    title: string;
    description?: string;
    navItems: JsonEditorNavItem[];
}

defineProps<Props>();

const activeSection = defineModel<string>('activeSection', { required: true });
</script>

<template>
    <div class="space-y-6">
        <HeadingSmall :title="title" :description="description" />

        <div class="flex flex-col space-y-8 md:space-y-0 lg:flex-row lg:space-x-12 lg:space-y-0">
            <aside class="w-full max-w-xl lg:w-48">
                <nav class="flex flex-col space-y-1">
                    <Button
                        v-for="item in navItems"
                        :key="item.key"
                        variant="ghost"
                        type="button"
                        :class="['w-full justify-start capitalize', { 'bg-muted': activeSection === item.key }]"
                        @click="activeSection = item.key"
                    >
                        {{ item.title }}
                    </Button>
                </nav>
            </aside>

            <Separator class="my-6 md:hidden" />

            <div class="min-w-0 flex-1 md:max-w-3xl">
                <section class="space-y-6">
                    <slot />
                </section>
            </div>
        </div>
    </div>
</template>
