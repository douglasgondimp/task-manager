import http from './http'
import type { Task, TaskCreateData, TaskUpdateData, TaskListParams } from '../interfaces/task'
import type { CursorPaginatedResponse } from '@/interfaces/paginate'

export const taskService = {
    async listByProject(
        projectId: number,
        cursor?: string | null,
        filters?: TaskListParams,
    ): Promise<CursorPaginatedResponse<Task>> {
        const response = await http.get<CursorPaginatedResponse<Task>>(
            `/projects/${projectId}/tasks`,
            { params: { cursor: cursor || undefined, ...filters } },
        )
        return response.data
    },

    async create(projectId: number, data: TaskCreateData): Promise<Task> {
        const response = await http.post<{ data: Task }>(
            `/projects/${projectId}/tasks`,
            data,
        )
        return response.data.data
    },

    async getById(taskId: number): Promise<Task> {
        const response = await http.get<{ data: Task }>(`/tasks/${taskId}`)
        return response.data.data
    },

    async update(taskId: number, data: TaskUpdateData): Promise<Task> {
        const response = await http.patch<{ data: Task }>(`/tasks/${taskId}`, data)
        return response.data.data
    },

    async delete(taskId: number): Promise<void> {
        await http.delete(`/tasks/${taskId}`)
    },
}