<script setup lang="ts">
import { message, Modal } from 'ant-design-vue'
import { onMounted, ref } from 'vue'
import { api, ApiError } from '../api'
import { roleText, userStatusText } from '../format'
import type { AdminUser, Me, Page } from '../types'

const loading = ref(true)
const error = ref('')
const items = ref<AdminUser[]>([])
const total = ref(0)
const page = ref(1)
const me = ref<Me | null>(null)
const actingId = ref(0)

const columns = [
  { title: '昵称', dataIndex: 'nickname', key: 'nickname' },
  { title: '邮箱', key: 'email' },
  { title: '角色', key: 'role', width: 140 },
  { title: '状态', key: 'status', width: 120 },
  { title: '操作', key: 'action', width: 120 },
]

async function load(): Promise<void> {
  loading.value = true
  error.value = ''
  try {
    me.value = (await api<Me>('/api/v1/auth/me')).data
    const result = await api<Page<AdminUser>>(`/api/v1/admin/users?page=${page.value}&per_page=20`)
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

async function changeStatus(user: AdminUser, ban: boolean): Promise<void> {
  actingId.value = user.id
  try {
    await api(`/api/v1/admin/users/${user.id}/${ban ? 'ban' : 'unban'}`, { method: 'POST' })
    message.success(ban ? '已封禁' : '已解除封禁')
    await load()
  } catch (cause) {
    message.error(cause instanceof ApiError ? cause.message : '操作失败')
  } finally {
    actingId.value = 0
  }
}

function confirmBan(user: AdminUser): void {
  Modal.confirm({
    title: `封禁 ${user.nickname}？`,
    content: '封禁后该账号不能再登录当前租户。',
    okText: '封禁',
    okType: 'danger',
    cancelText: '取消',
    onOk: () => changeStatus(user, true),
  })
}

onMounted(load)
</script>

<template>
  <div class="page-head">
    <div>
      <h1>用户</h1>
      <p>当前租户的账号。封禁只影响登录，不删除已经发布的帖子。</p>
    </div>
  </div>
  <a-card>
    <a-alert v-if="error" type="error" :message="error" show-icon />
    <a-table
      v-else
      :columns="columns"
      :data-source="items"
      :loading="loading"
      row-key="id"
      :locale="{ emptyText: '这个租户还没有用户' }"
      :pagination="{ current: page, pageSize: 20, total, showSizeChanger: false }"
      @change="onTableChange"
    >
      <template #bodyCell="{ column, record }">
        <template v-if="column.key === 'email'">{{ record.email || record.phone || '—' }}</template>
        <template v-else-if="column.key === 'role'">{{ roleText[record.role] || record.role }}</template>
        <template v-else-if="column.key === 'status'">
          <a-tag :color="record.status === 'banned' ? 'red' : 'green'">
            {{ userStatusText[record.status] || record.status }}
          </a-tag>
        </template>
        <template v-else-if="column.key === 'action'">
          <a-button
            v-if="record.status === 'banned'"
            type="link"
            size="small"
            :loading="actingId === record.id"
            @click="changeStatus(record, false)"
          >
            解封
          </a-button>
          <a-button
            v-else-if="record.id !== me?.id"
            type="link"
            danger
            size="small"
            :loading="actingId === record.id"
            @click="confirmBan(record)"
          >
            封禁
          </a-button>
          <span v-else class="who">当前账号</span>
        </template>
      </template>
    </a-table>
  </a-card>
</template>
