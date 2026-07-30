<script setup lang="ts">
import { ref, nextTick, onMounted, onUnmounted, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useProjects } from '@/composables/useProject'

const router = useRouter()

const {
    projects,
    loading,
    loadingMore,
    error,
    loadingMoreError,
    hasMore,
    fetchProjects,
    loadMoreProjects,
    resetProjects,
} = useProjects()

const perPage = ref(15)
const sentinel = ref<HTMLElement | null>(null)

let observer: IntersectionObserver | null = null

function statusClass(status: string): string {
    const map: Record<string, string> = {
        active: 'bg-green-100 text-green-800',
        archived: 'bg-red-100 text-red-800',
    }
    return map[status] || 'bg-blue-100 text-blue-800'
}

function formatDate(dateStr: string): string {
    return new Date(dateStr).toLocaleDateString('pt-BR')
}

function setupObserver() {
    observer?.disconnect()

    observer = new IntersectionObserver(
        ([entry]) => {
            if (entry?.isIntersecting) {
                void loadMoreProjects(perPage.value)
            }
        },
        { rootMargin: '50px' },
    )

    if (sentinel.value) {
        observer.observe(sentinel.value)
    }
}

watch(perPage, async () => {
    resetProjects()

    await fetchProjects(perPage.value)
    await nextTick()

    setupObserver()
})

onMounted(async () => {
    await fetchProjects(perPage.value)
    await nextTick()

    setupObserver()
})

onUnmounted(() => {
    observer?.disconnect()
})
</script>

<template>
    <div class="projects-page">
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold text-white">Projetos</h1>

            <div class="flex items-center gap-2">
                <label for="per-page" class="text-sm text-gray-400">Itens por página:</label>
                <select id="per-page" v-model="perPage"
                    class="rounded-lg border border-gray-700 bg-gray-800 px-3 py-1.5 text-sm text-white focus:border-blue-500 focus:outline-none">
                    <option :value="5">5</option>
                    <option :value="10">10</option>
                    <option :value="15">15</option>
                    <option :value="20">20</option>
                    <option :value="30">30</option>
                    <option :value="50">50</option>
                </select>
            </div>
        </div>

        <div v-if="loading" class="flex items-center justify-center py-12">
            <div class="h-8 w-8 animate-spin rounded-full border-4 border-blue-500 border-t-transparent"></div>
        </div>

        <div v-else-if="error" class="rounded-lg bg-red-900/50 p-4 text-red-400">
            {{ error }}
        </div>

        <div v-else-if="projects.length === 0" class="py-12 text-center text-gray-500">
            Nenhum projeto encontrado.
        </div>

        <template v-else>
            <div class="grid gap-4">
                <div v-for="project in projects" :key="project.id" @click="router.push(`/projects/${project.id}`)"
                    class="cursor-pointer rounded-lg border border-gray-700 bg-gray-800 p-4 shadow-sm transition-shadow hover:border-blue-500 hover:shadow-md">
                    <h2 class="text-lg font-semibold text-white">{{ project.name }}</h2>
                    <p v-if="project.description" class="mt-1 text-gray-400">
                        {{ project.description }}
                    </p>
                    <div class="mt-2 flex items-center gap-2">
                        <span class="inline-block rounded-full px-2 py-0.5 text-xs font-medium"
                            :class="statusClass(project.status.value)">
                            {{ project.status.label }}
                        </span>
                        <span class="text-xs text-gray-500">
                            {{ formatDate(project.created_at) }}
                        </span>
                        <span class="text-xs text-gray-500">
                            · {{ project.tasks_count ?? 0 }} tarefas
                        </span>
                    </div>
                </div>
            </div>

            <!-- Sentinel element for infinite scroll -->
            <div ref="sentinel" class="mt-6 flex justify-center py-4">
                <div v-if="loadingMore"
                    class="h-6 w-6 animate-spin rounded-full border-4 border-blue-500 border-t-transparent"></div>
                <span v-else-if="loadingMoreError" class="text-sm text-red-400">
                    {{ loadingMoreError }}
                </span>
                <span v-else-if="!hasMore" class="text-sm text-gray-500">
                    Todos os projetos foram carregados.
                </span>
            </div>
        </template>
    </div>
</template>