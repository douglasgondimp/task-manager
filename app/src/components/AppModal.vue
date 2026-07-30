<script setup lang="ts">
import { onMounted, onUnmounted } from 'vue'

const props = defineProps<{
    modelValue: boolean
    title: string
}>()

const emit = defineEmits<{
    'update:modelValue': [value: boolean]
}>()

function close() {
    emit('update:modelValue', false)
}

function onBackdropClick(event: MouseEvent) {
    if (event.target === event.currentTarget) {
        close()
    }
}

function onKeydown(event: KeyboardEvent) {
    if (event.key === 'Escape' && props.modelValue) {
        close()
    }
}

onMounted(() => {
    document.addEventListener('keydown', onKeydown)
})

onUnmounted(() => {
    document.removeEventListener('keydown', onKeydown)
})
</script>

<template>
    <Teleport to="body">
        <Transition name="modal">
            <div v-if="modelValue" class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
                @click="onBackdropClick">
                <div class="w-full max-w-lg rounded-lg border border-gray-700 bg-gray-900 shadow-xl" @click.stop>
                    <!-- Header -->
                    <div class="flex items-center justify-between border-b border-gray-700 px-6 py-4">
                        <h2 class="text-lg font-semibold text-white">{{ title }}</h2>
                        <button @click="close"
                            class="rounded-lg p-1 text-gray-400 transition-colors hover:bg-gray-800 hover:text-white">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="px-6 py-4">
                        <slot />
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.2s ease;
}

.modal-enter-active>div,
.modal-leave-active>div {
    transition: transform 0.2s ease;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-from>div,
.modal-leave-to>div {
    transform: scale(0.95);
}
</style>