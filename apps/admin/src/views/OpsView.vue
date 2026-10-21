<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { api, ApiError } from '../api'
import type { BannerItem, GridItem } from '../types'

const loading = ref(true)
const error = ref('')
const banners = ref<BannerItem[]>([])
const grid = ref<GridItem[]>([])

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
  <section class="panel">
    <h2>Banner</h2>
    <p v-if="loading" class="state">正在读取…</p>
    <p v-else-if="error" class="state error">{{ error }}</p>
    <p v-else-if="!banners.length" class="state">还没有 Banner。</p>
    <table v-else>
      <thead>
        <tr><th>标题</th><th>链接类型</th><th>排序</th></tr>
      </thead>
      <tbody>
        <tr v-for="item in banners" :key="item.id">
          <td>{{ item.title || '未命名' }}</td>
          <td>{{ item.link_type }}</td>
          <td>{{ item.sort }}</td>
        </tr>
      </tbody>
    </table>
  </section>
  <section class="panel">
    <h2>九宫格</h2>
    <p v-if="!loading && !error && !grid.length" class="state">还没有九宫格入口。</p>
    <table v-else-if="grid.length">
      <thead>
        <tr><th>标题</th><th>角标</th><th>排序</th></tr>
      </thead>
      <tbody>
        <tr v-for="item in grid" :key="item.id">
          <td>{{ item.title }}</td>
          <td>{{ item.badge || '—' }}</td>
          <td>{{ item.sort }}</td>
        </tr>
      </tbody>
    </table>
    <p class="state">写入接口返回骨架占位，本页只读。</p>
  </section>
</template>
