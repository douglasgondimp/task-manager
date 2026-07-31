<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'

type AlertType = 'success' | 'error' | 'info' | 'warning'

const props = withDefaults(
    defineProps<{
        modelValue: boolean
        type: AlertType
        message: string
        duration?: number
    }>(),
    {
        duration: 5000,
    },
)

const emit = defineEmits<{
    'update:modelValue': [value: boolean]
}>()

const timeoutId = ref<ReturnType<typeof setTimeout> | null>(null)

const typeConfig: Record<
    AlertType,
    { bg: string; border: string; icon: string; textColor: string }
> = {
    success: {
        bg: 'bg-green-900/50',
        border: 'border-green-500/50',
        icon: 'text-green-400',
        textColor: 'text-green-300',
    },
    error: {
        bg: 'bg-red-900/50',
        border: 'border-red-500/50',
        icon: 'text-red-400',
        textColor: 'text-red-300',
    },
    info: {
        bg: 'bg-blue-900/50',
        border: 'border-blue-500/50',
        icon: 'text-blue-400',
        textColor: 'text-blue-300',
    },
    warning: {
        bg: 'bg-amber-900/50',
        border: 'border-amber-500/50',
        icon: 'text-amber-400',
        textColor: 'text-amber-300',
    },
}

const config = computed(() => typeConfig[props.type])

function close() {
    if (timeoutId.value) {
        clearTimeout(timeoutId.value)
        timeoutId.value = null
    }
    emit('update:modelValue', false)
}

function onKeydown(event: KeyboardEvent) {
    if (event.key === 'Escape' && props.modelValue) {
        close()
    }
}

watch(
    () => props.modelValue,
    (newVal) => {
        if (newVal && props.duration) {
            timeoutId.value = setTimeout(() => {
                close()
            }, props.duration)
        } else if (!newVal && timeoutId.value) {
            clearTimeout(timeoutId.value)
            timeoutId.value = null
        }
    },
)

onMounted(() => {
    document.addEventListener('keydown', onKeydown)
})

onUnmounted(() => {
    document.removeEventListener('keydown', onKeydown)
})
</script>

<template>
    <Teleport to="body">
        <Transition name="alert">
            <div v-if="modelValue" class="absolute top-[20px] right-[15px] z-150" @click="close">
                <div :class="[config.bg, config.border, config.textColor]" class="relative rounded-lg border p-4"
                    @click.stop>
                    <div class="flex items-start gap-3">
                        <div :class="config.icon">
                            <svg v-if="type === 'success'" class="h-5 w-5" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12.75L11.25 15 15 9.75" />
                            </svg>
                            <svg v-else-if="type === 'error'" class="h-5 w-5" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                            <svg v-else-if="type === 'info'" class="h-5 w-5" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10zm1-5h-2v-6h2v6zm0-8h-2V7h2v2z" />
                            </svg>
                            <svg v-else-if="type === 'warning'" class="h-5 w-5" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v4m0 4h.01m-6.907 1.818A10.999 10.999 0 0112 15c2.938 0 5.676 1.117 7.757 3M12 21a9 9 0 110-18 9 9 0 010 18z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm">{{ message }}</p>
                        </div>
                        <button @click="close"
                            class="rounded-lg p-1 text-gray-400 transition-colors hover:bg-gray-800 hover:text-white">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.alert-enter-active,
.alert-leave-active {
    transition: opacity 0.2s ease;
}

.alert-enter-from,
.alert-leave-to {
    opacity: 0;
}
</style>
