<script lang="ts" setup>
import { computed, onMounted, reactive, ref } from 'vue';

import { Page } from '@vben/common-ui';

import { Alert, Modal, message } from 'ant-design-vue';

import { requestClient } from '#/api/request';

import {
  paopaoApi,
  type AppRow,
  type AppShellConfig,
  type AppShellCustomPage,
  type AppShellSubpage,
  type BannerRow,
  type BoardRow,
  type BroadcastRow,
  type GridRow,
  type MallProductRow,
  type PageResult,
  type PluginRow,
  type PostRow,
} from '../api';
import ShellPhone, {
  type ShellFeed,
  type ShellMessage,
} from './shell-phone.vue';

interface NoticeRow {
  body?: string;
  id: number;
  title?: string;
}

interface ThreadRow {
  id: number;
  last_message?: null | { body?: null | string };
  peer?: null | { nickname?: string };
}

interface MeRow {
  id: number;
  nickname?: string;
  stats?: {
    follower_count?: number;
    following_count?: number;
    like_count?: number;
    post_count?: number;
  };
}

const error = ref('');
const loading = ref(true);
const saving = ref(false);
const step = ref<1 | 2>(1);
const catalog = ref<AppShellConfig['catalog']>({ modules: [], tabbars: [] });
const modules = ref<string[]>([]);
const skins = ref<Record<string, string>>({});
const tabbar = ref('labeled');
const customPages = ref<AppShellCustomPage[]>([]);
const subpages = ref<Record<string, AppShellSubpage[]>>({});
const maxPages = ref(20);
const previewId = ref('home');
const mineAudience = ref<'guest' | 'member'>('member');
const packNote = ref('');
const subModalOpen = ref(false);
const subParentId = ref('');
const customModalOpen = ref(false);
const customForm = reactive({ label: '', path: '', summary: '' });
const draftSubs = ref<AppShellSubpage[]>([]);

const feed = ref<ShellFeed>({
  apps: [],
  banners: [],
  boards: [],
  chips: [],
  loading: true,
  me: null,
  messages: [],
  plugins: [],
  posts: [],
  products: [],
});

const preview = computed(
  () =>
    catalog.value.modules.find((item) => item.id === previewId.value) ||
    catalog.value.modules[0],
);
const enabled = computed(() =>
  catalog.value.modules.filter((item) => modules.value.includes(item.id)),
);
const selectedCount = computed(() => modules.value.length + customPages.value.length);
const enabledSidebar = computed(() => {
  const builtin = enabled.value.map((item) => ({
    id: item.id,
    label: item.label,
    custom: false as boolean,
  }));
  const custom = customPages.value.map((item) => ({
    id: item.id,
    label: item.label,
    custom: true,
  }));
  return [...builtin, ...custom];
});
const tabbarLabel = computed(
  () =>
    catalog.value.tabbars.find((item) => item.id === tabbar.value)?.label ||
    '图标+文字',
);

const skinCopy: Record<string, string> = {
  'community:board': '单列版块',
  'community:compact': '单列版块，标题更紧',
  'community:forum': '单列版块，话题更长',
  'community:haosns': '发现页：搜索、关注/发现/附近、横滑版块',
  'discover:haosns': '搜索单列，图标和入口卡',
  'home:haosns': '单列动态卡，头像、入口、封面',
  'mall:haosns': '图鸟商品卡，封面在右',
  'messages:haosns': '图鸟消息卡',
  'mine:haosns': '头图、成就标、动态',
  'discover:grid': '三列图标入口，常驻和扩展',
  'discover:list': '窄行，常驻和扩展并排',
  'discover:shelf': '图标、扩展和扩展区',
  'home:compact': '单列瀑布，封面在下，入口卡在顶。',
  'home:feed': '全屏沉浸视频流：作者在左下，底栏赞分享收藏评论。',
  'home:magazine': '双列杂志流。封面错落，入口不是头像和赞。这是默认首页。',
  'mall:card': '封面、说明和价格',
  'mall:compact': '一行入口和价格',
  'mall:poster': '大商品图，下方跟价格',
  'messages:bubble': '会话气泡，内容气泡对齐',
  'messages:compact': '下方入口卡',
  'messages:list': '每个入口加私信',
  'mine:card': '小红书，卡片头图',
  'mine:grid': '小红书，快捷入口三列',
  'mine:plain': '小红书，浅色头图',
};

