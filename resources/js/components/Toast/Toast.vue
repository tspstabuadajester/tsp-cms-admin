<script setup lang="ts">
import { cn } from '@/lib/utils';
import { toastContentVariants, toastVariants, type ToastVariants } from '.';
import ToastIcon from './ToastIcon.vue';
import type { HTMLAttributes } from 'vue';

interface Props {
    variant?: ToastVariants['variant'];
    class?: HTMLAttributes['class'];
}

const props = defineProps<Props>();
</script>

<template>
    <div role="alert" :class="cn(toastVariants({ variant: props.variant }), props.class)">
        <div :class="toastContentVariants({ variant: props.variant })">
            <ToastIcon :variant="props.variant" />
            <p>
                <slot />
            </p>
        </div>
    </div>
</template>
