import http from './http'
import type { Project, ProjectCreateData } from '../interfaces/project'

export interface PaginatedResponse<T> {
    data: T[]
    current_page: number
    last_page: number
    per_page: number
    total: number
}

export const projectService = {
    async list(page = 1, perPage = 15): Promise<PaginatedResponse<Project>> {
        const response = await http.get<PaginatedResponse<Project>>('/projects', {
            params: { page, per_page: perPage },
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