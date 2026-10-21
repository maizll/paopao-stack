<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { api, ApiError, setToken, tenantSlug } from '../api'
import type { Me } from '../types'

const router = useRouter()
const me = ref<Me | null>(null)
const error = ref('')

const roleText: Record<string, string> = {
  platform_super_admin: '平台超管',
  tenant_admin: '租户管理员',
  member: '成员',
}

onMounted(async () => {
  try {
    me.value = (await api<Me>('/api/v1/auth/me')).data
  } catch (cause) {
    if (cause instanceof ApiError && (cause.status === 401 || cause.status === 403)) {
      setToken(null)
      await router.push('/login')
      return
    }
    error.value = cause instanceof ApiError ? cause.message : '无法读取登录态'
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
  <div class="shell">
    <aside class="side">
      <div>
        <strong>泡泡栈</strong>
        <span>{{ me ? roleText[me.roles[0]] || me.roles[0] : '后台' }}</span>
        <span v-if="tenantSlug()"> · {{ tenantSlug() }}</span>
      </div>
      <nav>
        <router-link to="/">概览</router-link>
        <router-link to="/tenants">租户</router-link>
        <router-link to="/boards">版块</router-link>
        <router-link to="/posts">帖子</router-link>
        <router-link to="/apps">软件</router-link>
        <router-link to="/ops">运营</router-link>
      </nav>
      <button class="ghost" type="button" @click="logout">退出</button>
      <p v-if="error" class="state error">{{ error }}</p>
    </aside>
    <main class="main">
      <router-view />
    </main>
  </div>
</template>
