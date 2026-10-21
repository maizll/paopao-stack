<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { api, ApiError, setTenantSlug, setToken, tenantSlug, token } from './api'
import type { AuthUser, Banner, Board, GridItem, Health, Page, PostItem, SiteConfig, SoftwareItem } from './types'

const slug = ref(tenantSlug())
const loading = ref(true)
const error = ref('')
const health = ref<Health | null>(null)
const config = ref<SiteConfig | null>(null)
const banners = ref<Banner[]>([])
const grid = ref<GridItem[]>([])
const boards = ref<Board[]>([])
const posts = ref<PostItem[]>([])
const apps = ref<SoftwareItem[]>([])
const activeBoard = ref<number | null>(null)
const user = ref<AuthUser | null>(null)
const account = ref('member@demo.paopao.local')
const password = ref('password')
const showLogin = ref(false)
const loginError = ref('')
const title = ref('')
const content = ref('')
const boardId = ref<number | ''>('')
const postMessage = ref('')
const posting = ref(false)

const visiblePosts = computed(() => {
  if (!activeBoard.value) return posts.value
  return posts.value.filter((post) => post.board?.id === activeBoard.value)
})

const bannerTitle = computed(() => banners.value[0]?.title || config.value?.tenant?.name || '泡泡栈')

function messageOf(cause: unknown): string {
  return cause instanceof ApiError ? cause.message : '请求失败'
}

async function load(): Promise<void> {
  loading.value = true
  error.value = ''
  postMessage.value = ''
  try {
    const [healthRes, configRes, bannerRes, gridRes, boardRes, postRes, appRes] = await Promise.all([
      api<Health>('/api/v1/system/health'),
      api<SiteConfig>('/api/v1/system/config'),
      api<Banner[]>('/api/v1/ops/banners'),
      api<GridItem[]>('/api/v1/ops/grid'),
      api<Board[]>('/api/v1/boards'),
      api<Page<PostItem>>('/api/v1/posts?sort=new'),
      api<Page<SoftwareItem>>('/api/v1/apps?sort=new'),
    ])
    health.value = healthRes.data
    config.value = configRes.data
    banners.value = bannerRes.data
    grid.value = gridRes.data
    boards.value = boardRes.data
    posts.value = postRes.data.items
    apps.value = appRes.data.items
    if (!boardId.value && boards.value[0]) boardId.value = boards.value[0].id
    if (token()) {
      const me = await api<AuthUser & { permissions: string[] }>('/api/v1/auth/me')
      user.value = me.data
    } else {
      user.value = null
    }
  } catch (cause) {
    error.value = messageOf(cause)
    if (cause instanceof ApiError && cause.status === 401) {
      setToken(null)
      user.value = null
    }
  } finally {
    loading.value = false
  }
}

async function applyTenant(): Promise<void> {
  setTenantSlug(slug.value.trim() || 'demo')
  slug.value = tenantSlug()
  activeBoard.value = null
  await load()
}

async function login(): Promise<void> {
  loginError.value = ''
  try {
    const result = await api<{ access_token: string; user: AuthUser }>('/api/v1/auth/login', {
      method: 'POST',
      body: JSON.stringify({ account: account.value, password: password.value }),
    })
    setToken(result.data.access_token)
    user.value = result.data.user
    showLogin.value = false
  } catch (cause) {
    loginError.value = messageOf(cause)
  }
}

function logout(): void {
  setToken(null)
  user.value = null
  postMessage.value = ''
}

async function publish(): Promise<void> {
  if (!boardId.value) return
  posting.value = true
  postMessage.value = ''
  try {
    await api('/api/v1/posts', {
      method: 'POST',
      body: JSON.stringify({
        board_id: boardId.value,
        title: title.value,
        content: content.value,
      }),
    })
    title.value = ''
    content.value = ''
    postMessage.value = '已提交审核。通过后会出现在公开列表。'
  } catch (cause) {
    postMessage.value = messageOf(cause)
  } finally {
    posting.value = false
  }
}

function filterBoard(id: number): void {
  activeBoard.value = activeBoard.value === id ? null : id
}

onMounted(load)
</script>

