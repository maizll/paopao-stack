# App 形态旗舰备选页 Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** 砍掉换皮雷同备选，重做真正不同产品感的旗舰模板（含论坛双列瀑布），后台挑选卡带缩略图、真机预览填满；保留用户当下正在使用的皮肤不动。

**Architecture:** 后端 `AppShell` catalog 区分「旗舰展示」与「遗留保留」；已存 skin 永不在读路径被静默改写。后台 `shell.vue` 挑选卡改为缩略图卡；`shell-phone.vue` 按 skin 分支出真正不同的布局并强制封面兜底。uni 端非当前皮肤对应页面同步重做；当前皮肤页只做缺陷修复（有图、无空），不改信息架构。盖包沿用 0.1.86 流程：vite 重编 admin shell 指纹映射进 release zip。

**Tech Stack:** Laravel `AppShell.php` + Feature 测试；Vue/Vben admin `shell.vue` / `shell-phone.vue`；uni-app `apps/uni` 各 pages；GitHub `maizll/paopao-stack`；发布仓 `maizll/paopao-stack-app`（若仅 APK）/ 现网 zip apply 流程。

## Global Constraints

- 硬约束：用户当下使用的模板原样保留；打开「应用形态」不得静默改写已选 skins/tabbar。
- 除当前使用外，其余模板全部可重做（用户 2026-10-27 确认）。
- 论坛页对标实机：关注/推荐/最新 + 搜索 + 双列有封面瀑布。
- 禁止手改混淆后的 import 别名；必须 vite 源码重编再指纹映射。
- 验收必须有浏览器截图；禁止口头「修好了」。
- 版本：0.1.86 基线 → 0.1.87+。
- Spec：`docs/superpowers/specs/2026-10-27-paopao-app-shell-flagship-skins-design.md`（box 副本；实现时同步进仓库 `docs/`）。

**Repos文件：**
- `backend/app/Support/AppShell.php`
- `backend/tests/Feature/Phase8ShellTest.php`
- `apps/admin/apps/web-antd/src/views/paopao/system/shell.vue`
- `apps/admin/apps/web-antd/src/views/paopao/system/shell-phone.vue`
- `apps/uni/src/pages/home/home.vue`（及 community/discover/mall/messages/mine）
- 静态缩略图：`apps/admin/apps/web-antd/public/shell-thumbs/`（新建）

---

### Task 1: 冻结现网「当前使用」快照

**Files:**
- Create: `docs/superpowers/specs/2026-10-27-shell-current-skins.json`（仓库内）
- Test: 人工/脚本读现网 API，写入 JSON

**Interfaces:**
- Produces: JSON `{ modules, skins, tabbar, captured_at }` 作为后续任务「不得改动的 baseline」

- [ ] **Step 1: 从现网读 shell 配置**

通过已登录 admin 会话或 `SiteSetting.shell` 导出当前 `modules`/`skins`/`tabbar`。写入：

```json
{
  "captured_at": "2026-10-27T12:00:00+08:00",
  "modules": ["home", "discover", "mall", "messages", "mine"],
  "skins": { "home": "<actual>", "discover": "<actual>", "mall": "<actual>", "messages": "<actual>", "mine": "<actual>", "community": "<actual-if-any>" },
  "tabbar": "labeled"
}
```

（值以实读为准，勿抄示例。）

- [ ] **Step 2: 浏览器拍「改前」第二步各模块高亮截图**

目录：`/workspace/paopao-app-templates-before/`，文件名 `before-{module}-{skin}.png`。

- [ ] **Step 3: Commit 快照 JSON**

```bash
git add docs/superpowers/specs/2026-10-27-shell-current-skins.json
git commit -m "docs: snapshot live App shell skins before flagship redo"
```

---

### Task 2: 后端 catalog —— 旗舰 + 遗留保留 API

**Files:**
- Modify: `backend/app/Support/AppShell.php`
- Modify: `backend/tests/Feature/Phase8ShellTest.php`

**Interfaces:**
- Produces: `AppShell::FLAGSHIP_SKINS`, `AppShell::LEGACY_SKIN_MAP`, `catalog()` 每 skin 增加 `flagship: bool`, `thumb: string`, `blurb: string`；`normalize()` 仍接受全部已知 skin id（含遗留），**读路径不 remap**。

- [ ] **Step 1: 写失败测试 —— catalog 标记与 normalize 不静默改写**

在 `Phase8ShellTest.php` 增加：

