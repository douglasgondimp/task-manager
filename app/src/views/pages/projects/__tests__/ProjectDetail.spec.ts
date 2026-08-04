/**
 * @vitest-environment jsdom
 */
import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount, flushPromises, type VueWrapper } from '@vue/test-utils'
import { createPinia, setActivePinia, type Pinia } from 'pinia'
import { defineComponent, h } from 'vue'
import ProjectDetail from '@/views/pages/projects/ProjectDetail.vue'
import { taskService } from '@/services/task.service'
import { projectService } from '@/services/project.service'
import { useTaskStore } from '@/stores/tasks'
import type { Task, TaskStatus } from '@/interfaces/task'
import type { CursorPaginatedResponse } from '@/interfaces/paginate'
import type { Project } from '@/interfaces/project'

// Mock vue-router
const pushMock = vi.fn<() => Promise<void>>()
vi.mock('vue-router', () => ({
    useRoute: () => ({ params: { id: '1' } }),
    useRouter: () => ({ push: pushMock }),
}))

// Mock services
vi.mock('@/services/task.service', () => ({
    taskService: {
        listByProject: vi.fn<
            (
                projectId: number,
                cursor?: string | null,
                filters?: Record<string, unknown>,
            ) => Promise<CursorPaginatedResponse<Task>>
        >(),
        create: vi.fn<
            (projectId: number, data: unknown) => Promise<Task>
        >(),
        getById: vi.fn<(taskId: number) => Promise<Task>>(),
        update: vi.fn<(taskId: number, data: unknown) => Promise<Task>>(),
        delete: vi.fn<(taskId: number) => Promise<void>>(),
    },
}))

vi.mock('@/services/project.service', () => ({
    projectService: {
        list: vi.fn<() => Promise<unknown>>(),
        getById: vi.fn<(id: number) => Promise<Project>>(),
        create: vi.fn<() => Promise<unknown>>(),
        update: vi.fn<() => Promise<unknown>>(),
        delete: vi.fn<() => Promise<void>>(),
    },
}))

// Mock vue-draggable-plus
vi.mock('vue-draggable-plus', () => ({
    VueDraggable: defineComponent({
        name: 'VueDraggable',
        props: {
            modelValue: { type: Array, default: () => [] },
            group: { type: String, default: '' },
            animation: { type: Number, default: 0 },
            ghostClass: { type: String, default: '' },
            class: { type: String, default: '' },
        },
        emits: ['add', 'update:modelValue'],
        setup(_, { slots }) {
            return () =>
                h(
                    'div',
                    { 'data-test': 'vue-draggable' },
                    slots.default?.(),
                )
        },
    }),
}))

// Mock child components to simplify rendering
vi.mock('@/components/AppModal.vue', () => ({
    default: defineComponent({
        name: 'AppModal',
        props: {
            modelValue: { type: Boolean, default: false },
            title: { type: String, default: '' },
        },
        emits: ['update:modelValue'],
        setup(_, { slots }) {
            return () => h('div', { 'data-test': 'app-modal' }, slots.default?.())
        },
    }),
}))

vi.mock('@/components/ProjectForm.vue', () => ({
    default: defineComponent({
        name: 'ProjectForm',
        props: {
            initialData: { type: Object, default: null },
            showStatus: { type: Boolean, default: false },
            submitting: { type: Boolean, default: false },
        },
        emits: ['submit', 'cancel'],
        setup() {
            return () => h('div', { 'data-test': 'project-form' })
        },
    }),
}))

vi.mock('@/components/TaskForm.vue', () => ({
    default: defineComponent({
        name: 'TaskForm',
        props: {
            initialData: { type: Object, default: null },
            submitting: { type: Boolean, default: false },
        },
        emits: ['submit', 'cancel'],
        setup() {
            return () => h('div', { 'data-test': 'task-form' })
        },
    }),
}))

