<script setup lang="ts">
import { computed, ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { VueDraggable, type DraggableEvent } from 'vue-draggable-plus'
import { useTask } from '@/composables/useTask'
import { useProjects } from '@/composables/useProject'
import AppModal from '@/components/AppModal.vue'
import ProjectForm from '@/components/ProjectForm.vue'
import TaskForm from '@/components/TaskForm.vue'
import TaskCard from '@/components/TaskCard.vue'
import type { Task, TaskStatus } from '@/interfaces/task'

const route = useRoute()
const router = useRouter()

const {
    project,
    loading: loadingProject,
    error: projectError,
    fetchProject,
    updateProject,
} = useProjects()

const {
    tasksByStatus,
    pagination,
    loading: loadingTasks,
    loadingMore,
    errors: taskErrors,
    updatingTaskIds,
    fetchTasks,
    loadMoreTasks,
    updateTaskStatus,
    createTask,
    updateTask,
} = useTask()

const loading = computed(() => loadingProject.value || loadingTasks.value)
const error = computed(() => projectError.value)
const showCreateModal = ref(false)
const createError = ref<string | null>(null)
const showEditModal = ref(false)
const editError = ref<string | null>(null)
const showTaskEditModal = ref(false)
const taskEditError = ref<string | null>(null)
const selectedTask = ref<Task | null>(null)
const updateError = ref<string | null>(null)

const columns = [
    { key: 'todo', label: 'A fazer' },
    { key: 'in_progress', label: 'Em desenvolvimento' },
    { key: 'done', label: 'Completo' },
] as const

async function loadProject(): Promise<void> {
    const projectId = Number(route.params.id)

    if (!projectId) {
        await router.push('/projects')
        return
    }

    await Promise.all([
        fetchProject(projectId),
        fetchTasks(projectId),
    ])
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
    const [date] = dateStr.split('T')
    const [year, month, day] = date!.split('-')
    return `${day}/${month}/${year}`
}

async function onTaskAdd(
    columnStatus: TaskStatus,
    event: DraggableEvent,
): Promise<void> {
    const newIndex = event.newIndex
    if (newIndex === undefined) return

    const task = tasksByStatus.value[columnStatus][newIndex]
    if (!task || task.status.value === columnStatus) return

    const updatedTask = await updateTaskStatus(task.id, columnStatus)

    if (!updatedTask) {
        await fetchTasks(Number(route.params.id))
    }
}

async function onColumnScroll(
    status: TaskStatus,
    event: Event,
): Promise<void> {
    const element = event.currentTarget as HTMLElement
    const distanceFromBottom =
        element.scrollHeight - element.scrollTop - element.clientHeight

    if (distanceFromBottom > 200) return

    await loadMoreTasks(Number(route.params.id), status)
}

async function onCreateTask(data: {
    title: string
    description?: string | null
    priority: 'low' | 'medium' | 'high'
    due_date?: string | null
}) {
    createError.value = null
    const projectId = Number(route.params.id)
    const task = await createTask(projectId, {
        title: data.title,
        description: data.description,
        priority: data.priority,
        due_date: data.due_date,
    })
    if (task) {
        showCreateModal.value = false
    } else {
        createError.value = 'Erro ao criar tarefa. Tente novamente.'
    }
}

function getStatusColor(status: string): string {
    return status === 'active' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
}

function getStatusLabel(status: string): string {
    return status === 'active' ? 'Ativo' : 'Arquivado'
}

// Edit modal
async function onEditProject(data: { name: string; description?: string | null; status?: 'active' | 'archived' }) {
    editError.value = null
    if (!project.value) return
    const success = await updateProject(project.value.id, data)
    if (!success) {
        editError.value = 'Erro ao atualizar projeto'
    } else {
        showEditModal.value = false
    }
}

function onTaskClick(task: Task) {
    selectedTask.value = task
    showTaskEditModal.value = true
}

async function onUpdateTask(data: {
    title: string
    description?: string | null
    priority: 'low' | 'medium' | 'high'
    due_date?: string | null
}) {
    taskEditError.value = null
    if (!selectedTask.value) return
    const success = await updateTask(selectedTask.value.id, {
        title: data.title,
        description: data.description,
        priority: data.priority,
        due_date: data.due_date,
    })
    if (!success) {
        taskEditError.value = 'Erro ao atualizar tarefa'
    } else {
        showTaskEditModal.value = false
    }
}

onMounted(() => {
    void loadProject()
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
            <div class="mb-6 flex gap-2 items-center justify-between">
                <div>
                    <div class="mt-2 flex items-center gap-2">
                        <button
                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium transition-colors hover:opacity-80"
                            :class="getStatusColor(project.status.value)"
                            :title="project.status.value === 'active' ? 'Clique para arquivar' : 'Clique para ativar'">
                            {{ getStatusLabel(project.status.value) }}
                        </button>
                    </div>

                    <h1 class="text-2xl font-bold text-white">{{ project.name }}</h1>
                    <p v-if="project.description" class="mt-1 text-gray-400">{{ project.description }}</p>
                </div>
                <div class="text-right">
                    <button @click="showEditModal = true"
                        class="inline-flex items-center gap-1.5 mb-3 rounded-lg border border-gray-700 bg-gray-800 px-4 py-2 text-sm font-medium text-gray-300 transition-colors hover:bg-gray-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Editar Projeto
                    </button>
                    <button @click="showCreateModal = true"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Nova Tarefa
                    </button>
                </div>
            </div>

            <!-- Update error -->
            <div v-if="updateError" class="mb-4 rounded-lg bg-red-900/50 p-3 text-sm text-red-400">
                {{ updateError }}
            </div>

            <div class="flex gap-4 overflow-x-auto pb-4">
                <div v-for="column in columns" :key="column.key"
                    class="min-w-[280px] flex-1 rounded-lg border border-gray-700 bg-gray-800/50">
                    <div class="border-b border-gray-700 px-4 py-3">
                        <div class="flex items-center justify-between">
                            <h3 class="font-semibold text-white">{{ column.label }}</h3>
                            <!-- <span class="rounded-full bg-gray-700 px-2 py-0.5 text-xs text-gray-400">
                                {{ tasksByStatus[column.key].length }}
                            </span> -->
                        </div>
                    </div>

                    <div class="max-h-[65vh] overflow-y-auto" @scroll.passive="onColumnScroll(column.key, $event)">
                        <VueDraggable v-model="tasksByStatus[column.key]" group="kanban-tasks" :animation="200"
                            ghost-class="opacity-40" class="flex min-h-[200px] flex-col gap-2 p-3"
                            @add="onTaskAdd(column.key, $event)">
                            <TaskCard v-for="task in tasksByStatus[column.key]" :key="task.id" :task="task"
                                :class="{ 'opacity-50': updatingTaskIds.has(task.id) }" @click="onTaskClick" />
                        </VueDraggable>

                        <div v-if="loadingMore[column.key]" class="p-3 text-center text-sm text-gray-400">
                            Carregando...
                        </div>

                        <button v-if="taskErrors[column.key]" class="w-full p-3 text-sm text-red-400"
                            @click="loadMoreTasks(Number(route.params.id), column.key)">
                            {{ taskErrors[column.key] }}. Tentar novamente.
                        </button>

                        <p v-else-if="!pagination[column.key].hasMore && tasksByStatus[column.key].length > 0"
                            class="p-3 text-center text-xs text-gray-500">
                            Todas as tarefas foram carregadas.
                        </p>
                    </div>
                </div>
            </div>
        </template>

        <!-- Create task modal -->
        <AppModal v-model="showCreateModal" title="Nova Tarefa">
            <p v-if="createError" class="mb-3 text-sm text-red-400">{{ createError }}</p>
            <TaskForm @submit="onCreateTask" @cancel="showCreateModal = false" />
        </AppModal>

        <!-- Edit project modal -->
        <AppModal v-model="showEditModal" title="Editar Projeto">
            <p v-if="editError" class="mb-3 text-sm text-red-400">{{ editError }}</p>
            <ProjectForm v-if="project" :initial-data="{
                name: project.name,
                description: project.description,
                status: project.status.value as 'active' | 'archived'
            }" :show-status="true" @submit="onEditProject" @cancel="showEditModal = false" />
        </AppModal>

        <!-- Edit task modal -->
        <AppModal v-model="showTaskEditModal" title="Editar Tarefa">
            <p v-if="taskEditError" class="mb-3 text-sm text-red-400">{{ taskEditError }}</p>
            <TaskForm v-if="selectedTask" :initial-data="{
                title: selectedTask.title,
                description: selectedTask.description,
                priority: selectedTask.priority.value,
                due_date: selectedTask.due_date,
            }" @submit="onUpdateTask" @cancel="showTaskEditModal = false" />
        </AppModal>
    </div>
</template>