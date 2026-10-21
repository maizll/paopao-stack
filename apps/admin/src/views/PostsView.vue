<script setup lang="ts">
import { message } from 'ant-design-vue'
import { onMounted, ref } from 'vue'
import { api, ApiError } from '../api'
import { formatTime, postStatusColor, postStatusText } from '../format'
import type { Page, PostDetail, PostItem } from '../types'

const loading = ref(true)
const error = ref('')
const items = ref<PostItem[]>([])
const total = ref(0)
const page = ref(1)
const status = ref<string | undefined>(undefined)
const actingId = ref(0)
const drawerOpen = ref(false)
const detailLoading = ref(false)
const detail = ref<PostDetail | null>(null)
const rejectOpen = ref(false)
const rejectId = ref(0)
const reason = ref('')
const rejecting = ref(false)

const columns = [
  { title: '标题', dataIndex: 'title', key: 'title', ellipsis: true },
  { title: '版块', key: 'board', width: 140 },
  { title: '作者', key: 'author', width: 140 },
  { title: '状态', key: 'status', width: 110 },
  { title: '提交时间', key: 'created_at', width: 170 },
  { title: '操作', key: 'action', width: 200 },
]

const statusOptions = [
  { value: 'pending', label: '待审' },
  { value: 'published', label: '已发布' },
  { value: 'rejected', label: '已拒绝' },
]

async function load(): Promise<void> {
  loading.value = true
  error.value = ''
  const query = new URLSearchParams({ page: String(page.value), per_page: '20' })
  if (status.value) query.set('status', status.value)
  try {
    const result = await api<Page<PostItem>>(`/api/v1/admin/posts?${query.toString()}`)
    items.value = result.data.items
    total.value = result.data.meta.total
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : '读取失败'
  } finally {
    loading.value = false
  }
}

function onFilter(): void {
  page.value = 1
  void load()
}

function onTableChange(pagination: { current?: number }): void {
  page.value = pagination.current || 1
  void load()
}

async function openDetail(id: number): Promise<void> {
  drawerOpen.value = true
  detailLoading.value = true
  detail.value = null
  try {
    detail.value = (await api<PostDetail>(`/api/v1/admin/posts/${id}`)).data
  } catch (cause) {
    message.error(cause instanceof ApiError ? cause.message : '读取正文失败')
  } finally {
    detailLoading.value = false
  }
}

async function approve(id: number): Promise<void> {
  actingId.value = id
  try {
    await api(`/api/v1/admin/posts/${id}/approve`, { method: 'POST' })
    message.success('已通过，用户端公开列表可以看到这篇帖子')
    await load()
    if (detail.value?.id === id) await openDetail(id)
  } catch (cause) {
    message.error(cause instanceof ApiError ? cause.message : '操作失败')
  } finally {
    actingId.value = 0
  }
}

function askReject(id: number): void {
  rejectId.value = id
  reason.value = ''
  rejectOpen.value = true
}

async function submitReject(): Promise<void> {
  rejecting.value = true
  try {
    await api(`/api/v1/admin/posts/${rejectId.value}/reject`, {
      method: 'POST',
      body: JSON.stringify(reason.value ? { reason: reason.value } : {}),
    })
    message.success('已拒绝')
    rejectOpen.value = false
    await load()
    if (detail.value?.id === rejectId.value) await openDetail(rejectId.value)
  } catch (cause) {
    message.error(cause instanceof ApiError ? cause.message : '操作失败')
    throw cause
  } finally {
    rejecting.value = false
  }
}

onMounted(load)
</script>

<template>
  <div class="page-head">
    <div>
      <h1>帖子审核</h1>
      <p>成员发帖后先停在待审。通过之后才会出现在 App 和 H5 的信息流里。</p>
    </div>
    <a-select
      v-model:value="status"
      allow-clear
      placeholder="全部状态"
      style="width: 140px"
      :options="statusOptions"
      @change="onFilter"
    />
  </div>
  <a-card>
    <a-alert v-if="error" type="error" :message="error" show-icon />
    <a-table
      v-else
      :columns="columns"
      :data-source="items"
      :loading="loading"
      row-key="id"
      :locale="{ emptyText: '当前筛选下没有帖子' }"
      :pagination="{ current: page, pageSize: 20, total, showSizeChanger: false }"
      @change="onTableChange"
    >
      <template #bodyCell="{ column, record }">
        <template v-if="column.key === 'board'">{{ record.board?.name || '—' }}</template>
        <template v-else-if="column.key === 'author'">{{ record.author?.nickname || '—' }}</template>
        <template v-else-if="column.key === 'status'">
          <a-tag :color="postStatusColor[record.status] || 'default'">
            {{ postStatusText[record.status] || record.status }}
          </a-tag>
        </template>
        <template v-else-if="column.key === 'created_at'">{{ formatTime(record.created_at) }}</template>
        <template v-else-if="column.key === 'action'">
          <a-button type="link" size="small" @click="openDetail(record.id)">查看</a-button>
          <a-button
            v-if="record.status === 'pending'"
            type="link"
            size="small"
            :loading="actingId === record.id"
            @click="approve(record.id)"
          >
            通过
          </a-button>
          <a-button
            v-if="record.status !== 'rejected'"
            type="link"
            size="small"
            danger
            @click="askReject(record.id)"
          >
            拒绝
          </a-button>
        </template>
      </template>
    </a-table>
  </a-card>

  <a-drawer v-model:open="drawerOpen" title="帖子正文" width="480" :destroy-on-close="false">
    <a-spin :spinning="detailLoading">
      <template v-if="detail">
        <a-tag :color="postStatusColor[detail.status] || 'default'">
          {{ postStatusText[detail.status] || detail.status }}
        </a-tag>
        <h2 style="margin: 12px 0 8px">{{ detail.title }}</h2>
        <p style="color: rgba(0, 0, 0, 0.45)">
          {{ detail.board?.name || '未分区' }} · {{ detail.author?.nickname || '—' }} · {{ formatTime(detail.created_at) }}
        </p>
        <p style="white-space: pre-wrap; line-height: 1.7">{{ detail.content }}</p>
        <a-space v-if="detail.status === 'pending'">
          <a-button type="primary" :loading="actingId === detail.id" @click="approve(detail.id)">通过</a-button>
          <a-button danger @click="askReject(detail.id)">拒绝</a-button>
        </a-space>
      </template>
    </a-spin>
  </a-drawer>

  <a-modal
    v-model:open="rejectOpen"
    title="拒绝这篇帖子"
    ok-text="拒绝"
    cancel-text="取消"
    :confirm-loading="rejecting"
    @ok="submitReject"
  >
    <a-textarea v-model:value="reason" :maxlength="200" placeholder="原因，可留空" :rows="3" />
  </a-modal>
</template>