const barCopy: Record<string, string> = {
  icon: '只有图标',
  labeled: '图标加文字',
  raised: '中间凸起发布',
};

function itemsOf<T>(value: unknown): T[] {
  if (Array.isArray(value)) return value as T[];
  if (value && typeof value === 'object' && Array.isArray((value as { items?: T[] }).items)) {
    return (value as { items: T[] }).items;
  }
  return [];
}

function textOf(value: unknown, limit = 48) {
  const text = typeof value === 'string' ? value.trim() : '';
  return text.length > limit ? `${text.slice(0, limit)}…` : text;
}

function subCount(id: string) {
  return (subpages.value[id] || []).length;
}

function toggleModule(id: string, on: boolean) {
  if (!on) {
    if (modules.value.length <= 1 && customPages.value.length === 0) {
      message.warning('至少保留一个页面');
      return;
    }
    modules.value = modules.value.filter((item) => item !== id);
    if (previewId.value === id) {
      previewId.value = modules.value[0] || customPages.value[0]?.id || 'home';
    }
    return;
  }
  if (selectedCount.value >= maxPages.value) {
    message.warning(`最多选择 ${maxPages.value} 个页面`);
    return;
  }
  modules.value = catalog.value.modules
    .map((item) => item.id)
    .filter((item) => item === id || modules.value.includes(item));
  if (!skins.value[id] && preview.value) {
    const meta = catalog.value.modules.find((item) => item.id === id);
    skins.value = { ...skins.value, [id]: meta?.skins[0]?.id || 'feed' };
  }
  if (!subpages.value[id]?.length) {
    const meta = catalog.value.modules.find((item) => item.id === id);
    if (meta) {
      subpages.value = {
        ...subpages.value,
        [id]: [{ id: `${id}_main`, label: meta.label, path: meta.path }],
      };
    }
  }
}

function openSubpages(id: string) {
  subParentId.value = id;
  const rows = subpages.value[id] || [];
  draftSubs.value = rows.map((row) => ({ ...row }));
  if (!draftSubs.value.length) {
    const meta = catalog.value.modules.find((item) => item.id === id);
    const custom = customPages.value.find((item) => item.id === id);
    draftSubs.value = [
      {
        id: `${id}_main`,
        label: meta?.label || custom?.label || '主页',
        path: meta?.path || custom?.path || `pages/${id}/index`,
      },
    ];
  }
  subModalOpen.value = true;
}

function addDraftSub() {
  if (draftSubs.value.length >= 12) {
    message.warning('每个页面最多 12 个子页');
    return;
  }
  const n = draftSubs.value.length + 1;
  draftSubs.value.push({
    id: `${subParentId.value}_sub_${Date.now().toString(36)}`,
    label: `子页 ${n}`,
    path: `pages/${subParentId.value}/sub${n}`,
  });
}

function removeDraftSub(index: number) {
  if (draftSubs.value.length <= 1) {
    message.warning('至少保留一个子页');
    return;
  }
  draftSubs.value.splice(index, 1);
}

function saveSubpages() {
  for (const row of draftSubs.value) {
    row.id = row.id.trim();
    row.label = row.label.trim();
    row.path = row.path.trim();
    if (!row.id || !row.label || !row.path) {
      message.warning('子页标识、名称和路径不能为空');
      return;
    }
  }
  subpages.value = { ...subpages.value, [subParentId.value]: draftSubs.value.map((row) => ({ ...row })) };
  subModalOpen.value = false;
  message.success('子页面已更新');
}

function openCustomModal() {
  if (selectedCount.value >= maxPages.value) {
    message.warning(`最多选择 ${maxPages.value} 个页面`);
    return;
  }
  customForm.label = '';
  customForm.path = '';
  customForm.summary = '';
  customModalOpen.value = true;
}

function addCustomPage() {
  const label = customForm.label.trim();
  let path = customForm.path.trim();
  const summary = customForm.summary.trim();
  if (!label) {
    message.warning('请填写页面名称');
    return;
  }
  if (!path) {
    path = `pages/custom/${Date.now().toString(36)}`;
  }
  if (!path.startsWith('pages/')) {
    message.warning('路径须以 pages/ 开头');
    return;
  }
  const id = `custom_${Date.now().toString(36)}`;
  const page: AppShellCustomPage = { id, label, path, summary };
  customPages.value = [...customPages.value, page];
  subpages.value = {
    ...subpages.value,
    [id]: [{ id: `${id}_main`, label, path }],
  };
  customModalOpen.value = false;
  message.success('已添加自定义页面');
}

