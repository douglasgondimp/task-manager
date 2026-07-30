import { createRouter, createWebHistory } from 'vue-router'
import About from '@/views/About.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'about',
      component: About,
    },
    {
      path: '/projects',
      name: 'projects',
      component: () => import('../views/pages/projects/ProjectList.vue'),
    },
    {
      path: '/projects/:id',
      name: 'project-detail',
      component: () => import('../views/pages/projects/ProjectDetail.vue'),
    },
  ],
})

export default router
