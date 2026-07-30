<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { VueDraggable, type DraggableEvent } from 'vue-draggable-plus'
import { taskService } from '@/services/task.service'
import { projectService } from '@/services/project.service'
import type { Task } from '@/interfaces/task'
import type { Project } from '@/interfaces/project'

const route = useRoute()
const router = useRouter()

const project = ref<Project | null>(null)
const tasks = ref<Task[]>([])
const loading = ref(true)
const error = ref<string | null>(null)
const updatingStatus = ref<Set<number>>(new Set())
let draggedTaskId: number | null = null

const columns = [
    { key: 'todo', label: 'A fazer' },
    { key: 'in_progress', label: 'Em desenvolvimento' },
    { key: 'done', label: 'Completo' },
] as const

const todoList = ref<Task[]>([])
const inProgressList = ref<Task[]>([])
const doneList = ref<Task[]>([])

const columnMap: Record<string, typeof todoList> = {
    todo: todoList,
    in_progress: inProgressList,
    done: doneList,
}

function groupTasksByStatus() {
    todoList.value = tasks.value.filter((t) => t.status.value === 'todo')
    inProgressList.value = tasks.value.filter((t) => t.status.value === 'in_progress')
    doneList.value = tasks.value.filter((t) => t.status.value === 'done')
}

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
    return new Date(dateStr).toLocaleDateString('pt-BR')
}

async function loadProject() {
    const projectId = Number(route.params.id)
    if (!projectId) {
        router.push('/projects')
        return
    }

    loading.value = true
    error.value = null
    try {
        const [projectData, allTasks] = await Promise.all([
            projectService.getById(projectId),
            taskService.listAllByProject(projectId),
        ])
        project.value = projectData
        tasks.value = allTasks
        groupTasksByStatus()
    } catch (e) {
        error.value = 'Erro ao carregar dados do projeto'
    } finally {
        loading.value = false
    }
}

async function onTaskAdd(columnStatus: string, event: DraggableEvent) {
    const newIndex = event.newIndex as number
    const targetList = columnMap[columnStatus]
    if (!targetList) return
    const task = targetList.value[newIndex]

    if (!task || task.status.value === columnStatus) return

    updatingStatus.value.add(task.id)
    try {
        const updated = await taskService.update(task.id, {
            status: columnStatus as 'todo' | 'in_progress' | 'done',
        })
        const idx = tasks.value.findIndex((t) => t.id === task.id)
        if (idx !== -1) {
            tasks.value[idx] = updated
        }
    } catch (e) {
        await loadProject()
    } finally {
        updatingStatus.value.delete(task.id)
    }
}

function onStart(event: DraggableEvent) {
    const oldIndex = event.oldIndex as number
    for (const [_key, list] of Object.entries(columnMap)) {
        if (list.value[oldIndex]) {
            draggedTaskId = list.value[oldIndex].id
            break
        }
    }
}

onMounted(() => {
    loadProject()
})
</script>

<template>
    <div class="project-detail">
        <button @click="router.push('/projects')"
            class="mb-4 inline-flex items-center gap-1 text-sm text-gray-400 transition-colors hover:text-white">
            &larr; Voltar para projetos
        </button>

        <div v-if="loading" class="flex items-center justify-center py-12">
            <div class="h-8 w-8 animate-spin rounded-full border-4 border-blue-500 border-t-transparent"></div>
        </div>

        <div v-else-if="error" class="rounded-lg bg-red-900/50 p-4 text-red-400">
            {{ error }}
        </div>

        <template v-else-if="project">
            <h1 class="mb-2 text-2xl font-bold text-white">{{ project.name }}</h1>
            <p v-if="project.description" class="mb-6 text-gray-400">{{ project.description }}</p>

            <div class="flex gap-4 overflow-x-auto pb-4">
                <div v-for="column in columns" :key="column.key"
                    class="min-w-[280px] flex-1 rounded-lg border border-gray-700 bg-gray-800/50">
                    <div class="border-b border-gray-700 px-4 py-3">
                        <div class="flex items-center justify-between">
                            <h3 class="font-semibold text-white">{{ column.label }}</h3>
                            <span class="rounded-full bg-gray-700 px-2 py-0.5 text-xs text-gray-400">
                                {{ columnMap[column.key]?.value.length ?? 0 }}
                            </span>
                        </div>
                    </div>

                    <VueDraggable v-model="columnMap[column.key]!.value" group="kanban-tasks" :animation="200"
                        ghost-class="opacity-40" class="flex min-h-[200px] flex-col gap-2 p-3"
                        @add="onTaskAdd(column.key, $event)" @start="onStart($event)">
                        <div v-for="task in columnMap[column.key]!.value" :key="task.id" :data-task-id="task.id"
                            class="cursor-grab rounded-lg border border-gray-600 bg-gray-800 p-3 shadow-sm transition-shadow hover:shadow-md active:cursor-grabbing"
                            :class="{ 'opacity-50': updatingStatus.has(task.id) }">
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
                    </VueDraggable>
                </div>
            </div>
        </template>
    </div>
</template>