function removeCustomPage(id: string) {
  customPages.value = customPages.value.filter((item) => item.id !== id);
  const next = { ...subpages.value };
  delete next[id];
  subpages.value = next;
  if (previewId.value === id) {
    previewId.value = modules.value[0] || customPages.value[0]?.id || 'home';
  }
}

function pickPage(id: string) {
  previewId.value = id;
}

function pickSkin(skinId: string) {
  const id = previewId.value;
  if (!id || customPages.value.some((item) => item.id === id)) return;
  skins.value = { ...skins.value, [id]: skinId };
  if (!modules.value.includes(id)) toggleModule(id, true);
}

function goStep2() {
  if (selectedCount.value < 1) {
    message.warning('至少选择一个页面');
    return;
  }
  if (!enabledSidebar.value.some((item) => item.id === previewId.value)) {
    previewId.value = enabledSidebar.value[0]?.id || 'home';
  }
  step.value = 2;
}

async function load() {
  loading.value = true;
  error.value = '';
  try {
    const data = await paopaoApi.appShell();
    catalog.value = data.catalog;
    modules.value = data.modules;
    skins.value = { ...data.skins };
    tabbar.value = data.tabbar;
    customPages.value = data.customPages || [];
    subpages.value = data.subpages || {};
    maxPages.value = data.limits?.max_pages || 20;
    if (!data.modules.includes(previewId.value)) {
      previewId.value = data.modules[0] || data.catalog.modules[0]?.id || 'home';
    }
    packNote.value = '';
  } catch (cause) {
    error.value = cause instanceof Error ? cause.message : '形态配置没有读到';
  } finally {
    loading.value = false;
  }
}

