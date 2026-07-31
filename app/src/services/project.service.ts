import http from './http'
import type { Project, ProjectCreated, ProjectCreateData, ProjectListParams } from '../interfaces/project'
import type { CursorPaginatedResponse } from '@/interfaces/paginate'

export const projectService = {
    async list(params?: ProjectListParams): Promise<CursorPaginatedResponse<Project>> {
        const response = await http.get<CursorPaginatedResponse<Project>>('/projects', {
            params: { per_page: 15, ...params },
        })
        return response.data
    },

    async getById(id: number): Promise<Project> {
        const response = await http.get<{ data: Project }>(`/projects/${id}`)
        return response.data.data
    },

    async create(data: ProjectCreateData): Promise<ProjectCreated> {
        const response = await http.post<ProjectCreated>('/projects', data)
        return response.data
    },

    async update(id: number, data: Partial<ProjectCreateData>): Promise<Project> {
        const response = await http.patch<{ data: Project }>(`/projects/${id}`, data)
        return response.data.data
    },

    async delete(id: number): Promise<void> {
        await http.delete(`/projects/${id}`)
    },
}