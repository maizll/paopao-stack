<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { api, ApiError } from '../api'
import type { BannerItem, GridItem } from '../types'

const loading = ref(true)
const error = ref('')
const banners = ref<BannerItem[]>([])
const grid = ref<GridItem[]>([])

const bannerColumns = [
  { title: '标题', key: 'title' },
  { title: '链接类型', dataIndex: 'link_type', key: 'link_type', width: 160 },
  { title: '排序', dataIndex: 'sort', key: 'sort', width: 100 },
]

const gridColumns = [
  { title: '标题', dataIndex: 'title', key: 'title' },
  { title: '角标', key: 'badge', width: 140 },
  { title: '排序', dataIndex: 'sort', key: 'sort', width: 100 },
]

onMounted(async () => {
  try {
    const [bannerRes, gridRes] = await Promise.all([
      api<BannerItem[]>('/api/v1/admin/ops/banners'),
      api<GridItem[]>('/api/v1/admin/ops/grid'),
    ])
    banners.value = bannerRes.data
    grid.value = gridRes.data
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : '读取失败'
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div class="page-head">
    <div>
      <h1>运营位</h1>
      <p>Banner 和九宫格会出现在用户端首页。写入接口仍是骨架占位，这里只核对现有配置。</p>
    </div>
  </div>
  <a-alert
    v-if="error"
    type="error"
    :message="error"
    show-icon
  />
  <div v-else class="stack">
    <a-card title="Banner">
      <a-table
        :columns="bannerColumns"
        :data-source="banners"
        :loading="loading"
        row-key="id"
        :pagination="false"
        :locale="{ emptyText: '还没有 Banner' }"
      >
        <template #bodyCell="{ column, record }">
          <template v-if="column.key === 'title'">{{ record.title || '未命名' }}</template>
        </template>
      </a-table>
    </a-card>
    <a-card title="九宫格">
      <a-table
        :columns="gridColumns"
        :data-source="grid"
        :loading="loading"
        row-key="id"
        :pagination="false"
        :locale="{ emptyText: '还没有九宫格入口' }"
      >
        <template #bodyCell="{ column, record }">
          <template v-if="column.key === 'badge'">{{ record.badge || '—' }}</template>
        </template>
      </a-table>
    </a-card>
  </div>
</template>
