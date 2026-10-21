<script setup lang="ts">
import { ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { ApiError, request, token } from '../../utils/api'

const guest = ref(true)
const footnote = ref('站内通知还在占位，审核结果请到「我的」看笔记状态。')
const sheet = ref('')

async function load(): Promise<void> {
  sheet.value = ''
  if (!token()) {
    guest.value = true
    return
  }
  guest.value = false
  try {
    await request<unknown>('/notifications')
    footnote.value = '还没有新的通知。'
  } catch (cause) {
    footnote.value = cause instanceof ApiError ? cause.message : '通知尚未接入'
  }
}

function goMine(): void {
  uni.switchTab({ url: '/pages/mine/mine' })
}

function openKind(name: string): void {
  sheet.value = name
  uni.showToast({ title: `${name}还没接入`, icon: 'none' })
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
      <text class="guest-text">赞、关注和评论会汇总在这里。私信还没做成会话。</text>
      <view class="guest-btn">去登录</view>
    </view>

    <view v-else class="body">
      <view class="entries">
        <view class="entry" @click="openKind('赞和收藏')">
          <view class="icon" style="background: #feebec">
            <uni-icons type="heart-filled" size="22" color="#ff2442" />
          </view>
          <text class="label">赞和收藏</text>
        </view>
        <view class="entry" @click="openKind('新增关注')">
          <view class="icon" style="background: #e7f0ff">
            <uni-icons type="personadd-filled" size="22" color="#3d8af5" />
          </view>
          <text class="label">新增关注</text>
        </view>
        <view class="entry" @click="openKind('评论和@')">
          <view class="icon" style="background: #e6fbf3">
            <uni-icons type="chatboxes-filled" size="22" color="#12b886" />
          </view>
          <text class="label">评论和@</text>
        </view>
      </view>

      <view class="sessions">
        <text class="sessions-title">会话</text>
        <view class="empty">
          <text class="empty-title">暂无会话</text>
          <text class="empty-text">{{ footnote }}</text>
          <text v-if="sheet" class="empty-text">刚才点的是「{{ sheet }}」。</text>
        </view>
      </view>
    </view>
  </view>
</template>

<style scoped>
.page {
  min-height: 100vh;
  background: #f8fafc;
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
  line-height: 36px;
  padding: 0 18px;
  border-radius: 18px;
  background: #ff2442;
  color: #fff;
  font-size: 14px;
  font-weight: 650;
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
}

.label {
  margin-top: 8px;
  font-size: 12px;
  color: #16160e;
}

.sessions {
  margin: 0 12px;
  background: #fff;
  border-radius: 16px;
  min-height: 220px;
}

.sessions-title {
  display: block;
  padding: 14px 16px 0;
  font-size: 15px;
  font-weight: 650;
  color: #16160e;
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
</style>
