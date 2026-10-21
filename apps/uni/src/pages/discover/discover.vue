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
const keyword = ref('')

const visible = computed(() => {
  const word = keyword.value.trim()
  return apps.value.filter((item) => {
    if (categoryId.value && item.category?.id !== categoryId.value) return false
    if (!word) return true
    return `${item.name} ${item.summary || ''}`.includes(word)
  })
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

function obtain(item: SoftwareItem): void {
  uni.showToast({ title: `${item.name} 的下载还没开放`, icon: 'none' })
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
    <app-nav title="发现" />
    <view class="search">
      <uni-icons type="search" size="16" color="#9ca3af" />
      <input v-model="keyword" class="search-input" placeholder="搜索软件" placeholder-class="ph" />
    </view>
    <scroll-view v-if="categories.length" scroll-x class="cat-scroll" :show-scrollbar="false">
      <view class="cat-row">
        <view class="cat" :class="{ on: categoryId === 0 }" @click="categoryId = 0">全部</view>
        <view
          v-for="item in categories"
          :key="item.id"
          class="cat"
          :class="{ on: categoryId === item.id }"
          @click="categoryId = item.id"
        >
          {{ item.name }}
        </view>
      </view>
    </scroll-view>

    <view v-if="loading" class="state">正在读取软件架…</view>
    <view v-else-if="error" class="state">
      <text class="state-title">没读出来</text>
      <text class="state-text">{{ error }}</text>
      <button class="btn" @click="load">重试</button>
    </view>
    <view v-else-if="visible.length === 0" class="state">
      <text class="state-title">架上还没有软件</text>
      <text class="state-text">管理员发布之后，会出现在这里。</text>
    </view>
    <view v-else class="list">
      <view v-for="item in visible" :key="item.id" class="soft">
        <view class="mark">{{ item.name.slice(0, 1) }}</view>
        <view class="body">
          <text class="name">{{ item.name }}</text>
          <text class="summary">{{ item.summary || '暂无简介' }}</text>
          <text class="meta">{{ item.latest_version?.version_name || '未标版本' }} · {{ item.download_count }} 次获取</text>
        </view>
        <view class="get" @click="obtain(item)">获取</view>
      </view>
    </view>
  </view>
</template>

<style scoped>
.page {
  min-height: 100vh;
  background: #f8fafc;
}

.search {
  margin: 8px 12px;
  height: 36px;
  border-radius: 18px;
  background: #fff;
  border: 1px solid #f0f0f0;
  padding: 0 14px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.search-input {
  flex: 1;
  height: 36px;
  font-size: 14px;
}

.ph {
  color: #9ca3af;
}

.cat-scroll {
  height: 40px;
  white-space: nowrap;
}

.cat-row {
  display: inline-flex;
  align-items: center;
  height: 40px;
  padding: 0 12px;
}

.cat {
  display: inline-flex;
  align-items: center;
  height: 28px;
  margin-right: 8px;
  padding: 0 12px;
  border-radius: 14px;
  background: #fff;
  color: #606266;
  font-size: 13px;
}

.cat.on {
  background: #16160e;
  color: #fff;
}

.list {
  display: flex;
  flex-direction: column;
  gap: 10px;
  padding: 4px 12px 24px;
}

.soft {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px;
  background: #fff;
  border-radius: 12px;
  border: 1px solid #f0f0f0;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.mark {
  width: 52px;
  height: 52px;
  border-radius: 14px;
  background: #16160e;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
  font-weight: 700;
  flex: none;
}

.body {
  min-width: 0;
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.name {
  font-size: 16px;
  font-weight: 650;
  color: #16160e;
}

.summary,
.meta {
  color: #909399;
  font-size: 12px;
  line-height: 1.4;
}

.summary {
  color: #606266;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.get {
  flex: none;
  min-width: 52px;
  height: 28px;
  padding: 0 10px;
  border-radius: 14px;
  background: #f3f4f6;
  color: #ff2442;
  font-size: 13px;
  font-weight: 650;
  line-height: 28px;
  text-align: center;
}

.state {
  padding: 64px 20px;
  text-align: center;
  color: #909399;
}

.state-title {
  display: block;
  color: #16160e;
  font-size: 16px;
  font-weight: 650;
  margin-bottom: 6px;
}

.state-text {
  display: block;
}

.btn {
  margin-top: 16px;
  width: 120px;
  background: #ff2442;
  color: #fff;
}
</style>
