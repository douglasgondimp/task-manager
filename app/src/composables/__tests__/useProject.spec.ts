/**
 * @vitest-environment node
 */
import { describe, it, expect, vi, beforeEach } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'
import { useProjects } from '@/composables/useProject'
import { projectService } from '@/services/project.service'
import type { Project, ProjectCreated, ProjectListParams } from '@/interfaces/project'
import type { CursorPaginatedResponse } from '@/interfaces/paginate'

vi.mock('@/services/project.service', () => ({
    projectService: {
        list: vi.fn(),
        getById: vi.fn(),
        create: vi.fn(),
        update: vi.fn(),
        delete: vi.fn(),
    },
}))

function createMockProject(id: number, name: string = 'Test Project'): Project {
    return {
        id,
        name,
        description: 'Test description',
        status: { value: 'active', label: 'Ativo' },
        tasks_count: 0,
        created_at: '2024-01-01',
        updated_at: '2024-01-01',
    }
}

function createMockPaginatedResponse(
    projects: Project[],
    nextCursor: string | null = null,
): CursorPaginatedResponse<Project> {
    return {
        data: projects,
        links: { first: null, last: null, next: null, prev: null },
        meta: {
            next_cursor: nextCursor,
            path: '',
            per_page: 15,
            prev_cursor: null,
        },
    }
}

