<script setup lang="ts">
import { computed, ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { ApiError, request, token } from '../../utils/api'
import { formatTime } from '../../utils/format'
import type { NoticeItem, Page } from '../../utils/types'

const guest = ref(true)
const loading = ref(false)
const error = ref('')
const items = ref<NoticeItem[]>([])
const filter = ref<'all' | 'like' | 'follow' | 'comment'>('all')

const likeTypes = ['post.liked', 'post.favorited']
const commentTypes = ['post.commented', 'comment.rejected', 'comment.hidden']

const visible = computed(() => {
  if (filter.value === 'like') return items.value.filter((item) => likeTypes.includes(item.type))
  if (filter.value === 'comment') return items.value.filter((item) => commentTypes.includes(item.type))
  if (filter.value === 'follow') return []
  return items.value
})

const emptyText = computed(() => {
  if (filter.value === 'follow') return '关注还没开放，这里不会编造新粉丝。'
  if (filter.value === 'like') return '还没有赞和收藏。'
  if (filter.value === 'comment') return '还没有评论提醒。'
  return '审核结果、点赞和评论会出现在这里。'
})

async function load(): Promise<void> {
  error.value = ''
  if (!token()) {
    guest.value = true
    items.value = []
    return
  }
  guest.value = false
  loading.value = true
  try {
    const page = await request<Page<NoticeItem>>('/notifications', { data: { per_page: 30 } })
    items.value = page.items
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : '通知没有加载出来'
    items.value = []
  } finally {
    loading.value = false
  }
}

function goMine(): void {
  uni.switchTab({ url: '/pages/mine/mine' })
}

function pick(next: 'all' | 'like' | 'follow' | 'comment'): void {
  filter.value = next
}

async function openNotice(item: NoticeItem): Promise<void> {
  if (!item.read_at) {
    try {
      const updated = await request<NoticeItem>(`/notifications/${item.id}/read`, { method: 'POST' })
      item.read_at = updated.read_at
    } catch (cause) {
      uni.showToast({ title: cause instanceof ApiError ? cause.message : '标已读失败', icon: 'none' })
    }
  }
  const postId = item.payload?.post_id
  if (postId) {
    uni.navigateTo({ url: `/pages/post/detail?id=${postId}` })
  }
}

onShow(() => {
  void load()
})
</script>

<template>
  <view class="page">
    <app-nav title="消息" />

    <view v-if="guest" class="guest" @click="goMine">
      <view class="guest-mark">
        <uni-icons type="email" size="28" color="#ff2442" />
      </view>
      <text class="guest-title">登录后查看消息</text>
      <text class="guest-text">审核结果、赞和评论会汇总在这里。私信还没做成会话。</text>
      <view class="guest-btn"><text class="guest-btn-text">去登录</text></view>
    </view>

    <view v-else class="body">
      <view class="entries">
        <view class="entry" @click="pick('like')">
          <view class="icon" :class="{ on: filter === 'like' }" style="background: #feebec">
            <uni-icons type="heart-filled" size="22" color="#ff2442" />
          </view>
          <text class="label">赞和收藏</text>
        </view>
        <view class="entry" @click="pick('follow')">
          <view class="icon" :class="{ on: filter === 'follow' }" style="background: #e7f0ff">
            <uni-icons type="personadd-filled" size="22" color="#3d8af5" />
          </view>
          <text class="label">新增关注</text>
        </view>
        <view class="entry" @click="pick('comment')">
          <view class="icon" :class="{ on: filter === 'comment' }" style="background: #e6fbf3">
            <uni-icons type="chatboxes-filled" size="22" color="#12b886" />
          </view>
          <text class="label">评论和@</text>
        </view>
      </view>

      <view class="sessions">
        <view class="sessions-head">
          <text class="sessions-title">通知</text>
          <text v-if="filter !== 'all'" class="reset" @click="pick('all')">看全部</text>
        </view>
        <view v-if="loading" class="empty">
          <text class="empty-title">正在读取通知…</text>
        </view>
        <view v-else-if="error" class="empty">
          <text class="empty-title">通知暂时打不开</text>
          <text class="empty-text">{{ error }}</text>
        </view>
        <view v-else-if="visible.length === 0" class="empty">
          <text class="empty-title">暂无通知</text>
          <text class="empty-text">{{ emptyText }}</text>
        </view>
        <view v-else class="list">
          <view v-for="item in visible" :key="item.id" class="notice" @click="openNotice(item)">
            <view class="dot" :class="{ unread: !item.read_at }" />
            <view class="notice-main">
              <text class="notice-title">{{ item.title }}</text>
              <text class="notice-body">{{ item.body }}</text>
              <text class="notice-time">{{ formatTime(item.created_at) }}</text>
            </view>
          </view>
        </view>
      </view>
    </view>
  </view>
</template>

<style scoped>
.page {
  min-height: 100vh;
  background: #f8fafc;
  box-sizing: border-box;
}

.guest {
  margin: 48px 24px;
  padding: 36px 20px;
  background: #fff;
  border-radius: 16px;
  border: 1px solid #f0f0f0;
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05);
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.guest-mark {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background: #feebec;
  display: flex;
  align-items: center;
  justify-content: center;
}

.guest-title {
  margin-top: 14px;
  font-size: 18px;
  font-weight: 700;
  color: #16160e;
}

.guest-text {
  margin-top: 8px;
  color: #909399;
  font-size: 13px;
  line-height: 1.6;
}

.guest-btn {
  margin-top: 18px;
  min-width: 120px;
  height: 36px;
  padding: 0 18px;
  border-radius: 18px;
  background: #ff2442;
  display: flex;
  align-items: center;
  justify-content: center;
}

.guest-btn-text {
  color: #fff;
  font-size: 14px;
  font-weight: 650;
  line-height: 1;
}

.entries {
  margin: 12px;
  padding: 16px 8px 12px;
  background: #fff;
  border-radius: 16px;
  display: flex;
  justify-content: space-around;
}

.entry {
  width: 88px;
  max-width: 30%;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.icon {
  width: 52px;
  height: 52px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  box-sizing: border-box;
}

.icon.on {
  box-shadow: 0 0 0 2px #ff2442;
}

.label {
  margin-top: 8px;
  font-size: 12px;
  color: #16160e;
  text-align: center;
}

.sessions {
  margin: 0 12px 16px;
  background: #fff;
  border-radius: 16px;
  min-height: 220px;
  overflow: hidden;
}

.sessions-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 16px 0;
}

.sessions-title {
  font-size: 15px;
  font-weight: 650;
  color: #16160e;
}

.reset {
  color: #ff2442;
  font-size: 13px;
  line-height: 1;
}

.empty {
  padding: 48px 20px;
  text-align: center;
}

.empty-title {
  display: block;
  font-size: 15px;
  color: #16160e;
  font-weight: 650;
}

.empty-text {
  display: block;
  margin-top: 8px;
  color: #909399;
  font-size: 13px;
  line-height: 1.5;
}

.notice {
  display: flex;
  gap: 10px;
  padding: 14px 16px;
  border-top: 1px solid #f5f5f5;
  min-width: 0;
}

.dot {
  width: 8px;
  height: 8px;
  margin-top: 6px;
  border-radius: 50%;
  background: transparent;
  flex: none;
}

.dot.unread {
  background: #ff2442;
}

.notice-main {
  flex: 1;
  min-width: 0;
}

.notice-title {
  display: block;
  color: #16160e;
  font-size: 15px;
  font-weight: 650;
}

.notice-body {
  display: block;
  margin-top: 4px;
  color: #606266;
  font-size: 13px;
  line-height: 1.5;
  overflow-wrap: anywhere;
}

.notice-time {
  display: block;
  margin-top: 6px;
  color: #909399;
  font-size: 12px;
}
</style>
