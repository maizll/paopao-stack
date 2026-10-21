<script setup lang="ts">
import { message } from 'ant-design-vue'
import { onMounted, reactive, ref } from 'vue'
import { api, ApiError } from '../api'
import { publishStatusText } from '../format'
import type { Me, Page, TenantItem } from '../types'

const loading = ref(true)
const error = ref('')
const items = ref<TenantItem[]>([])
const isPlatform = ref(false)
const submitting = ref(false)
const form = reactive({ name: '', slug: '' })

const columns = [
  { title: '名称', dataIndex: 'name', key: 'name' },
  { title: '标识', dataIndex: 'slug', key: 'slug' },
  { title: '状态', key: 'status', width: 120 },
  { title: '套餐', dataIndex: 'plan_code', key: 'plan_code', width: 120 },
]

async function load(): Promise<void> {
  loading.value = true
  error.value = ''
  try {
    const me = (await api<Me>('/api/v1/auth/me')).data
    isPlatform.value = me.roles.includes('platform_super_admin')
    items.value = isPlatform.value
      ? (await api<Page<TenantItem>>('/api/v1/admin/tenants', {}, false)).data.items
      : []
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : '读取失败'
  } finally {
    loading.value = false
  }
}

async function createTenant(): Promise<void> {
  submitting.value = true
  try {
    await api(
      '/api/v1/admin/tenants',
      {
        method: 'POST',
        body: JSON.stringify({ name: form.name, slug: form.slug, plan_code: 'free' }),
      },
      false,
    )
    form.name = ''
    form.slug = ''
    message.success('租户已开通')
    await load()
  } catch (cause) {
    message.error(cause instanceof ApiError ? cause.message : '开通失败')
  } finally {
    submitting.value = false
  }
}

onMounted(load)
</script>

<template>
  <div class="page-head">
    <div>
      <h1>租户</h1>
      <p>平台超管开通圈子。租户管理员只能管理自己的租户，不能看这份名单。</p>
    </div>
  </div>
  <a-spin :spinning="loading">
  <a-alert
    v-if="!loading && !isPlatform"
    type="info"
    show-icon
    message="租户开通只对平台超管开放。请用 super@paopao.local 登录，租户标识留空。"
  />
  <div v-else-if="isPlatform" class="stack">
    <a-card title="开通租户">
      <a-form layout="inline" :model="form" @finish="createTenant">
        <a-form-item label="名称" name="name" :rules="[{ required: true, message: '填写名称' }]">
          <a-input v-model:value="form.name" placeholder="例如 海边论坛" />
        </a-form-item>
        <a-form-item label="标识" name="slug" :rules="[{ required: true, message: '填写标识' }]">
          <a-input v-model:value="form.slug" placeholder="小写字母，例如 seaside" />
        </a-form-item>
        <a-form-item>
          <a-button type="primary" html-type="submit" :loading="submitting">开通</a-button>
        </a-form-item>
      </a-form>
    </a-card>
    <a-card title="租户列表">
      <a-alert v-if="error" type="error" :message="error" show-icon />
      <a-table
        v-else
        :columns="columns"
        :data-source="items"
        :loading="loading"
        row-key="id"
        :pagination="false"
        :locale="{ emptyText: '还没有租户' }"
      >
        <template #bodyCell="{ column, record }">
          <template v-if="column.key === 'status'">
            <a-tag :color="record.status === 'active' ? 'green' : 'default'">
              {{ publishStatusText[record.status] || record.status }}
            </a-tag>
          </template>
        </template>
      </a-table>
    </a-card>
  </div>
  </a-spin>
</template>
