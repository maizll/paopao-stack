<script setup lang="ts">
import { ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { ApiError, request, token } from '../../utils/api'

const mode = ref<'guest' | 'empty' | 'closed'>('guest')
const hint = ref('')

async function load(): Promise<void> {
  if (!token()) {
    mode.value = 'guest'
    hint.value = ''
    return
  }
  try {
    await request<unknown>('/notifications')
    mode.value = 'empty'
    hint.value = '还没有新消息。'
  } catch (cause) {
    mode.value = 'closed'
    hint.value = cause instanceof ApiError ? cause.message : '通知尚未接入'
  }
}

function goMine(): void {
  uni.switchTab({ url: '/pages/mine/mine' })
}

onShow(() => {
  void load()
})
</script>

<template>
  <view class="page">
    <view class="panel">
      <uni-icons type="email" size="36" color="#ff5c39" />
      <text class="title">{{ mode === 'guest' ? '登录后查看消息' : '消息还是空的' }}</text>
      <text v-if="mode === 'guest'" class="text">私信和通知都还没做成业务。登录只是为了确认接口状态。</text>
      <text v-else-if="mode === 'closed'" class="text">
        站内通知还没接入：{{ hint }}。帖子审核结果不会推到这里，去「我的」里看自己的待审帖。
      </text>
      <text v-else class="text">{{ hint }}</text>
      <button v-if="mode === 'guest'" class="btn" @click="goMine">去登录</button>
    </view>
  </view>
</template>

<style scoped>
.page {
  min-height: 100vh;
  padding: 24px 16px;
  box-sizing: border-box;
}

.panel {
  margin-top: 48px;
  background: #fff;
  border-radius: 20px;
  padding: 36px 20px;
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
}

.title {
  margin-top: 12px;
  font-size: 18px;
  font-weight: 700;
}

.text {
  margin-top: 8px;
  color: #6f675f;
  font-size: 14px;
  line-height: 1.6;
}

.btn {
  margin-top: 20px;
  width: 160px;
  background: #ff5c39;
  color: #fff;
  border: none;
  border-radius: 999px;
}

.btn::after {
  border: none;
}
</style>
