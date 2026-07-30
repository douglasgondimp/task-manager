export interface CursorPaginatedResponse<T> {
    data: T[]
    links: {
        first: string | null
        last: string | null
        next: string | null
        prev: string | null
    }
    meta: {
        next_cursor: string | null
        path: string
        per_page: number
        prev_cursor: string | null
    }
}