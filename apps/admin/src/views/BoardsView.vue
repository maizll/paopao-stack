<script setup lang="ts">
import { message } from 'ant-design-vue'
import { onMounted, reactive, ref } from 'vue'
import { api, ApiError } from '../api'
import { publishStatusText } from '../format'
import type { BoardItem } from '../types'

const loading = ref(true)
const error = ref('')
const items = ref<BoardItem[]>([])
const submitting = ref(false)
const form = reactive({ name: '', slug: '' })

const columns = [
  { title: '名称', dataIndex: 'name', key: 'name' },
  { title: '标识', dataIndex: 'slug', key: 'slug' },
  { title: '帖子', dataIndex: 'post_count', key: 'post_count', width: 100 },
  { title: '状态', key: 'status', width: 120 },
]

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
  submitting.value = true
  try {
    await api('/api/v1/admin/boards', {
      method: 'POST',
      body: JSON.stringify({ name: form.name, slug: form.slug, status: 'published' }),
    })
    form.name = ''
    form.slug = ''
    message.success('版块已创建')
    await load()
  } catch (cause) {
    message.error(cause instanceof ApiError ? cause.message : '创建失败')
  } finally {
    submitting.value = false
  }
}

onMounted(load)
</script>

<template>
  <div class="page-head">
    <div>
      <h1>版块</h1>
      <p>版块是用户端社区的分区。这里只维护结构，不展示帖子正文。</p>
    </div>
  </div>
  <div class="stack">
    <a-card title="新建版块">
      <a-form layout="inline" :model="form" @finish="createBoard">
        <a-form-item label="名称" name="name" :rules="[{ required: true, message: '填写名称' }]">
          <a-input v-model:value="form.name" placeholder="例如 反馈" />
        </a-form-item>
        <a-form-item label="标识" name="slug" :rules="[{ required: true, message: '填写标识' }]">
          <a-input v-model:value="form.slug" placeholder="例如 feedback" />
        </a-form-item>
        <a-form-item>
          <a-button type="primary" html-type="submit" :loading="submitting">创建</a-button>
        </a-form-item>
      </a-form>
    </a-card>
    <a-card>
      <a-alert v-if="error" type="error" :message="error" show-icon />
      <a-table
        v-else
        :columns="columns"
        :data-source="items"
        :loading="loading"
        row-key="id"
        :pagination="false"
        :locale="{ emptyText: '当前租户还没有版块' }"
      >
        <template #bodyCell="{ column, record }">
          <template v-if="column.key === 'status'">
            {{ publishStatusText[record.status] || record.status }}
          </template>
        </template>
      </a-table>
    </a-card>
  </div>
</template>
