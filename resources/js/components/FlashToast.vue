<script setup lang="ts">
import { Toast } from '@/components/Toast';
import type { FlashToast } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { onUnmounted, ref, watch } from 'vue';

const DISMISS_MS = 5000;

const page = usePage();
const visible = ref(false);
const currentToast = ref<FlashToast | null>(null);

let dismissTimer: ReturnType<typeof setTimeout> | undefined;

const clearDismissTimer = (): void => {
    if (dismissTimer !== undefined) {
        clearTimeout(dismissTimer);
        dismissTimer = undefined;
    }
};

const showToast = (toast: FlashToast): void => {
    clearDismissTimer();
    currentToast.value = toast;
    visible.value = true;

    dismissTimer = setTimeout(() => {
        visible.value = false;
    }, DISMISS_MS);
};

watch(
    () => (page.props as { flash?: { toast?: FlashToast | null } }).flash?.toast,
    (toast) => {
        if (toast) {
            showToast(toast);
        }
    },
    { immediate: true },
);

onUnmounted(() => {
    clearDismissTimer();
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0 translate-x-2"
            enter-to-class="opacity-100 translate-x-0"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100 translate-x-0"
            leave-to-class="opacity-0 translate-x-2"
        >
            <div v-if="visible && currentToast" class="pointer-events-none fixed top-4 right-4 z-[100]">
                <Toast :variant="currentToast.variant" class="pointer-events-auto shadow-lg">
                    {{ currentToast.message }}
                </Toast>
            </div>
        </Transition>
    </Teleport>
</template>
