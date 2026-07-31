import { reactive, ref } from 'vue'
import { storeToRefs } from 'pinia'
import { taskService } from '@/services/task.service'
import { useTaskStore } from '@/stores/tasks'
import type {
    Task,
    TaskCreateData,
    TaskListParams,
    TaskStatus,
    TaskUpdateData,
} from '@/interfaces/task'

const taskStatuses: TaskStatus[] = ['todo', 'in_progress', 'done']

export function useTask() {
    const taskStore = useTaskStore()
    const { tasksByStatus, pagination, updatingTaskIds, statusFilter } = storeToRefs(taskStore)

    const loading = ref(false)
    const loadingMore = reactive<Record<TaskStatus, boolean>>({
        todo: false,
        in_progress: false,
        done: false,
    })
    const errors = reactive<Record<TaskStatus, string | null>>({
        todo: null,
        in_progress: null,
        done: null,
    })

    async function fetchTaskColumn(
        projectId: number,
        status: TaskStatus,
        filters?: TaskListParams,
    ): Promise<void> {
        errors[status] = null

        try {
            // Remove status filter from filters, but keep the column's status for the API call
            const { status: _, ...filtersWithoutStatus } = filters || {}
            const response = await taskService.listByProject(projectId, undefined, { ...filtersWithoutStatus, status })

            taskStore.setTasks(status, response.data)
            taskStore.setPagination(status, response.meta.next_cursor)
        } catch {
            errors[status] = 'Erro ao carregar tarefas'
        }
    }

    async function fetchTasks(projectId: number, filters?: TaskListParams): Promise<void> {
        loading.value = true
        taskStore.resetTasks()

        try {
            const filteredStatus = statusFilter.value

            if (filteredStatus) {
                await fetchTaskColumn(projectId, filteredStatus, filters)
            } else {
                await Promise.all(
                    taskStatuses.map((status) =>
                        fetchTaskColumn(projectId, status, filters),
                    ),
                )
            }
        } finally {
            loading.value = false
        }
    }

    async function loadMoreTasks(
        projectId: number,
        status: TaskStatus,
        filters?: TaskListParams
    ): Promise<void> {
        if (statusFilter.value && status !== statusFilter.value) {
            return
        }

        const currentPagination = pagination.value[status]

        if (
            loadingMore[status] ||
            !currentPagination.hasMore ||
            !currentPagination.nextCursor
        ) {
            return
        }

        loadingMore[status] = true
        errors[status] = null

        try {
            const response = await taskService.listByProject(
                projectId,
                currentPagination.nextCursor,
                {
                    ...filters,
                    status
                },
            )

            taskStore.appendTasks(status, response.data)
            taskStore.setPagination(status, response.meta.next_cursor)
        } catch {
            errors[status] = 'Erro ao carregar mais tarefas'
        } finally {
            loadingMore[status] = false
        }
    }

    async function createTask(
        projectId: number,
        data: TaskCreateData,
    ): Promise<Task | null> {
        try {
            const task = await taskService.create(projectId, data)
            taskStore.addTask(task)
            return task
        } catch {
            return null
        }
    }

    async function updateTask(
        taskId: number,
        data: TaskUpdateData,
    ): Promise<Task | null> {
        taskStore.addUpdatingTaskId(taskId)

        try {
            const updatedTask = await taskService.update(taskId, data)
            taskStore.updateTask(updatedTask)
            return updatedTask
        } catch {
            return null
        } finally {
            taskStore.removeUpdatingTaskId(taskId)
        }
    }

    async function updateTaskStatus(
        taskId: number,
        status: TaskStatus,
    ): Promise<Task | null> {
        return updateTask(taskId, { status })
    }

    async function deleteTask(taskId: number): Promise<boolean> {
        try {
            await taskService.delete(taskId)
            taskStore.removeTask(taskId)
            return true
        } catch {
            return false
        }
    }

    function setStatusFilter(status: TaskStatus | null): void {
        taskStore.setStatusFilter(status)
    }

    function getFilteredTasksByStatus(status: TaskStatus): Task[] {
        return taskStore.getFilteredTasksByStatus(status)
    }

    return {
        tasksByStatus,
        pagination,
        updatingTaskIds,
        statusFilter,
        loading,
        loadingMore,
        errors,
        fetchTasks,
        loadMoreTasks,
        createTask,
        updateTask,
        updateTaskStatus,
        deleteTask,
        setStatusFilter,
        getFilteredTasksByStatus,
    }
}
