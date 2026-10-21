<script setup lang="ts">
import { ref } from 'vue'
import { onPullDownRefresh, onShow } from '@dcloudio/uni-app'
import PostFeed from '../../components/post-feed/post-feed.vue'
import { ApiError, request } from '../../utils/api'
import type { Board, Page, PostItem } from '../../utils/types'

const loading = ref(true)
const error = ref('')
const posts = ref<PostItem[]>([])
const boards = ref<Board[]>([])
const boardId = ref(0)

async function load(): Promise<void> {
  loading.value = true
  error.value = ''
  try {
    const [boardList, postPage] = await Promise.all([
      request<Board[]>('/boards'),
      request<Page<PostItem>>('/posts', {
        data: { per_page: 20, board_id: boardId.value || undefined },
      }),
    ])
    boards.value = boardList
    posts.value = postPage.items
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

function openPost(id: number): void {
  uni.navigateTo({ url: `/pages/post/detail?id=${id}` })
}

function writePost(): void {
  uni.navigateTo({ url: '/pages/post/compose' })
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
    <view class="toolbar">
      <text class="hint">按版块看已经通过审核的笔记</text>
      <button class="write" @click="writePost">写一篇</button>
    </view>
    <scroll-view scroll-x class="chips" :show-scrollbar="false">
      <view class="chip" :class="{ on: boardId === 0 }" @click="pickBoard(0)">全部</view>
      <view
        v-for="board in boards"
        :key="board.id"
        class="chip"
        :class="{ on: boardId === board.id }"
        @click="pickBoard(board.id)"
      >
        {{ board.name }}
        <text class="count">{{ board.post_count }}</text>
      </view>
    </scroll-view>
    <post-feed
      :items="posts"
      :loading="loading"
      :error="error"
      empty-title="这个版块还是空的"
      empty-text="写一篇之后，租户管理员在后台通过，这里才会出现。"
      @retry="load"
      @open="openPost"
    />
  </view>
</template>

<style scoped>
.page {
  min-height: 100vh;
  padding: 8px 8px 16px;
  box-sizing: border-box;
}

.toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 8px;
}

.hint {
  color: #6f675f;
  font-size: 13px;
}

.write {
  margin: 0;
  padding: 0 14px;
  height: 32px;
  line-height: 32px;
  border-radius: 999px;
  background: #ff5c39;
  color: #fff;
  font-size: 13px;
  border: none;
}

.write::after {
  border: none;
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
  color: #3a332f;
  font-size: 13px;
}

.chip.on {
  background: #1f1a17;
  color: #fff;
}

.count {
  margin-left: 6px;
  font-size: 12px;
  opacity: 0.7;
}
</style>
