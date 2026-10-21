<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { api, ApiError } from '../api'
import StateBlock from '../components/StateBlock.vue'
import type { Page, PostItem } from '../types'

const loading = ref(true)
const error = ref('')
const items = ref<PostItem[]>([])
const notice = ref('')

const statusText: Record<string, string> = {
  pending: '待审',
  published: '已发布',
  rejected: '已拒绝',
}

async function load(): Promise<void> {
  loading.value = true
  error.value = ''
  try {
    items.value = (await api<Page<PostItem>>('/api/v1/admin/posts')).data.items
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : '读取失败'
  } finally {
    loading.value = false
  }
}

async function approve(id: number): Promise<void> {
  notice.value = ''
  try {
    await api(`/api/v1/admin/posts/${id}/approve`, { method: 'POST' })
    notice.value = '已通过，用户端公开列表可以看到这篇帖子。'
    await load()
  } catch (cause) {
    notice.value = cause instanceof ApiError ? cause.message : '操作失败'
  }
}

onMounted(load)
</script>

<template>
  <section class="panel">
    <h2>帖子审核</h2>
    <p v-if="notice" class="state">{{ notice }}</p>
    <StateBlock :loading="loading" :error="error" :empty="items.length === 0" empty-text="当前租户还没有帖子">
      <table>
        <thead>
          <tr><th>标题</th><th>版块</th><th>作者</th><th>状态</th><th></th></tr>
        </thead>
        <tbody>
          <tr v-for="item in items" :key="item.id">
            <td>{{ item.title }}</td>
            <td>{{ item.board?.name || '—' }}</td>
            <td>{{ item.author?.nickname || '—' }}</td>
            <td>{{ statusText[item.status] || item.status }}</td>
            <td>
              <button v-if="item.status === 'pending'" class="tiny" type="button" @click="approve(item.id)">通过</button>
            </td>
          </tr>
        </tbody>
      </table>
    </StateBlock>
  </section>
</template>
