<script setup lang="ts">
import { ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { ApiError, request, token } from '../../utils/api'
import type { Board } from '../../utils/types'

const boards = ref<Board[]>([])
const boardIndex = ref(0)
const title = ref('')
const content = ref('')
const error = ref('')
const submitting = ref(false)
const ready = ref(false)

async function loadBoards(): Promise<void> {
  if (!token()) {
    uni.showToast({ title: '先登录再发帖', icon: 'none' })
    uni.switchTab({ url: '/pages/mine/mine' })
    return
  }
  try {
    boards.value = await request<Board[]>('/boards')
    ready.value = true
    if (!boards.value.length) error.value = '当前租户还没有可发帖的版块'
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : '读取版块失败'
  }
}

function onBoard(event: { detail: { value: string } }): void {
  boardIndex.value = Number(event.detail.value)
}

async function submit(): Promise<void> {
  const board = boards.value[boardIndex.value]
  if (!board) {
    error.value = '先选择版块'
    return
  }
  if (!title.value.trim() || !content.value.trim()) {
    error.value = '标题和正文都要写'
    return
  }
  submitting.value = true
  error.value = ''
  try {
    await request('/posts', {
      method: 'POST',
      data: {
        board_id: board.id,
        title: title.value.trim(),
        content: content.value.trim(),
      },
    })
    uni.showToast({ title: '已提交，等待审核', icon: 'none' })
    setTimeout(() => {
      uni.switchTab({ url: '/pages/mine/mine' })
    }, 500)
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : '发送失败'
  } finally {
    submitting.value = false
  }
}

onShow(() => {
  void loadBoards()
})
</script>

<template>
  <view class="page">
    <text class="lead">发出去之后是待审。租户管理员通过，才会出现在首页和社区。</text>
    <text class="label">版块</text>
    <picker v-if="boards.length" :range="boards" range-key="name" :value="boardIndex" @change="onBoard">
      <view class="picker">{{ boards[boardIndex]?.name || '选择版块' }}</view>
    </picker>
    <text class="label">标题</text>
    <uni-easyinput v-model="title" :maxlength="80" placeholder="一句话说明这篇笔记" />
    <text class="label">正文</text>
    <uni-easyinput v-model="content" type="textarea" :maxlength="4000" placeholder="写给这个圈子的内容" />
    <text v-if="error" class="error">{{ error }}</text>
    <button class="btn" :disabled="submitting || !ready" @click="submit">
      {{ submitting ? '提交中…' : '提交审核' }}
    </button>
  </view>
</template>

<style scoped>
.page {
  min-height: 100vh;
  padding: 16px 16px 32px;
  background: #fff;
  box-sizing: border-box;
}

.lead,
.label,
.error {
  display: block;
}

.lead {
  color: #6f675f;
  font-size: 13px;
  line-height: 1.5;
}

.label {
  margin: 16px 0 6px;
  font-size: 13px;
  color: #6f675f;
}

.picker {
  height: 44px;
  line-height: 44px;
  padding: 0 12px;
  border-radius: 10px;
  background: #f6f3f0;
}

.error {
  margin-top: 12px;
  color: #c2410c;
  font-size: 13px;
}

.btn {
  margin-top: 20px;
  background: #ff5c39;
  color: #fff;
  border: none;
  border-radius: 999px;
}

.btn::after {
  border: none;
}

.btn[disabled] {
  opacity: 0.5;
}
</style>
