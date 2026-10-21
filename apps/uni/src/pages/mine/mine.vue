<script setup lang="ts">
import { ref } from 'vue'
import { onPullDownRefresh, onShow } from '@dcloudio/uni-app'
import PostFeed from '../../components/post-feed/post-feed.vue'
import { ApiError, request, setTenantSlug, setToken, tenantSlug, token } from '../../utils/api'
import type { AuthResult, AuthUser, Page, PostItem } from '../../utils/types'

const loading = ref(false)
const error = ref('')
const me = ref<AuthUser | null>(null)
const posts = ref<PostItem[]>([])
const account = ref('member@demo.paopao.local')
const password = ref('password')
const slug = ref(tenantSlug())
const submitting = ref(false)

async function loadProfile(): Promise<void> {
  if (!token()) {
    me.value = null
    posts.value = []
    return
  }
  loading.value = true
  error.value = ''
  try {
    me.value = await request<AuthUser>('/auth/me')
    const page = await request<Page<PostItem>>(`/users/${me.value.id}/posts`, { data: { per_page: 20 } })
    posts.value = page.items
  } catch (cause) {
    if (cause instanceof ApiError && (cause.code === 40101 || cause.message.includes('未'))) {
      setToken('')
      me.value = null
    }
    error.value = cause instanceof ApiError ? cause.message : '读取失败'
  } finally {
    loading.value = false
  }
}

async function login(): Promise<void> {
  submitting.value = true
  error.value = ''
  setTenantSlug(slug.value || 'demo')
  setToken('')
  try {
    const result = await request<AuthResult>('/auth/login', {
      method: 'POST',
      data: { account: account.value, password: password.value },
    })
    setToken(result.access_token)
    await loadProfile()
    uni.showToast({ title: '已登录', icon: 'none' })
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : '登录失败'
  } finally {
    submitting.value = false
  }
}

function logout(): void {
  setToken('')
  me.value = null
  posts.value = []
}

function writePost(): void {
  uni.navigateTo({ url: '/pages/post/compose' })
}

function openPost(id: number): void {
  uni.navigateTo({ url: `/pages/post/detail?id=${id}` })
}

onShow(() => {
  slug.value = tenantSlug()
  void loadProfile()
})

onPullDownRefresh(async () => {
  await loadProfile()
  uni.stopPullDownRefresh()
})
</script>

<template>
  <view class="page">
    <view v-if="me" class="hero">
      <image class="hero-photo" src="/static/covers/cover-city.jpg" mode="aspectFill" />
      <view class="hero-shade" />
      <view class="hero-body">
        <view class="avatar">{{ me.nickname.slice(0, 1) }}</view>
        <view>
          <text class="name">{{ me.nickname }}</text>
          <text class="sub">{{ tenantSlug() }} · {{ posts.length }} 篇笔记</text>
        </view>
      </view>
    </view>

    <view v-else class="card">
      <text class="card-title">登录这个圈子</text>
      <text class="label">租户</text>
      <uni-easyinput v-model="slug" placeholder="demo" />
      <text class="label">账号</text>
      <uni-easyinput v-model="account" placeholder="邮箱" />
      <text class="label">密码</text>
      <uni-easyinput v-model="password" type="password" placeholder="密码" />
      <button class="btn" :disabled="submitting" @click="login">{{ submitting ? '登录中…' : '登录' }}</button>
      <text v-if="error" class="error">{{ error }}</text>
    </view>

    <view v-if="me" class="actions">
      <button class="btn" @click="writePost">写一篇</button>
      <button class="btn ghost" @click="logout">退出</button>
    </view>
    <text v-if="me && error" class="error">{{ error }}</text>

    <view v-if="me" class="feed">
      <post-feed
        :items="posts"
        :loading="loading"
        :error="error"
        empty-title="你还没有发过笔记"
        empty-text="写一篇之后会停在待审，通过后才会出现在首页。"
        show-status
        @retry="loadProfile"
        @open="openPost"
      />
    </view>
  </view>
</template>

<style scoped>
.page {
  min-height: 100vh;
  padding: 0 0 24px;
  box-sizing: border-box;
}

.hero {
  position: relative;
  height: 168px;
  margin-bottom: 12px;
  overflow: hidden;
}

.hero-photo {
  width: 100%;
  height: 168px;
}

.hero-shade {
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  height: 96px;
  background: linear-gradient(180deg, transparent, rgba(20, 12, 8, 0.72));
}

.hero-body {
  position: absolute;
  left: 16px;
  right: 16px;
  bottom: 14px;
  display: flex;
  align-items: center;
  gap: 12px;
}

.card,
.actions {
  background: #fff;
  border-radius: 18px;
  padding: 16px;
  margin: 0 12px 12px;
}

.avatar {
  width: 52px;
  height: 52px;
  border-radius: 50%;
  background: #ff5c39;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 22px;
  font-weight: 700;
}

.name,
.card-title {
  display: block;
  font-size: 20px;
  font-weight: 700;
  color: #fff;
}

.card-title {
  color: #1f1a17;
}

.sub,
.muted,
.label {
  display: block;
  color: rgba(255, 255, 255, 0.86);
  font-size: 13px;
}

.label,
.muted {
  color: #6f675f;
}

.label {
  margin: 12px 0 6px;
}

.actions {
  display: flex;
  gap: 10px;
  background: transparent;
  padding: 0;
}

.actions .btn {
  flex: 1;
}

.btn {
  margin-top: 16px;
  background: #ff5c39;
  color: #fff;
  border: none;
  border-radius: 999px;
}

.btn.ghost {
  background: #fff;
  color: #1f1a17;
}

.btn::after {
  border: none;
}

.error {
  display: block;
  margin-top: 10px;
  color: #c2410c;
  font-size: 13px;
}

.feed {
  padding: 0 8px 12px;
}
</style>
