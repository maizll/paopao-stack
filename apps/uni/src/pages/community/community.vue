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
    <app-nav title="社区">
      <template #right>
        <text class="publish" @click="writePost">发布</text>
      </template>
    </app-nav>
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
          <text class="count">{{ board.post_count }}</text>
        </view>
      </view>
    </scroll-view>
    <view class="feed">
      <post-feed
        :items="posts"
        :loading="loading"
        :error="error"
        empty-title="这个版块还没有笔记"
        empty-text="发布后进入待审，管理员通过才会出现在这里。"
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

.publish {
  color: #ff2442;
  font-size: 15px;
  font-weight: 650;
  padding-right: 8px;
}

.cat-scroll {
  height: 44px;
  background: #fff;
  white-space: nowrap;
}

.cat-row {
  display: inline-flex;
  align-items: center;
  height: 44px;
  padding: 0 8px;
}

.cat {
  display: inline-flex;
  align-items: center;
  height: 28px;
  margin-right: 8px;
  padding: 0 12px;
  border-radius: 14px;
  background: #f3f4f6;
  color: #606266;
  font-size: 13px;
}

.cat.on {
  background: #16160e;
  color: #fff;
}

.count {
  margin-left: 4px;
  font-size: 11px;
  opacity: 0.72;
}

.feed {
  padding: 8px 0 16px;
}
</style>
