<script setup lang="ts">
import {
  AppstoreOutlined,
  AuditOutlined,
  DashboardOutlined,
  PictureOutlined,
  SettingOutlined,
  ShopOutlined,
  TeamOutlined,
  UserOutlined,
} from '@ant-design/icons-vue'
import { computed, h, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { api, setToken, tenantSlug } from '../api'
import { roleText } from '../format'
import type { Me } from '../types'

const route = useRoute()
const router = useRouter()
const collapsed = ref(false)
const me = ref<Me | null>(null)

const menuItems = [
  { key: '/', icon: () => h(DashboardOutlined), label: '概览' },
  { key: '/tenants', icon: () => h(TeamOutlined), label: '租户' },
  { key: '/users', icon: () => h(UserOutlined), label: '用户' },
  { key: '/boards', icon: () => h(AppstoreOutlined), label: '版块' },
  { key: '/posts', icon: () => h(AuditOutlined), label: '帖子审核' },
  { key: '/apps', icon: () => h(ShopOutlined), label: '软件审核' },
  { key: '/ops', icon: () => h(PictureOutlined), label: '运营位' },
  { key: '/system', icon: () => h(SettingOutlined), label: '系统' },
]

const selected = computed(() => (route.path === '/' ? '/' : route.path))
const pageTitle = computed(() => menuItems.find((item) => item.key === selected.value)?.label ?? '运营后台')
const roleLabel = computed(() => {
  const role = me.value?.roles[0]
  return role ? roleText[role] || role : ''
})

function onMenu(info: { key: string | number }): void {
  void router.push(String(info.key))
}

onMounted(async () => {
  try {
    me.value = (await api<Me>('/api/v1/auth/me')).data
  } catch {
    setToken(null)
    await router.push('/login')
  }
})

async function logout(): Promise<void> {
  try {
    await api('/api/v1/admin/auth/logout', { method: 'POST' })
  } catch {
    // 本地会话仍然清掉，避免卡在失效 token。
  }
  setToken(null)
  await router.push('/login')
}
</script>

<template>
  <a-layout class="admin-shell">
    <a-layout-sider
      v-model:collapsed="collapsed"
      collapsible
      theme="dark"
      width="220"
      breakpoint="lg"
    >
      <div class="brand" :class="{ collapsed }" @click="router.push('/')">
        <span class="mark">泡</span>
        <span v-if="!collapsed" class="brand-name">泡泡栈</span>
      </div>
      <a-menu
        theme="dark"
        mode="inline"
        :selected-keys="[selected]"
        :items="menuItems"
        @click="onMenu"
      />
    </a-layout-sider>
    <a-layout>
      <a-layout-header class="admin-header">
        <div class="header-title">{{ pageTitle }}</div>
        <div class="header-meta">
          <a-tag color="blue">{{ tenantSlug() || '平台' }}</a-tag>
          <span v-if="me" class="who">{{ me.nickname }}<template v-if="roleLabel && roleLabel !== me.nickname"> · {{ roleLabel }}</template></span>
          <a-button type="link" @click="logout">退出</a-button>
        </div>
      </a-layout-header>
      <a-layout-content class="admin-content">
        <router-view />
      </a-layout-content>
    </a-layout>
  </a-layout>
</template>
