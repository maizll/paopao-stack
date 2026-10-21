<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { api, ApiError } from '../api'
import StateBlock from '../components/StateBlock.vue'
import type { Me, Page, TenantItem } from '../types'

const loading = ref(true)
const error = ref('')
const items = ref<TenantItem[]>([])
const name = ref('')
const slug = ref('')
const notice = ref('')
const isPlatform = ref(false)

async function load(): Promise<void> {
  loading.value = true
  error.value = ''
  try {
    const me = (await api<Me>('/api/v1/auth/me')).data
    isPlatform.value = me.roles.includes('platform_super_admin')
    if (!isPlatform.value) {
      items.value = []
      return
    }
    items.value = (await api<Page<TenantItem>>('/api/v1/admin/tenants', {}, false)).data.items
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : '读取失败'
  } finally {
    loading.value = false
  }
}

async function createTenant(): Promise<void> {
  notice.value = ''
  try {
    await api('/api/v1/admin/tenants', {
      method: 'POST',
      body: JSON.stringify({ name: name.value, slug: slug.value, plan_code: 'free' }),
    }, false)
    name.value = ''
    slug.value = ''
    notice.value = '租户已开通，状态为 active，套餐 free。'
    await load()
  } catch (cause) {
    notice.value = cause instanceof ApiError ? cause.message : '开通失败'
  }
}

onMounted(load)
</script>

<template>
  <section class="panel">
    <h2>租户</h2>
    <p v-if="!loading && !isPlatform" class="state">租户开通只对平台超管开放。请用 super@paopao.local 登录，租户标识留空。</p>
    <form v-if="isPlatform" @submit.prevent="createTenant">
      <label>名称<input v-model="name" required /></label>
      <label>标识<input v-model="slug" required placeholder="小写字母和连字符" /></label>
      <button class="primary" type="submit">开通</button>
      <p v-if="notice" class="state">{{ notice }}</p>
    </form>
  </section>
  <section v-if="isPlatform || loading || error" class="panel">
    <StateBlock :loading="loading" :error="error" :empty="items.length === 0" empty-text="还没有租户">
      <table v-if="isPlatform">
        <thead>
          <tr><th>名称</th><th>标识</th><th>状态</th><th>套餐</th></tr>
        </thead>
        <tbody>
          <tr v-for="item in items" :key="item.id">
            <td>{{ item.name }}</td>
            <td>{{ item.slug }}</td>
            <td>{{ item.status }}</td>
            <td>{{ item.plan_code }}</td>
          </tr>
        </tbody>
      </table>
    </StateBlock>
  </section>
</template>
