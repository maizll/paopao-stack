import { createRouter, createWebHistory } from 'vue-router'
import { token } from './api'
import AppsView from './views/AppsView.vue'
import BoardsView from './views/BoardsView.vue'
import LoginView from './views/LoginView.vue'
import OpsView from './views/OpsView.vue'
import OverviewView from './views/OverviewView.vue'
import PostsView from './views/PostsView.vue'
import ShellView from './views/ShellView.vue'
import TenantsView from './views/TenantsView.vue'

export const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/login', component: LoginView },
    {
      path: '/',
      component: ShellView,
      children: [
        { path: '', component: OverviewView },
        { path: 'tenants', component: TenantsView },
        { path: 'boards', component: BoardsView },
        { path: 'posts', component: PostsView },
        { path: 'apps', component: AppsView },
        { path: 'ops', component: OpsView },
      ],
    },
  ],
})

router.beforeEach((to) => {
  const signedIn = Boolean(token())
  if (to.path !== '/login' && !signedIn) return '/login'
  if (to.path === '/login' && signedIn) return '/'
  return true
})
