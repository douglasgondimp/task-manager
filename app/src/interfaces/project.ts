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
