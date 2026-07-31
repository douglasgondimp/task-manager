export type TaskStatus = 'todo' | 'in_progress' | 'done'
export type TaskPriority = 'low' | 'medium' | 'high'
export interface Task {
    id: number
    title: string
    description: string | null
    status: {
        value: TaskStatus
        label: string
    }
    priority: {
        value: TaskPriority
        label: string
    }
    due_date: string | null
    is_overdue: boolean
    created_at: string
    updated_at: string
}

export interface TaskCreateData {
    title: string
    description?: string | null
    status?: TaskStatus
    priority?: TaskPriority
    due_date?: string | null
}

export interface TaskUpdateData {
    title?: string
    description?: string | null
    status?: TaskStatus
    priority?: TaskPriority
    due_date?: string | null
}

export const TASK_STATUS_LABELS: Record<string, string> = {
    todo: 'A fazer',
    in_progress: 'Em desenvolvimento',
    done: 'Completo',
}

export const TASK_COLUMNS = [
    { key: 'todo', label: 'A fazer' },
    { key: 'in_progress', label: 'Em desenvolvimento' },
    { key: 'done', label: 'Completo' },
] as const

export interface TaskListParams {
    search?: string | null
    status?: TaskStatus
    priority?: TaskPriority | null
    is_overdue?: boolean
    created_at?: [string, string] | [] | null
    cursor?: string | null
}

export interface TaskPagination {
    nextCursor: string | null
    hasMore: boolean
}