<script setup lang="ts">
defineProps<{
    show: boolean;
}>();
</script>

<template>
    <Teleport to="body">
        <Transition name="loading-overlay">
            <div
                v-if="show"
                class="fixed inset-0 z-[100] flex items-center justify-center bg-black/50"
                aria-busy="true"
                aria-live="polite"
            >
                <div class="loader" aria-hidden="true">
                    <div class="dot dot-1"></div>
                    <div class="dot dot-2"></div>
                    <div class="dot dot-3"></div>
                    <div class="dot dot-4"></div>
                    <div class="dot dot-5"></div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.loader {
    display: flex;
    justify-content: center;
    align-items: center;
}

.dot {
    display: inline-block;
    width: 10px;
    height: 10px;
    margin-right: 6px;
    border-radius: 50%;
    animation: dot-pulse2 1.5s ease-in-out infinite;
}

.dot-1 {
    background-color: #4285f4;
    animation-delay: 0s;
}

.dot-2 {
    background-color: #34a853;
    animation-delay: 0.3s;
}

.dot-3 {
    background-color: #fbbc05;
    animation-delay: 0.6s;
}

.dot-4 {
    background-color: #ea4335;
    animation-delay: 0.9s;
}

.dot-5 {
    background-color: #4285f4;
    animation-delay: 1.2s;
    margin-right: 0;
}

@keyframes dot-pulse2 {
    0% {
        transform: scale(0.5);
        opacity: 0.5;
    }

    50% {
        transform: scale(1);
        opacity: 1;
    }

    100% {
        transform: scale(0.5);
        opacity: 0.5;
    }
}

.loading-overlay-enter-active,
.loading-overlay-leave-active {
    transition: opacity 0.2s ease;
}

.loading-overlay-enter-from,
.loading-overlay-leave-to {
    opacity: 0;
}
</style>
