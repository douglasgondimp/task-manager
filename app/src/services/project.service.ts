import http from './http'
import type { Project, ProjectCreateData } from '../interfaces/project'
import type { CursorPaginatedResponse } from '@/interfaces/paginate'

export const projectService = {
    async list(perPage = 15, cursor?: string | null): Promise<CursorPaginatedResponse<Project>> {
        const response = await http.get<CursorPaginatedResponse<Project>>('/projects', {
            params: { per_page: perPage, cursor },
        })
        return response.data
    },

    async getById(id: number): Promise<Project> {
        const response = await http.get<{ data: Project }>(`/projects/${id}`)
        return response.data.data
    },

    async create(data: ProjectCreateData): Promise<Project> {
        const response = await http.post<{ data: Project }>('/projects', data)
        return response.data.data
    },

    async update(id: number, data: Partial<ProjectCreateData>): Promise<Project> {
        const response = await http.put<{ data: Project }>(`/projects/${id}`, data)
        return response.data.data
    },

    async delete(id: number): Promise<void> {
        await http.delete(`/projects/${id}`)
    },
}