```php
public function test_catalog_marks_flagship_and_normalize_keeps_legacy_current_skin(): void
{
    $catalog = AppShell::catalog();
    $homeIds = array_column(
        collect($catalog['modules'])->firstWhere('id', 'home')['skins'],
        'id'
    );
    $this->assertContains('feed', $homeIds);
    $this->assertTrue(
        collect($catalog['modules'])->firstWhere('id', 'home')['skins'][0]['flagship']
        || collect($catalog['modules'])->firstWhere('id', 'home')['skins'][array_search('feed', $homeIds, true)]['flagship']
    );

    $saved = AppShell::normalize([
        'modules' => ['home', 'mine'],
        'skins' => ['home' => 'compact', 'mine' => 'plain'],
        'tabbar' => 'labeled',
    ]);
    $this->assertSame('compact', $saved['skins']['home']);
    $this->assertSame('plain', $saved['skins']['mine']);
}
```

- [ ] **Step 2: 跑测试确认失败**

Run: `cd backend && php artisan test --filter=test_catalog_marks_flagship_and_normalize_keeps_legacy_current_skin`  
Expected: FAIL（无 flagship 字段或方法）

- [ ] **Step 3: 实现 catalog 字段与常量（不删 SKINS 键）**

在 `AppShell.php`：

```php
/** 旗舰展示清单（UI 主推）；遗留 id 仍留在 SKINS 里供当前用户保留 */
public const FLAGSHIP_SKINS = [
    'home' => ['magazine', 'feed', 'haosns'], // magazine 视觉重做为论坛双列瀑布
    'community' => ['forum', 'haosns'],
    'discover' => ['grid', 'haosns'],
    'mall' => ['poster', 'haosns'],
    'messages' => ['list', 'bubble'],
    'mine' => ['card', 'haosns'],
];

/** 仅用于「新建默认 / 用户主动放弃遗留后」的建议映射；读路径禁止调用 */
public const LEGACY_SKIN_MAP = [
    'home' => ['compact' => 'magazine'],
    'community' => ['board' => 'forum', 'compact' => 'forum'],
    'discover' => ['shelf' => 'grid', 'list' => 'haosns'],
    'mall' => ['card' => 'haosns', 'compact' => 'haosns'],
    'messages' => ['compact' => 'list', 'haosns' => 'list'],
    'mine' => ['plain' => 'card', 'grid' => 'card'],
];
```

`catalog()` 每个 skin 输出：`id`, `label`, `flagship`, `thumb`（如 `/admin/shell-thumbs/home-feed.png`）, `blurb`（产品定位一句，非换皮文案）。`normalize()` 逻辑保持接受 `SKINS` 全量 id，**不要**自动套用 `LEGACY_SKIN_MAP`。

- [ ] **Step 4: 跑测试通过 + 既有 Phase8 全绿**

