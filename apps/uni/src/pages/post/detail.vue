<script setup lang="ts">
import { ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { ApiError, request, token } from '../../utils/api'
import { formatTime, postStatusText } from '../../utils/format'
import type { PostItem } from '../../utils/types'

const loading = ref(true)
const error = ref('')
const post = ref<PostItem | null>(null)

async function load(id: number): Promise<void> {
  loading.value = true
  error.value = ''
  try {
    post.value = await request<PostItem>(`/posts/${id}`)
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : '读取失败'
  } finally {
    loading.value = false
  }
}

onLoad((query) => {
  const id = Number(query?.id || 0)
  if (!id) {
    loading.value = false
    error.value = '缺少帖子编号'
    return
  }
  void load(id)
})
</script>

<template>
  <view class="page">
    <view v-if="loading" class="muted">正在读取…</view>
    <view v-else-if="error" class="state">
      <text class="title">打不开这篇笔记</text>
      <text class="muted">{{ error }}</text>
      <text v-if="!token()" class="muted">待审帖子只有作者登录后才能看。</text>
    </view>
    <view v-else-if="post">
      <text v-if="post.status && post.status !== 'published'" class="flag">
        {{ postStatusText[post.status] || post.status }}
      </text>
      <text class="title">{{ post.title }}</text>
      <text class="meta">
        {{ post.author?.nickname || '匿名' }} · {{ post.board?.name || '未分区' }} · {{ formatTime(post.created_at) }}
      </text>
      <text class="content">{{ post.content }}</text>
    </view>
  </view>
</template>

<style scoped>
.page {
  min-height: 100vh;
  padding: 20px 18px 40px;
  background: #fff;
  box-sizing: border-box;
}

.title {
  display: block;
  font-size: 22px;
  font-weight: 700;
  line-height: 1.35;
}

.meta,
.muted {
  display: block;
  margin-top: 8px;
  color: #6f675f;
  font-size: 13px;
}

.content {
  display: block;
  margin-top: 18px;
  font-size: 16px;
  line-height: 1.75;
  white-space: pre-wrap;
}

.flag {
  display: inline-block;
  margin-bottom: 10px;
  padding: 2px 8px;
  border-radius: 999px;
  background: #fff1ec;
  color: #c2410c;
  font-size: 12px;
}

.state {
  padding-top: 40px;
}
</style>
