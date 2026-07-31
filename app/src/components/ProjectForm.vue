<script setup lang="ts">
import { ref } from 'vue'

const props = withDefaults(
    defineProps<{
        initialData?: {
            name: string
            description?: string | null
            status?: 'active' | 'archived'
        }
        showStatus?: boolean,
        submitting?: boolean,
    }>(),
    {
        submitting: false,
    }
)


const emit = defineEmits<{
    submit: [data: { name: string; description?: string | null; status?: 'active' | 'archived' }]
    cancel: []
}>()

const name = ref(props.initialData?.name ?? '')
const description = ref(props.initialData?.description ?? '')
const status = ref<'active' | 'archived'>(props.initialData?.status ?? 'active')

const nameError = ref<string | null>(null)

function validate(): boolean {
    nameError.value = null

    if (!name.value.trim()) {
        nameError.value = 'O nome é obrigatório.'
        return false
    }

    if (name.value.trim().length > 50) {
        nameError.value = 'O nome excede o limite permitido. Máx: 50 caracteres.'
        return false
    }

    return true
}

function onSubmit() {
    if (!validate()) return

    const data: { name: string; description?: string | null; status?: 'active' | 'archived' } = {
        name: name.value.trim(),
        description: description.value.trim() || null,
    }

    if (props.showStatus) {
        data.status = status.value
    }

    emit('submit', data)
}

function onCancel() {
    emit('cancel')
}
</script>

<template>
    <form @submit.prevent="onSubmit" class="space-y-4">
        <!-- Name field -->
        <div>
            <label for="project-name" class="mb-1 block text-sm font-medium text-gray-300">
                Nome <span class="text-red-400">*</span>
            </label>
            <input id="project-name" v-model="name" type="text" maxlength="50" placeholder="Nome do projeto"
                class="w-full rounded-lg border bg-gray-800 px-3 py-2 text-sm text-white placeholder-gray-500 transition-colors focus:outline-none"
                :class="nameError ? 'border-red-500' : 'border-gray-700 focus:border-blue-500'"
                @input="nameError = null" />
            <p v-if="nameError" class="mt-1 text-xs text-red-400">{{ nameError }}</p>
        </div>

        <!-- Description field -->
        <div>
            <label for="project-description" class="mb-1 block text-sm font-medium text-gray-300">
                Descrição
            </label>
            <textarea id="project-description" v-model="description" rows="3"
                placeholder="Descrição do projeto (opcional)"
                class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-sm text-white placeholder-gray-500 transition-colors focus:border-blue-500 focus:outline-none"></textarea>
        </div>

        <!-- Status field (optional) -->
        <div v-if="showStatus">
            <label for="project-status" class="mb-1 block text-sm font-medium text-gray-300">
                Status
            </label>
            <select id="project-status" v-model="status"
                class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-sm text-white focus:border-blue-500 focus:outline-none">
                <option value="active">Ativo</option>
                <option value="archived">Arquivado</option>
            </select>
        </div>

        <!-- Actions -->
        <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="onCancel"
                class="rounded-lg border border-gray-700 px-4 py-2 text-sm font-medium text-gray-300 transition-colors hover:bg-gray-800">
                Cancelar
            </button>
            <button type="submit" :disabled="submitting"
                class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700 disabled:opacity-50">
                {{ submitting ? 'Salvando...' : 'Salvar' }}
            </button>
        </div>
    </form>
</template>
