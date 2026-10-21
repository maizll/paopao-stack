<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { api, ApiError } from '../api'
import { publishStatusText } from '../format'
import type { Page, SoftwareItem } from '../types'

const loading = ref(true)
const error = ref('')
const items = ref<SoftwareItem[]>([])
const total = ref(0)
const page = ref(1)

const columns = [
  { title: '名称', dataIndex: 'name', key: 'name' },
  { title: '分类', key: 'category', width: 140 },
  { title: '版本', key: 'version', width: 120 },
  { title: '下载', dataIndex: 'download_count', key: 'download_count', width: 100 },
  { title: '状态', key: 'status', width: 120 },
]

async function load(): Promise<void> {
  loading.value = true
  error.value = ''
  try {
    const result = await api<Page<SoftwareItem>>(`/api/v1/admin/apps?page=${page.value}&per_page=20`)
    items.value = result.data.items
    total.value = result.data.meta.total
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : '读取失败'
  } finally {
    loading.value = false
  }
}

function onTableChange(pagination: { current?: number }): void {
  page.value = pagination.current || 1
  void load()
}

onMounted(load)
</script>

<template>
  <div class="page-head">
    <div>
      <h1>软件审核</h1>
      <p>当前租户已登记的软件。创建、上下架和版本发布还是骨架占位，本页只读。</p>
    </div>
  </div>
  <a-alert
    type="info"
    show-icon
    message="写入接口返回「骨架占位」。审核列表可以看，不能在这里上架新包。"
    style="margin-bottom: 16px"
  />
  <a-card>
    <a-alert v-if="error" type="error" :message="error" show-icon />
    <a-table
      v-else
      :columns="columns"
      :data-source="items"
      :loading="loading"
      row-key="id"
      :locale="{ emptyText: '还没有软件' }"
      :pagination="{ current: page, pageSize: 20, total, showSizeChanger: false }"
      @change="onTableChange"
    >
      <template #bodyCell="{ column, record }">
        <template v-if="column.key === 'category'">{{ record.category?.name || '—' }}</template>
        <template v-else-if="column.key === 'version'">{{ record.latest_version?.version_name || '—' }}</template>
        <template v-else-if="column.key === 'status'">
          <a-tag>{{ publishStatusText[record.status] || record.status }}</a-tag>
        </template>
      </template>
    </a-table>
  </a-card>
</template>
