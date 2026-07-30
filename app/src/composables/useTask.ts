import { ref } from 'vue'
import { storeToRefs } from 'pinia'
import { taskService } from '@/services/task.service'
import { useTaskStore } from '@/stores/tasks'
import type { Task, TaskCreateData, TaskUpdateData } from '@/interfaces/task'

type TaskStatus = Task['status']['value']

export function useTask() {
    const taskStore = useTaskStore()

    const { tasks, updatingTaskIds, todoTasks, inProgressTasks, doneTasks } =
        storeToRefs(taskStore)

    const loading = ref(false)
    const error = ref<string | null>(null)

    async function fetchTasks(projectId: number): Promise<void> {
        loading.value = true
        error.value = null

        try {
            const allTasks = await taskService.listAllByProject(projectId)
            taskStore.setTasks(allTasks)
        } catch {
            error.value = 'Erro ao carregar tarefas'
        } finally {
            loading.value = false
        }
    }

    async function createTask(
        projectId: number,
        data: TaskCreateData,
    ): Promise<Task | null> {
        error.value = null

        try {
            const task = await taskService.create(projectId, data)
            taskStore.addTask(task)
            return task
        } catch {
            error.value = 'Erro ao criar tarefa'
            return null
        }
    }

    async function updateTask(
        taskId: number,
        data: TaskUpdateData,
    ): Promise<Task | null> {
        taskStore.addUpdatingTaskId(taskId)
        error.value = null

        try {
            const updatedTask = await taskService.update(taskId, data)
            taskStore.updateTaskById(taskId, updatedTask)
            return updatedTask
        } catch {
            error.value = 'Erro ao atualizar tarefa'
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
        error.value = null

        try {
            await taskService.delete(taskId)
            taskStore.removeTaskById(taskId)
            return true
        } catch {
            error.value = 'Erro ao excluir tarefa'
            return false
        }
    }

    return {
        tasks,
        todoTasks,
        inProgressTasks,
        doneTasks,
        loading,
        error,
        updatingTaskIds,
        fetchTasks,
        createTask,
        updateTask,
        updateTaskStatus,
        deleteTask,
    }
}