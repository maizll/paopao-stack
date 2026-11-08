<script lang="ts" setup>
import { computed } from 'vue';

type Audience = 'guest' | 'member';

export interface ShellPost {
  author: string;
  board: string;
  comments: number;
  cover: null | string;
  excerpt: string;
  favorites: number;
  id: number;
  likes: number;
  title: string;
  video: boolean;
  views: number;
}

export interface ShellBoard {
  description: string;
  id: number;
  name: string;
  posts: number;
}

export interface ShellApp {
  downloads: number;
  icon: null | string;
  id: number;
  name: string;
  summary: string;
}

export interface ShellProduct {
  cover: null | string;
  id: number;
  price: string;
  summary: string;
  title: string;
}

export interface ShellBanner {
  id: number;
  image: string;
  title: string;
}

export interface ShellChip {
  id: number;
  title: string;
}

export interface ShellMessage {
  body: string;
  id: number;
  title: string;
}

export interface ShellMe {
  followers: number;
  following: number;
  id: number;
  likes: number;
  nickname: string;
  posts: number;
}

export interface ShellFeed {
  apps: ShellApp[];
  banners: ShellBanner[];
  boards: ShellBoard[];
  chips: ShellChip[];
  loading: boolean;
  me: null | ShellMe;
  messages: ShellMessage[];
  plugins: string[];
  posts: ShellPost[];
  products: ShellProduct[];
}

const props = withDefaults(
  defineProps<{
    audience?: Audience;
    caption: string;
    data: ShellFeed;
    modules: Array<{ id: string; label: string }>;
    pageId: string;
    selected: boolean;
    skin: string;
    tabbar: string;
  }>(),
  { audience: 'member' },
);

const emit = defineEmits<{
  open: [id: string];
  select: [];
}>();

const FALLBACK_COVERS = [
  '/admin/shell-thumbs/fallback-1.jpg',
  '/admin/shell-thumbs/fallback-2.jpg',
  '/admin/shell-thumbs/fallback-3.jpg',
  '/admin/shell-thumbs/fallback-4.jpg',
];

const hubShortcuts = ['订单', '软件', '设置', '收藏', '草稿', '客服'];

