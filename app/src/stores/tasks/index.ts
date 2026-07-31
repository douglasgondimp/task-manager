import { defineStore } from 'pinia'
import { ref, reactive } from 'vue'
import type { Task, TaskPagination, TaskStatus } from '@/interfaces/task'

const taskStatuses: TaskStatus[] = ['todo', 'in_progress', 'done']

function createColumns(): Record<TaskStatus, Task[]> {
    return {
        todo: [],
        in_progress: [],
        done: [],
    }
}

function createPagination(): Record<TaskStatus, TaskPagination> {
    return {
        todo: { nextCursor: null, hasMore: true },
        in_progress: { nextCursor: null, hasMore: true },
        done: { nextCursor: null, hasMore: true },
    }
}

export const useTaskStore = defineStore('tasks', () => {
    const tasksByStatus = reactive(createColumns())
    const pagination = reactive(createPagination())
    const updatingTaskIds = ref<Set<number>>(new Set())
    const statusFilter = ref<TaskStatus | null>(null)

    function setTasks(status: TaskStatus, tasks: Task[]): void {
        tasksByStatus[status] = tasks
    }

    function appendTasks(status: TaskStatus, tasks: Task[]): void {
        const loadedIds = new Set(tasksByStatus[status].map((task) => task.id))

        tasksByStatus[status].push(
            ...tasks.filter((task) => !loadedIds.has(task.id)),
        )
    }

    function setPagination(status: TaskStatus, nextCursor: string | null): void {
        pagination[status].nextCursor = nextCursor
        pagination[status].hasMore = nextCursor !== null
    }

    function addTask(task: Task): void {
        tasksByStatus[task.status.value].unshift(task)
    }

    function updateTask(updatedTask: Task): void {
        let currentStatus: TaskStatus | null = null
        let currentIndex = -1

        for (const status of taskStatuses) {
            const index = tasksByStatus[status].findIndex(
                (task) => task.id === updatedTask.id,
            )

            if (index !== -1) {
                currentStatus = status
                currentIndex = index
                break
            }
        }

        if (currentStatus === updatedTask.status.value) {
            tasksByStatus[currentStatus][currentIndex] = updatedTask
            return
        }

        removeTask(updatedTask.id)
        tasksByStatus[updatedTask.status.value].unshift(updatedTask)
    }

    function removeTask(taskId: number): void {
        for (const status of taskStatuses) {
            tasksByStatus[status] = tasksByStatus[status].filter(
                (task) => task.id !== taskId,
            )
        }
    }

    function addUpdatingTaskId(taskId: number): void {
        updatingTaskIds.value.add(taskId)
    }

    function removeUpdatingTaskId(taskId: number): void {
        updatingTaskIds.value.delete(taskId)
    }

    function resetTasks(): void {
        for (const status of taskStatuses) {
            tasksByStatus[status] = []
            pagination[status].nextCursor = null
            pagination[status].hasMore = true
        }

        updatingTaskIds.value = new Set()
    }

    function setStatusFilter(status: TaskStatus | null): void {
        statusFilter.value = status
    }

    function getFilteredTasksByStatus(status: TaskStatus): Task[] {
        if (!statusFilter.value) {
            return tasksByStatus[status]
        }

        // When a status filter is active, only show tasks in the matching column
        if (status === statusFilter.value) {
            return tasksByStatus[status]
        }

        // Return empty array for non-matching columns
        return []
    }

    return {
        tasksByStatus,
        pagination,
        updatingTaskIds,
        statusFilter,
        setTasks,
        appendTasks,
        setPagination,
        addTask,
        updateTask,
        removeTask,
        addUpdatingTaskId,
        removeUpdatingTaskId,
        resetTasks,
        setStatusFilter,
        getFilteredTasksByStatus,
    }
})
