/**
 * @vitest-environment node
 */
import { describe, it, expect, vi, beforeEach } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { useTask } from '@/composables/useTask'
import { taskService } from '@/services/task.service'
import type { Task, TaskStatus } from '@/interfaces/task'
import type { CursorPaginatedResponse } from '@/interfaces/paginate'

vi.mock('@/services/task.service', () => ({
    taskService: {
        listByProject: vi.fn(),
    },
}))

function createMockTask(id: number, status: TaskStatus): Task {
    return {
        id,
        title: `Task ${id}`,
        description: null,
        status: { value: status, label: status },
        priority: { value: 'medium', label: 'Média' },
        due_date: null,
        is_overdue: false,
        created_at: '2024-01-01',
        updated_at: '2024-01-01',
    }
}

function createMockResponse(
    tasks: Task[],
    nextCursor: string | null = null,
): CursorPaginatedResponse<Task> {
    return {
        data: tasks,
        links: { first: null, last: null, next: null, prev: null },
        meta: {
            next_cursor: nextCursor,
            path: '',
            per_page: 10,
            prev_cursor: null,
        },
    }
}

describe('useTask', () => {
    beforeEach(() => {
        setActivePinia(createPinia())
        vi.clearAllMocks()
    })

    describe('fetchTasks', () => {
        it('makes only one API call when status filter is active', async () => {
            const { fetchTasks, setStatusFilter, tasksByStatus } = useTask()

            setStatusFilter('todo')

            const mockTasks = [createMockTask(1, 'todo')]
            vi.mocked(taskService.listByProject).mockResolvedValue(
                createMockResponse(mockTasks),
            )

            await fetchTasks(1)

            // Should only call API once (for the filtered status)
            expect(taskService.listByProject).toHaveBeenCalledTimes(1)
            expect(taskService.listByProject).toHaveBeenCalledWith(1, undefined, {
                status: 'todo',
            })

            // Only the filtered status should have tasks; others remain empty
            expect(tasksByStatus.value.todo).toHaveLength(1)
            expect(tasksByStatus.value.in_progress).toHaveLength(0)
            expect(tasksByStatus.value.done).toHaveLength(0)
        })

        it('makes three API calls when no status filter is active', async () => {
            const { fetchTasks, tasksByStatus } = useTask()

            vi.mocked(taskService.listByProject)
                .mockResolvedValueOnce(
                    createMockResponse([createMockTask(1, 'todo')]),
                )
                .mockResolvedValueOnce(
                    createMockResponse([createMockTask(2, 'in_progress')]),
                )
                .mockResolvedValueOnce(
                    createMockResponse([createMockTask(3, 'done')]),
                )

            await fetchTasks(1)

            // Should call API 3 times (one for each status)
            expect(taskService.listByProject).toHaveBeenCalledTimes(3)

            // All statuses should have tasks
            expect(tasksByStatus.value.todo).toHaveLength(1)
            expect(tasksByStatus.value.in_progress).toHaveLength(1)
            expect(tasksByStatus.value.done).toHaveLength(1)
        })
    })

    describe('loadMoreTasks', () => {
        it('does not make API call for non-filtered status when filter is active', async () => {
            const { fetchTasks, loadMoreTasks, setStatusFilter } = useTask()

            setStatusFilter('todo')

            vi.mocked(taskService.listByProject).mockResolvedValue(
                createMockResponse([createMockTask(1, 'todo')], 'cursor123'),
            )

            await fetchTasks(1)

            // Reset mock call history to isolate loadMore calls
            vi.clearAllMocks()

            // Attempt to load more for a non-filtered status
            await loadMoreTasks(1, 'in_progress')

            expect(taskService.listByProject).not.toHaveBeenCalled()
        })

        it('makes API call for filtered status when loadMore is triggered', async () => {
            const { fetchTasks, loadMoreTasks, setStatusFilter } = useTask()

            setStatusFilter('todo')

            vi.mocked(taskService.listByProject).mockResolvedValue(
                createMockResponse([createMockTask(1, 'todo')], 'cursor123'),
            )

            await fetchTasks(1)

            // Reset mock call history to isolate loadMore calls
            vi.clearAllMocks()

            vi.mocked(taskService.listByProject).mockResolvedValue(
                createMockResponse([createMockTask(2, 'todo')], null),
            )

            await loadMoreTasks(1, 'todo')

            expect(taskService.listByProject).toHaveBeenCalledTimes(1)
            expect(taskService.listByProject).toHaveBeenCalledWith(1, 'cursor123', {
                status: 'todo',
            })
        })
    })

    describe('getFilteredTasksByStatus', () => {
        it('returns all tasks when no filter is active', async () => {
            const { fetchTasks, getFilteredTasksByStatus } = useTask()

            vi.mocked(taskService.listByProject)
                .mockResolvedValueOnce(
                    createMockResponse([createMockTask(1, 'todo')]),
                )
                .mockResolvedValueOnce(
                    createMockResponse([createMockTask(2, 'in_progress')]),
                )
                .mockResolvedValueOnce(
                    createMockResponse([createMockTask(3, 'done')]),
                )

            await fetchTasks(1)

            expect(getFilteredTasksByStatus('todo')).toHaveLength(1)
            expect(getFilteredTasksByStatus('in_progress')).toHaveLength(1)
            expect(getFilteredTasksByStatus('done')).toHaveLength(1)
        })

        it('returns tasks only for filtered status, empty for others', async () => {
            const { fetchTasks, setStatusFilter, getFilteredTasksByStatus } =
                useTask()

            setStatusFilter('todo')

            vi.mocked(taskService.listByProject).mockResolvedValue(
                createMockResponse([createMockTask(1, 'todo')]),
            )

            await fetchTasks(1)

            expect(getFilteredTasksByStatus('todo')).toHaveLength(1)
            expect(getFilteredTasksByStatus('in_progress')).toHaveLength(0)
            expect(getFilteredTasksByStatus('done')).toHaveLength(0)
        })
    })
})
