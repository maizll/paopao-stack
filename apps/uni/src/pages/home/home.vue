<script setup lang="ts">
import { computed, ref } from 'vue'
import { onPullDownRefresh, onShow } from '@dcloudio/uni-app'
import PostFeed from '../../components/post-feed/post-feed.vue'
import { ApiError, request } from '../../utils/api'
import type { Banner, Board, GridItem, Page, PostItem, SiteConfig } from '../../utils/types'

const loading = ref(true)
const error = ref('')
const posts = ref<PostItem[]>([])
const boards = ref<Board[]>([])
const grid = ref<GridItem[]>([])
const banner = ref('')
const tenantName = ref('泡泡栈')
const keyword = ref('')
const boardId = ref(0)
const statusBar = ref(0)

const info = uni.getSystemInfoSync()
statusBar.value = info.statusBarHeight || 0

const visibleBoards = computed(() => boards.value.slice(0, 8))

async function load(): Promise<void> {
  loading.value = true
  error.value = ''
  try {
    const [config, postPage, boardList, banners, shortcuts] = await Promise.all([
      request<SiteConfig>('/system/config'),
      request<Page<PostItem>>('/posts', {
        data: {
          per_page: 20,
          keyword: keyword.value || undefined,
          board_id: boardId.value || undefined,
        },
      }),
      request<Board[]>('/boards'),
      request<Banner[]>('/ops/banners'),
      request<GridItem[]>('/ops/grid'),
    ])
    tenantName.value = config.tenant?.name || '泡泡栈'
    posts.value = postPage.items
    boards.value = boardList
    banner.value = banners[0]?.title || ''
    grid.value = shortcuts.slice(0, 4)
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : '读取失败'
  } finally {
    loading.value = false
  }
}

function pickBoard(id: number): void {
  boardId.value = id
  void load()
}

function onSearch(event: { value?: string }): void {
  keyword.value = event.value || ''
  void load()
}

function onClear(): void {
  keyword.value = ''
  void load()
}

function openPost(id: number): void {
  uni.navigateTo({ url: `/pages/post/detail?id=${id}` })
}

function goCommunity(): void {
  uni.switchTab({ url: '/pages/community/community' })
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
  <view class="page" :style="{ paddingTop: statusBar + 12 + 'px' }">
    <view class="brand-row">
      <view class="logo-wrap">
        <view class="dot" />
        <text class="logo">泡泡栈</text>
      </view>
      <text class="tenant">{{ tenantName }}</text>
    </view>
    <uni-search-bar
      v-model="keyword"
      placeholder="搜笔记"
      cancel-button="none"
      bg-color="#ffffff"
      @confirm="onSearch"
      @clear="onClear"
    />
    <uni-notice-bar v-if="banner" single :text="banner" background-color="#fff1ec" color="#c2410c" />
    <scroll-view v-if="visibleBoards.length" scroll-x class="chips" :show-scrollbar="false">
      <view class="chip" :class="{ on: boardId === 0 }" @click="pickBoard(0)">全部</view>
      <view
        v-for="board in visibleBoards"
        :key="board.id"
        class="chip"
        :class="{ on: boardId === board.id }"
        @click="pickBoard(board.id)"
      >
        {{ board.name }}
      </view>
    </scroll-view>
    <scroll-view v-if="grid.length" scroll-x class="shortcuts" :show-scrollbar="false">
      <view v-for="item in grid" :key="item.id" class="shortcut" @click="goCommunity">
        <text class="shortcut-title">{{ item.title }}</text>
        <text v-if="item.badge" class="shortcut-badge">{{ item.badge }}</text>
      </view>
    </scroll-view>
    <post-feed
      :items="posts"
      :loading="loading"
      :error="error"
      empty-title="这里还没有公开笔记"
      empty-text="通过审核的帖子会排成两列出现在这里。"
      @retry="load"
      @open="openPost"
    />
  </view>
</template>

<style scoped>
.page {
  min-height: 100vh;
  padding: 12px 12px 24px;
  box-sizing: border-box;
}

.brand-row {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  padding: 4px 4px 0;
}

.logo-wrap {
  display: flex;
  align-items: center;
  gap: 8px;
}

.dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: #ff5c39;
}

.logo {
  font-size: 22px;
  font-weight: 700;
  letter-spacing: -0.03em;
}

.tenant {
  color: #6f675f;
  font-size: 13px;
}

.chips,
.shortcuts {
  white-space: nowrap;
  margin: 4px 0 12px;
  height: 36px;
}

.chip,
.shortcut {
  display: inline-flex;
  align-items: center;
  height: 32px;
  padding: 0 12px;
  margin-right: 8px;
  border-radius: 999px;
  background: #fff;
  color: #3a332f;
  font-size: 13px;
}

.chip.on {
  background: #1f1a17;
  color: #fff;
}

.shortcut-badge {
  margin-left: 6px;
  color: #c2410c;
  font-size: 12px;
}
</style>
