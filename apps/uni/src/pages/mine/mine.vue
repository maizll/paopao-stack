<script setup lang="ts">
import { computed, ref } from 'vue'
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
const tab = ref(0)

const filtered = computed(() => {
  if (tab.value === 1) return posts.value.filter((item) => item.status === 'pending')
  if (tab.value === 2) return posts.value.filter((item) => item.status === 'rejected')
  return posts.value
})

const likes = computed(() => posts.value.reduce((sum, item) => sum + (item.stats?.like || 0), 0))
const pending = computed(() => posts.value.filter((item) => item.status === 'pending').length)

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
  tab.value = 0
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
    <view class="cover">
      <image class="cover-photo" src="/static/covers/cover-city.jpg" mode="aspectFill" />
      <view class="cover-shade" />
      <view class="cover-nav">
        <phone-status tone="light" />
        <view class="cover-bar">
          <text class="cover-title">我的</text>
          <text v-if="me" class="cover-action" @click="writePost">发布</text>
        </view>
      </view>
    </view>

    <view v-if="me" class="sheet">
      <view class="profile">
        <view class="avatar">{{ me.nickname.slice(0, 1) }}</view>
        <view class="profile-main">
          <text class="name">{{ me.nickname }}</text>
          <text class="sub">泡泡号 {{ me.id }} · {{ tenantSlug() }}</text>
        </view>
        <text class="logout" @click="logout">退出</text>
      </view>
      <text class="bio">笔记通过审核后，会出现在首页和社区。</text>
      <view class="stats">
        <view class="stat">
          <text class="num">{{ posts.length }}</text>
          <text class="label">笔记</text>
        </view>
        <view class="stat">
          <text class="num">{{ likes }}</text>
          <text class="label">获赞</text>
        </view>
        <view class="stat">
          <text class="num">{{ pending }}</text>
          <text class="label">待审</text>
        </view>
      </view>
      <view class="tabs">
        <view class="tab" :class="{ on: tab === 0 }" @click="tab = 0">
          <text>笔记</text>
          <view class="line" />
        </view>
        <view class="tab" :class="{ on: tab === 1 }" @click="tab = 1">
          <text>待审</text>
          <view class="line" />
        </view>
        <view class="tab" :class="{ on: tab === 2 }" @click="tab = 2">
          <text>未通过</text>
          <view class="line" />
        </view>
      </view>
    </view>

    <view v-else class="login">
      <text class="login-title">登录泡泡栈</text>
      <text class="login-sub">演示成员已填好，登录后可以发笔记。</text>
      <text class="field-label">租户</text>
      <input v-model="slug" class="field" placeholder="demo" />
      <text class="field-label">账号</text>
      <input v-model="account" class="field" placeholder="邮箱" />
      <text class="field-label">密码</text>
      <input v-model="password" class="field" password placeholder="密码" />
      <button class="btn" :disabled="submitting" @click="login">{{ submitting ? '登录中…' : '登录' }}</button>
      <text v-if="error" class="error">{{ error }}</text>
    </view>

    <view v-if="me" class="feed">
      <text v-if="error" class="error pad">{{ error }}</text>
      <post-feed
        :items="filtered"
        :loading="loading"
        :error="''"
        :empty-title="tab === 0 ? '还没有笔记' : '这里是空的'"
        :empty-text="tab === 1 ? '没有待审笔记。' : '去社区发布一篇，通过后会出现在首页。'"
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
  background: #f8fafc;
}

.cover {
  position: relative;
  height: 168px;
}

.cover-photo,
.cover-shade,
.cover-nav {
  position: absolute;
  left: 0;
  right: 0;
  top: 0;
}

.cover-photo {
  width: 100%;
  height: 168px;
}

.cover-shade {
  height: 168px;
  background: linear-gradient(180deg, rgba(0, 0, 0, 0.28), rgba(0, 0, 0, 0.05) 42%, rgba(0, 0, 0, 0.35));
}

.cover-bar {
  height: 44px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 16px;
}

.cover-title,
.cover-action {
  color: #fff;
  font-size: 17px;
  font-weight: 650;
}

.cover-action {
  font-size: 15px;
}

.sheet,
.login {
  position: relative;
  margin-top: -28px;
  background: #fff;
  border-radius: 16px 16px 0 0;
  padding: 0 16px 8px;
}

.profile {
  display: flex;
  align-items: flex-end;
  gap: 12px;
}

.avatar {
  width: 72px;
  height: 72px;
  margin-top: -28px;
  border-radius: 50%;
  border: 3px solid #fff;
  background: #ff2442;
  color: #fff;
  font-size: 28px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex: none;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
}

.profile-main {
  flex: 1;
  min-width: 0;
  padding-bottom: 6px;
}

.name {
  display: block;
  font-size: 20px;
  font-weight: 700;
  color: #16160e;
}

.sub,
.bio {
  display: block;
  margin-top: 4px;
  color: #909399;
  font-size: 12px;
}

.logout {
  padding-bottom: 8px;
  color: #606266;
  font-size: 13px;
}

.bio {
  margin-top: 12px;
  color: #606266;
  font-size: 13px;
  line-height: 1.5;
}

.stats {
  display: flex;
  margin-top: 14px;
}

.stat {
  width: 72px;
  display: flex;
  flex-direction: column;
}

.num {
  font-size: 18px;
  font-weight: 700;
  color: #16160e;
}

.label {
  margin-top: 2px;
  font-size: 12px;
  color: #909399;
}

.tabs {
  display: flex;
  margin-top: 12px;
  border-bottom: 1px solid #f0f0f0;
}

.tab {
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-right: 22px;
  padding-top: 8px;
  color: #606266;
  font-size: 15px;
}

.tab.on {
  color: #16160e;
  font-weight: 650;
}

.line {
  width: 18px;
  height: 2px;
  margin-top: 8px;
  border-radius: 2px;
  background: transparent;
}

.tab.on .line {
  background: #ff2442;
}

.login {
  margin: -28px 0 0;
  padding: 20px 16px 28px;
}

.login-title {
  display: block;
  font-size: 22px;
  font-weight: 700;
  color: #16160e;
}

.login-sub,
.field-label,
.error {
  display: block;
}

.login-sub {
  margin-top: 6px;
  color: #909399;
  font-size: 13px;
}

.field-label {
  margin: 14px 0 6px;
  font-size: 12px;
  color: #909399;
}

.field {
  height: 44px;
  padding: 0 12px;
  border-radius: 10px;
  background: #f3f4f6;
  font-size: 15px;
}

.btn {
  margin-top: 18px;
  background: #ff2442;
  color: #fff;
}

.btn[disabled] {
  opacity: 0.55;
}

.error {
  margin-top: 10px;
  color: #ff2442;
  font-size: 13px;
}

.error.pad {
  padding: 8px 16px 0;
}

.feed {
  padding: 8px 0 20px;
  background: #f8fafc;
}
</style>