Run: `cd backend && php artisan test --filter=Phase8ShellTest`  
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add backend/app/Support/AppShell.php backend/tests/Feature/Phase8ShellTest.php
git commit -m "feat(shell): flagship catalog metadata without remapping saved skins"
```

---

### Task 3: 真机预览 `shell-phone.vue` —— 非当前皮肤重做 + 全皮肤补图

**Files:**
- Modify: `apps/admin/apps/web-antd/src/views/paopao/system/shell-phone.vue`
- Modify: 对应 scoped CSS（同文件或 `shell-phone` 样式段）

**Interfaces:**
- Consumes: `props.skin`, `props.pageId`, `props.data.posts|products|apps|me`（已有）
- Produces: 各 skin 分支布局肉眼可分；`fallbackCover(i)` 保证格子有图

- [ ] **Step 1: 加封面兜底 helper（所有皮肤共用）**

```ts
const FALLBACK_COVERS = [
  '/admin/shell-thumbs/fallback-1.jpg',
  '/admin/shell-thumbs/fallback-2.jpg',
  '/admin/shell-thumbs/fallback-3.jpg',
  '/admin/shell-thumbs/fallback-4.jpg',
];
function coverOf(src: string | null | undefined, index: number): string {
  if (src && /^(https?:)?\/\//i.test(src) || (src && src.startsWith('/'))) return src;
  return FALLBACK_COVERS[index % FALLBACK_COVERS.length];
}
```

- [ ] **Step 2: 重做首页非「当前使用」皮肤；`magazine` 做成论坛双列瀑布**

对标用户实机：顶栏 关注/推荐/最新 + 搜索 + 双列有封面卡片。`feed` 必须有视频舞台/封面帧，禁止纯黑空台。`haosns` 做成与双列明显不同的单列动态卡（头像+封面+互动）。若 Task1 快照显示当前 home skin 为某 id，该分支只修缺图，不改结构。

- [ ] **Step 3: 重做 community / discover / mall / messages / mine 非当前皮肤**

最低标准（与规格一致）：
- discover `grid`：真三列，文案不裁切
- mall `poster`：每条商品大图，不只第一条
- messages `bubble`：像真实会话，禁止黑块对齐 demo
- mine `card`：头图 + 双列笔记封面，禁止纯文字砖

- [ ] **Step 4: 本地/代理打开 `#/system/shell` 第二步点每套旗舰截图**

存 `/workspace/paopao-app-templates-after/phone-*.png`。对照 before：非当前皮肤须明显不同；当前皮肤高亮与 before 一致。

- [ ] **Step 5: Commit**

```bash
git add apps/admin/apps/web-antd/src/views/paopao/system/shell-phone.vue
git commit -m "feat(admin): rebuild shell-phone flagship previews with cover fallbacks"
```

---

### Task 4: 挑选卡 `shell.vue` —— 缩略图 + 「当前使用」标记

**Files:**
- Modify: `apps/admin/apps/web-antd/src/views/paopao/system/shell.vue`
- Create: `apps/admin/apps/web-antd/public/shell-thumbs/*.png`（每旗舰一张 + fallback）

**Interfaces:**
- Consumes: catalog skins 的 `thumb`/`blurb`/`flagship`
- Produces: 第二步皮肤行展示缩略图；非旗舰但等于 `A.value[module]` 的项显示「当前使用」

- [ ] **Step 1: 生成/导出缩略图资源**

从 Task3 真机预览裁切或绘制固定比例缩略图，命名 `home-magazine.png` 等，放入 `public/shell-thumbs/`。

- [ ] **Step 2: 改皮肤按钮为图文卡**

模板逻辑：列出 `flagship === true` 的皮肤；若当前选中 skin 不在旗舰列表，额外插入一张带「当前使用」角标的卡。点击仍写 `A[module]=skinId`。文案用 `blurb`，禁止「标题更紧」类换皮句。

- [ ] **Step 3: 浏览器验收挑选卡一眼可分 + 当前项仍高亮**

截图 `after-picker.png`。与 Task1 before 对比选中项。

- [ ] **Step 4: Commit**

```bash
git add apps/admin/apps/web-antd/src/views/paopao/system/shell.vue apps/admin/apps/web-antd/public/shell-thumbs
git commit -m "feat(admin): shell picker thumbnails and keep-current badge"
```

---

### Task 5: uni 端 —— 非当前皮肤页面重做

**Files:**
- Modify: `apps/uni/src/pages/home/home.vue`
- Modify: `apps/uni/src/pages/community/community.vue`
- Modify: `apps/uni/src/pages/discover/discover.vue`
- Modify: `apps/uni/src/pages/messages/messages.vue`
- Modify: `apps/uni/src/pages/mine/mine.vue`
- Modify: mall 对应页（若路径为 `pages/mall/home` 按仓库实路径）
- 可能 Modify: `apps/uni/src/components/post-feed/post-feed.vue`, `note-card/note-card.vue`

**Interfaces:**
- Consumes: 运行时 shell skin（现有下发方式，保持不变）
- Produces: 非当前 skin 分支布局重做；当前 skin 仅补封面/空态，信息架构不动

- [ ] **Step 1: 对照 Task1 快照，列出「可动 / 冻结」skin 分支表**

写入 PR 说明。冻结分支禁止改 DOM 结构。

- [ ] **Step 2: 按表重做可动分支（首页论坛双列优先对齐实机图）**

关注/推荐/最新 + 搜索 + 双列封面瀑布。商城海报全量大图；发现真三列；我的双列笔记有图。

- [ ] **Step 3: 真机或 H5 切换非当前皮肤目测；当前皮肤回归与实机图一致**

- [ ] **Step 4: Commit**

```bash
git add apps/uni/src/pages apps/uni/src/components
git commit -m "feat(uni): rebuild non-current shell skins; keep active layouts"
```

---

### Task 6: 盖包 0.1.87、代理验收、现网 apply

**Files:**
- Modify: 版本号 / `server.json` / release notes（沿 0.1.86 打包脚本）
- 产出: `paopao-api-0.1.87.zip` + sha256 + GitHub Release

- [ ] **Step 1: vite 重编 admin shell 相关 chunk，指纹映射进 zip（禁止手改 jse 别名）**

- [ ] **Step 2: apply 到 `127.0.0.1:18780`，紧急登录验收清单**

清单（每项截图）：
1. 挑选卡有缩略图，旗舰布局肉眼不同
2. 真机预览封面/商品图可见，无大片留白
3. 已选皮肤与 Task1 快照一致
4. 软跳离开应用形态再进其它页无需整页刷新
5. 保存后再读，当前遗留 skin 仍在

- [ ] **Step 3: 用户确认后 apply 现网 `:18080`**

- [ ] **Step 4: Commit + tag + Release**

```bash
git commit -m "release: 0.1.87 app shell flagship skins"
git tag v0.1.87
```

---

## Spec coverage (self-check)

| Spec 要求 | Task |
|-----------|------|
| 砍雷同留旗舰 | 2 |
| 保留当下使用 | 1, 2, 4 |
| 论坛双列瀑布 | 3, 5 |
| 缩略图挑选卡 | 4 |
| 真机填满/兜底图 | 3 |
| 旧 id 可读不静默改 | 2 |
| vite 重编盖包验收 | 6 |