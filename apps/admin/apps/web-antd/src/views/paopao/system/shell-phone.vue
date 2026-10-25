<script lang="ts" setup>
import { computed, ref } from 'vue';

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

const broken = ref<Record<string, boolean>>({});

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

function show(key: string, src: null | string | undefined) {
  return !!src && !broken.value[key];
}

function hide(key: string) {
  broken.value = { ...broken.value, [key]: true };
}

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
            <div v-if="skin === 'feed'" class="imm">
              <div class="imm-tabs">
                <span>关注</span>
                <span class="on">推荐</span>
                <span>最新</span>
              </div>
              <p v-if="!homePosts.length" class="imm-empty">还没有作品</p>
              <div v-else class="imm-foot">
                <div class="imm-row">
                  <i>{{ initial(homePosts[0].author) }}</i>
                  <div>
                    <b>@{{ homePosts[0].author || '匿名' }}</b>
                    <em>刚刚</em>
                  </div>
                  <span>关注</span>
                </div>
                <strong>{{ homePosts[0].title }}</strong>
                <p class="imm-search">相关搜索</p>
                <div class="imm-acts">
                  <span>赞</span>
                  <span>分享</span>
                  <span>收藏</span>
                  <span>评论</span>
                </div>
                <p class="imm-say">说点什么，友善发言</p>
              </div>
            </div>
            <template v-else>
            <div class="mini-tabs">
              <span>关注</span>
              <span class="on">推荐</span>
              <span>最新</span>
            </div>
            <p v-if="!homePosts.length" class="empty">还没有笔记</p>
            <div v-else-if="skin === 'compact'" class="notes">
              <article v-for="post in homePosts.slice(0, 4)" :key="post.id" class="note">
                <img
                  v-if="show(`post-${post.id}`, post.cover)"
                  :alt="post.title"
                  :src="post.cover || ''"
                  @error="hide(`post-${post.id}`)"
                />
                <div>
                  <b>{{ post.title }}</b>
                  <span>{{ post.author }}</span>
                </div>
              </article>
            </div>
            <div v-else-if="skin === 'haosns'" class="sns-home">
              <article v-for="post in homePosts.slice(0, 2)" :key="post.id" class="sns-card">
                <div class="sns-who">
                  <em>{{ initial(post.author) }}</em>
                  <b>{{ post.author }}</b>
                  <i>成员</i>
                </div>
                <p class="sns-title">{{ post.title }}</p>
                <p v-if="post.excerpt" class="sns-excerpt">{{ post.excerpt }}</p>
                <img
                  v-if="show(`post-${post.id}`, post.cover)"
                  class="sns-photo"
                  :alt="post.title"
                  :src="post.cover || ''"
                  @error="hide(`post-${post.id}`)"
                />
                <div class="sns-foot">
                  <span>{{ post.views }} 浏览</span>
                  <span>赞 {{ post.likes }}</span>
                </div>
              </article>
            </div>
            <div v-else class="waterfall">
              <article v-for="post in homePosts.slice(0, 4)" :key="post.id" class="fall">
                <img
                  v-if="show(`post-${post.id}`, post.cover)"
                  :alt="post.title"
                  :src="post.cover || ''"
                  @error="hide(`post-${post.id}`)"
                />
                <b>{{ post.title }}</b>
                <span>{{ post.author }}</span>
              </article>
            </div>
            </template>
          </template>

          <template v-else-if="pageId === 'community'">
            <template v-if="skin === 'haosns'">
              <div class="sns-search"><i /><span>搜索</span></div>
              <div class="sns-tabs">
                <span>关注</span>
                <span class="on">发现</span>
                <span>附近</span>
              </div>
            </template>
            <div v-else class="sns-plain">社区</div>
            <div class="sns-chips">
              <span class="on">全部</span>
              <span v-for="board in data.boards.slice(0, 4)" :key="board.id">{{ board.name }}</span>
            </div>
            <p v-if="!data.posts.length" class="empty">还没有帖子</p>
            <article
              v-for="post in data.posts.slice(0, skin === 'forum' ? 2 : 3)"
              v-else
              :key="post.id"
              class="sns-card"
              :class="{ tight: skin === 'compact', long: skin === 'forum' }"
            >
              <div class="sns-who">
                <em>{{ initial(post.author) }}</em>
                <b>{{ post.author }}</b>
                <i>成员</i>
              </div>
              <p class="sns-title">{{ post.title }}</p>
              <p v-if="post.excerpt" class="sns-excerpt">{{ post.excerpt }}</p>
              <img
                v-if="show(`post-${post.id}`, post.cover)"
                class="sns-photo"
                :alt="post.title"
                :src="post.cover || ''"
                @error="hide(`post-${post.id}`)"
              />
              <div class="sns-foot">
                <span>{{ post.views }} 浏览</span>
                <span>赞 {{ post.likes }} · 藏 {{ post.favorites }} · 评 {{ post.comments }}</span>
              </div>
            </article>
          </template>

          <template v-else-if="pageId === 'discover'">
            <div v-if="data.chips.length" class="chips">
              <span v-for="chip in data.chips.slice(0, 4)" :key="chip.id">{{ chip.title }}</span>
            </div>
            <p v-if="!data.apps.length" class="empty">还没有上架的软件</p>
            <div v-else class="apps" :class="`is-${skin}`">
              <article v-for="app in data.apps.slice(0, skin === 'grid' ? 4 : 5)" :key="app.id" class="app">
                <img
                  v-if="show(`app-${app.id}`, app.icon)"
                  :alt="app.name"
                  :src="app.icon || ''"
                  @error="hide(`app-${app.id}`)"
                />
                <em v-else>{{ initial(app.name) }}</em>
                <div>
                  <b>{{ app.name }}</b>
                  <span v-if="skin !== 'list'">{{ app.summary || '暂无简介' }}</span>
                  <small v-else>{{ app.downloads }} 次获取</small>
                </div>
                <i v-if="skin === 'shelf'">获取</i>
              </article>
            </div>
          </template>

          <template v-else-if="pageId === 'mall'">
            <p v-if="!data.products.length" class="empty">还没有商品</p>
            <template v-else-if="skin === 'poster'">
              <article class="poster">
                <img
                  v-if="show(`sku-${data.products[0].id}`, data.products[0].cover)"
                  :alt="data.products[0].title"
                  :src="data.products[0].cover || ''"
                  @error="hide(`sku-${data.products[0].id}`)"
                />
                <b>{{ data.products[0].title }}</b>
                <span>{{ data.products[0].summary || '卡密自动发货' }}</span>
                <strong>¥{{ data.products[0].price }}</strong>
              </article>
              <article v-for="item in data.products.slice(1, 4)" :key="item.id" class="sku line">
                <b>{{ item.title }}</b>
                <strong>¥{{ item.price }}</strong>
              </article>
            </template>
            <div v-else-if="skin === 'haosns'" class="sns-mall">
              <article v-for="item in data.products.slice(0, 2)" :key="item.id" class="sns-good">
                <img
                  v-if="show(`sku-${item.id}`, item.cover)"
                  :alt="item.title"
                  :src="item.cover || ''"
                  @error="hide(`sku-${item.id}`)"
                />
                <em v-else>{{ initial(item.title) }}</em>
                <b>{{ item.title }}</b>
                <strong>¥{{ item.price }}</strong>
              </article>
            </div>
            <div v-else class="skus" :class="{ tight: skin === 'compact' }">
              <article v-for="item in data.products.slice(0, 5)" :key="item.id" class="sku">
                <img
                  v-if="show(`sku-${item.id}`, item.cover)"
                  :alt="item.title"
                  :src="item.cover || ''"
                  @error="hide(`sku-${item.id}`)"
                />
                <em v-else>{{ initial(item.title) }}</em>
                <div>
                  <b>{{ item.title }}</b>
                  <span v-if="skin !== 'compact'">{{ item.summary || '卡密自动发货' }}</span>
                </div>
                <strong>¥{{ item.price }}</strong>
              </article>
            </div>
          </template>

          <template v-else-if="pageId === 'messages'">
            <div class="inbox" :class="{ pills: skin === 'bubble', sns: skin === 'haosns' }">
              <span>赞和收藏</span>
              <span>关注</span>
              <span>评论</span>
              <span>系统</span>
            </div>
            <p v-if="!data.messages.length" class="empty">还没有消息</p>
            <div v-else class="msgs" :class="{ bubbles: skin === 'bubble', sns: skin === 'haosns' }">
              <article
                v-for="(item, index) in data.messages.slice(0, 5)"
                :key="item.id"
                class="msg"
                :class="{ self: skin === 'bubble' && index % 2 === 1 }"
              >
                <em v-if="skin !== 'bubble'">{{ initial(item.title) }}</em>
                <div>
                  <b>{{ item.title }}</b>
                  <span v-if="skin !== 'compact'">{{ item.body }}</span>
                </div>
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
            <div class="sns-stats">
              <span><b>{{ profile ? profile.following : '-' }}</b>关注</span>
              <span><b>{{ profile ? profile.followers : '-' }}</b>粉丝</span>
              <span><b>{{ profile ? profile.likes : '-' }}</b>获赞</span>
            </div>
            <div class="sns-tabs mine-tabs"><span class="on">动态</span><span>收藏</span></div>
            <article v-if="data.posts[0]" class="sns-card">
              <p class="sns-title">{{ data.posts[0].title }}</p>
              <p v-if="data.posts[0].excerpt" class="sns-excerpt">{{ data.posts[0].excerpt }}</p>
            </article>
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
              <div class="xhs-chips" :class="{ wrap: skin === 'grid' }">
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
                <article v-for="post in homePosts.slice(0, 2)" :key="post.id">
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
.body.page-home.skin-feed { padding: 0; background: #111; }
.imm { position: relative; height: 100%; min-height: 280px; background: linear-gradient(180deg, #4a4a4a 0%, #1a1a1a 46%, #000 100%); color: #fff; }
.imm-tabs { display: flex; justify-content: center; gap: 14px; padding-top: 6px; color: rgba(255, 255, 255, 0.72); font-size: 12px; }
.imm-tabs .on { color: #fff; font-weight: 700; }
.imm-empty { margin-top: 80px; color: #fff; text-align: center; }
.imm-foot { position: absolute; left: 10px; right: 10px; bottom: 8px; }
.imm-row { display: flex; align-items: center; gap: 6px; }
.imm-row i { width: 22px; height: 22px; flex: none; border-radius: 50%; background: #fff; color: #ff2442; font-style: normal; font-size: 10px; font-weight: 700; line-height: 22px; text-align: center; }
.imm-row div { flex: 1; min-width: 0; }
.imm-row b, .imm-row em, .imm-foot strong { display: block; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }
.imm-row b { font-size: 11px; font-weight: 700; }
.imm-row em { color: rgba(255, 255, 255, 0.62); font-size: 9px; font-style: normal; }
.imm-row > span { flex: none; height: 16px; padding: 0 6px; border-radius: 8px; background: #ff2442; font-size: 10px; line-height: 16px; }
.imm-foot strong { margin-top: 6px; font-size: 11px; font-weight: 500; }
.imm-search, .imm-say { height: 16px; margin: 6px 0 0; border-radius: 8px; background: rgba(68, 68, 70, 0.72); color: rgba(255, 255, 255, 0.8); font-size: 9px; line-height: 16px; }
.imm-search { display: inline-block; padding: 0 8px; }
.imm-say { padding: 0 8px; color: rgba(255, 255, 255, 0.55); }
.imm-acts { display: flex; justify-content: space-around; margin-top: 6px; font-size: 9px; }
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
.xhs-falls article { min-width: 0; padding: 8px; border-radius: 8px; background: #f7f4ef; }
.xhs-falls b { font-size: 12px; -webkit-line-clamp: 2; }
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
  background: #16160e;
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
