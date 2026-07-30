<script setup lang="ts">
import { RouterLink, RouterView } from 'vue-router'
</script>

<template>
    <div class="h-screen">
        <!-- Header fixed at top -->
        <header
            class="fixed top-0 left-0 right-0 z-30 h-14 border-b border-gray-950/5 bg-white dark:border-white/10 dark:bg-gray-950">
            <div class="flex h-full items-center px-6">
                <h4 class="text-lg text-blue-600 dark:text-white">Task Manager</h4>
            </div>
        </header>

        <!-- Sidebar fixed on the left with full height -->
        <aside
            class="fixed left-0 top-0 z-20 h-screen w-50 border-r border-gray-950/5 bg-gray-50 pt-14 dark:border-white/10 dark:bg-gray-900">
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
        <main class="min-h-screen pl-55 pt-14">
            <div class="p-6">
                <RouterView />
            </div>
        </main>
    </div>
</template>