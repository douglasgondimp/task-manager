<script setup lang="ts">
import { computed, ref, onMounted, watch, onBeforeUnmount } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { VueDraggable, type DraggableEvent } from 'vue-draggable-plus'
import { useTask } from '@/composables/useTask'
import { useProjects } from '@/composables/useProject'
import { useAlert } from '@/composables/useAlert'
import AppModal from '@/components/AppModal.vue'
import ProjectForm from '@/components/ProjectForm.vue'
import TaskForm from '@/components/TaskForm.vue'
import TaskCard from '@/components/TaskCard.vue'
import type { Task, TaskStatus, TaskListParams } from '@/interfaces/task'

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
    deleteTask,
    setStatusFilter,
    getFilteredTasksByStatus,
} = useTask()

const loadingP = computed(() => loadingProject.value)
const loadingT = computed(() => loadingTasks.value)
const error = computed(() => projectError.value)
const showCreateModal = ref(false)
const showEditModal = ref(false)
const showTaskEditModal = ref(false)
const selectedTask = ref<Task | null>(null)
const showDeleteModal = ref(false)
const taskToDelete = ref<Task | null>(null)
const creatingTask = ref(false)
const updatingTask = ref(false)
const updatingProject = ref(false)

const alert = useAlert()

// Watch composable errors and show as alerts
watch(error, (newError) => {
    if (newError) {
        alert.showAlert('error', newError)
    }
})

// Filter state
const showFilters = ref(false)
const filterSearch = ref('')
const filterStatus = ref<'todo' | 'in_progress' | 'done' | ''>('')
const filterPriority = ref<'low' | 'medium' | 'high' | ''>('')
const filterOverdue = ref(false)
const filterDateFrom = ref('')
const filterDateTo = ref('')

let debounceTimer: ReturnType<typeof setTimeout>

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

