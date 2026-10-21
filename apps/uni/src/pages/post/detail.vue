<script setup lang="ts">
import { computed, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { ApiError, request, token } from '../../utils/api'
import { formatTime, localCover, noteCover, postStatusText } from '../../utils/format'
import type { PostItem } from '../../utils/types'

const loading = ref(true)
const error = ref('')
const post = ref<PostItem | null>(null)
const broken = ref(false)

const html = computed(() => /<[a-z][\s\S]*>/i.test(post.value?.content || ''))
const cover = computed(() => {
  if (!post.value || broken.value) return localCover(post.value?.id || 0)
  return noteCover(post.value)
})
const author = computed(() => post.value?.author?.nickname || '匿名')
const likes = computed(() => post.value?.stats?.like ?? 0)
const favorites = computed(() => post.value?.stats?.favorite ?? 0)
const comments = computed(() => post.value?.stats?.comment ?? 0)

async function load(id: number): Promise<void> {
  loading.value = true
  error.value = ''
  broken.value = false
  try {
    post.value = await request<PostItem>(`/posts/${id}`)
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : '读取失败'
  } finally {
    loading.value = false
  }
}

function back(): void {
  const pages = getCurrentPages()
  if (pages.length > 1) uni.navigateBack()
  else uni.switchTab({ url: '/pages/home/home' })
}

function soon(name: string): void {
  uni.showToast({ title: `${name}还没开放`, icon: 'none' })
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
    <view class="top">
      <app-nav :title="''">
        <template #left>
          <view class="back" @click="back">
            <uni-icons type="back" size="22" color="#16160e" />
          </view>
        </template>
        <view v-if="post" class="author">
          <view class="avatar">{{ author.slice(0, 1) }}</view>
          <text class="author-name">{{ author }}</text>
        </view>
        <text v-else class="nav-fallback">笔记</text>
      </app-nav>
    </view>

    <view v-if="loading" class="state">正在打开笔记…</view>
    <view v-else-if="error" class="state">
      <text class="state-title">打不开这篇笔记</text>
      <text class="state-text">{{ error }}</text>
      <text v-if="!token()" class="state-text">待审帖只有作者登录后才能看。</text>
    </view>
    <view v-else-if="post" class="body">
      <image class="cover" :src="cover" mode="aspectFill" @error="broken = true" />
      <view class="note">
        <text v-if="post.status && post.status !== 'published'" class="flag">
          {{ postStatusText[post.status] || post.status }}
        </text>
        <text class="title">{{ post.title }}</text>
        <rich-text v-if="html" class="content" :nodes="post.content" />
        <text v-else class="content">{{ post.content }}</text>
        <view class="tags">
          <text v-if="post.board?.name" class="tag"># {{ post.board.name }}</text>
        </view>
        <text class="meta">
          {{ formatTime(post.created_at) }} · {{ post.stats?.view || 0 }} 浏览
        </text>
      </view>
      <view class="comments">
        <text class="comment-head">{{ comments > 0 ? `共 ${comments} 条评论` : '还没有评论' }}</text>
        <view class="composer" @click="soon('评论')">
          <view class="mini">我</view>
          <text class="composer-ph">说点什么…</text>
        </view>
      </view>
    </view>

    <view v-if="post" class="footer">
      <view class="footer-input" @click="soon('评论')">说点什么…</view>
      <view class="acts">
        <view class="act" @click="soon('点赞')">
          <uni-icons :type="likes ? 'heart-filled' : 'heart'" size="22" :color="likes ? '#ff2442' : '#16160e'" />
          <text>{{ likes || '赞' }}</text>
        </view>
        <view class="act" @click="soon('收藏')">
          <uni-icons type="star" size="22" color="#16160e" />
          <text>{{ favorites || '收藏' }}</text>
        </view>
        <view class="act" @click="soon('评论')">
          <uni-icons type="chat" size="22" color="#16160e" />
          <text>{{ comments || '评论' }}</text>
        </view>
      </view>
    </view>
  </view>
</template>

<style scoped>
.page {
  min-height: 100vh;
  background: #fff;
  padding-bottom: 64px;
  box-sizing: border-box;
}

.top {
  position: sticky;
  top: 0;
  z-index: 5;
  background: #fff;
}

.back {
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.author {
  display: flex;
  align-items: center;
  min-width: 0;
}

.avatar,
.mini {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: #f3f4f6;
  color: #16160e;
  font-size: 13px;
  font-weight: 650;
  display: flex;
  align-items: center;
  justify-content: center;
  flex: none;
}

.author-name,
.nav-fallback {
  margin-left: 8px;
  font-size: 15px;
  font-weight: 650;
  color: #16160e;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.cover {
  display: block;
  width: 100%;
  height: 280px;
  background: #f3f4f6;
}

.note,
.comments {
  padding: 16px 16px 0;
}

.flag {
  display: inline-block;
  margin-bottom: 8px;
  padding: 2px 8px;
  border-radius: 999px;
  background: #fff1f0;
  color: #ff2442;
  font-size: 12px;
}

.title {
  display: block;
  font-size: 20px;
  font-weight: 700;
  color: #16160e;
  line-height: 1.4;
}

.content {
  display: block;
  margin-top: 12px;
  font-size: 16px;
  line-height: 1.75;
  color: #16160e;
  white-space: pre-wrap;
}

.tags {
  margin-top: 12px;
}

.tag {
  color: #3d8af5;
  font-size: 14px;
}

.meta {
  display: block;
  margin-top: 10px;
  color: #909399;
  font-size: 12px;
}

.comments {
  margin-top: 16px;
  padding-bottom: 24px;
  border-top: 8px solid #f8fafc;
}

.comment-head {
  display: block;
  font-size: 15px;
  font-weight: 650;
  color: #16160e;
}

.composer {
  margin-top: 12px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.composer-ph,
.footer-input {
  color: #909399;
  font-size: 14px;
}

.composer-ph {
  flex: 1;
  height: 36px;
  line-height: 36px;
  padding: 0 12px;
  border-radius: 18px;
  background: #f3f4f6;
}

.footer {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  height: 56px;
  padding: 0 12px;
  background: #fff;
  border-top: 1px solid #f0f0f0;
  display: flex;
  align-items: center;
  gap: 8px;
  box-sizing: border-box;
}

.footer-input {
  flex: 1;
  height: 34px;
  line-height: 34px;
  padding: 0 12px;
  border-radius: 17px;
  background: #f3f4f6;
}

.acts {
  display: flex;
  align-items: center;
  gap: 10px;
}

.act {
  display: flex;
  align-items: center;
  gap: 2px;
  color: #16160e;
  font-size: 12px;
}

.state {
  padding: 48px 24px;
  text-align: center;
  color: #909399;
}

.state-title {
  display: block;
  color: #16160e;
  font-size: 16px;
  font-weight: 650;
  margin-bottom: 8px;
}

.state-text {
  display: block;
  margin-top: 6px;
}
</style>