<template>
  <div class="app">
    <header class="top">
      <div class="brand">
        <div class="mark" aria-hidden="true"><span></span><span></span></div>
        <div>
          <p class="eyebrow">论坛 + 软件商店</p>
          <h1>泡泡栈</h1>
        </div>
      </div>
      <div class="health">
        <span class="pill" :class="{ bad: health?.status !== 'ok' }">
          {{ health?.status === 'ok' ? '服务正常' : loading ? '连接中' : '服务异常' }}
        </span>
        <form class="tenant-switch" @submit.prevent="applyTenant">
          <label>
            租户
            <input v-model="slug" aria-label="租户标识" />
          </label>
          <button class="ghost" type="submit">切换</button>
        </form>
      </div>
    </header>

    <p v-if="loading" class="state">正在打开 {{ slug }} …</p>
    <p v-else-if="error" class="state error">{{ error }}</p>

    <div v-else class="layout">
      <div class="stack">
        <section class="banner">
          <span>{{ banners[0]?.link_type === 'board' ? '运营位 · 圈子' : '运营位' }}</span>
          <strong>{{ bannerTitle }}</strong>
        </section>

        <section class="card">
          <h2>入口</h2>
          <div v-if="grid.length" class="grid">
            <button v-for="item in grid" :key="item.id" type="button">
              <span v-if="item.badge" class="badge">{{ item.badge }}</span>
              <b>{{ item.title }}</b>
              <em>九宫格</em>
            </button>
          </div>
          <p v-else class="state">这个租户还没有九宫格入口。</p>
        </section>

        <section class="card">
          <h2>版块</h2>
          <div v-if="boards.length" class="chips">
            <button
              v-for="board in boards"
              :key="board.id"
              class="chip"
              :class="{ active: activeBoard === board.id }"
              type="button"
              @click="filterBoard(board.id)"
            >
              {{ board.name }} · {{ board.post_count }}
            </button>
          </div>
          <p v-else class="state">这个租户还没有已发布的版块。</p>
        </section>

        <section class="card">
          <h2>讨论</h2>
          <article v-for="post in visiblePosts" :key="post.id" class="post">
            <div>
              <h3>{{ post.title }}</h3>
              <p class="meta">{{ post.board?.name || '版块' }} · {{ post.author?.nickname || '匿名' }}</p>
              <p class="meta">{{ post.excerpt }}</p>
            </div>
          </article>
          <p v-if="!visiblePosts.length" class="state">还没有已发布的帖子。登录后可以先发一篇，等管理员通过。</p>
        </section>
      </div>

      <aside class="stack">
        <section class="card">
          <h2>{{ user ? user.nickname : '登录后发帖' }}</h2>
          <div v-if="user" class="form">
            <p class="meta">角色 {{ user.roles.join('、') }}</p>
            <select v-model="boardId" aria-label="选择版块">
              <option v-for="board in boards" :key="board.id" :value="board.id">{{ board.name }}</option>
            </select>
            <input v-model="title" placeholder="标题" maxlength="80" />
            <textarea v-model="content" placeholder="写给这个圈子的正文"></textarea>
            <div class="actions">
              <button class="primary" type="button" :disabled="posting" @click="publish">提交审核</button>
              <button class="ghost" type="button" @click="logout">退出</button>
            </div>
            <p v-if="postMessage" class="ok-note">{{ postMessage }}</p>
          </div>
          <div v-else>
            <button class="primary" type="button" @click="showLogin = !showLogin">
              {{ showLogin ? '收起' : '成员登录' }}
            </button>
            <form v-if="showLogin" class="form" @submit.prevent="login">
              <input v-model="account" autocomplete="username" aria-label="账号" />
              <input v-model="password" type="password" autocomplete="current-password" aria-label="密码" />
              <button class="primary" type="submit">登录</button>
              <p v-if="loginError" class="state error">{{ loginError }}</p>
              <p class="meta">演示成员 member@demo.paopao.local / password</p>
            </form>
          </div>
        </section>

        <section class="card">
          <h2>软件商店</h2>
          <article v-for="app in apps" :key="app.id" class="app-row">
            <div>
              <h3>{{ app.name }}</h3>
              <p class="meta">{{ app.category?.name || '未分类' }} · {{ app.latest_version?.version_name || '暂无版本' }}</p>
              <p class="meta">{{ app.summary }}</p>
            </div>
            <div class="count">
              <strong>{{ app.download_count }}</strong>
              下载
            </div>
          </article>
          <p v-if="!apps.length" class="state">商店里还没有已上架的软件。</p>
        </section>
      </aside>
    </div>

    <p class="foot">
      {{ config?.tenant ? `${config.tenant.name} · 套餐 ${config.tenant.plan_code}` : '未解析到租户' }}。
      商城、支付、私信、短视频是二期，这里没有入口。
    </p>
  </div>
</template>
