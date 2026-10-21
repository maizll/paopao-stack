<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { api, ApiError } from '../api'
import StateBlock from '../components/StateBlock.vue'
import type { BoardItem } from '../types'

const loading = ref(true)
const error = ref('')
const items = ref<BoardItem[]>([])
const name = ref('')
const slug = ref('')
const notice = ref('')

async function load(): Promise<void> {
  loading.value = true
  error.value = ''
  try {
    items.value = (await api<BoardItem[]>('/api/v1/admin/boards')).data
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : '读取失败'
  } finally {
    loading.value = false
  }
}

async function createBoard(): Promise<void> {
  notice.value = ''
  try {
    await api('/api/v1/admin/boards', {
      method: 'POST',
      body: JSON.stringify({ name: name.value, slug: slug.value, status: 'published' }),
    })
    name.value = ''
    slug.value = ''
    notice.value = '版块已创建。'
    await load()
  } catch (cause) {
    notice.value = cause instanceof ApiError ? cause.message : '创建失败'
  }
}

onMounted(load)
</script>

<template>
  <section class="panel">
    <h2>新建版块</h2>
    <form @submit.prevent="createBoard">
      <label>名称<input v-model="name" required /></label>
      <label>标识<input v-model="slug" required placeholder="例如 feedback" /></label>
      <button class="primary" type="submit">创建</button>
      <p v-if="notice" class="state">{{ notice }}</p>
    </form>
  </section>
  <section class="panel">
    <h2>版块</h2>
    <StateBlock :loading="loading" :error="error" :empty="items.length === 0" empty-text="当前租户还没有版块">
      <table>
        <thead>
          <tr><th>名称</th><th>标识</th><th>帖子</th><th>状态</th></tr>
        </thead>
        <tbody>
          <tr v-for="item in items" :key="item.id">
            <td>{{ item.name }}</td>
            <td>{{ item.slug }}</td>
            <td>{{ item.post_count }}</td>
            <td>{{ item.status }}</td>
          </tr>
        </tbody>
      </table>
    </StateBlock>
  </section>
</template>
