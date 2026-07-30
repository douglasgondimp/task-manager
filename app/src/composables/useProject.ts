import { ref } from 'vue'
import { storeToRefs } from 'pinia'
import { projectService } from '@/services/project.service'
import { useProjectStore } from '@/stores/projects'

export function useProjects() {
    const projectStore = useProjectStore()

    const {
        projects,
        selectedProject,
        nextCursor,
        hasMore,
    } = storeToRefs(projectStore)

    const loading = ref(false)
    const loadingMore = ref(false)

    const error = ref<string | null>(null)
    const loadingMoreError = ref<string | null>(null)

    async function fetchProjects(perPage = 15): Promise<void> {
        loading.value = true
        error.value = null
        loadingMoreError.value = null

        try {
            const response = await projectService.list(perPage)

            projectStore.setProjects(response.data)
            projectStore.setPagination(response.meta.next_cursor)
        } catch {
            error.value = 'Não foi possível carregar os projetos.'
        } finally {
            loading.value = false
        }
    }

    async function loadMoreProjects(perPage = 15): Promise<void> {
        if (
            loading.value ||
            loadingMore.value ||
            !hasMore.value ||
            !nextCursor.value
        ) {
            return
        }

        loadingMore.value = true
        loadingMoreError.value = null

        try {
            const response = await projectService.list(
                perPage,
                nextCursor.value,
            )

            projectStore.appendProjects(response.data)
            projectStore.setPagination(response.meta.next_cursor)
        } catch {
            loadingMoreError.value =
                'Não foi possível carregar mais projetos.'
        } finally {
            loadingMore.value = false
        }
    }

    async function fetchProject(id: number): Promise<boolean> {
        loading.value = true
        error.value = null

        try {
            const project = await projectService.getById(id)

            projectStore.setSelectedProject(project)

            return true
        } catch {
            error.value = 'Não foi possível carregar o projeto.'
            projectStore.setSelectedProject(null)

            return false
        } finally {
            loading.value = false
        }
    }

    function resetProjects(): void {
        projectStore.resetProjects()

        error.value = null
        loadingMoreError.value = null
    }

    return {
        projects,
        project: selectedProject,
        nextCursor,
        hasMore,

        loading,
        loadingMore,
        error,
        loadingMoreError,

        fetchProjects,
        loadMoreProjects,
        fetchProject,
        resetProjects,
    }
}