<script setup lang="ts">
import { computed, ref } from 'vue'
import { onShow } from '@dcloudio/uni-app'
import { ApiError, request, token } from '../../utils/api'
import type { Board } from '../../utils/types'

const boards = ref<Board[]>([])
const boardId = ref(0)
const title = ref('')
const content = ref('')
const error = ref('')
const submitting = ref(false)
const ready = ref(false)
const sheet = ref(false)

const boardName = computed(() => boards.value.find((item) => item.id === boardId.value)?.name || '')

async function loadBoards(): Promise<void> {
  if (!token()) {
    uni.showToast({ title: '先登录再发帖', icon: 'none' })
    uni.switchTab({ url: '/pages/mine/mine' })
    return
  }
  try {
    boards.value = await request<Board[]>('/boards')
    ready.value = true
    if (!boardId.value && boards.value[0]) boardId.value = boards.value[0].id
    if (!boards.value.length) error.value = '当前租户还没有可发帖的版块'
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : '读取版块失败'
  }
}

function pick(id: number): void {
  boardId.value = id
  sheet.value = false
}

function back(): void {
  const pages = getCurrentPages()
  if (pages.length > 1) uni.navigateBack()
  else uni.switchTab({ url: '/pages/community/community' })
}

function soonCover(): void {
  uni.showToast({ title: '上传还没开放', icon: 'none' })
}

async function submit(): Promise<void> {
  if (!boardId.value) {
    error.value = '先选择版块'
    sheet.value = true
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
        board_id: boardId.value,
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
    <app-nav title="发布笔记">
      <template #left>
        <view class="back" @click="back">
          <uni-icons type="back" size="22" color="#16160e" />
        </view>
      </template>
    </app-nav>

    <view class="editor">
      <view class="media-row">
        <view class="media">
          <image class="media-img" src="/static/covers/cover-notes.jpg" mode="aspectFill" />
          <text class="media-badge">默认封面</text>
        </view>
        <view class="media add" @click="soonCover">
          <uni-icons type="plusempty" size="26" color="#c0c4cc" />
        </view>
      </view>
      <text class="media-tip">封面地址由管理员在审核时填写，这里不能上传。</text>
      <input v-model="title" class="title-input" maxlength="80" placeholder="填写标题会有更多赞哦" placeholder-class="ph" />
      <textarea
        v-model="content"
        class="content-input"
        maxlength="4000"
        placeholder="添加正文，让更多人看见你"
        placeholder-class="ph"
      />
    </view>

    <view class="cell" @click="sheet = true">
      <text class="cell-label">选择版块</text>
      <text class="cell-value" :class="{ ph: !boardName }">{{ boardName || '请选择' }}</text>
      <uni-icons type="right" size="16" color="#c0c4cc" />
    </view>
    <text v-if="error" class="error">{{ error }}</text>
    <view class="pad" />

    <view class="footer">
      <button class="publish" :disabled="submitting || !ready" @click="submit">
        {{ submitting ? '发布中…' : '发布' }}
      </button>
    </view>

    <view v-if="sheet" class="mask" @click="sheet = false">
      <view class="sheet" @click.stop>
        <text class="sheet-title">选择版块</text>
        <view v-if="!boards.length" class="sheet-empty">暂无版块</view>
        <view
          v-for="board in boards"
          :key="board.id"
          class="sheet-item"
          :class="{ on: board.id === boardId }"
          @click="pick(board.id)"
        >
          {{ board.name }}
        </view>
      </view>
    </view>
  </view>
</template>

<style scoped>
.page {
  min-height: 100vh;
  background: #fff;
  padding-bottom: 76px;
  box-sizing: border-box;
}

.back {
  width: 36px;
  height: 36px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.editor {
  padding: 8px 16px 0;
}

.media-row {
  display: flex;
  gap: 8px;
}

.media {
  position: relative;
  width: 84px;
  height: 84px;
  border-radius: 8px;
  overflow: hidden;
  background: #f3f4f6;
}

.media-img {
  width: 84px;
  height: 84px;
}

.media-badge {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.45);
  color: #fff;
  font-size: 11px;
  text-align: center;
  line-height: 20px;
}

.media.add {
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px dashed #dcdfe6;
  box-sizing: border-box;
}

.media-tip {
  display: block;
  margin-top: 8px;
  color: #909399;
  font-size: 12px;
}

.title-input {
  margin-top: 16px;
  height: 44px;
  font-size: 18px;
  font-weight: 650;
  color: #16160e;
  border-bottom: 1px solid #f0f0f0;
}

.content-input {
  width: 100%;
  height: 160px;
  margin-top: 12px;
  font-size: 15px;
  line-height: 1.6;
  color: #16160e;
}

.ph {
  color: #c0c4cc;
  font-weight: 400;
}

.cell {
  margin: 8px 16px 0;
  height: 52px;
  display: flex;
  align-items: center;
  border-top: 8px solid #f8fafc;
  border-bottom: 1px solid #f0f0f0;
}

.cell-label {
  color: #16160e;
  font-size: 15px;
}

.cell-value {
  flex: 1;
  text-align: right;
  margin-right: 4px;
  color: #16160e;
  font-size: 14px;
}

.cell-value.ph {
  color: #c0c4cc;
}

.error {
  display: block;
  margin: 12px 16px 0;
  color: #ff2442;
  font-size: 13px;
}

.footer {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  padding: 10px 16px 16px;
  background: #fff;
}

.publish {
  height: 44px;
  line-height: 44px;
  border-radius: 22px;
  background: #ff2442;
  color: #fff;
  font-size: 16px;
  font-weight: 650;
  border: none;
}

.publish::after {
  border: none;
}

.publish[disabled] {
  opacity: 0.5;
}

.mask {
  position: fixed;
  left: 0;
  right: 0;
  top: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.45);
  z-index: 20;
  display: flex;
  align-items: flex-end;
}

.sheet {
  width: 100%;
  background: #fff;
  border-radius: 16px 16px 0 0;
  padding: 16px 16px 28px;
}

.sheet-title {
  display: block;
  text-align: center;
  font-size: 16px;
  font-weight: 650;
  margin-bottom: 8px;
}

.sheet-item,
.sheet-empty {
  height: 48px;
  line-height: 48px;
  text-align: center;
  font-size: 15px;
  color: #16160e;
  border-top: 1px solid #f5f5f5;
}

.sheet-item.on {
  color: #ff2442;
  font-weight: 650;
}

.sheet-empty {
  color: #909399;
}
</style>
