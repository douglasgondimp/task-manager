import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { Task } from '@/interfaces/task'

export const useTaskStore = defineStore('tasks', () => {
    const tasks = ref<Task[]>([])
    const updatingTaskIds = ref<Set<number>>(new Set())

    const todoTasks = computed(() => tasks.value.filter((t) => t.status.value === 'todo'))
    const inProgressTasks = computed(() => tasks.value.filter((t) => t.status.value === 'in_progress'))
    const doneTasks = computed(() => tasks.value.filter((t) => t.status.value === 'done'))

    function setTasks(data: Task[]): void {
        tasks.value = data
    }

    function addTask(task: Task): void {
        tasks.value.push(task)
    }

    function updateTaskById(taskId: number, updatedTask: Task): void {
        const index = tasks.value.findIndex((t) => t.id === taskId)
        if (index !== -1) {
            tasks.value[index] = updatedTask
        }
    }

    function removeTaskById(taskId: number): void {
        tasks.value = tasks.value.filter((t) => t.id !== taskId)
    }

    function addUpdatingTaskId(taskId: number): void {
        updatingTaskIds.value.add(taskId)
    }

    function removeUpdatingTaskId(taskId: number): void {
        updatingTaskIds.value.delete(taskId)
    }

    function resetTasks(): void {
        tasks.value = []
        updatingTaskIds.value = new Set()
    }

    return {
        tasks,
        updatingTaskIds,
        todoTasks,
        inProgressTasks,
        doneTasks,
        setTasks,
        addTask,
        updateTaskById,
        removeTaskById,
        addUpdatingTaskId,
        removeUpdatingTaskId,
        resetTasks,
    }
})