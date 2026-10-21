<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { api, ApiError, setTenantSlug, setToken } from '../api'
import type { Me } from '../types'

const router = useRouter()
const account = ref('admin@demo.paopao.local')
const password = ref('password')
const slug = ref('demo')
const error = ref('')
const submitting = ref(false)

function useDemo(kind: 'tenant' | 'platform'): void {
  if (kind === 'platform') {
    account.value = 'super@paopao.local'
    slug.value = ''
  } else {
    account.value = 'admin@demo.paopao.local'
    slug.value = 'demo'
  }
  password.value = 'password'
}

async function submit(): Promise<void> {
  submitting.value = true
  error.value = ''
  setTenantSlug(slug.value)
  setToken(null)
  try {
    const result = await api<{ access_token: string }>('/api/v1/admin/auth/login', {
      method: 'POST',
      body: JSON.stringify({ account: account.value, password: password.value }),
    })
    setToken(result.data.access_token)
    await api<Me>('/api/v1/auth/me')
    await router.push('/')
  } catch (cause) {
    setToken(null)
    error.value = cause instanceof ApiError ? cause.message : '登录失败'
  } finally {
    submitting.value = false
  }
}
</script>

<template>
  <main class="login-page">
    <section class="login-card">
      <h1>泡泡栈后台</h1>
      <p>平台超管开通租户，租户管理员审核圈子和商店。这是骨架，不是完整运营台。</p>
      <form @submit.prevent="submit">
        <label>
          租户标识
          <input v-model="slug" placeholder="平台超管可留空" autocomplete="off" />
        </label>
        <label>
          账号
          <input v-model="account" autocomplete="username" />
        </label>
        <label>
          密码
          <input v-model="password" type="password" autocomplete="current-password" />
        </label>
        <button class="primary" type="submit" :disabled="submitting">进入后台</button>
        <p v-if="error" class="state error">{{ error }}</p>
      </form>
      <div class="row">
        <button class="ghost" type="button" @click="useDemo('tenant')">填入租户管理员</button>
        <button class="ghost" type="button" @click="useDemo('platform')">填入平台超管</button>
      </div>
    </section>
  </main>
</template>
