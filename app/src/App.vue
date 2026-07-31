<script setup lang="ts">
import { provide, ref } from 'vue'
import { RouterLink, RouterView } from 'vue-router'
import { AlertKey, useAlertProvider } from '@/composables/useAlert'
import Alert from '@/components/Alert.vue'

const sidebarOpen = ref(false)

const AlertProvider = useAlertProvider()
provide(AlertKey, AlertProvider)
</script>

<template>
    <div class="h-screen">
        <!-- Header fixed at top -->
        <header
            class="fixed top-0 left-0 right-0 z-30 h-14 border-b border-gray-950/5 bg-white dark:border-white/10 dark:bg-gray-950">
            <div class="flex h-full items-center px-6">
                <button @click="sidebarOpen = !sidebarOpen"
                    class="mr-4 rounded-lg p-2 text-gray-700 transition-colors hover:bg-gray-200 dark:text-gray-300 dark:hover:bg-gray-800 lg:hidden">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <h4 class="text-lg text-blue-600 dark:text-white">Task Manager</h4>
            </div>
        </header>

        <!-- Overlay for mobile -->
        <div v-if="sidebarOpen" @click="sidebarOpen = false" class="fixed inset-0 z-20 bg-black/50 lg:hidden"></div>

        <!-- Sidebar fixed on the left with full height -->
        <aside
            :class="['fixed left-0 top-0 z-20 h-screen w-50 border-r border-gray-950/5 bg-gray-50 pt-14 transition-transform duration-300 dark:border-white/10 dark:bg-gray-900 lg:translate-x-0', sidebarOpen ? 'translate-x-0' : '-translate-x-full']">
            <nav class="flex flex-col gap-1 p-4">
                <RouterLink to="/"
                    class="rounded-lg px-3 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-200 dark:text-gray-300 dark:hover:bg-gray-800">
                    Sobre
                </RouterLink>
                <RouterLink to="/projects"
                    class="rounded-lg px-3 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-200 dark:text-gray-300 dark:hover:bg-gray-800">
                    Projetos
                </RouterLink>
            </nav>
        </aside>

        <!-- Main content -->
        <main class="min-h-screen pt-14 lg:pl-55">
            <div class="p-6">
                <RouterView />
            </div>
        </main>

        <!-- API Alert overlay (global) -->
        <Alert v-model="AlertProvider.show.value" :type="AlertProvider.type.value"
            :message="AlertProvider.message.value" />
    </div>
</template>
