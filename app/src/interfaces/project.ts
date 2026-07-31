export interface Project {
    id: number
    name: string
    description: string | null
    status: {
        value: string,
        label: string
    }
    tasks_count?: number
    created_at: string
    updated_at: string
}

export interface ProjectCreateData {
    name: string
    description?: string | null
    status?: string
}

export interface ProjectCreated {
    message: string,
    project: Project
}

export interface ProjectListParams {
    search?: string
    status?: 'active' | 'archived'
    per_page?: number
    cursor?: string | null
}