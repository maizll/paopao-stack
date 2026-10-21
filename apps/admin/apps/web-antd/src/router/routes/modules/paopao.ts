import type { RouteRecordRaw } from 'vue-router';

const routes: RouteRecordRaw[] = [
  {
    meta: {
      icon: 'lucide:layout-dashboard',
      order: -1,
      title: '运营',
    },
    name: 'Paopao',
    path: '/paopao',
    redirect: '/overview',
    children: [
      {
        name: 'Overview',
        path: '/overview',
        component: () => import('#/views/paopao/overview/index.vue'),
        meta: {
          affixTab: true,
          icon: 'lucide:gauge',
          title: '概览',
        },
      },
      {
        name: 'Tenants',
        path: '/tenants',
        component: () => import('#/views/paopao/tenants/index.vue'),
        meta: {
          icon: 'lucide:building-2',
          title: '租户',
        },
      },
      {
        name: 'Users',
        path: '/users',
        component: () => import('#/views/paopao/users/index.vue'),
        meta: {
          icon: 'lucide:users',
          title: '用户',
        },
      },
      {
        name: 'Boards',
        path: '/boards',
        component: () => import('#/views/paopao/boards/index.vue'),
        meta: {
          icon: 'lucide:panels-top-left',
          title: '版块',
        },
      },
      {
        name: 'Posts',
        path: '/posts',
        component: () => import('#/views/paopao/posts/index.vue'),
        meta: {
          icon: 'lucide:file-check-2',
          title: '帖子审核',
        },
      },
      {
        name: 'Apps',
        path: '/apps',
        component: () => import('#/views/paopao/apps/index.vue'),
        meta: {
          icon: 'lucide:package',
          title: '软件审核',
        },
      },
      {
        name: 'OpsSlots',
        path: '/ops',
        component: () => import('#/views/paopao/ops/index.vue'),
        meta: {
          icon: 'lucide:gallery-horizontal',
          title: '运营位',
        },
      },
      {
        name: 'System',
        path: '/system',
        component: () => import('#/views/paopao/system/index.vue'),
        meta: {
          icon: 'lucide:settings',
          title: '系统',
        },
      },
    ],
  },
];

export default routes;