async function loadFeed() {
  feed.value = { ...feed.value, loading: true };
  const settled = await Promise.allSettled([
    paopaoApi.posts({ per_page: 8, status: 'published' }),
    paopaoApi.boards(),
    paopaoApi.apps({ per_page: 8 }),
    paopaoApi.mallProducts(),
    paopaoApi.banners(),
    paopaoApi.grid(),
    paopaoApi.broadcasts(),
    requestClient.get<PageResult<NoticeRow>>('/notifications', { params: { per_page: 8 } }),
    requestClient.get<PageResult<ThreadRow>>('/conversations', { params: { per_page: 8 } }),
    requestClient.get<MeRow>('/auth/me'),
    paopaoApi.plugins(),
  ]);
  const posts = itemsOf<PostRow & { excerpt?: string; stats?: { comment?: number; favorite?: number; like?: number; view?: number } }>(
    settled[0].status === 'fulfilled' ? settled[0].value : [],
  );
  const boards = itemsOf<BoardRow>(settled[1].status === 'fulfilled' ? settled[1].value : []);
  const apps = itemsOf<AppRow>(settled[2].status === 'fulfilled' ? settled[2].value : []);
  const products = itemsOf<MallProductRow & { cover?: null | string }>(
    settled[3].status === 'fulfilled' ? settled[3].value : [],
  );
  const banners = itemsOf<BannerRow>(settled[4].status === 'fulfilled' ? settled[4].value : []);
  const grid = itemsOf<GridRow>(settled[5].status === 'fulfilled' ? settled[5].value : []);
  const broadcasts = itemsOf<BroadcastRow>(settled[6].status === 'fulfilled' ? settled[6].value : []);
  const notices = itemsOf<NoticeRow>(settled[7].status === 'fulfilled' ? settled[7].value : []);
  const threads = itemsOf<ThreadRow>(settled[8].status === 'fulfilled' ? settled[8].value : []);
  const me = settled[9].status === 'fulfilled' ? settled[9].value : null;
  const plugins = settled[10].status === 'fulfilled' ? settled[10].value.items : [];

  const messages: ShellMessage[] = [];
  threads.forEach((item) => {
    messages.push({
      body: textOf(item.last_message?.body) || '暂无内容',
      id: item.id,
      title: item.peer?.nickname || '私信',
    });
  });
  notices.forEach((item) => {
    messages.push({
      body: textOf(item.body) || '暂无内容',
      id: 10_000 + item.id,
      title: textOf(item.title, 18) || '通知',
    });
  });
  broadcasts.forEach((item) => {
    messages.push({
      body: textOf(item.body) || '暂无内容',
      id: 20_000 + item.id,
      title: textOf(item.title, 18) || '系统通知',
    });
  });

  feed.value = {
    apps: apps.map((item) => ({
      downloads: item.download_count || 0,
      icon: item.icon || null,
      id: item.id,
      name: item.name,
      summary: textOf(item.summary),
    })),
    banners: banners
      .filter((item) => item.status === 'published' && item.image)
      .map((item) => ({ id: item.id, image: item.image, title: item.title || '运营' })),
    boards: boards.map((item) => ({
      description: textOf(item.description),
      id: item.id,
      name: item.name,
      posts: item.post_count || 0,
    })),
    chips: grid
      .filter((item) => item.status === 'published')
      .map((item) => ({ id: item.id, title: item.title })),
    loading: false,
    me: me
      ? {
          followers: me.stats?.follower_count || 0,
          following: me.stats?.following_count || 0,
          id: me.id,
          likes: me.stats?.like_count || 0,
          nickname: me.nickname || '未命名',
          posts: me.stats?.post_count || 0,
        }
      : null,
    messages,
    plugins: (plugins as PluginRow[]).filter((item) => item.enabled).map((item) => item.name),
    posts: posts.map((item) => ({
      author: item.author?.nickname || '用户',
      board: item.board?.name || '',
      comments: item.stats?.comment || 0,
      cover: item.cover || null,
      excerpt: textOf(item.excerpt || item.content, 72),
      favorites: item.stats?.favorite || 0,
      id: item.id,
      likes: item.stats?.like || 0,
      title: item.title,
      video: Boolean((item as { video_url?: null | string }).video_url),
      views: item.stats?.view || 0,
    })),
    products: products.map((item) => ({
      cover: item.cover || null,
      id: item.id,
      price: item.price,
      summary: textOf(item.summary),
      title: item.title,
    })),
  };
}

function noteFrom(data: AppShellConfig) {
  const kept = data.pack.tabs.map((item) => item.label).join('、') || '无';
  const dropped = data.pack.omitted.map((item) => item.label).join('、');
  return dropped
    ? `已写入下次打包。留下 ${kept}，去掉 ${dropped}。`
    : `已写入下次打包。六个页面都留下：${kept}。`;
}

async function save() {
  saving.value = true;
  try {
    const data = await paopaoApi.saveAppShell({
      modules: modules.value,
      skins: skins.value,
      tabbar: tabbar.value,
      customPages: customPages.value,
      subpages: subpages.value,
    });
    modules.value = data.modules;
    skins.value = { ...data.skins };
    tabbar.value = data.tabbar;
    customPages.value = data.customPages || [];
    subpages.value = data.subpages || {};
    packNote.value = noteFrom(data);
    message.success('App 形态已保存并应用');
  } catch (cause) {
    message.error(cause instanceof Error ? cause.message : '保存失败');
  } finally {
    saving.value = false;
  }
}

onMounted(() => {
  void load();
  void loadFeed();
});
</script>

