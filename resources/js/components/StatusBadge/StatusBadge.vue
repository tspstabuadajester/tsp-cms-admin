<script setup lang="ts">
import { cn } from '@/lib/utils';
import { statusBadgeVariants, type StatusBadgeVariants } from '.';
import type { HTMLAttributes } from 'vue';

interface Props {
    variant?: StatusBadgeVariants['variant'];
    class?: HTMLAttributes['class'];
}

const props = defineProps<Props>();
</script>

<template>
    <span :class="cn(statusBadgeVariants({ variant: props.variant }), props.class)">
        <slot />
    </span>
</template>
