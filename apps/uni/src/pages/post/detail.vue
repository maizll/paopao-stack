<script setup lang="ts">
import { computed, ref } from 'vue'
import { onLoad } from '@dcloudio/uni-app'
import { ApiError, request, token } from '../../utils/api'
import { formatTime, localCover, noteCover, postStatusText } from '../../utils/format'
import type { CommentItem, InteractionState, Page, PostItem } from '../../utils/types'

const loading = ref(true)
const error = ref('')
const post = ref<PostItem | null>(null)
const broken = ref(false)
const comments = ref<CommentItem[]>([])
const commentsError = ref('')
const draft = ref('')
const sending = ref(false)
const liked = ref(false)
const favorited = ref(false)
const busy = ref('')

const html = computed(() => /<[a-z][\s\S]*>/i.test(post.value?.content || ''))
const cover = computed(() => {
  if (!post.value || broken.value) return localCover(post.value?.id || 0)
  return noteCover(post.value)
})
const author = computed(() => post.value?.author?.nickname || '匿名')
const likeCount = computed(() => post.value?.stats?.like ?? 0)
const favoriteCount = computed(() => post.value?.stats?.favorite ?? 0)
const commentCount = computed(() => post.value?.stats?.comment ?? 0)

const commentStatusText: Record<string, string> = {
  pending: '待审',
  rejected: '未通过',
  hidden: '已屏蔽',
}

async function load(id: number): Promise<void> {
  loading.value = true
  error.value = ''
  broken.value = false
  commentsError.value = ''
  try {
    post.value = await request<PostItem>(`/posts/${id}`)
    await Promise.all([loadComments(id), loadState(id)])
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : '读取失败'
  } finally {
    loading.value = false
  }
}

async function loadComments(id: number): Promise<void> {
  try {
    const page = await request<Page<CommentItem>>(`/posts/${id}/comments`, { data: { per_page: 30 } })
    comments.value = page.items
  } catch (cause) {
    commentsError.value = cause instanceof ApiError ? cause.message : '评论没有加载出来'
  }
}

async function loadState(id: number): Promise<void> {
  if (!token()) {
    liked.value = false
    favorited.value = false
    return
  }
  try {
    const result = await request<{ items: InteractionState[] }>('/interactions/status', {
      data: { targets: `post:${id}` },
    })
    const mine = result.items[0]
    liked.value = !!mine?.liked
    favorited.value = !!mine?.favorited
  } catch {
    liked.value = false
    favorited.value = false
  }
}

function back(): void {
  const pages = getCurrentPages()
  if (pages.length > 1) uni.navigateBack()
  else uni.switchTab({ url: '/pages/home/home' })
}

function needLogin(): boolean {
  if (token()) return false
  uni.showToast({ title: '请先登录', icon: 'none' })
  uni.switchTab({ url: '/pages/mine/mine' })
  return true
}

async function toggle(kind: 'like' | 'favorite'): Promise<void> {
  if (!post.value || busy.value) return
  if (needLogin()) return
  busy.value = kind
  const on = kind === 'like' ? liked.value : favorited.value
  try {
    const path = kind === 'like' ? '/interactions/like' : '/interactions/favorite'
    const result = on
      ? await request<InteractionState>(`${path}?target_type=post&target_id=${post.value.id}`, { method: 'DELETE' })
      : await request<InteractionState>(path, {
          method: 'POST',
          data: { target_type: 'post', target_id: post.value.id },
        })
    liked.value = result.liked
    favorited.value = result.favorited
    if (post.value.stats) {
      if (result.like_count != null) post.value.stats.like = result.like_count
      if (result.favorite_count != null) post.value.stats.favorite = result.favorite_count
    }
  } catch (cause) {
    uni.showToast({ title: cause instanceof ApiError ? cause.message : '操作失败', icon: 'none' })
  } finally {
    busy.value = ''
  }
}

function focusComposer(): void {
  uni.pageScrollTo({ selector: '#composer', duration: 200 })
}

function currentDraft(): string {
  if (typeof document !== 'undefined') {
    const host = document.getElementById('note-draft')
    const field = host instanceof HTMLTextAreaElement ? host : host?.querySelector('textarea')
    const dom = field?.value?.trim() || ''
    if (dom) return dom
  }
  return draft.value.trim()
}