<template>
  <Page
    description="两步配置 App：先勾选页面和子页，再选每页皮肤与底部导航。"
    title="App 形态"
  >
    <Alert v-if="error" class="mb-4" show-icon type="error">
      <template #message>
        {{ error }}
        <button class="retry" type="button" @click="load">重试</button>
      </template>
    </Alert>

    <div v-if="loading" class="studio wait">正在读取形态配置</div>
    <div v-else class="studio">
      <div class="steps">
        <div class="step" :class="{ on: step === 1, done: step > 1 }">
          <b>1</b>
          <div>
            <strong>选择页面</strong>
            <span>配置应用包含的页面</span>
          </div>
        </div>
        <i />
        <div class="step" :class="{ on: step === 2 }">
          <b>2</b>
          <div>
            <strong>选择模板</strong>
            <span>选择页面模板和样式</span>
          </div>
        </div>
      </div>

      <template v-if="step === 1">
        <div class="head">
          <div>
            <h2>1. 配置应用页面</h2>
            <p>勾选内置模块，管理子页面，或添加自定义扩展页。</p>
          </div>
          <div class="head-meta">
            <span>已选 {{ selectedCount }}/{{ maxPages }} 页</span>
          </div>
        </div>

        <section class="home-card">
          <header>
            <label class="check">
              <input
                :checked="modules.includes('home')"
                type="checkbox"
                @change="toggleModule('home', ($event.target as HTMLInputElement).checked)"
              />
              首页
            </label>
            <em class="tag">首页</em>
            <span class="on-dot">已开启</span>
          </header>
          <div class="module-grid">
            <article
              v-for="item in catalog.modules"
              :key="item.id"
              class="module"
              :class="{ off: !modules.includes(item.id) }"
            >
              <div class="module-top">
                <strong>{{ item.label }}</strong>
                <label class="switch">
                  <input
                    :checked="modules.includes(item.id)"
                    type="checkbox"
                    @change="toggleModule(item.id, ($event.target as HTMLInputElement).checked)"
                  />
                  <i />
                </label>
              </div>
              <p>{{ item.summary || '内置页面模块' }}</p>
              <div class="module-foot">
                <span>子页 {{ subCount(item.id) }}</span>
                <button type="button" @click="openSubpages(item.id)">管理子页面 →</button>
              </div>
            </article>
          </div>
        </section>

        <section v-if="customPages.length" class="custom-list">
          <article v-for="item in customPages" :key="item.id" class="module">
            <div class="module-top">
              <strong>{{ item.label }}</strong>
              <button class="link danger" type="button" @click="removeCustomPage(item.id)">删除</button>
            </div>
            <p>{{ item.summary || item.path }}</p>
            <div class="module-foot">
              <span>子页 {{ subCount(item.id) }}</span>
              <button type="button" @click="openSubpages(item.id)">管理子页面 →</button>
            </div>
          </article>
        </section>

        <button class="add-page" type="button" @click="openCustomModal">
          <b>+</b>
          <strong>添加页面</strong>
          <span>自定义扩展页，创建专属功能</span>
        </button>

        <footer class="bar">
          <span>已选 {{ selectedCount }} 页</span>
          <button class="primary" type="button" @click="goStep2">下一步 →</button>
        </footer>
      </template>

      <template v-else>
        <div class="step2">
          <aside class="side">
            <h3>已启用页面</h3>
            <button
              v-for="item in enabledSidebar"
              :key="item.id"
              type="button"
              class="side-item"
              :class="{ on: previewId === item.id }"
              @click="pickPage(item.id)"
            >
              {{ item.label }}
              <em v-if="item.custom">自定义</em>
            </button>
          </aside>

          <div class="main">
            <section v-if="!customPages.some((p) => p.id === previewId)">
              <h2>{{ preview?.label || '页面' }}模板</h2>
              <p>点选版式后，右侧真机立即换成同一套。保留全部现有皮肤。</p>
              <div class="skin-row">
                <button
                  v-for="skin in preview?.skins || []"
                  :key="skin.id"
                  type="button"
                  class="skin-card"
                  :class="{ on: skins[previewId] === skin.id }"
                  @click="pickSkin(skin.id)"
                >
                  <strong>{{ skin.label }}</strong>
                  <span>{{ skinCopy[`${previewId}:${skin.id}`] || `${skin.label}版式` }}</span>
                </button>
              </div>
            </section>
            <section v-else>
              <h2>{{ enabledSidebar.find((i) => i.id === previewId)?.label }}</h2>
              <p>自定义页暂无内置皮肤，保存后会随壳层下发路径与子页。</p>
            </section>

            <section>
              <h2>底部导航样式</h2>
              <div class="skin-row">
                <button
                  v-for="item in catalog.tabbars"
                  :key="item.id"
                  type="button"
                  class="skin-card"
                  :class="{ on: tabbar === item.id }"
                  @click="tabbar = item.id"
                >
                  <strong>{{ item.label }}</strong>
                  <span>{{ barCopy[item.id] || '底栏样式' }}</span>
                </button>
              </div>
            </section>
          </div>

          <aside class="phone-pane">
            <ShellPhone
              v-if="!customPages.some((p) => p.id === previewId)"
              :audience="mineAudience"
              caption="真机预览"
              :data="feed"
              :modules="enabled"
              :page-id="previewId"
              :selected="false"
              :skin="skins[previewId] || preview?.skins?.[0]?.id || 'plain'"
              :tabbar="tabbar"
              @open="pickPage"
            />
            <div v-else class="custom-phone">
              <strong>{{ enabledSidebar.find((i) => i.id === previewId)?.label }}</strong>
              <p>自定义扩展页预览占位</p>
            </div>
            <p class="hint">当前底栏：{{ tabbarLabel }}</p>
          </aside>
        </div>

        <footer class="bar">
          <button class="ghost" type="button" @click="step = 1">上一步</button>
          <button class="primary" type="button" :disabled="saving" @click="save">
            {{ saving ? '保存中' : '保存并应用' }}
          </button>
        </footer>
        <p v-if="packNote" class="saved">{{ packNote }}</p>
      </template>
    </div>

    <Modal v-model:open="subModalOpen" title="管理子页面" ok-text="保存" @ok="saveSubpages">
      <div class="sub-editor">
        <div v-for="(row, index) in draftSubs" :key="row.id + index" class="sub-row">
          <input v-model="row.label" placeholder="名称" />
          <input v-model="row.path" placeholder="pages/..." />
          <button type="button" @click="removeDraftSub(index)">删</button>
        </div>
        <button type="button" class="link" @click="addDraftSub">+ 添加子页</button>
      </div>
    </Modal>

    <Modal v-model:open="customModalOpen" title="添加页面" ok-text="添加" @ok="addCustomPage">
      <div class="sub-editor">
        <label>名称<input v-model="customForm.label" placeholder="例如：活动页" /></label>
        <label>路径<input v-model="customForm.path" placeholder="pages/custom/activity" /></label>
        <label>说明<input v-model="customForm.summary" placeholder="可选" /></label>
      </div>
    </Modal>
  </Page>