async function onTaskAdd(
    columnStatus: TaskStatus,
    event: DraggableEvent,
): Promise<void> {
    const newIndex = event.newIndex
    if (newIndex === undefined) return

    const task = tasksByStatus.value[columnStatus][newIndex]
    if (!task || task.status.value === columnStatus) return

    const updatedTask = await updateTaskStatus(task.id, columnStatus)

    if (updatedTask) {
        alert.showAlert('success', 'Tarefa atualizada com sucesso.')
        return
    }

    await fetchTasks(Number(route.params.id))
    alert.showAlert('error', 'Ocorreu um erro ao atualizar a tarefa')

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
    creatingTask.value = true

    try {
        const projectId = Number(route.params.id)
        const task = await createTask(projectId, {
            title: data.title,
            description: data.description,
            priority: data.priority,
            due_date: data.due_date,
        })
        if (task) {
            showCreateModal.value = false
            alert.showAlert('success', 'Tarefa criada com sucesso.')
        } else {
            alert.showAlert('error', 'Erro ao criar tarefa. Tente novamente.')
        }
    } catch {
        alert.showAlert('error', 'Erro ao criar tarefa. Tente novamente.')
    } finally {
        creatingTask.value = false
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
    if (!project.value) return

    updatingProject.value = true

    try {
        const success = await updateProject(project.value.id, data)
        if (success) {
            showEditModal.value = false
            alert.showAlert('success', 'Projeto atualizado com sucesso.')
            return
        }

        alert.showAlert('error', 'Erro ao atualizar projeto')
    } catch {
        alert.showAlert('error', 'Erro ao atualizar projeto')
    } finally {
        updatingProject.value = false
    }
}

function onTaskClick(task: Task) {
    selectedTask.value = task
    showTaskEditModal.value = true
}

function onTaskDelete(task: Task) {
    taskToDelete.value = task
    showDeleteModal.value = true
}

async function confirmDeleteTask() {
    if (!taskToDelete.value) return

    const success = await deleteTask(taskToDelete.value.id)

    if (success) {
        showDeleteModal.value = false
        taskToDelete.value = null
        alert.showAlert('success', 'Tarefa excluída com sucesso.')
    } else {
        alert.showAlert('error', 'Erro ao excluir tarefa')
    }
}

function cancelDeleteTask() {
    showDeleteModal.value = false
    taskToDelete.value = null
}

async function onUpdateTask(data: {
    title: string
    description?: string | null
    priority: 'low' | 'medium' | 'high'
    due_date?: string | null
}) {
    if (!selectedTask.value) return

    updatingTask.value = true

    try {
        const success = await updateTask(selectedTask.value.id, {
            title: data.title,
            description: data.description,
            priority: data.priority,
            due_date: data.due_date,
        })

        if (success) {
            showTaskEditModal.value = false
            alert.showAlert('success', 'Tarefa atualizada com sucesso.')
            return
        }

        alert.showAlert('error', 'Erro ao atualizar tarefa')
    } catch {
        alert.showAlert('error', 'Erro ao atualizar tarefa')
    } finally {
        updatingTask.value = false
    }
}

// Filters
function applyFilters() {
    const filters: TaskListParams = {
        search: filterSearch.value || undefined,
        priority: filterPriority.value || undefined,
        is_overdue: filterOverdue.value || undefined,
        created_at: (filterDateFrom.value && filterDateTo.value) ? [filterDateFrom.value, filterDateTo.value] : undefined,
    }

    // Set status filter for client-side filtering
    setStatusFilter(filterStatus.value || null)

    loadProjectTasks(filters)
}

function resetFilters() {
    filterSearch.value = ''
    filterStatus.value = ''
    filterPriority.value = ''
    filterOverdue.value = false
    filterDateFrom.value = ''
    filterDateTo.value = ''

    // Clear status filter
    setStatusFilter(null)

    loadProjectTasks()
}

async function loadProjectTasks(filters?: TaskListParams) {
    if (!project.value) return
    await fetchTasks(project.value.id, filters)
}

onMounted(() => {
    void loadProject()
})

watch(
    [
        () => filterSearch.value,
        () => filterDateFrom.value,
        () => filterDateTo.value
    ],
    () => {
        clearTimeout(debounceTimer)

        debounceTimer = setTimeout(() => {
            void applyFilters()
        }, 500)
    }
)
watch(
    [
        () => filterStatus.value,
        () => filterPriority.value,
        () => filterOverdue.value,
    ],
    () => {
        void applyFilters()
    }
)

onBeforeUnmount(() => {
    clearTimeout(debounceTimer)
})
</script>

<template>
    <div class="project-detail">
        <button @click="router.push('/projects')"
            class="mb-4 inline-flex items-center gap-1 text-sm text-gray-400 transition-colors hover:text-white">
            &larr; Voltar para projetos
        </button>

        <div v-if="loadingP" class="flex items-center justify-center py-12">
            <div class="h-8 w-8 animate-spin rounded-full border-4 border-blue-500 border-t-transparent"></div>
        </div>

        <template v-if="project">
            <div class="mb-6">
                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div class="flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <button
                                class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium transition-colors hover:opacity-80"
                                :class="getStatusColor(project.status.value)"
                                :title="project.status.value === 'active' ? 'Clique para arquivar' : 'Clique para ativar'">
                                {{ getStatusLabel(project.status.value) }}
                            </button>
                        </div>

                        <h1 class="mt-2 text-2xl font-bold text-white">{{ project.name }}</h1>
                        <p v-if="project.description" class="mt-1 text-gray-400">{{ project.description }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button @click="showFilters = !showFilters"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-gray-700 bg-gray-800 px-4 py-2 text-sm font-medium text-gray-300 transition-colors hover:bg-gray-700">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                            Filtros
                        </button>
                        <button @click="showEditModal = true"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-gray-700 bg-gray-800 px-4 py-2 text-sm font-medium text-gray-300 transition-colors hover:bg-gray-700">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            Editar
                        </button>
                        <button @click="showCreateModal = true"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4v16m8-8H4" />
                            </svg>
                            Nova Tarefa
                        </button>
                    </div>
                </div>
            </div>

            <!-- Filters panel -->
            <div v-if="showFilters" class="mb-6 rounded-lg border border-gray-700 bg-gray-800/50 p-4">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <label for="filter-search" class="mb-1 block text-sm font-medium text-gray-300">Buscar</label>
                        <input id="filter-search" v-model="filterSearch" type="text" placeholder="Buscar tarefa..."
                            class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-sm text-white placeholder-gray-500 focus:border-blue-500 focus:outline-none" />
                    </div>
                    <div>
                        <label for="filter-status" class="mb-1 block text-sm font-medium text-gray-300">Status</label>
                        <select id="filter-status" v-model="filterStatus"
                            class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-sm text-white focus:border-blue-500 focus:outline-none">
                            <option value="">Todos</option>
                            <option value="todo">A fazer</option>
                            <option value="in_progress">Em desenvolvimento</option>
                            <option value="done">Completo</option>
                        </select>
                    </div>
                    <div>
                        <label for="filter-priority"
                            class="mb-1 block text-sm font-medium text-gray-300">Prioridade</label>
                        <select id="filter-priority" v-model="filterPriority"
                            class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-sm text-white focus:border-blue-500 focus:outline-none">
                            <option value="">Todas</option>
                            <option value="low">Baixa</option>
                            <option value="medium">Média</option>
                            <option value="high">Alta</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-2">
                        <input id="filter-overdue" v-model="filterOverdue" type="checkbox"
                            class="h-4 w-4 rounded border-gray-700 bg-gray-800 text-blue-600 focus:ring-blue-500" />
                        <label for="filter-overdue" class="text-sm text-gray-300">Apenas atrasadas</label>
                    </div>
                    <div>
                        <label for="filter-date-from" class="mb-1 block text-sm font-medium text-gray-300">Data
                            de</label>
                        <input id="filter-date-from" v-model="filterDateFrom" type="date"
                            class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-sm text-white focus:border-blue-500 focus:outline-none" />
                    </div>
                    <div>
                        <label for="filter-date-to" class="mb-1 block text-sm font-medium text-gray-300">Data
                            até</label>
                        <input id="filter-date-to" v-model="filterDateTo" type="date"
                            class="w-full rounded-lg border border-gray-700 bg-gray-800 px-3 py-2 text-sm text-white focus:border-blue-500 focus:outline-none" />
                    </div>
                </div>
                <div class="mt-4 flex justify-end gap-2">
                    <button @click="resetFilters"
                        class="rounded-lg border border-gray-700 px-4 py-2 text-sm font-medium text-gray-300 transition-colors hover:bg-gray-700">
                        Limpar
                    </button>
                    <button @click="applyFilters"
                        class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700">
                        Aplicar Filtros
                    </button>
                </div>
            </div>

            <div class="flex gap-4 overflow-x-auto pb-4">
                <div v-for="column in columns" :key="column.key"
                    class="min-w-[280px] flex-1 rounded-lg border border-gray-700 bg-gray-800/50">
                    <div class="border-b border-gray-700 px-4 py-3">
                        <div class="flex items-center justify-between">
                            <h3 class="font-semibold text-white">{{ column.label }}</h3>
                        </div>
                    </div>

                    <div class="max-h-[50vh] lg:max-h-[65vh] overflow-y-auto"
                        @scroll.passive="onColumnScroll(column.key, $event)">
                        <div v-if="loadingT" class="p-3 text-center text-sm text-gray-400">
                            Carregando...
                        </div>

                        <div v-else>
                            <VueDraggable v-model="tasksByStatus[column.key]" group="kanban-tasks" :animation="200"
                                ghost-class="opacity-40" class="flex min-h-[200px] flex-col gap-2 p-3"
                                @add="onTaskAdd(column.key, $event)">
                                <TaskCard v-for="task in getFilteredTasksByStatus(column.key)" :key="task.id"
                                    :task="task" :class="{ 'opacity-50': updatingTaskIds.has(task.id) }"
                                    @click="onTaskClick" @delete="onTaskDelete" />
                            </VueDraggable>

                            <div v-if="loadingMore[column.key]" class="p-3 text-center text-sm text-gray-400">
                                Carregando...
                            </div>

                            <button v-if="taskErrors[column.key]" class="w-full p-3 text-sm text-red-400"
                                @click="loadMoreTasks(Number(route.params.id), column.key)">
                                {{ taskErrors[column.key] }}. Tentar novamente.
                            </button>

                            <p v-else-if="!pagination[column.key].hasMore && getFilteredTasksByStatus(column.key).length > 0"
                                class="p-3 text-center text-xs text-gray-500">
                                Todas as tarefas foram carregadas.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        <!-- Create task modal -->
        <AppModal v-model="showCreateModal" title="Nova Tarefa">
            <TaskForm :submiting="creatingTask" @submit="onCreateTask" @cancel="showCreateModal = false" />
        </AppModal>

        <!-- Edit project modal -->
        <AppModal v-model="showEditModal" title="Editar Projeto">
            <ProjectForm v-if="project" :initial-data="{
                name: project.name,
                description: project.description,
                status: project.status.value as 'active' | 'archived'
            }" :submitting="updatingProject" :show-status="true" @submit="onEditProject"
                @cancel="showEditModal = false" />
        </AppModal>

        <!-- Edit task modal -->
        <AppModal v-model="showTaskEditModal" title="Editar Tarefa">
            <TaskForm v-if="selectedTask" :initial-data="{
                title: selectedTask.title,
                description: selectedTask.description,
                priority: selectedTask.priority.value,
                due_date: selectedTask.due_date,
            }" :submitting="updatingTask" @submit="onUpdateTask" @cancel="showTaskEditModal = false" />
        </AppModal>

        <!-- Delete task confirmation modal -->
        <AppModal v-model="showDeleteModal" title="Confirmar Exclusão">
            <div class="space-y-4">
                <p class="text-sm text-gray-300">
                    Tem certeza que deseja excluir a tarefa <strong class="text-white">{{ taskToDelete?.title
                    }}</strong>?
                </p>
                <div class="flex justify-end gap-3">
                    <button @click="cancelDeleteTask"
                        class="rounded-lg border border-gray-700 px-4 py-2 text-sm font-medium text-gray-300 transition-colors hover:bg-gray-700">
                        Não
                    </button>
                    <button @click="confirmDeleteTask"
                        class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-red-700">
                        Sim
                    </button>
                </div>
            </div>
        </AppModal>
    </div>
</template>
