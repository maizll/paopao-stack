<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { api, ApiError } from '../api'
import StateBlock from '../components/StateBlock.vue'
import type { Page, SoftwareItem } from '../types'

const loading = ref(true)
const error = ref('')
const items = ref<SoftwareItem[]>([])

onMounted(async () => {
  try {
    items.value = (await api<Page<SoftwareItem>>('/api/v1/admin/apps')).data.items
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : '读取失败'
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <section class="panel">
    <h2>软件</h2>
    <p class="state">列表来自当前租户。创建、上下架和版本发布仍是骨架占位。</p>
    <StateBlock :loading="loading" :error="error" :empty="items.length === 0" empty-text="还没有软件">
      <table>
        <thead>
          <tr><th>名称</th><th>版本</th><th>下载</th><th>状态</th></tr>
        </thead>
        <tbody>
          <tr v-for="item in items" :key="item.id">
            <td>{{ item.name }}</td>
            <td>{{ item.latest_version?.version_name || '—' }}</td>
            <td>{{ item.download_count }}</td>
            <td>{{ item.status }}</td>
          </tr>
        </tbody>
      </table>
    </StateBlock>
  </section>
</template>
