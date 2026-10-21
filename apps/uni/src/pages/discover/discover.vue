<script setup lang="ts">
import { computed, ref } from 'vue'
import { onPullDownRefresh, onShow } from '@dcloudio/uni-app'
import { ApiError, request } from '../../utils/api'
import type { Category, Page, SoftwareItem } from '../../utils/types'

const loading = ref(true)
const error = ref('')
const apps = ref<SoftwareItem[]>([])
const categories = ref<Category[]>([])
const categoryId = ref(0)

const visible = computed(() => {
  if (!categoryId.value) return apps.value
  return apps.value.filter((item) => item.category?.id === categoryId.value)
})

async function load(): Promise<void> {
  loading.value = true
  error.value = ''
  try {
    const [appPage, categoryList] = await Promise.all([
      request<Page<SoftwareItem>>('/apps', { data: { per_page: 20 } }),
      request<Category[]>('/apps/categories'),
    ])
    apps.value = appPage.items
    categories.value = categoryList
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : '读取失败'
  } finally {
    loading.value = false
  }
}

onShow(() => {
  void load()
})

onPullDownRefresh(async () => {
  await load()
  uni.stopPullDownRefresh()
})
</script>

<template>
  <view class="page">
    <text class="lead">租户软件架。下载和支付还没接上，这里只看目录。</text>
    <scroll-view v-if="categories.length" scroll-x class="chips" :show-scrollbar="false">
      <view class="chip" :class="{ on: categoryId === 0 }" @click="categoryId = 0">全部</view>
      <view
        v-for="item in categories"
        :key="item.id"
        class="chip"
        :class="{ on: categoryId === item.id }"
        @click="categoryId = item.id"
      >
        {{ item.name }}
      </view>
    </scroll-view>
    <view v-if="loading" class="state">正在读取软件目录…</view>
    <view v-else-if="error" class="state">
      <text class="state-title">没读出来</text>
      <text class="state-text">{{ error }}</text>
      <button class="btn" @click="load">重试</button>
    </view>
    <view v-else-if="visible.length === 0" class="state">
      <text class="state-title">架上还没有软件</text>
      <text class="state-text">管理员登记并发布之后，会出现在这里。</text>
    </view>
    <view v-else class="list">
      <view v-for="item in visible" :key="item.id" class="soft">
        <view class="mark">{{ item.name.slice(0, 1) }}</view>
        <view class="body">
          <text class="name">{{ item.name }}</text>
          <text class="summary">{{ item.summary || '暂无简介' }}</text>
          <text class="meta">
            {{ item.latest_version?.version_name || '未标版本' }} · {{ item.download_count }} 次下载
          </text>
        </view>
      </view>
    </view>
  </view>
</template>

<style scoped>
.page {
  min-height: 100vh;
  padding: 12px 12px 28px;
  box-sizing: border-box;
}

.lead {
  display: block;
  color: #6f675f;
  font-size: 13px;
  margin: 4px 4px 12px;
}

.chips {
  white-space: nowrap;
  height: 40px;
  margin-bottom: 12px;
}

.chip {
  display: inline-flex;
  align-items: center;
  height: 32px;
  padding: 0 12px;
  margin-right: 8px;
  border-radius: 999px;
  background: #fff;
  font-size: 13px;
}

.chip.on {
  background: #1f1a17;
  color: #fff;
}

.list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.soft {
  display: flex;
  gap: 12px;
  padding: 14px;
  background: #fff;
  border-radius: 16px;
}

.mark {
  width: 48px;
  height: 48px;
  border-radius: 14px;
  background: #ff5c39;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  font-weight: 700;
  flex: none;
}

.body {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.name {
  font-size: 16px;
  font-weight: 650;
}

.summary,
.meta {
  color: #6f675f;
  font-size: 13px;
  line-height: 1.4;
}

.state {
  padding: 48px 8px;
  text-align: center;
  color: #6f675f;
}

.state-title {
  display: block;
  color: #1f1a17;
  font-size: 16px;
  font-weight: 650;
  margin-bottom: 6px;
}

.state-text {
  display: block;
}

.btn {
  margin-top: 16px;
  background: #ff5c39;
  color: #fff;
  border: none;
  border-radius: 999px;
}

.btn::after {
  border: none;
}
</style>
