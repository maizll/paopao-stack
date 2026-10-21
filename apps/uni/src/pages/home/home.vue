<script setup lang="ts">
import { computed, ref } from 'vue'
import { onPullDownRefresh, onShow } from '@dcloudio/uni-app'
import PostFeed from '../../components/post-feed/post-feed.vue'
import { ApiError, request } from '../../utils/api'
import type { Board, Page, PostItem } from '../../utils/types'

const loading = ref(true)
const error = ref('')
const posts = ref<PostItem[]>([])
const boards = ref<Board[]>([])
const keyword = ref('')
const boardId = ref(0)
const tab = ref(0)
const catOpen = ref(false)

const visible = computed(() => {
  const list = posts.value.slice()
  if (tab.value === 1) {
    list.sort((a, b) => String(b.created_at || '').localeCompare(String(a.created_at || '')))
  }
  return list
})

async function load(): Promise<void> {
  loading.value = true
  error.value = ''
  try {
    const [postPage, boardList] = await Promise.all([
      request<Page<PostItem>>('/posts', {
        data: {
          per_page: 20,
          keyword: keyword.value || undefined,
          board_id: boardId.value || undefined,
        },
      }),
      request<Board[]>('/boards'),
    ])
    posts.value = postPage.items
    boards.value = boardList
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : '读取失败'
  } finally {
    loading.value = false
  }
}

function pickBoard(id: number): void {
  boardId.value = id
  catOpen.value = false
  void load()
}

function pickTab(index: number): void {
  tab.value = index
}

function onSearch(): void {
  void load()
}

function openPost(id: number): void {
  uni.navigateTo({ url: `/pages/post/detail?id=${id}` })
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
    <app-nav>
      <view class="tabs">
        <view class="tab" :class="{ on: tab === 0 }" @click="pickTab(0)">
          <text class="tab-text">推荐</text>
          <view class="line" />
        </view>
        <view class="tab" :class="{ on: tab === 1 }" @click="pickTab(1)">
          <text class="tab-text">最新</text>
          <view class="line" />
        </view>
      </view>
    </app-nav>

    <view class="search">
      <uni-icons type="search" size="16" color="#9ca3af" />
      <input
        v-model="keyword"
        class="search-input"
        confirm-type="search"
        placeholder="搜索笔记"
        placeholder-class="ph"
        @confirm="onSearch"
      />
      <text v-if="keyword" class="clear" @click="keyword = ''; onSearch()">清除</text>
    </view>

    <view class="cat-bar">
      <scroll-view scroll-x class="cat-scroll" :show-scrollbar="false">
        <view class="cat-row">
          <view class="cat" :class="{ on: boardId === 0 }" @click="pickBoard(0)">全部</view>
          <view
            v-for="board in boards"
            :key="board.id"
            class="cat"
            :class="{ on: boardId === board.id }"
            @click="pickBoard(board.id)"
          >
            {{ board.name }}
          </view>
        </view>
      </scroll-view>
      <view v-if="boards.length > 3" class="cat-more" @click="catOpen = !catOpen">
        <uni-icons :type="catOpen ? 'top' : 'bottom'" size="16" color="#666" />
      </view>
    </view>
    <view v-if="catOpen" class="cat-panel">
      <view class="cat-grid">
        <view class="cat-cell" :class="{ on: boardId === 0 }" @click="pickBoard(0)">全部</view>
        <view
          v-for="board in boards"
          :key="board.id"
          class="cat-cell"
          :class="{ on: boardId === board.id }"
          @click="pickBoard(board.id)"
        >
          {{ board.name }}
        </view>
      </view>
    </view>

    <view class="feed">
      <post-feed
        :items="visible"
        :loading="loading"
        :error="error"
        empty-title="暂无笔记"
        empty-text="换个分类看看，或去社区发布第一条。"
        @retry="load"
        @open="openPost"
      />
    </view>
  </view>
</template>

<style scoped>
.page {
  min-height: 100vh;
  background: #f8fafc;
}

.tabs {
  display: flex;
  align-items: flex-end;
  justify-content: center;
  height: 42px;
}

.tab {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: flex-end;
  height: 42px;
  padding: 0 14px 4px;
}

.tab-text {
  font-size: 16px;
  color: #606266;
  line-height: 1.2;
}

.tab.on .tab-text {
  color: #16160e;
  font-weight: 650;
  font-size: 17px;
}

.line {
  width: 20px;
  height: 2px;
  margin-top: 4px;
  border-radius: 2px;
  background: transparent;
}

.tab.on .line {
  background: #ff2442;
}

.search {
  margin: 8px 12px 4px;
  height: 36px;
  border-radius: 18px;
  background: #f3f4f6;
  padding: 0 14px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.search-input {
  flex: 1;
  height: 36px;
  font-size: 14px;
  color: #16160e;
}

.ph {
  color: #9ca3af;
}

.clear {
  color: #909399;
  font-size: 12px;
}

.cat-bar {
  display: flex;
  align-items: center;
  background: #fff;
  height: 40px;
}

.cat-scroll {
  flex: 1;
  width: 0;
  height: 40px;
  white-space: nowrap;
}

.cat-row {
  display: inline-flex;
  align-items: center;
  height: 40px;
  padding: 0 4px 0 8px;
}

.cat,
.cat-cell {
  color: #606266;
  font-size: 14px;
  font-weight: 500;
}

.cat {
  display: inline-flex;
  align-items: center;
  height: 40px;
  padding: 0 12px;
}

.cat.on,
.cat-cell.on {
  color: #16160e;
  font-weight: 650;
}

.cat-more {
  width: 36px;
  height: 40px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #fff;
  box-shadow: -8px 0 8px #fff;
}

.cat-panel {
  background: #fff;
  padding: 4px 8px 10px;
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.06);
}

.cat-grid {
  display: flex;
  flex-wrap: wrap;
}

.cat-cell {
  width: 25%;
  text-align: center;
  line-height: 36px;
  font-size: 13px;
}

.feed {
  padding: 8px 0 16px;
}
</style>
