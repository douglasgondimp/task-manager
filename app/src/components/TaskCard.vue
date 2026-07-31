<script setup lang="ts">
import { computed } from 'vue'
import type { Task } from '@/interfaces/task'

const props = defineProps<{
    task: Task
}>()

const emit = defineEmits<{
    click: [task: Task]
    delete: [task: Task]
}>()

function priorityClass(priority: string): string {
    const map: Record<string, string> = {
        low: 'border-green-500 text-green-400',
        medium: 'border-yellow-500 text-yellow-400',
        high: 'border-red-500 text-red-400',
    }
    return map[priority] || 'border-gray-500 text-gray-400'
}

function formatDate(dateStr: string | null): string {
    if (!dateStr) return ''
    const [year, month, day] = dateStr.split('-')
    return `${day}/${month}/${year}`
}
</script>

<template>
    <div
        class="group relative rounded-lg border border-gray-600 bg-gray-800 p-3 shadow-sm transition-shadow hover:shadow-md">
        <div @click="emit('click', task)" class="cursor-pointer active:cursor-grabbing">
            <div class="flex items-start justify-between gap-2">
                <h4 class="text-sm font-medium text-white">{{ task.title }}</h4>
                <span class="shrink-0 rounded border px-1.5 py-0.5 text-[10px] font-medium uppercase"
                    :class="priorityClass(task.priority.value)">
                    {{ task.priority.label }}
                </span>
            </div>

            <p v-if="task.description" class="mt-1 line-clamp-2 text-xs text-gray-500">
                {{ task.description }}
            </p>

            <div class="mt-2 flex items-center gap-3 text-[11px] text-gray-500">
                <div v-if="task.due_date" :class="{ 'text-red-400': task.is_overdue }">
                    {{ task.is_overdue ? '⚠ ' : '' }}{{ formatDate(task.due_date) }}
                </div>
            </div>
        </div>

        <div class="text-right">
            <button @click.stop="emit('delete', task)"
                class="rounded-lg p-1.5 text-gray-400 cursor-pointer transition-opacity bg-red-900/50 hover:text-red-400"
                title="Excluir tarefa">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </button>
        </div>
    </div>
</template>