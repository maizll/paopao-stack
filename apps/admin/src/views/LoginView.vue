<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { api, ApiError, setTenantSlug, setToken } from '../api'
import type { Me } from '../types'

const router = useRouter()
const submitting = ref(false)
const error = ref('')
const form = reactive({
  slug: 'demo',
  account: 'admin@demo.paopao.local',
  password: 'password',
})

function useDemo(kind: 'tenant' | 'platform'): void {
  if (kind === 'platform') {
    form.account = 'super@paopao.local'
    form.slug = ''
  } else {
    form.account = 'admin@demo.paopao.local'
    form.slug = 'demo'
  }
  form.password = 'password'
  error.value = ''
}

async function submit(): Promise<void> {
  submitting.value = true
  error.value = ''
  setTenantSlug(form.slug)
  setToken(null)
  try {
    const result = await api<{ access_token: string }>('/api/v1/admin/auth/login', {
      method: 'POST',
      body: JSON.stringify({ account: form.account, password: form.password }),
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
    <a-card class="login-card" :bordered="false">
      <div class="login-mark">泡</div>
      <h1>泡泡栈运营后台</h1>
      <p class="lead">开通租户，审核帖子和软件，查看运营位。社区内容在 App 里看，不在这里浏览。</p>
      <a-form layout="vertical" :model="form" @finish="submit">
        <a-form-item label="租户标识" name="slug">
          <a-input v-model:value="form.slug" placeholder="平台超管可留空，审核演示内容时填 demo" autocomplete="off" />
        </a-form-item>
        <a-form-item label="账号" name="account" :rules="[{ required: true, message: '请填写账号' }]">
          <a-input v-model:value="form.account" autocomplete="username" />
        </a-form-item>
        <a-form-item label="密码" name="password" :rules="[{ required: true, message: '请填写密码' }]">
          <a-input-password v-model:value="form.password" autocomplete="current-password" />
        </a-form-item>
        <a-alert v-if="error" type="error" :message="error" show-icon style="margin-bottom: 16px" />
        <a-button type="primary" html-type="submit" block :loading="submitting">进入后台</a-button>
      </a-form>
      <a-space style="margin-top: 16px" wrap>
        <a-button @click="useDemo('tenant')">填入租户管理员</a-button>
        <a-button @click="useDemo('platform')">填入平台超管</a-button>
      </a-space>
    </a-card>
  </main>
</template>