async function send(): Promise<void> {
  if (!post.value || sending.value) return
  if (needLogin()) return
  const content = currentDraft()
  if (!content) {
    uni.showToast({ title: '先写一句评论', icon: 'none' })
    return
  }
  sending.value = true
  try {
    await request<CommentItem>(`/posts/${post.value.id}/comments`, {
      method: 'POST',
      data: { content },
    })
    draft.value = ''
    uni.showToast({ title: '已提交，等审核通过后公开', icon: 'none' })
    await loadComments(post.value.id)
  } catch (cause) {
    uni.showToast({ title: cause instanceof ApiError ? cause.message : '发送失败', icon: 'none' })
  } finally {
    sending.value = false
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
      <view id="composer" class="comments">
        <text class="comment-head">{{ commentCount > 0 ? `共 ${commentCount} 条评论` : '还没有公开评论' }}</text>
        <view v-if="commentsError" class="comment-miss">{{ commentsError }}</view>
        <view v-else-if="comments.length === 0" class="comment-miss">通过审核的评论会显示在这里。</view>
        <view v-for="item in comments" :key="item.id" class="comment">
          <view class="mini">{{ (item.author?.nickname || '匿').slice(0, 1) }}</view>
          <view class="comment-main">
            <view class="comment-line">
              <text class="comment-name">{{ item.author?.nickname || '匿名' }}</text>
              <text v-if="item.status !== 'published'" class="flag">{{ commentStatusText[item.status] || item.status }}</text>
            </view>
            <text class="comment-body">{{ item.content }}</text>
            <text class="comment-time">{{ formatTime(item.created_at) }}</text>
          </view>
        </view>
        <view class="composer">
          <textarea
            id="note-draft"
            v-model="draft"
            class="draft"
            maxlength="500"
            placeholder="说点什么，提交后需审核"
            :disabled="sending"
          />
          <view class="send" :class="{ 'send-off': sending || !draft.trim() }" @click="send">
            <text class="send-text">{{ sending ? '发送中' : '发送' }}</text>
          </view>
        </view>
      </view>
    </view>

    <view v-if="post" class="footer">
      <view class="footer-input" @click="focusComposer">说点什么…</view>
      <view class="acts">
        <view class="act" @click="toggle('like')">
          <uni-icons :type="liked ? 'heart-filled' : 'heart'" size="22" :color="liked ? '#ff2442' : '#16160e'" />
          <text class="act-text">{{ likeCount || '赞' }}</text>
        </view>
        <view class="act" @click="toggle('favorite')">
          <uni-icons :type="favorited ? 'star-filled' : 'star'" size="22" :color="favorited ? '#ff2442' : '#16160e'" />
          <text class="act-text">{{ favoriteCount || '收藏' }}</text>
        </view>
        <view class="act" @click="focusComposer">
          <uni-icons type="chat" size="22" color="#16160e" />
          <text class="act-text">{{ commentCount || '评论' }}</text>
        </view>
      </view>
    </view>
  </view>
</template>

<style scoped>
.page {
  min-height: 100vh;
  background: #fff;
  padding-bottom: 72px;
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
  display: inline-flex;
  align-items: center;
  height: 20px;
  margin-bottom: 8px;
  padding: 0 8px;
  border-radius: 999px;
  background: #fff1f0;
  color: #ff2442;
  font-size: 12px;
  line-height: 20px;
}

.title {
  display: block;
  font-size: 20px;
  font-weight: 700;
  color: #16160e;
  line-height: 1.4;
  overflow-wrap: anywhere;
}

.content {
  display: block;
  margin-top: 12px;
  font-size: 16px;
  line-height: 1.75;
  color: #16160e;
  white-space: pre-wrap;
  overflow-wrap: anywhere;
}

.tags {
  margin-top: 12px;
}

.tag {
  color: #3d8af5;
  font-size: 14px;
}

.meta,
.comment-time {
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

.comment-miss {
  margin-top: 12px;
  color: #909399;
  font-size: 13px;
  line-height: 1.5;
}

.comment {
  display: flex;
  gap: 8px;
  margin-top: 14px;
  min-width: 0;
}

.comment-main {
  flex: 1;
  min-width: 0;
}

.comment-line {
  display: flex;
  align-items: center;
  gap: 8px;
  min-width: 0;
}

.comment-name {
  font-size: 13px;
  color: #606266;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.comment-line .flag {
  margin-bottom: 0;
  flex: none;
}

.comment-body {
  display: block;
  margin-top: 4px;
  color: #16160e;
  font-size: 15px;
  line-height: 1.6;
  overflow-wrap: anywhere;
}

.composer {
  margin-top: 16px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.draft {
  width: 100%;
  min-height: 72px;
  padding: 10px 12px;
  box-sizing: border-box;
  border-radius: 12px;
  background: #f3f4f6;
  font-size: 14px;
  line-height: 1.5;
  color: #16160e;
}

.send {
  align-self: flex-end;
  min-width: 72px;
  height: 36px;
  padding: 0 16px;
  border-radius: 18px;
  background: #ff2442;
  display: flex;
  align-items: center;
  justify-content: center;
}

.send-off {
  opacity: 0.55;
}

.send-text {
  color: #fff;
  font-size: 14px;
  font-weight: 650;
  line-height: 1;
}

.footer {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  min-height: 56px;
  padding: 8px 12px;
  background: #fff;
  border-top: 1px solid #f0f0f0;
  display: flex;
  align-items: center;
  gap: 8px;
  box-sizing: border-box;
}

.footer-input {
  flex: 1;
  min-width: 0;
  height: 34px;
  line-height: 34px;
  padding: 0 12px;
  border-radius: 17px;
  background: #f3f4f6;
  color: #909399;
  font-size: 14px;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.acts {
  display: flex;
  align-items: center;
  gap: 10px;
  flex: none;
}

.act {
  min-height: 32px;
  display: flex;
  align-items: center;
  gap: 2px;
  color: #16160e;
}

.act-text {
  font-size: 12px;
  line-height: 1;
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
