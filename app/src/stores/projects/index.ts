import { defineStore } from 'pinia';
import type { Project, ProjectCreateData } from '../../interfaces/project';

export const useProjectStore = defineStore('projects', {
    state: () => ({
        projects: [] as Project[],
        loading: false,
        error: null as string | null,
    }),
    actions: {
        async fetchProjects() {
            this.loading = true;
            this.error = null;
            try {
                const response = await fetch('/projects');

                if (!response.ok) {
                    throw new Error('Erro ao carregar projetos');
                }

                const data = await response.json();
                this.projects = data.data;
            } catch (e) {
                this.error = 'Erro ao carregar projetos';
            } finally {
                this.loading = false;
            }
        },
        async createProject(data: ProjectCreateData) {
            this.loading = true;
            this.error = null;
            try {
                const response = await fetch('/projects', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data),
                });
                if (!response.ok) throw new Error('Erro ao criar projeto');
                const project = await response.json();
                this.projects.push(project.data);
                return project;
            } catch (e) {
                this.error = 'Erro ao criar projeto';
                throw e;
            } finally {
                this.loading = false;
            }
        },
    },
});
