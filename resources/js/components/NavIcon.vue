<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';

interface Props {
    src: string;
    alt?: string;
    class?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <img :src="src" :alt="alt ?? ''" :class="cn('size-4 shrink-0', $props.class)" />
</template>