describe('useProjects', () => {
    beforeEach(() => {
        setActivePinia(createPinia())
        vi.clearAllMocks()
    })

    describe('fetchProjects', () => {
        it('fetches projects and updates state', async () => {
            const { fetchProjects, projects, loading, error } = useProjects()

            const mockProjects = [createMockProject(1), createMockProject(2)]
            vi.mocked(projectService.list).mockResolvedValue(
                createMockPaginatedResponse(mockProjects, 'cursor123'),
            )

            await fetchProjects()

            expect(projectService.list).toHaveBeenCalledTimes(1)
            expect(projectService.list).toHaveBeenCalledWith(undefined)
            expect(projects.value).toHaveLength(2)
            expect(loading.value).toBe(false)
            expect(error.value).toBe(null)
        })

        it('sets error when fetch fails', async () => {
            const { fetchProjects, error, loading } = useProjects()

            vi.mocked(projectService.list).mockRejectedValue(
                new Error('Network error'),
            )

            await fetchProjects()

            expect(error.value).toBe('Não foi possível carregar os projetos.')
            expect(loading.value).toBe(false)
        })

        it('passes params to the API', async () => {
            const { fetchProjects } = useProjects()

            const params: ProjectListParams = {
                search: 'test',
                status: 'active',
            }
            vi.mocked(projectService.list).mockResolvedValue(
                createMockPaginatedResponse([]),
            )

            await fetchProjects(params)

            expect(projectService.list).toHaveBeenCalledWith(params)
        })
    })

    describe('loadMoreProjects', () => {
        it('loads more projects with cursor and appends to list', async () => {
            const {
                fetchProjects,
                loadMoreProjects,
                projects,
                loadingMore,
            } = useProjects()

            // Initial fetch
            vi.mocked(projectService.list).mockResolvedValueOnce(
                createMockPaginatedResponse(
                    [createMockProject(1)],
                    'cursor123',
                ),
            )
            await fetchProjects()

            expect(projects.value).toHaveLength(1)

            // Load more
            vi.mocked(projectService.list).mockResolvedValueOnce(
                createMockPaginatedResponse([createMockProject(2)], null),
            )

            await loadMoreProjects()

            expect(projectService.list).toHaveBeenCalledTimes(2)
            expect(projectService.list).toHaveBeenLastCalledWith({
                cursor: 'cursor123',
            })
            expect(projects.value).toHaveLength(2)
            expect(loadingMore.value).toBe(false)
        })

        it('does not load more when hasMore is false', async () => {
            const { fetchProjects, loadMoreProjects } = useProjects()

            vi.mocked(projectService.list).mockResolvedValueOnce(
                createMockPaginatedResponse([createMockProject(1)], null),
            )
            await fetchProjects()

            vi.clearAllMocks()

            await loadMoreProjects()

            expect(projectService.list).not.toHaveBeenCalled()
        })

        it('does not load more when already loading', async () => {
            const { fetchProjects, loadMoreProjects, loading } = useProjects()

            vi.mocked(projectService.list).mockResolvedValueOnce(
                createMockPaginatedResponse(
                    [createMockProject(1)],
                    'cursor123',
                ),
            )
            await fetchProjects()

            vi.clearAllMocks()

            // Simulate loading state
            loading.value = true

            await loadMoreProjects()

            expect(projectService.list).not.toHaveBeenCalled()
        })

        it('merges params with cursor when loading more', async () => {
            const { fetchProjects, loadMoreProjects } = useProjects()

            vi.mocked(projectService.list).mockResolvedValueOnce(
                createMockPaginatedResponse(
                    [createMockProject(1)],
                    'cursor123',
                ),
            )
            await fetchProjects()

            vi.clearAllMocks()

            vi.mocked(projectService.list).mockResolvedValueOnce(
                createMockPaginatedResponse([createMockProject(2)], null),
            )

            await loadMoreProjects({ search: 'test' })

            expect(projectService.list).toHaveBeenCalledWith({
                search: 'test',
                cursor: 'cursor123',
            })
        })

        it('sets loadingMoreError when load more fails', async () => {
            const {
                fetchProjects,
                loadMoreProjects,
                loadingMoreError,
            } = useProjects()

            vi.mocked(projectService.list).mockResolvedValueOnce(
                createMockPaginatedResponse(
                    [createMockProject(1)],
                    'cursor123',
                ),
            )
            await fetchProjects()

            vi.clearAllMocks()

            vi.mocked(projectService.list).mockRejectedValue(
                new Error('Network error'),
            )

            await loadMoreProjects()

            expect(loadingMoreError.value).toBe(
                'Não foi possível carregar mais projetos.',
            )
        })
    })

    describe('fetchProject', () => {
        it('fetches a single project and sets it as selected', async () => {
            const { fetchProject, project, loading } = useProjects()

            const mockProject = createMockProject(1, 'My Project')
            vi.mocked(projectService.getById).mockResolvedValue(mockProject)

            const result = await fetchProject(1)

            expect(result).toBe(true)
            expect(projectService.getById).toHaveBeenCalledWith(1)
            expect(project.value).toEqual(mockProject)
            expect(loading.value).toBe(false)
        })

        it('returns false and clears selected project on error', async () => {
            const { fetchProject, project, error } = useProjects()

            vi.mocked(projectService.getById).mockRejectedValue(
                new Error('Not found'),
            )

            const result = await fetchProject(999)

            expect(result).toBe(false)
            expect(error.value).toBe('Não foi possível carregar o projeto.')
            expect(project.value).toBe(null)
        })
    })

    describe('createProject', () => {
        it('creates a project and adds it to the list', async () => {
            const { createProject, projects, error } = useProjects()

            const newProject = createMockProject(1, 'New Project')
            const mockResponse: ProjectCreated = {
                message: 'Projeto criado com sucesso',
                project: newProject,
            }
            vi.mocked(projectService.create).mockResolvedValue(mockResponse)

            const result = await createProject({ name: 'New Project' })

            expect(result).toBe(true)
            expect(projectService.create).toHaveBeenCalledWith({
                name: 'New Project',
            })
            expect(projects.value).toHaveLength(1)
            expect(projects.value[0]).toEqual(newProject)
            expect(error.value).toBe(null)
        })

        it('returns false and sets error on failure', async () => {
            const { createProject, error } = useProjects()

            vi.mocked(projectService.create).mockRejectedValue(
                new Error('Failed'),
            )

            const result = await createProject({ name: 'New Project' })

            expect(result).toBe(false)
            expect(error.value).toBe('Erro ao criar projeto')
        })
    })

    describe('updateProject', () => {
        it('updates a project and updates selected project', async () => {
            const { fetchProject, updateProject, project } = useProjects()

            // First, set a selected project
            const mockProject = createMockProject(1, 'Original Name')
            vi.mocked(projectService.getById).mockResolvedValue(mockProject)
            await fetchProject(1)

            // Now update it
            const updatedProject = { ...mockProject, name: 'Updated Name' }
            vi.mocked(projectService.update).mockResolvedValue(updatedProject)

            const result = await updateProject(1, { name: 'Updated Name' })

            expect(result).toBe(true)
            expect(projectService.update).toHaveBeenCalledWith(1, {
                name: 'Updated Name',
            })
            expect(project.value?.name).toBe('Updated Name')
        })

        it('returns false and sets error on failure', async () => {
            const { updateProject, error } = useProjects()

            vi.mocked(projectService.update).mockRejectedValue(
                new Error('Failed'),
            )

            const result = await updateProject(1, { name: 'Updated Name' })

            expect(result).toBe(false)
            expect(error.value).toBe('Erro ao atualizar projeto')
        })
    })

    describe('resetProjects', () => {
        it('resets all state', async () => {
            const { fetchProjects, resetProjects, projects, error } =
                useProjects()

            vi.mocked(projectService.list).mockResolvedValue(
                createMockPaginatedResponse([createMockProject(1)]),
            )
            await fetchProjects()

            expect(projects.value).toHaveLength(1)

            resetProjects()

            expect(projects.value).toHaveLength(0)
            expect(error.value).toBe(null)
        })
    })
})
