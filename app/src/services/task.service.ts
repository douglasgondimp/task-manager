import http from './http'
import type { Task, TaskCreateData, TaskUpdateData } from '../interfaces/task'
import type { CursorPaginatedResponse } from '@/interfaces/paginate'

export const taskService = {
    async listByProject(
        projectId: number,
        perPage = 50,
        cursor?: string | null,
    ): Promise<CursorPaginatedResponse<Task>> {
        const response = await http.get<CursorPaginatedResponse<Task>>(
            `/projects/${projectId}/tasks`,
            { params: { per_page: perPage, cursor } },
        )
        return response.data
    },

    async listAllByProject(projectId: number): Promise<Task[]> {
        const tasks: Task[] = []
        let cursor: string | null = null
        let hasMore = true

        while (hasMore) {
            const response = await this.listByProject(projectId, 100, cursor)
            tasks.push(...response.data)
            cursor = response.meta.next_cursor
            hasMore = cursor !== null
        }

        return tasks
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