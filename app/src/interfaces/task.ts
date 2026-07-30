export interface Task {
    id: number
    title: string
    description: string | null
    status: {
        value: 'todo' | 'in_progress' | 'done'
        label: string
    }
    priority: {
        value: 'low' | 'medium' | 'high'
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
    status?: 'todo' | 'in_progress' | 'done'
    priority?: 'low' | 'medium' | 'high'
    due_date?: string | null
}

export interface TaskUpdateData {
    title?: string
    description?: string | null
    status?: 'todo' | 'in_progress' | 'done'
    priority?: 'low' | 'medium' | 'high'
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