</template>

<style scoped>
.studio {
  background: var(--paopao-soft, #f6f4ef);
  color: var(--paopao-ink, #1c1c1a);
  border-radius: 16px;
  padding: 24px 28px 28px;
}
.wait { color: var(--paopao-muted, #6d675f); font-size: 14px; }
.steps {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 24px;
}
.step {
  display: flex;
  align-items: center;
  gap: 10px;
  opacity: 0.45;
}
.step.on, .step.done { opacity: 1; }
.step b {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  display: grid;
  place-items: center;
  background: #e8e2f8;
  color: #6b4eff;
  font-size: 13px;
}
.step.on b { background: #6b4eff; color: #fff; }
.step strong { display: block; font-size: 14px; }
.step span { color: var(--paopao-muted, #6d675f); font-size: 12px; }
.steps > i {
  flex: 1;
  height: 1px;
  background: #ddd6f3;
}
.head {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 16px;
}
.head h2 { margin: 0; font-size: 18px; }
.head p { margin: 6px 0 0; color: var(--paopao-muted, #6d675f); font-size: 13px; }
.head-meta { color: var(--paopao-muted, #6d675f); font-size: 13px; }
.home-card {
  border: 1px solid #cfc3f5;
  border-radius: 16px;
  background: #fff;
  padding: 16px;
}
.home-card header {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 14px;
}
.check { display: flex; align-items: center; gap: 8px; font-weight: 650; }
.tag {
  background: #eee8ff;
  color: #6b4eff;
  border-radius: 999px;
  padding: 2px 8px;
  font-style: normal;
  font-size: 12px;
}
.on-dot { margin-left: auto; color: #1a9f5b; font-size: 12px; }
.module-grid, .custom-list {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 12px;
}
.custom-list { margin-top: 12px; }
.module {
  border: 1px solid #ece7df;
  border-radius: 12px;
  padding: 12px;
  background: #faf9f6;
}
.module.off { opacity: 0.55; }
.module-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 8px;
}
.module p {
  margin: 8px 0;
  min-height: 36px;
  color: var(--paopao-muted, #6d675f);
  font-size: 12px;
  line-height: 1.45;
}
.module-foot {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 8px;
  font-size: 12px;
}
.module-foot button, .link {
  border: 0;
  background: transparent;
  color: #6b4eff;
  cursor: pointer;
  padding: 0;
}
.link.danger { color: #c23b3b; }
.switch {
  position: relative;
  width: 36px;
  height: 20px;
}
.switch input { opacity: 0; width: 0; height: 0; }
.switch i {
  position: absolute;
  inset: 0;
  border-radius: 999px;
  background: #d7d2c8;
  transition: 0.15s;
}
.switch i::after {
  content: '';
  position: absolute;
  top: 2px;
  left: 2px;
  width: 16px;
  height: 16px;
  border-radius: 50%;
  background: #fff;
  transition: 0.15s;
}
.switch input:checked + i { background: #6b4eff; }
.switch input:checked + i::after { transform: translateX(16px); }
.add-page {
  margin-top: 16px;
  width: 100%;
  border: 1px dashed #cfc3f5;
  border-radius: 14px;
  background: #fbfaff;
  padding: 22px;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  color: inherit;
}
.add-page b {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  display: grid;
  place-items: center;
  background: #eee8ff;
  color: #6b4eff;
  font-size: 22px;
  font-weight: 400;
}
.add-page span { color: var(--paopao-muted, #6d675f); font-size: 12px; }
.bar {
  margin-top: 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
}
.primary, .ghost {
  height: 36px;
  padding: 0 18px;
  border-radius: 10px;
  border: 0;
  cursor: pointer;
  font-size: 14px;
}
.primary { background: #6b4eff; color: #fff; }
.primary:disabled { opacity: 0.6; cursor: default; }
.ghost { background: #fff; border: 1px solid #e4dfd6; color: inherit; }
.step2 {
  display: grid;
  grid-template-columns: 180px minmax(0, 1fr) 300px;
  gap: 20px;
}
.side h3 { margin: 0 0 10px; font-size: 13px; color: var(--paopao-muted, #6d675f); }
.side-item {
  width: 100%;
  text-align: left;
  border: 0;
  background: transparent;
  border-radius: 8px;
  padding: 10px 12px;
  cursor: pointer;
  color: inherit;
  display: flex;
  justify-content: space-between;
  gap: 8px;
}
.side-item.on { background: #eee8ff; color: #6b4eff; font-weight: 650; }
.side-item em {
  font-style: normal;
  font-size: 11px;
  background: #6b4eff;
  color: #fff;
  border-radius: 999px;
  padding: 1px 6px;
}
.main section { margin-bottom: 22px; }
.main h2 { margin: 0; font-size: 15px; }
.main p { margin: 6px 0 0; color: var(--paopao-muted, #6d675f); font-size: 13px; }
.skin-row {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap: 10px;
  margin-top: 12px;
}
.skin-card {
  text-align: left;
  border: 1px solid transparent;
  border-radius: 12px;
  background: #fff;
  padding: 12px;
  cursor: pointer;
  color: inherit;
}
.skin-card.on { border-color: #6b4eff; box-shadow: 0 0 0 1px #6b4eff inset; }
.skin-card strong { display: block; font-size: 14px; }
.skin-card span {
  display: block;
  margin-top: 6px;
  color: var(--paopao-muted, #6d675f);
  font-size: 12px;
  line-height: 1.4;
}
.phone-pane { position: sticky; top: 16px; }
.custom-phone {
  height: 520px;
  border-radius: 28px;
  background: #17171a;
  color: #fff;
  display: grid;
  place-content: center;
  text-align: center;
  gap: 8px;
}
.hint { color: var(--paopao-muted, #6d675f); font-size: 12px; }
.saved { margin-top: 12px; font-size: 13px; }
.sub-editor { display: flex; flex-direction: column; gap: 10px; }
.sub-row, .sub-editor label {
  display: grid;
  grid-template-columns: 1fr 1.4fr auto;
  gap: 8px;
  align-items: center;
}
.sub-editor label { grid-template-columns: 64px 1fr; }
.sub-editor input {
  height: 32px;
  border: 1px solid #e4dfd6;
  border-radius: 8px;
  padding: 0 10px;
}
.retry {
  margin-left: 8px;
  border: 0;
  background: transparent;
  color: inherit;
  cursor: pointer;
  text-decoration: underline;
}
@media (max-width: 1100px) {
  .step2 { grid-template-columns: 1fr; }
  .phone-pane { position: static; }
}
</style>
