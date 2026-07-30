import { defineStore } from 'pinia'
import { ref } from 'vue'
import type { Project } from '@/interfaces/project'

export const useProjectStore = defineStore('projects', () => {
    const projects = ref<Project[]>([])
    const selectedProject = ref<Project | null>(null)

    const nextCursor = ref<string | null>(null)
    const hasMore = ref(true)

    function setProjects(data: Project[]): void {
        projects.value = data
    }

    function addProject(project: Project): void {
        projects.value.unshift(project)
    }

    function appendProjects(data: Project[]): void {
        projects.value.push(...data)
    }

    function setSelectedProject(project: Project | null): void {
        selectedProject.value = project
    }

    function setPagination(cursor: string | null): void {
        nextCursor.value = cursor
        hasMore.value = cursor !== null
    }

    function resetProjects(): void {
        projects.value = []
        nextCursor.value = null
        hasMore.value = true
    }

    return {
        projects,
        selectedProject,
        nextCursor,
        hasMore,
        setProjects,
        addProject,
        appendProjects,
        setSelectedProject,
        setPagination,
        resetProjects,
    }
})