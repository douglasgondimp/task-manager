<script setup lang="ts">
import { computed } from 'vue'
import type { Task } from '@/interfaces/task'

const props = defineProps<{
    task: Task
}>()

const emit = defineEmits<{
    click: [task: Task]
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
    <div @click="emit('click', task)"
        class="cursor-pointer rounded-lg border border-gray-600 bg-gray-800 p-3 shadow-sm transition-shadow hover:shadow-md active:cursor-grabbing"
        :class="{ 'opacity-50': task }">
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
</template>