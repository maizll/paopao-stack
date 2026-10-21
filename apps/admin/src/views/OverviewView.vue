<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { api, ApiError, tenantSlug } from '../api'
import { formatTime } from '../format'
import type { BoardItem, Health, Page, PostItem, SiteConfig, SoftwareItem } from '../types'

const router = useRouter()
const loading = ref(true)
const error = ref('')
const queueError = ref('')
const health = ref<Health | null>(null)
const config = ref<SiteConfig | null>(null)
const pending = ref<PostItem[]>([])
const pendingTotal = ref(0)
const publishedTotal = ref(0)
const boardTotal = ref(0)
const appTotal = ref(0)

const columns = [
  { title: '标题', dataIndex: 'title', key: 'title', ellipsis: true },
  { title: '作者', key: 'author', width: 140 },
  { title: '提交时间', key: 'created_at', width: 170 },
  { title: '', key: 'action', width: 88 },
]

onMounted(async () => {
  try {
    const [healthRes, configRes] = await Promise.all([
      api<Health>('/api/v1/system/health'),
      api<SiteConfig>('/api/v1/system/config'),
    ])
    health.value = healthRes.data
    config.value = configRes.data
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : '读取失败'
  }

  try {
    const [pendingRes, publishedRes, boardsRes, appsRes] = await Promise.all([
      api<Page<PostItem>>('/api/v1/admin/posts?status=pending&per_page=5'),
      api<Page<PostItem>>('/api/v1/admin/posts?status=published&per_page=1'),
      api<BoardItem[]>('/api/v1/admin/boards'),
      api<Page<SoftwareItem>>('/api/v1/admin/apps?per_page=1'),
    ])
    pending.value = pendingRes.data.items
    pendingTotal.value = pendingRes.data.meta.total
    publishedTotal.value = publishedRes.data.meta.total
    boardTotal.value = boardsRes.data.length
    appTotal.value = appsRes.data.meta.total
  } catch (cause) {
    queueError.value = cause instanceof ApiError ? cause.message : '审核队列读取失败'
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <a-spin :spinning="loading">
    <div class="page-head">
      <div>
        <h1>工作台</h1>
        <p>
          {{ config?.tenant?.name || (tenantSlug() ? tenantSlug() : '平台视角') }}
          · 数据库{{ health?.database === 'ok' ? '可连接' : '异常' }}
          · {{ health?.app }} {{ health?.version }}
        </p>
      </div>
      <a-button type="primary" @click="router.push('/posts')">处理帖子</a-button>
    </div>
    <a-alert v-if="error" type="error" :message="error" show-icon style="margin-bottom: 16px" />
    <a-row :gutter="[16, 16]">
      <a-col :xs="12" :lg="6">
        <a-card><a-statistic title="待审帖子" :value="pendingTotal" /></a-card>
      </a-col>
      <a-col :xs="12" :lg="6">
        <a-card><a-statistic title="已发布帖子" :value="publishedTotal" /></a-card>
      </a-col>
      <a-col :xs="12" :lg="6">
        <a-card><a-statistic title="版块" :value="boardTotal" /></a-card>
      </a-col>
      <a-col :xs="12" :lg="6">
        <a-card><a-statistic title="软件" :value="appTotal" /></a-card>
      </a-col>
    </a-row>
    <a-card title="待审队列" style="margin-top: 16px">
      <a-alert v-if="queueError" type="warning" :message="queueError" show-icon />
      <a-table
        v-else
        :columns="columns"
        :data-source="pending"
        :pagination="false"
        row-key="id"
        :locale="{ emptyText: '没有待审帖子' }"
      >
        <template #bodyCell="{ column, record }">
          <template v-if="column.key === 'author'">{{ record.author?.nickname || '—' }}</template>
          <template v-else-if="column.key === 'created_at'">{{ formatTime(record.created_at) }}</template>
          <template v-else-if="column.key === 'action'">
            <a-button type="link" size="small" @click="router.push('/posts')">审核</a-button>
          </template>
        </template>
      </a-table>
    </a-card>
  </a-spin>
</template>