vi.mock('@/components/TaskCard.vue', () => ({
    default: defineComponent({
        name: 'TaskCard',
        props: {
            task: { type: Object, required: true },
        },
        emits: ['click', 'delete'],
        setup() {
            return () => h('div', { 'data-test': 'task-card' })
        },
    }),
}))

// Mock useAlert
vi.mock('@/composables/useAlert', () => ({
    useAlert: () => ({
        show: { value: false },
        type: { value: 'error' },
        message: { value: '' },
        showAlert: vi.fn<(type: string, message: string) => void>(),
        hideAlert: vi.fn<() => void>(),
    }),
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

function createMockProject(id: number): Project {
    return {
        id,
        name: 'Test Project',
        description: 'Test description',
        status: { value: 'active', label: 'Ativo' },
        tasks_count: 0,
        created_at: '2024-01-01',
        updated_at: '2024-01-01',
    }
}

describe('ProjectDetail.vue - rollback da atualização otimista', () => {
    let pinia: Pinia

    beforeEach(() => {
        pinia = createPinia()
        setActivePinia(pinia)

        vi.clearAllMocks()

        // Mock project fetch
        vi.mocked(projectService.getById).mockResolvedValue(
            createMockProject(1),
        )

        // Mock task list fetch - return a task in 'todo' column
        vi.mocked(taskService.listByProject).mockResolvedValue(
            createMockResponse([createMockTask(1, 'todo')]),
        )
    })

    async function mountComponent(): Promise<VueWrapper> {
        const wrapper = mount(ProjectDetail, {
            global: {
                plugins: [pinia],
            },
        })

        await flushPromises()
        return wrapper
    }

    it('re-envia os filtros ativos ao recarregar o Kanban após falha na atualização otimista', async () => {
        const wrapper = await mountComponent()

        // Configure active filters
        const vm = wrapper.vm as unknown as {
            filterSearch: string
            filterPriority: string
            filterOverdue: boolean
            filterDateFrom: string
            filterDateTo: string
            onTaskAdd: (
                columnStatus: TaskStatus,
                event: { newIndex: number },
            ) => Promise<void>
        }

        vm.filterSearch = 'documentação'
        vm.filterPriority = 'high'
        vm.filterOverdue = true
        vm.filterDateFrom = '2026-07-01'
        vm.filterDateTo = '2026-07-31'

        // Wait for filter watches to complete, including the 500ms debounce
        // applied to search/date filters
        await new Promise((resolve) => setTimeout(resolve, 501))
        await flushPromises()

        // Simulate the drag: the task was moved to the 'in_progress' column
        // by VueDraggable's v-model, but its status.value is still 'todo'
        const taskStore = useTaskStore()
        taskStore.setTasks('in_progress', [createMockTask(1, 'todo')])

        // Clear mock call history to isolate the rollback call
        vi.clearAllMocks()

        // Re-setup mocks for the rollback fetch
        vi.mocked(projectService.getById).mockResolvedValue(
            createMockProject(1),
        )
        vi.mocked(taskService.listByProject).mockResolvedValue(
            createMockResponse([createMockTask(1, 'todo')]),
        )

        // Make the task update (persistence) fail
        vi.mocked(taskService.update).mockRejectedValue(
            new Error('Network error'),
        )

        // Trigger the optimistic update handler directly
        await vm.onTaskAdd('in_progress', { newIndex: 0 })

        // Wait for all async error handling
        await flushPromises()

        // The rollback calls fetchTasks(1, buildActiveFilters()), which invokes
        // listByProject once per column (todo, in_progress, done). All calls must
        // carry the active filters. The last call is for the 'done' column.
        expect(taskService.listByProject).toHaveBeenCalledTimes(3)
        expect(taskService.listByProject).toHaveBeenLastCalledWith(
            1,
            undefined,
            {
                search: 'documentação',
                priority: 'high',
                is_overdue: true,
                created_at: ['2026-07-01', '2026-07-31'],
                status: 'done',
            },
        )

        wrapper.unmount()
    })
})