function coverOf(src: string | null | undefined, index: number): string {
  if ((src && /^(https?:)?\/\//i.test(src)) || (src && src.startsWith('/'))) return src;
  return FALLBACK_COVERS[index % FALLBACK_COVERS.length] ?? FALLBACK_COVERS[0];
}

function onCoverError(event: Event, index: number) {
  const img = event.target;
  if (!(img instanceof HTMLImageElement) || img.dataset.fb === '1') return;
  img.dataset.fb = '1';
  img.src = FALLBACK_COVERS[index % FALLBACK_COVERS.length] ?? FALLBACK_COVERS[0];
}

const pageLabel = computed(() => {
  const found = props.modules.find((item) => item.id === props.pageId);
  if (found) return found.label;
  const names: Record<string, string> = {
    community: '社区',
    discover: '发现',
    home: '首页',
    login: '登录',
    mall: '商城',
    messages: '消息',
    mine: '我的',
  };
  return names[props.pageId] || '页面';
});

const slots = computed(() => {
  const tabs = props.modules.map((item) => ({ kind: 'tab' as const, ...item }));
  if (props.tabbar !== 'raised') return tabs;
  const mid = Math.ceil(tabs.length / 2);
  return [
    ...tabs.slice(0, mid),
    { kind: 'plus' as const, id: 'plus', label: '发布' },
    ...tabs.slice(mid),
  ];
});

const homePosts = computed(() => props.data.posts.slice(0, 6));
const profile = computed(() => (props.audience === 'guest' ? null : props.data.me));
const showTitle = computed(() => !['community', 'home', 'login', 'mine'].includes(props.pageId));

function initial(value: string) {
  const text = value.trim();
  return text ? text.slice(0, 1) : '泡';
}

function onTab(slot: { id: string; kind: 'plus' | 'tab' }) {
  emit('select');
  if (slot.kind === 'tab') emit('open', slot.id);
}
</script>

<template>
  <figure class="unit">
    <div class="bezel" :class="{ on: selected }" @click="emit('select')">
      <div class="screen">
        <div class="status">
          <span>9:41</span>
          <i class="island" aria-hidden="true" />
          <span class="sig" aria-hidden="true" />
        </div>
        <div class="body" :class="[`page-${pageId}`, `skin-${skin}`]">
          <p v-if="showTitle" class="ptitle">{{ pageLabel }}</p>
          <p v-if="data.loading" class="empty">正在读取这个租户的内容</p>

          <template v-else-if="pageId === 'home'">
            <div v-if="skin === 'feed'" class="stage">
              <img
                class="stage-frame"
                alt=""
                :src="coverOf(homePosts[0]?.cover, 0)"
                @error="onCoverError($event, 0)"
              />
              <div class="stage-scrim">
                <div class="stage-tabs">
                  <span>关注</span>
                  <span class="on">推荐</span>
                  <span>直播</span>
                </div>
                <i class="stage-play" aria-hidden="true" />
                <p v-if="!homePosts.length" class="stage-empty">还没有作品</p>
                <div v-else class="stage-foot">
                  <div class="stage-author">
                    <i>{{ initial(homePosts[0].author) }}</i>
                    <b>@{{ homePosts[0].author || '匿名' }}</b>
                    <span>关注</span>
                  </div>
                  <strong>{{ homePosts[0].title }}</strong>
                  <div class="stage-side">
                    <span>赞 {{ homePosts[0].likes }}</span>
                    <span>评 {{ homePosts[0].comments }}</span>
                    <span>藏 {{ homePosts[0].favorites }}</span>
                  </div>
                </div>
              </div>
            </div>
            <div v-else-if="skin === 'compact'" class="stream">
              <header class="stream-top">
                <b>关注</b>
                <span>只看已关注的人</span>
              </header>
              <p v-if="!homePosts.length" class="empty">还没有笔记</p>
              <article v-for="(post, index) in homePosts.slice(0, 3)" :key="post.id" class="stream-card">
                <p class="stream-title">{{ post.title }}</p>
                <p v-if="post.excerpt" class="stream-excerpt">{{ post.excerpt }}</p>
                <img
                  class="stream-cover"
                  :alt="post.title"
                  :src="coverOf(post.cover, index)"
                  @error="onCoverError($event, index)"
                />
                <p class="stream-meta">{{ post.author || '匿名' }} · {{ post.views }} 浏览</p>
              </article>
            </div>
            <div v-else-if="skin === 'haosns'" class="sns-home">
              <p v-if="!homePosts.length" class="empty">还没有动态</p>
              <article v-for="(post, index) in homePosts.slice(0, 2)" :key="post.id" class="sns-card">
                <div class="sns-who">
                  <em>{{ initial(post.author) }}</em>
                  <b>{{ post.author }}</b>
                  <i>成员</i>
                </div>
                <p class="sns-title">{{ post.title }}</p>
                <img
                  class="sns-photo sns-photo-lg"
                  :alt="post.title"
                  :src="coverOf(post.cover, index)"
                  @error="onCoverError($event, index)"
                />
                <div class="sns-acts">
                  <span>赞 {{ post.likes }}</span>
                  <span>评 {{ post.comments }}</span>
                  <span>转</span>
                  <span>藏 {{ post.favorites }}</span>
                </div>
              </article>
            </div>
            <template v-else>
              <div class="mini-tabs">
                <span>关注</span>
                <span class="on">推荐</span>
                <span>最新</span>
              </div>
              <p v-if="!homePosts.length" class="empty">还没有笔记</p>
              <div v-else class="waterfall">
                <article v-for="(post, index) in homePosts.slice(0, 4)" :key="post.id" class="fall">
                  <img
                    :alt="post.title"
                    :src="coverOf(post.cover, index)"
                    @error="onCoverError($event, index)"
                  />
                  <b>{{ post.title }}</b>
                  <span>{{ post.author }}</span>
                </article>
              </div>
            </template>
          </template>

          <template v-else-if="pageId === 'community'">
            <template v-if="skin === 'forum'">
              <div class="sns-plain">社区</div>
              <div class="sns-chips">
                <span class="on">全部</span>
                <span v-for="board in data.boards.slice(0, 4)" :key="board.id">{{ board.name }}</span>
              </div>
              <p v-if="!data.posts.length" class="empty">还没有帖子</p>
              <article
                v-for="(post, index) in data.posts.slice(0, 2)"
                v-else
                :key="post.id"
                class="sns-card long"
              >
                <div class="sns-who">
                  <em>{{ initial(post.author) }}</em>
                  <b>{{ post.author }}</b>
                  <i>成员</i>
                </div>
                <p class="sns-title">{{ post.title }}</p>
                <p v-if="post.excerpt" class="sns-excerpt">{{ post.excerpt }}</p>
                <img
                  class="sns-photo"
                  :alt="post.title"
                  :src="coverOf(post.cover, index)"
                  @error="onCoverError($event, index)"
                />
                <div class="sns-foot">
                  <span>{{ post.views }} 浏览</span>
                  <span>赞 {{ post.likes }} · 藏 {{ post.favorites }} · 评 {{ post.comments }}</span>
                </div>
              </article>
            </template>
            <div v-else-if="skin === 'haosns'" class="circle-discover">
              <div class="sns-search"><i /><span>搜索圈子</span></div>
              <div class="sns-tabs">
                <span>关注</span>
                <span class="on">发现</span>
                <span>附近</span>
              </div>
              <div class="circle-rail">
                <article v-for="(board, index) in data.boards.slice(0, 4)" :key="board.id" class="circle-tile">
                  <img
                    :alt="board.name"
                    :src="coverOf(null, index)"
                    @error="onCoverError($event, index)"
                  />
                  <b>{{ board.name }}</b>
                </article>
              </div>
              <p v-if="!data.posts.length" class="empty">还没有圈子动态</p>
              <article v-for="(post, index) in data.posts.slice(0, 2)" :key="post.id" class="circle-post">
                <img
                  :alt="post.title"
                  :src="coverOf(post.cover, index + 1)"
                  @error="onCoverError($event, index + 1)"
                />
                <div>
                  <b>{{ post.board || '圈子' }}</b>
                  <span>{{ post.title }}</span>
                  <i>加入</i>
                </div>
              </article>
            </div>
            <div v-else-if="skin === 'board'" class="board-dir">
              <header class="board-head">
                <b>版块</b>
                <span>按地盘进帖</span>
              </header>
              <p v-if="!data.boards.length" class="empty">还没有版块</p>
              <article v-for="(board, index) in data.boards.slice(0, 4)" :key="board.id" class="board-tile">
                <img
                  :alt="board.name"
                  :src="coverOf(null, index)"
                  @error="onCoverError($event, index)"
                />
                <div>
                  <b>{{ board.name }}</b>
                  <p>{{ board.description || '进入这个版块看帖' }}</p>
                  <span>{{ board.posts }} 帖</span>
                </div>
              </article>
            </div>
            <div v-else class="scan">
              <header class="board-head">
                <b>最新</b>
                <span>快速扫版</span>
              </header>
              <p v-if="!data.posts.length" class="empty">还没有帖子</p>
              <article v-for="(post, index) in data.posts.slice(0, 6)" :key="post.id" class="scan-row">
                <img
                  :alt="post.title"
                  :src="coverOf(post.cover, index)"
                  @error="onCoverError($event, index)"
                />
                <b>{{ post.title }}</b>
                <span>{{ post.comments }}</span>
              </article>
            </div>
          </template>

          <template v-else-if="pageId === 'discover'">
            <template v-if="skin === 'shelf'">
              <div v-if="data.chips.length" class="chips">
                <span v-for="chip in data.chips.slice(0, 4)" :key="chip.id">{{ chip.title }}</span>
              </div>
              <p v-if="!data.apps.length" class="empty">还没有上架的软件</p>
              <div v-else class="apps is-shelf">
                <article v-for="(app, index) in data.apps.slice(0, 5)" :key="app.id" class="app">
                  <img
                    :alt="app.name"
                    :src="coverOf(app.icon, index)"
                    @error="onCoverError($event, index)"
                  />
                  <div>
                    <b>{{ app.name }}</b>
                    <span>{{ app.summary || '暂无简介' }}</span>
                  </div>
                  <i>获取</i>
                </article>
              </div>
            </template>
            <div v-else-if="skin === 'grid'" class="discover-grid">
              <p v-if="!data.apps.length" class="empty">还没有上架的软件</p>
              <article v-for="(app, index) in data.apps.slice(0, 6)" :key="app.id">
                <img
                  :alt="app.name"
                  :src="coverOf(app.icon, index)"
                  @error="onCoverError($event, index)"
                />
                <b>{{ app.name }}</b>
                <span>{{ app.summary || '暂无简介' }}</span>
              </article>
            </div>
            <div v-else-if="skin === 'list'" class="find-list">
              <div class="sns-search"><i /><span>搜索应用</span></div>
              <p v-if="!data.apps.length" class="empty">没有匹配的应用</p>
              <article v-for="(app, index) in data.apps.slice(0, 6)" :key="app.id" class="find-row">
                <img
                  :alt="app.name"
                  :src="coverOf(app.icon, index)"
                  @error="onCoverError($event, index)"
                />
                <b>{{ app.name }}</b>
                <small>{{ app.downloads }} 次</small>
              </article>
            </div>
            <div v-else class="mix-discover">
              <div class="sns-search"><i /><span>搜索发现</span></div>
              <div v-if="data.chips.length" class="hot-words">
                <span v-for="chip in data.chips.slice(0, 4)" :key="chip.id">{{ chip.title }}</span>
              </div>
              <p v-if="!data.apps.length" class="empty">还没有可发现的内容</p>
              <article v-for="(app, index) in data.apps.slice(0, 2)" :key="app.id" class="mix-card">
                <img
                  :alt="app.name"
                  :src="coverOf(app.icon, index)"
                  @error="onCoverError($event, index)"
                />
                <b>{{ app.name }}</b>
                <p>{{ app.summary || '热门内容' }}</p>
              </article>
            </div>
          </template>

          <template v-else-if="pageId === 'mall'">
            <p v-if="!data.products.length" class="empty">还没有商品</p>
            <div v-else-if="skin === 'poster'" class="poster-stack">
              <article v-for="(item, index) in data.products.slice(0, 2)" :key="item.id" class="poster">
                <img
                  :alt="item.title"
                  :src="coverOf(item.cover, index)"
                  @error="onCoverError($event, index)"
                />
                <b>{{ item.title }}</b>
                <span>{{ item.summary || '卡密自动发货' }}</span>
                <strong>¥{{ item.price }}</strong>
              </article>
            </div>
            <div v-else-if="skin === 'haosns'" class="sns-mall">
              <article v-for="(item, index) in data.products.slice(0, 2)" :key="item.id" class="sns-good">
                <img
                  :alt="item.title"
                  :src="coverOf(item.cover, index)"
                  @error="onCoverError($event, index)"
                />
                <b>{{ item.title }}</b>
                <strong>¥{{ item.price }}</strong>
              </article>
            </div>
            <div v-else-if="skin === 'card'" class="sku-cards">
              <article v-for="(item, index) in data.products.slice(0, 4)" :key="item.id">
                <img
                  :alt="item.title"
                  :src="coverOf(item.cover, index)"
                  @error="onCoverError($event, index)"
                />
                <b>{{ item.title }}</b>
                <span>{{ item.summary || '卡密自动发货' }}</span>
                <strong>¥{{ item.price }}</strong>
              </article>
            </div>
            <div v-else class="price-rows">
              <article v-for="(item, index) in data.products.slice(0, 6)" :key="item.id">
                <img
                  :alt="item.title"
                  :src="coverOf(item.cover, index)"
                  @error="onCoverError($event, index)"
                />
                <b>{{ item.title }}</b>
                <strong>¥{{ item.price }}</strong>
              </article>
            </div>
          </template>

          <template v-else-if="pageId === 'messages'">
            <template v-if="skin === 'list'">
              <div class="inbox">
                <span>赞和收藏</span>
                <span>关注</span>
                <span>评论</span>
                <span>系统</span>
              </div>
              <p v-if="!data.messages.length" class="empty">还没有消息</p>
              <div v-else class="msgs">
                <article v-for="item in data.messages.slice(0, 5)" :key="item.id" class="msg">
                  <em>{{ initial(item.title) }}</em>
                  <div>
                    <b>{{ item.title }}</b>
                    <span>{{ item.body }}</span>
                  </div>
                </article>
              </div>
            </template>
            <div v-else-if="skin === 'bubble'" class="chat">
              <header class="chat-bar">
                <b>会话</b>
                <span>在线</span>
              </header>
              <p v-if="!data.messages.length" class="empty">还没有消息</p>
              <div
                v-for="(item, index) in data.messages.slice(0, 4)"
                :key="item.id"
                class="chat-line"
                :class="{ mine: index % 2 === 1 }"
              >
                <img
                  v-if="index % 2 === 0"
                  class="chat-ava"
                  alt=""
                  :src="coverOf(null, index)"
                  @error="onCoverError($event, index)"
                />
                <div class="chat-bubble">
                  <p>{{ item.body || item.title }}</p>
                </div>
              </div>
              <div class="chat-input">说点什么</div>
            </div>
            <div v-else-if="skin === 'compact'" class="note-rows">
              <p v-if="!data.messages.length" class="empty">还没有消息</p>
              <article v-for="(item, index) in data.messages.slice(0, 7)" :key="item.id">
                <img
                  alt=""
                  :src="coverOf(null, index)"
                  @error="onCoverError($event, index)"
                />
                <b>{{ item.title }}</b>
                <i />
              </article>
            </div>
            <div v-else class="act-list">
              <p v-if="!data.messages.length" class="empty">还没有消息</p>
              <article v-for="(item, index) in data.messages.slice(0, 3)" :key="item.id" class="act-card">
                <img
                  class="act-ava"
                  alt=""
                  :src="coverOf(null, index)"
                  @error="onCoverError($event, index)"
                />
                <div>
                  <b>{{ item.title }}</b>
                  <p>{{ item.body }}</p>
                </div>
                <img
                  class="act-cover"
                  alt=""
                  :src="coverOf(null, index + 1)"
                  @error="onCoverError($event, index + 1)"
                />
              </article>
            </div>
          </template>

          <template v-else-if="pageId === 'login'">
            <div class="login-nav">登录</div>
            <div class="login">
              <b>登录泡泡栈</b>
              <span class="login-sub">用邮箱登录后可以发笔记、抽奖和改密码。</span>
              <span class="login-label">邮箱</span>
              <div class="login-field">请输入邮箱</div>
              <span class="login-label">密码</span>
              <div class="login-field">请输入密码</div>
              <div class="login-btn">登录</div>
            </div>
          </template>

          <template v-else-if="pageId === 'mine' && skin === 'haosns'">
            <div class="sns-banner" />
            <div class="sns-who mine-who">
              <em>{{ profile ? initial(profile.nickname) : '我' }}</em>
              <b>{{ profile ? profile.nickname : '点击登录' }}</b>
              <i v-if="profile">成员</i>
            </div>
            <div class="badge-row">
              <span>笔记 {{ profile ? profile.posts : 0 }}</span>
              <span>获赞 {{ profile ? profile.likes : 0 }}</span>
              <span>粉丝 {{ profile ? profile.followers : 0 }}</span>
            </div>
            <article v-if="data.posts[0]" class="achieve-note">
              <img
                :alt="data.posts[0].title"
                :src="coverOf(data.posts[0].cover, 0)"
                @error="onCoverError($event, 0)"
              />
              <div>
                <b>{{ data.posts[0].title }}</b>
                <span>{{ data.posts[0].excerpt || '刚刚发布' }}</span>
              </div>
            </article>
            <p v-else class="empty">还没有动态</p>
          </template>

          <template v-else-if="pageId === 'mine' && skin === 'plain'">
            <div class="plain-mine">
              <div class="plain-hero">
                <img
                  class="plain-banner"
                  alt=""
                  :src="coverOf(homePosts[0]?.cover, 1)"
                  @error="onCoverError($event, 1)"
                />
                <em>{{ profile ? initial(profile.nickname) : '我' }}</em>
              </div>
              <b class="plain-name">{{ profile ? profile.nickname : '点击登录' }}</b>
              <p class="plain-bio">{{ profile ? '把简介写在头图下面' : '登录后查看主页' }}</p>
              <article v-for="(post, index) in (profile ? homePosts.slice(0, 3) : [])" :key="post.id" class="plain-row">
                <img
                  :alt="post.title"
                  :src="coverOf(post.cover, index)"
                  @error="onCoverError($event, index)"
                />
                <div>
                  <b>{{ post.title }}</b>
                  <span>{{ post.author }}</span>
                </div>
              </article>
              <p v-if="!profile || !homePosts.length" class="empty">
                {{ profile ? '还没有笔记' : '登录后可以看笔记、发帖和抽奖。' }}
              </p>
            </div>
          </template>

          <template v-else-if="pageId === 'mine' && skin === 'grid'">
            <div class="hub">
              <div class="hub-id">
                <em>{{ profile ? initial(profile.nickname) : '我' }}</em>
                <div>
                  <b>{{ profile ? profile.nickname : '点击登录' }}</b>
                  <span>{{ profile ? `${profile.posts} 笔记 · ${profile.followers} 粉丝` : '登录后使用快捷入口' }}</span>
                </div>
              </div>
              <div class="hub-grid">
                <article v-for="(item, index) in hubShortcuts" :key="item">
                  <img
                    alt=""
                    :src="coverOf(null, index)"
                    @error="onCoverError($event, index)"
                  />
                  <span>{{ item }}</span>
                </article>
              </div>
            </div>
          </template>

          <template v-else>
            <div class="xhs" :class="{ guest: !profile, paper: skin !== 'plain' || !profile }">
              <div class="xhs-id">
                <em>{{ profile ? initial(profile.nickname) : '我' }}</em>
                <div>
                  <b>{{ profile ? profile.nickname : '点击登录' }}</b>
                  <span>{{ profile ? `泡泡号：${profile.id}` : '登录后更精彩' }}</span>
                </div>
              </div>
              <div class="xhs-stats">
                <p><b>{{ profile ? profile.following : '-' }}</b><span>关注</span></p>
                <p><b>{{ profile ? profile.followers : '-' }}</b><span>粉丝</span></p>
                <p><b>{{ profile ? profile.likes : '-' }}</b><span>获赞与收藏</span></p>
              </div>
              <p class="xhs-bio">{{ profile ? '点击这里，填写简介' : '发笔记、抽奖和收藏' }}</p>
              <div class="xhs-chips">
                <span>订单</span>
                <span>软件</span>
                <span>设置</span>
                <span>收藏</span>
              </div>
            </div>
            <div class="xhs-sheet">
              <div class="xhs-tabs">
                <b>笔记</b>
                <span v-if="profile">待审</span>
                <span v-if="profile">收藏</span>
              </div>
              <div v-if="profile && homePosts.length" class="xhs-falls">
                <article v-for="(post, index) in homePosts.slice(0, 2)" :key="post.id">
                  <img
                    :alt="post.title"
                    :src="coverOf(post.cover, index)"
                    @error="onCoverError($event, index)"
                  />
                  <b>{{ post.title }}</b>
                  <span>{{ post.author }}</span>
                </article>
              </div>
              <p v-else class="xhs-empty">{{ profile ? '还没有笔记' : '登录后可以看笔记、发帖和抽奖。' }}</p>
            </div>
          </template>
        </div>
        <div class="bar" :class="`bar-${tabbar}`" @click.stop>
          <button
            v-for="slot in slots"
            :key="slot.kind === 'plus' ? 'plus' : slot.id"
            type="button"
            class="tab"
            :class="{ on: slot.kind === 'tab' && slot.id === pageId, plus: slot.kind === 'plus' }"
            @click="onTab(slot)"
          >
            <span v-if="slot.kind === 'plus'" class="bump">+</span>
            <template v-else>
              <svg v-if="slot.id === 'home'" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M4 10.5 12 4l8 6.5V20a1 1 0 0 1-1 1h-5v-6H10v6H5a1 1 0 0 1-1-1v-9.5Z" />
              </svg>
              <svg v-else-if="slot.id === 'community'" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M5 6.5A2.5 2.5 0 0 1 7.5 4h9A2.5 2.5 0 0 1 19 6.5v6A2.5 2.5 0 0 1 16.5 15H12l-4 3.5V15H7.5A2.5 2.5 0 0 1 5 12.5v-6Z" />
              </svg>
              <svg v-else-if="slot.id === 'discover'" viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="12" cy="12" r="7.25" />
                <path d="m10 14 1.2-3.2L14 10l-1.2 3.2L10 14Z" />
              </svg>
              <svg v-else-if="slot.id === 'mall'" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M6.5 8h11l-.8 10.2a1 1 0 0 1-1 .8H8.3a1 1 0 0 1-1-.8L6.5 8Z" />
                <path d="M9 8V6.8A3 3 0 0 1 12 4a3 3 0 0 1 3 2.8V8" />
              </svg>
              <svg v-else-if="slot.id === 'messages'" viewBox="0 0 24 24" aria-hidden="true">
                <path d="M12 4.5a6.5 6.5 0 0 1 6.5 6.5c0 3.6-2.9 6.2-6.5 6.2-.7 0-1.4-.1-2-.3L6 18.5l.7-2.6A6.4 6.4 0 0 1 5.5 11 6.5 6.5 0 0 1 12 4.5Z" />
              </svg>
              <svg v-else viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="12" cy="9" r="3" />
                <path d="M6.5 18.5a5.5 5.5 0 0 1 11 0" />
              </svg>
              <span v-if="tabbar !== 'icon'">{{ slot.label }}</span>
            </template>
          </button>
        </div>
      </div>
    </div>
    <figcaption>
      <button type="button" :class="{ on: selected }" @click="emit('select')">{{ caption }}</button>
    </figcaption>
  </figure>
</template>

<style scoped>
.unit { width: 280px; margin: 0; flex: none; scroll-snap-align: start; }
.bezel {
  width: 280px;
  aspect-ratio: 9 / 16;
  height: auto;
  padding: 10px;
  border-radius: 40px;
  background: #1c1c1a;
  box-shadow: 0 18px 44px rgba(28, 28, 26, 0.14);
  cursor: pointer;
}
.bezel.on { box-shadow: 0 0 0 2px #f7f6f3, 0 0 0 4px #1c1c1a, 0 16px 40px rgba(28, 28, 26, 0.12); }
.screen {
  height: 100%;
  border-radius: 24px;
  background: #fff;
  overflow: hidden;
  display: flex;
  flex-direction: column;
  color: #16160e;
}
.status {
  height: 28px;
  padding: 0 14px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 12px;
  font-weight: 650;
}
.island {
  width: 64px;
  height: 14px;
  border-radius: 999px;
  background: #1c1c1a;
}
.sig {
  width: 14px;
  height: 8px;
  border: 1.5px solid #16160e;
  border-radius: 2px;
  position: relative;
}
.sig::after {
  content: '';
  position: absolute;
  left: 1px;
  top: 1px;
  bottom: 1px;
  width: 8px;
  background: #16160e;
}
.body { flex: 1; min-height: 0; overflow: hidden; padding: 2px 12px 8px; }
.body.page-home.skin-feed { padding: 0; background: #2a2420; }
.stage { position: relative; height: 100%; min-height: 280px; overflow: hidden; background: #2a2420; }
.stage-frame { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
.stage-scrim { position: absolute; inset: 0; background: linear-gradient(180deg, rgba(22, 18, 16, 0.28) 0%, rgba(22, 18, 16, 0) 34%, rgba(22, 18, 16, 0.62) 100%); color: #fff; }
.stage-tabs { display: flex; justify-content: center; gap: 14px; padding-top: 6px; color: rgba(255, 255, 255, 0.78); font-size: 12px; }
.stage-tabs .on { color: #fff; font-weight: 700; }
.stage-play { position: absolute; left: 50%; top: 42%; width: 36px; height: 36px; margin: -18px 0 0 -18px; border-radius: 50%; background: rgba(255, 255, 255, 0.92); }
.stage-play::after { content: ''; position: absolute; left: 14px; top: 11px; border-style: solid; border-width: 7px 0 7px 11px; border-color: transparent transparent transparent #16160e; }
.stage-empty { margin-top: 92px; text-align: center; font-size: 13px; }
.stage-foot { position: absolute; left: 10px; right: 10px; bottom: 8px; }
.stage-author { display: flex; align-items: center; gap: 6px; }
.stage-author i { width: 22px; height: 22px; flex: none; border-radius: 50%; background: #fff; color: #ff2442; font-style: normal; font-size: 10px; font-weight: 700; line-height: 22px; text-align: center; }
.stage-author b { flex: 1; min-width: 0; overflow: hidden; font-size: 11px; white-space: nowrap; text-overflow: ellipsis; }
.stage-author > span { flex: none; height: 16px; padding: 0 6px; border-radius: 8px; background: #ff2442; font-size: 10px; line-height: 16px; }
.stage-foot strong { display: block; margin-top: 6px; overflow: hidden; font-size: 12px; font-weight: 650; white-space: nowrap; text-overflow: ellipsis; }
.stage-side { display: flex; gap: 8px; margin-top: 6px; font-size: 10px; }
.stream-top, .board-head, .chat-bar { display: flex; align-items: baseline; justify-content: space-between; margin-bottom: 8px; }
.stream-top b, .board-head b, .chat-bar b { font-size: 15px; }
.stream-top span, .board-head span, .chat-bar span { color: #8a847c; font-size: 11px; }
.stream-card { margin-bottom: 10px; padding-bottom: 8px; border-bottom: 1px solid #f1eee8; }
.stream-title { margin: 0; font-size: 13px; font-weight: 700; line-height: 1.35; }
.stream-excerpt { margin: 4px 0 0; color: #5c564e; font-size: 11px; line-height: 1.4; }
.stream-cover { display: block; width: 100%; height: 92px; margin-top: 6px; border-radius: 8px; object-fit: cover; }
.stream-meta { margin: 6px 0 0; color: #8a847c; font-size: 10px; }
.sns-photo-lg { height: 108px; }
.sns-acts { display: flex; justify-content: space-between; margin-top: 6px; color: #5c564e; font-size: 10px; }
.circle-rail { display: flex; gap: 8px; margin-bottom: 8px; overflow: hidden; }
.circle-tile { width: 52px; flex: none; text-align: center; }
.circle-tile img { display: block; width: 52px; height: 52px; border-radius: 50%; object-fit: cover; }
.circle-tile b { display: block; margin-top: 4px; overflow: hidden; font-size: 10px; font-weight: 650; white-space: nowrap; text-overflow: ellipsis; }
.circle-post { display: flex; gap: 8px; margin-bottom: 8px; padding: 6px; border-radius: 12px; background: #fff; }
.circle-post img { width: 64px; height: 64px; flex: none; border-radius: 8px; object-fit: cover; }
.circle-post b, .circle-post span { display: block; }
.circle-post b { font-size: 11px; }
.circle-post span { margin-top: 2px; color: #5c564e; font-size: 11px; line-height: 1.35; }
.circle-post i { display: inline-block; margin-top: 4px; padding: 0 6px; border-radius: 8px; background: #fff1e8; color: #c45c26; font-style: normal; font-size: 10px; line-height: 16px; }
.board-tile { display: flex; gap: 8px; margin-bottom: 8px; padding: 8px; border-radius: 12px; background: #fff; }
.board-tile img { width: 48px; height: 48px; flex: none; border-radius: 10px; object-fit: cover; }
.board-tile b { display: block; font-size: 13px; }
.board-tile p { margin: 2px 0 0; color: #5c564e; font-size: 11px; line-height: 1.35; }
.board-tile span { color: #8a847c; font-size: 10px; }
.scan-row, .find-row, .price-rows article, .note-rows article { display: flex; align-items: center; gap: 8px; min-height: 28px; padding: 5px 0; border-bottom: 1px solid #f1eee8; }
.scan-row img, .find-row img, .note-rows img { width: 22px; height: 22px; flex: none; border-radius: 4px; object-fit: cover; }
.scan-row b, .find-row b, .note-rows b, .price-rows b { flex: 1; min-width: 0; overflow: hidden; font-size: 12px; font-weight: 650; white-space: nowrap; text-overflow: ellipsis; }
.scan-row span, .find-row small { flex: none; color: #8a847c; font-size: 10px; }
.note-rows i { width: 6px; height: 6px; flex: none; border-radius: 50%; background: #ff2442; }
.discover-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px 6px; }
.discover-grid article { min-width: 0; }
.discover-grid img { display: block; width: 100%; height: 48px; border-radius: 8px; object-fit: cover; background: #f3e6df; }
.discover-grid b, .discover-grid span, .hub-grid span {
  display: block;
  white-space: normal;
  overflow: visible;
  text-overflow: unset;
  word-break: break-word;
  line-height: 1.35;
}
.discover-grid b { margin-top: 4px; font-size: 11px; font-weight: 650; }
.discover-grid span { margin-top: 2px; color: #5c564e; font-size: 10px; }
.mix-card { margin-bottom: 8px; overflow: hidden; border-radius: 12px; background: #fff; }
.mix-card img { display: block; width: 100%; height: 84px; object-fit: cover; }
.mix-card b, .mix-card p { margin: 0; padding: 0 8px; }
.mix-card b { margin-top: 6px; font-size: 13px; }
.mix-card p { margin: 2px 0 8px; color: #5c564e; font-size: 11px; line-height: 1.4; }
.hot-words { display: flex; flex-wrap: wrap; gap: 4px; margin-bottom: 8px; }
.hot-words span { padding: 3px 8px; border-radius: 999px; background: #fff; color: #5c564e; font-size: 10px; }
.poster-stack { display: flex; flex-direction: column; gap: 8px; }
.poster-stack .poster img { height: 84px; }
.sku-cards { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.sku-cards article { overflow: hidden; border: 1px solid #eeeae6; border-radius: 10px; background: #fff; }
.body.page-mall.skin-card,
.body.page-mine.skin-grid { background: #f6f5f2; }
.sku-cards img { display: block; width: 100%; height: 72px; object-fit: cover; }
.sku-cards b, .sku-cards span, .sku-cards strong { display: block; padding: 0 6px; }
.sku-cards b { margin-top: 6px; font-size: 12px; line-height: 1.3; }
.sku-cards span { margin-top: 2px; color: #5c564e; font-size: 10px; line-height: 1.35; white-space: normal; }
.sku-cards strong { margin: 2px 0 6px; color: #ff2442; font-size: 12px; }
.price-rows img { width: 28px; height: 28px; flex: none; border-radius: 6px; object-fit: cover; }
.price-rows strong { flex: none; color: #ff2442; font-size: 12px; }
.chat { display: flex; flex-direction: column; gap: 6px; min-height: 100%; }
.chat-line { display: flex; align-items: flex-end; gap: 6px; max-width: 92%; }
.chat-line.mine { align-self: flex-end; flex-direction: row-reverse; }
.chat-ava { width: 22px; height: 22px; flex: none; border-radius: 50%; object-fit: cover; }
.chat-bubble { max-width: 100%; padding: 6px 8px; border-radius: 12px 12px 12px 4px; background: #f4f1ec; color: #16160e; }
.chat-line.mine .chat-bubble { border-radius: 12px 12px 4px 12px; background: #ff8a9a; color: #3a1520; }
.chat-bubble p { margin: 0; font-size: 11px; line-height: 1.4; }
.chat-input { margin-top: auto; height: 24px; border-radius: 12px; background: #f6f4f1; color: #8a847c; font-size: 11px; line-height: 24px; text-align: center; }
.act-card { display: flex; align-items: center; gap: 6px; margin-bottom: 8px; padding: 6px; border-radius: 12px; background: #fff; }
.act-ava, .act-cover { flex: none; object-fit: cover; }
.act-ava { width: 28px; height: 28px; border-radius: 50%; }
.act-cover { width: 42px; height: 42px; border-radius: 8px; }
.act-card div { flex: 1; min-width: 0; }
.act-card b { display: block; font-size: 12px; }
.act-card p { margin: 2px 0 0; color: #5c564e; font-size: 10px; line-height: 1.35; }
.badge-row { display: flex; gap: 6px; margin: 8px 0; }
.badge-row span { padding: 3px 6px; border-radius: 999px; background: #fff1e8; color: #c45c26; font-size: 10px; }
.achieve-note { display: flex; gap: 8px; padding: 8px; border-radius: 12px; background: #fff; }
.achieve-note img { width: 64px; height: 64px; flex: none; border-radius: 8px; object-fit: cover; }
.achieve-note b { display: block; font-size: 12px; line-height: 1.35; }
.achieve-note span { display: block; margin-top: 4px; color: #5c564e; font-size: 11px; line-height: 1.35; }
.plain-hero { position: relative; height: 92px; margin: 0 -12px 0; }
.plain-banner { display: block; width: 100%; height: 92px; object-fit: cover; }
.plain-hero em { position: absolute; left: 12px; bottom: -14px; width: 36px; height: 36px; border: 2px solid #fff; border-radius: 50%; background: #f3e6df; color: #9a4d3a; font-style: normal; font-size: 14px; font-weight: 700; line-height: 32px; text-align: center; }
.plain-name { display: block; margin: 18px 0 0; font-size: 15px; }
.plain-bio { margin: 2px 0 8px; color: #5c564e; font-size: 11px; }
.plain-row { display: flex; gap: 8px; align-items: center; padding: 6px 0; border-top: 1px solid #f1eee8; }
.plain-row img { width: 48px; height: 48px; flex: none; border-radius: 8px; object-fit: cover; }
.plain-row b { display: block; font-size: 12px; }
.plain-row span { display: block; margin-top: 2px; color: #5c564e; font-size: 11px; }
.hub-id { display: flex; align-items: center; gap: 8px; margin-bottom: 10px; }
.hub-id em { width: 36px; height: 36px; flex: none; border-radius: 50%; background: #f3e6df; color: #9a4d3a; font-style: normal; font-size: 14px; font-weight: 700; line-height: 36px; text-align: center; }
.hub-id b { display: block; font-size: 15px; }
.hub-id span { display: block; margin-top: 2px; color: #5c564e; font-size: 11px; }
.hub-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px; }
.hub-grid article { min-width: 0; padding: 8px 4px; border-radius: 10px; background: #fff; text-align: center; }
.hub-grid img { width: 28px; height: 28px; border-radius: 8px; object-fit: cover; }
.hub-grid span { margin-top: 4px; font-size: 11px; text-align: center; }
.body.page-community { background: #f6f5f2; padding: 2px 8px 8px; }
.sns-search {
  display: flex;
  align-items: center;
  gap: 6px;
  height: 26px;
  margin-bottom: 8px;
  padding: 0 10px;
  border-radius: 13px;
  background: #fff;
  color: #8a847c;
  font-size: 11px;
}
.sns-search i {
  width: 8px;
  height: 8px;
  border: 1.5px solid #8a847c;
  border-radius: 50%;
}
.sns-tabs { display: flex; justify-content: center; gap: 16px; margin-bottom: 6px; color: #8a847c; font-size: 13px; }
.sns-tabs .on { color: #16160e; font-weight: 700; }
.sns-chips { display: flex; gap: 10px; margin-bottom: 8px; overflow: hidden; color: #8a847c; font-size: 11px; white-space: nowrap; }
.sns-chips .on { color: #16160e; font-weight: 700; }
.sns-card { margin-bottom: 8px; padding: 8px; border-radius: 12px; background: #fff; }
.sns-card.tight { margin-bottom: 6px; padding: 6px; }
.sns-who { display: flex; align-items: center; gap: 4px; min-width: 0; }
.sns-who em {
  width: 16px;
  height: 16px;
  flex: none;
  border-radius: 50%;
  background: #f3e6df;
  color: #9a4d3a;
  font-style: normal;
  font-size: 9px;
  font-weight: 700;
  line-height: 16px;
  text-align: center;
}
.sns-who b { min-width: 0; overflow: hidden; color: #16160e; font-size: 11px; font-weight: 650; white-space: nowrap; text-overflow: ellipsis; }
.sns-who i {
  flex: none;
  height: 14px;
  padding: 0 4px;
  border-radius: 3px;
  background: #fff1e8;
  color: #c45c26;
  font-style: normal;
  font-size: 9px;
  line-height: 14px;
}
.sns-title {
  margin: 4px 0 0;
  color: #16160e;
  font-size: 12px;
  font-weight: 700;
  line-height: 1.35;
  overflow: hidden;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}
.sns-excerpt {
  margin: 2px 0 0;
  color: #5c564e;
  font-size: 10px;
  line-height: 1.4;
  overflow: hidden;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}
.sns-card.long .sns-excerpt { -webkit-line-clamp: 4; }
.sns-card.tight .sns-excerpt { -webkit-line-clamp: 1; }
.sns-photo { display: block; width: 100%; height: 56px; margin-top: 6px; border-radius: 6px; object-fit: cover; }
.sns-card.tight .sns-photo { height: 40px; }
.sns-foot { display: flex; justify-content: space-between; gap: 6px; margin-top: 6px; color: #5c564e; font-size: 10px; }
.sns-plain { margin-bottom: 8px; color: #16160e; font-size: 15px; font-weight: 700; }
.sns-banner { height: 52px; margin: 0 -8px 8px; background: linear-gradient(160deg, #e7b89a 0%, #8d5a45 42%, #241e1b 100%); }
.mine-who { margin-top: -16px; }
.sns-stats { display: flex; gap: 10px; margin: 8px 0; color: #5c564e; font-size: 10px; }
.sns-stats b { margin-right: 2px; color: #16160e; font-size: 12px; }
.mine-tabs { justify-content: flex-start; gap: 14px; }
.sns-mall { display: flex; flex-direction: column; gap: 8px; }
.sns-good { overflow: hidden; border-radius: 10px; background: #fff; }
.sns-good img, .sns-good em { display: block; width: 100%; height: 64px; border-radius: 0; object-fit: cover; }
.sns-good em { background: #f3e6df; color: #9a4d3a; font-style: normal; font-size: 16px; font-weight: 700; line-height: 64px; text-align: center; }
.sns-good b, .sns-good strong { display: block; padding: 0 8px; }
.sns-good b { margin-top: 6px; font-size: 12px; }
.sns-good strong { margin: 2px 0 8px; color: #ff2442; font-size: 12px; }
.apps.is-haosns { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; }
.apps.is-haosns .app { flex-direction: column; align-items: stretch; gap: 4px; }
.apps.is-haosns .app img, .apps.is-haosns .app em { width: 100%; height: 72px; border-radius: 8px; }
.apps.is-haosns .app i, .apps.is-haosns .app span { display: none; }
.inbox.sns span { padding: 3px 6px; border-radius: 999px; background: #fff; }
.msgs.sns .msg { margin-bottom: 6px; padding: 6px; border-radius: 10px; background: #fff; }
.body.page-home.skin-haosns, .body.page-discover.skin-haosns, .body.page-mall.skin-haosns, .body.page-messages.skin-haosns, .body.page-mine.skin-haosns { background: #f6f5f2; }
.ptitle { margin: 0 0 8px; font-size: 18px; font-weight: 700; letter-spacing: 0; }
.empty { margin: 36px 8px; color: #5c564e; font-size: 13px; line-height: 1.5; text-align: center; }
.mini-tabs { display: flex; justify-content: center; gap: 16px; margin: 0 0 8px; color: #5c564e; font-size: 13px; }
.mini-tabs .on { color: #16160e; font-weight: 700; }
.channels, .notes, .waterfall, .threads { display: flex; flex-direction: column; gap: 8px; }
.channel-cover, .fall img, .note img, .post-photo {
  display: block;
  width: 100%;
  object-fit: cover;
  background: #1c1c1a;
  border-radius: 8px;
}
.channel-cover { position: relative; height: 108px; overflow: hidden; }
.channel-cover img { width: 100%; height: 108px; object-fit: cover; }
.play {
  position: absolute;
  left: 8px;
  top: 8px;
  width: 0;
  height: 0;
  border-style: solid;
  border-width: 5px 0 5px 8px;
  border-color: transparent transparent transparent #fff;
}
.channel b, .fall b, .note b, .post-title {
  display: block;
  margin: 4px 0 0;
  color: #16160e;
  font-size: 12px;
  font-weight: 650;
  line-height: 1.35;
  overflow: hidden;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}
.channel span, .fall span, .note span { display: block; margin-top: 2px; color: #5c564e; font-size: 11px; }
.dock, .post-counts { display: flex; justify-content: space-between; gap: 6px; margin-top: 4px; color: #16160e; font-size: 11px; }
.waterfall { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.fall img { height: 72px; }
.note { display: flex; gap: 8px; align-items: center; }
.note img { width: 52px; height: 52px; flex: none; }
.note div { min-width: 0; }
.circle-line { margin: 0 0 8px; color: #5c564e; font-size: 12px; }
.post-author { display: flex; align-items: center; gap: 6px; }
.post-author em {
  width: 22px;
  height: 22px;
  flex: none;
  border-radius: 50%;
  background: #f3e6df;
  color: #9a4d3a;
  font-style: normal;
  font-size: 11px;
  font-weight: 700;
  line-height: 22px;
  text-align: center;
}
.post-author b { margin: 0; font-size: 12px; -webkit-line-clamp: 1; }
.post-photo { height: 72px; margin-top: 4px; }
.post-card.tight { padding-bottom: 2px; }
.inbox { display: flex; justify-content: space-between; gap: 4px; margin-bottom: 8px; color: #5c564e; font-size: 10px; }
.inbox span { white-space: nowrap; }
.inbox.pills span { padding: 3px 6px; border-radius: 999px; background: #f4f1ec; color: #16160e; }
.login-nav { margin-bottom: 8px; font-size: 15px; font-weight: 650; text-align: center; }
.login b { display: block; font-size: 18px; line-height: 1.3; }
.login-sub { display: block; margin-top: 4px; color: #5c564e; font-size: 11px; line-height: 1.45; }
.login-label { display: block; margin: 10px 0 4px; color: #16160e; font-size: 12px; font-weight: 650; }
.login-field {
  height: 32px;
  padding: 0 10px;
  border-radius: 8px;
  background: #f6f7f8;
  color: #5c564e;
  font-size: 13px;
  line-height: 32px;
}
.login-btn {
  margin-top: 12px;
  height: 32px;
  border-radius: 16px;
  background: #ff2442;
  color: #fff;
  font-size: 14px;
  font-weight: 650;
  line-height: 32px;
  text-align: center;
}
.xhs {
  margin: 0 -12px;
  padding: 2px 12px 10px;
  background: linear-gradient(180deg, #241e1b 0%, #3d322c 58%, #e6ddd6 100%);
  color: #fff;
}
.xhs.paper, .xhs.guest { background: #f7f4ef; color: #16160e; }
.xhs-id { display: flex; align-items: center; gap: 8px; }
.xhs-id em {
  width: 36px;
  height: 36px;
  flex: none;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.2);
  color: #fff;
  font-style: normal;
  font-size: 14px;
  font-weight: 700;
  line-height: 36px;
  text-align: center;
}
.xhs.paper .xhs-id em, .xhs.guest .xhs-id em { background: #f2f3f5; color: #16160e; }
.xhs-id b { color: inherit; font-size: 15px; -webkit-line-clamp: 1; }
.xhs-id span, .xhs-bio { display: block; margin-top: 2px; color: inherit; opacity: 0.78; font-size: 11px; }
.xhs-stats { display: flex; gap: 10px; margin-top: 8px; }
.xhs-stats p { display: flex; align-items: baseline; gap: 4px; margin: 0; }
.xhs-stats b { font-size: 13px; }
.xhs-stats span { color: inherit; opacity: 0.78; font-size: 11px; }
.xhs-bio { margin: 8px 0 0; white-space: normal; }
.xhs-chips { display: flex; gap: 6px; margin-top: 8px; overflow: hidden; }
.xhs-chips span {
  flex: none;
  min-width: 48px;
  height: 32px;
  padding: 0 6px;
  border-radius: 8px;
  background: rgba(255, 255, 255, 0.18);
  color: inherit;
  font-size: 11px;
  line-height: 32px;
  text-align: center;
}
.xhs.paper .xhs-chips span, .xhs.guest .xhs-chips span {
  background: rgba(255, 255, 255, 0.72);
  border: 1px solid rgba(22, 22, 14, 0.08);
  color: #16160e;
}
.xhs-chips.wrap { flex-wrap: wrap; overflow: visible; }
.xhs-chips.wrap span { width: calc(50% - 3px); }
.xhs-sheet { margin: 8px -12px 0; padding: 8px 12px 0; background: #fff; border-radius: 12px 12px 0 0; }
.xhs-tabs { display: flex; gap: 12px; align-items: baseline; color: #5c564e; font-size: 13px; }
.xhs-tabs b { color: #16160e; font-size: 14px; }
.xhs-falls { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 8px; }
.xhs-falls article { min-width: 0; padding: 0 0 8px; border-radius: 8px; background: #f7f4ef; overflow: hidden; }
.xhs-falls img { display: block; width: 100%; height: 78px; object-fit: cover; }
.xhs-falls b { display: block; margin: 6px 6px 0; font-size: 12px; -webkit-line-clamp: 2; }
.xhs-falls span { margin: 4px 6px 0; }
.xhs-falls span, .xhs-empty { display: block; margin-top: 4px; color: #5c564e; font-size: 11px; }
.xhs-empty { margin: 16px 0; text-align: center; }
.chips { display: flex; gap: 4px; margin-bottom: 6px; overflow: hidden; }
.chips span {
  flex: none;
  max-width: 96px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  padding: 3px 8px;
  border-radius: 999px;
  background: #f6f3ee;
  color: #5c564e;
  font-size: 11px;
}
.cards, .skus, .apps { display: flex; flex-direction: column; gap: 8px; }
.skin-grid .apps {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px;
}
.card, .sku, .app, .board, .thread, .msg, .line, .feature, .poster {
  min-width: 0;
}
.card, .sku {
  background: #faf8f5;
  border-radius: 8px;
  overflow: hidden;
}
.card img, .sku img, .feature img, .poster img, .hero img {
  display: block;
  width: 100%;
  height: 92px;
  object-fit: cover;
}
.card div, .sku div, .app div, .msg div { min-width: 0; padding: 8px 8px 10px; }
.card b, .sku b, .app b, .board b, .thread b, .msg b, .line b, .feature h3, .poster b, .bubble b, .who b {
  display: block;
  margin: 0;
  font-size: 13px;
  font-weight: 650;
  line-height: 1.35;
  overflow: hidden;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
}
.card span, .sku span, .app span, .board p, .thread span, .msg span, .line span, .feature p, .poster span, .bubble span, .who span, .plugins {
  display: block;
  margin: 2px 0 0;
  color: #8d877f;
  font-size: 11px;
  line-height: 1.4;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.skin-compact .card, .skin-compact .sku, .skin-compact .app, .skin-compact .board, .skin-compact .msg {
  display: flex;
  align-items: center;
  gap: 6px;
  background: transparent;
  border-radius: 0;
  border-bottom: 1px solid #f1eee8;
}
.skin-compact .card img, .skin-compact .sku img, .skin-compact .app img {
  width: 28px;
  height: 28px;
  flex: none;
  border-radius: 6px;
}
.skin-compact .card div, .skin-compact .sku div, .skin-compact .app div { padding: 0; }
.feature, .poster, .hero { margin-bottom: 6px; }
.feature h3, .poster b { font-size: 13px; -webkit-line-clamp: 2; }
.feature p, .poster span { white-space: normal; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; }
.poster strong, .sku strong { color: #ff2442; font-size: 11px; font-weight: 700; }
.poster strong { display: block; margin-top: 2px; }
.sku.line { display: flex; justify-content: space-between; gap: 8px; padding: 5px 0; border-bottom: 1px solid #f1eee8; background: transparent; }
.board, .thread, .msg { padding: 5px 0; border-bottom: 1px solid #f1eee8; }
.board div, .msg { display: flex; justify-content: space-between; gap: 6px; align-items: baseline; }
.board div { padding: 0; }
.board span, .app small { flex: none; color: #8d877f; font-size: 11px; }
.app { display: flex; align-items: center; gap: 6px; }
.app img, .app em, .msg em, .who em {
  width: 28px;
  height: 28px;
  flex: none;
  border-radius: 8px;
  object-fit: cover;
  background: #f3e6df;
  color: #9a4d3a;
  font-style: normal;
  font-size: 12px;
  font-weight: 700;
  line-height: 28px;
  text-align: center;
}
.skin-grid .app { flex-direction: column; align-items: flex-start; padding: 6px; background: #faf8f5; border-radius: 8px; }
.skin-grid .app div { padding: 0; }
.skin-list .app em, .skin-list .app img { width: 22px; height: 22px; line-height: 22px; border-radius: 6px; }
.bubble { max-width: 88%; margin: 0 0 6px; padding: 6px 8px; border-radius: 12px 12px 12px 4px; background: #f4f1ec; }
.bubble.self { margin-left: auto; border-radius: 12px 12px 4px 12px; background: #ff2442; }
.bubble.self b, .bubble.self span { color: #fff; }
.who { display: flex; align-items: center; gap: 8px; margin-bottom: 8px; }
.who em { width: 36px; height: 36px; line-height: 36px; border-radius: 50%; font-size: 14px; }
.stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 4px; }
.skin-grid .stats { grid-template-columns: 1fr 1fr; gap: 6px; }
.stats p { margin: 0; text-align: center; }
.skin-grid .stats p { padding: 8px 0; background: #faf8f5; border-radius: 8px; }
.skin-card .who, .skin-card .stats { margin-left: 0; padding: 8px; background: #fff; border-radius: 12px; box-shadow: 0 6px 16px rgba(22, 22, 14, 0.06); }
.skin-card .stats { margin-top: 6px; }
.stats b { display: block; font-size: 14px; }
.stats span { color: #8d877f; font-size: 11px; }
.plugins { margin: 8px 2px; white-space: normal; }
.menu { list-style: none; margin: 8px 0 0; padding: 0; }
.menu li { padding: 8px 0; border-bottom: 1px solid #f1eee8; font-size: 13px; }
.bar {
  display: flex;
  align-items: flex-end;
  height: 58px;
  padding: 4px 2px 8px;
  background: #fff;
  border-top: 1px solid #f3f0ea;
}
.tab {
  flex: 1;
  min-width: 0;
  min-height: 44px;
  height: auto;
  padding: 4px 0;
  border: 0;
  background: transparent;
  color: #5c564e;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 1px;
  cursor: pointer;
}
.tab svg { width: 18px; height: 18px; fill: none; stroke: currentColor; stroke-width: 1.7; stroke-linejoin: round; }
.tab span { font-size: 11px; font-weight: 500; line-height: 1.2; white-space: nowrap; }
.tab.on { color: #ff2442; }
.bar-icon .tab svg { width: 22px; height: 22px; }
.plus { margin-top: -14px; }
.bump {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: #ff2442;
  color: #fff;
  font-size: 20px;
  line-height: 34px;
  text-align: center;
}
.apps.is-shelf .app,
.apps.is-list .app {
  padding: 8px 0;
  border-bottom: 1px solid #f3f0ea;
}
.apps.is-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px;
}
.apps.is-grid .app {
  flex-direction: column;
  align-items: flex-start;
  gap: 6px;
  padding: 8px;
  background: #fff;
  border: 1px solid #eeeae6;
  border-radius: 8px;
}
.apps.is-grid .app img,
.apps.is-grid .app em {
  width: 36px;
  height: 36px;
  border-radius: 10px;
  line-height: 36px;
}
.apps.is-list .app img,
.apps.is-list .app em {
  width: 22px;
  height: 22px;
  border-radius: 6px;
  line-height: 22px;
}
.app i {
  margin-left: auto;
  flex: none;
  height: 22px;
  padding: 0 8px;
  border-radius: 11px;
  background: #f6f4f1;
  color: #ff2442;
  font-style: normal;
  font-size: 11px;
  font-weight: 650;
  line-height: 22px;
}
.skus .sku {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 8px;
  background: #fff;
  border: 1px solid #eeeae6;
  border-radius: 8px;
}
.skus .sku img,
.skus .sku em {
  width: 44px;
  height: 44px;
  flex: none;
  border-radius: 8px;
  object-fit: cover;
  line-height: 44px;
}
.skus .sku div { padding: 0; }
.skus .sku strong { margin-left: auto; flex: none; }
.skus.tight .sku {
  padding: 6px 0;
  background: transparent;
  border: 0;
  border-radius: 0;
  border-bottom: 1px solid #eeeae6;
}
.skus.tight .sku img,
.skus.tight .sku em {
  width: 28px;
  height: 28px;
  line-height: 28px;
}
.msg em { border-radius: 50%; }
.msgs.bubbles .msg {
  max-width: 86%;
  margin-bottom: 6px;
  padding: 6px 8px;
  border: 0;
  border-radius: 12px 12px 12px 4px;
  background: #f4f1ec;
}
.msgs.bubbles .msg.self {
  margin-left: auto;
  border-radius: 12px 12px 4px 12px;
  background: #ff8a9a;
}
.msgs.bubbles .msg.self b,
.msgs.bubbles .msg.self span { color: #fff; }
figcaption { margin-top: 10px; text-align: center; }
figcaption button {
  border: 0;
  background: transparent;
  color: var(--paopao-muted);
  font-size: 14px;
  cursor: pointer;
  white-space: nowrap;
}
figcaption button.on { color: var(--paopao-ink); font-weight: 650; }
@media (max-width: 800px) {
  .unit, .bezel { width: min(280px, 100%); }
}
</style>
