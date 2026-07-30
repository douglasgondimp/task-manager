<script setup lang="ts">
import { ref } from 'vue'

const props = defineProps<{
    initialData?: {
        title: string
        description?: string | null
        priority?: 'low' | 'medium' | 'high'
        due_date?: string | null
    }
}>()

const emit = defineEmits<{
    submit: [data: {
        title: string
        description?: string | null
        priority: 'low' | 'medium' | 'high'
        due_date?: string | null
    }]
    cancel: []
}>()

const title = ref(props.initialData?.title ?? '')
const description = ref(props.initialData?.description ?? '')
const priority = ref<'low' | 'medium' | 'high'>(props.initialData?.priority ?? 'medium')
const dueDate = ref(props.initialData?.due_date ?? '')
const submitted = ref(false)

const titleError = ref<string | null>(null)

const priorityOptions = [
    { value: 'low', label: 'Baixo' },
    { value: 'medium', label: 'Médio' },
    { value: 'high', label: 'Alto' },
] as const

function validate(): boolean {
    titleError.value = null

    if (!title.value.trim()) {
        titleError.value = 'O título é obrigatório.'
        return false
    }

    return true
}

function onSubmit() {
    submitted.value = true

    if (!validate()) return

    emit('submit', {
        title: title.value.trim(),
        description: description.value.trim() || null,
        priority: priority.value,
        due_date: dueDate.value || null,
    })
}

function onCancel() {
    emit('cancel')
}
</script>

<template>
    <form @submit.prevent="onSubmit" class="space-y-4">
        <!-- Title -->
        <div>
            <label for="task-title" class="mb-1 block text-sm font-medium text-gray-300">
                Título <span class="text-red-400">*</span>
            </label>
            <input id="task-title" v-model="title" type="text" placeholder="Título da tarefa"
                class="w-full rounded-lg border bg-gray-800 px-3 py-2 text-sm text-white placeholder-gray-500 transition-colors focus:outline-none"
                :class="titleError ? 'border-red-500' : 'border-gray-700 focus:border-blue-500'"
                @input="titleError = null" />
            <p v-if="titleError" class="mt-1 text-xs text-red-400">{{ titleError }}</p>
        </div>

        <!-- Description -->
        <div>
            <label for="task-description" class="mb-1 block text-sm font-medium text-gray-300">
                Descrição
            </label>
            <textarea id="task-description" v-model="description" rows="3" placeholder="Descrição da tarefa (opcional)"
                class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-sm text-white placeholder-gray-500 transition-colors focus:border-blue-500 focus:outline-none"></textarea>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <!-- Priority -->
            <div>
                <label for="task-priority" class="mb-1 block text-sm font-medium text-gray-300">
                    Prioridade
                </label>
                <select id="task-priority" v-model="priority"
                    class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-sm text-white focus:border-blue-500 focus:outline-none">
                    <option v-for="opt in priorityOptions" :key="opt.value" :value="opt.value">
                        {{ opt.label }}
                    </option>
                </select>
            </div>

            <!-- Due date -->
            <div>
                <label for="task-due-date" class="mb-1 block text-sm font-medium text-gray-300">
                    Data de vencimento
                </label>
                <input id="task-due-date" v-model="dueDate" type="date"
                    class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-sm text-white focus:border-blue-500 focus:outline-none" />
            </div>
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="onCancel"
                class="rounded-lg border border-gray-700 px-4 py-2 text-sm font-medium text-gray-300 transition-colors hover:bg-gray-800">
                Cancelar
            </button>
            <button type="submit" :disabled="submitted"
                class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700 disabled:opacity-50">
                {{ submitted ? 'Salvando...' : 'Salvar' }}
            </button>
        </div>
    </form>
</template>