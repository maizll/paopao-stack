import { requestClient } from '#/api/request';

export interface PageResult<T> {
  items: T[];
  meta: { has_more: boolean; page: number; per_page: number; total: number };
}

export interface PostRow {
  author: null | { nickname: string };
  board: null | { id: number; name: string };
  content?: string;
  cover?: null | string;
  created_at: null | string;
  id: number;
  status: string;
  title: string;
}

export interface UserRow {
  audits?: AuditLogRow[];
  created_at?: null | string;
  email: null | string;
  email_verified_at?: null | string;
  id: number;
  nickname: string;
  phone: null | string;
  post_count?: number;
  role: string;
  role_name?: string;
  status: string;
}

export interface BoardRow {
  description?: null | string;
  id: number;
  name: string;
  post_count: number;
  slug: string;
  status: string;
}

export interface TenantRow {
  id: number;
  name: string;
  plan_code: string;
  slug: string;
  status: string;
}

export interface AppRow {
  category: null | { id: number; name: string };
  description?: null | string;
  download_count: number;
  icon?: null | string;
  id: number;
  latest_version: null | { version_name: string };
  name: string;
  screenshots?: Array<{ id: number; image_url: string; sort: number }>;
  status: string;
  summary: null | string;
}

export interface AppInput {
  category_id?: null | number;
  description?: null | string;
  icon?: null | string;
  name: string;
  screenshots?: string[];
  summary?: null | string;
}

export interface AppCategoryRow {
  id: number;
  name: string;
  slug: string;
  sort: number;
}

export interface AppVersionRow {
  app?: { id: number; name?: string; status?: string };
  changelog: null | string;
  id: number;
  is_current: boolean;
  package_url: null | string;
  size: null | number;
  status: string;
  version_code: number;
  version_name: string;
}

export interface BannerRow {
  end_at?: null | string;
  id: number;
  image: string;
  link_type: string;
  link_value: null | string;
  sort: number;
  start_at?: null | string;
  status: string;
  title: null | string;
}

export interface GridRow {
  badge: null | string;
  icon: null | string;
  id: number;
  link_type: string;
  link_value: null | string;
  sort: number;
  status: string;
  title: string;
}

export interface RecommendationRow {
  id: number;
  sort: number;
  status: string;
  target_id: number;
  target_title: null | string;
  target_type: string;
  title: null | string;
}

export interface WorkbenchStats {
  downloads: number;
  pending_apps: number;
  pending_posts: number;
  pending_reports: number;
}

export interface AppReviewRow {
  app?: null | { id: null | number; name: null | string };
  body: string;
  created_at: null | string;
  id: number;
  stars: number;
  status: string;
  user: null | { id: null | number; nickname: string };
}

export interface MailChannel {
  channel: string;
  expose_code: boolean;
  from: string;
  hint: string;
  ready: boolean;
}

export interface SmtpSettings {
  encryption: string;
  from_address: string;
  from_name: string;
  host: string;
  password_set: boolean;
  port: number;
  ready: boolean;
  username: string;
}

export interface SiteSettings {
  force_login_download: boolean;
  mail?: MailChannel;
  privacy_markdown?: string;
  register_enabled: boolean;
  site_name: string;
  smtp?: SmtpSettings;
  terms_markdown?: string;
}

export interface SiteSettingsInput {
  force_login_download: boolean;
  privacy_markdown?: string;
  register_enabled: boolean;
  site_name: string;
  smtp_encryption?: string;
  smtp_from_address?: string;
  smtp_from_name?: string;
  smtp_host?: string;
  smtp_password?: string;
  smtp_port?: number;
  smtp_username?: string;
  terms_markdown?: string;
}

export interface SensitiveWordRow {
  action: 'reject' | 'review';
  created_at?: null | string;
  id: number;
  word: string;
}

export interface AuditLogRow {
  action: string;
  actor: null | { id: number; nickname: string };
  created_at: null | string;
  id: number;
  summary: string;
  target_id: null | number;
  target_type: null | string;
}

export interface UploadResult {
  path: string;
  url: string;
}

export interface AppShellSubpage {
  id: string;
  label: string;
  path: string;
}

export interface AppShellCustomPage {
  id: string;
  label: string;
  path: string;
  summary: string;
}

export interface AppShellConfig {
  catalog: {
    modules: Array<{
      id: string;
      label: string;
      path: string;
      summary?: string;
      skins: Array<{ id: string; label: string }>;
    }>;
    tabbars: Array<{ id: string; label: string }>;
  };
  customPages: AppShellCustomPage[];
  limits?: { max_pages: number };
  modules: string[];
  pack: {
    omitted: Array<{ id: string; label: string; path: string }>;
    tabs: Array<{
      id: string;
      label: string;
      path: string;
      custom?: boolean;
      subpages?: AppShellSubpage[];
    }>;
  };
  skins: Record<string, string>;
  subpages: Record<string, AppShellSubpage[]>;
  tabbar: string;
}

export const paopaoApi = {
  apps: (params: Record<string, number | string | undefined>) =>
    requestClient.get<PageResult<AppRow>>('/admin/apps', { params }),
  app: (id: number) => requestClient.get<AppRow>(`/admin/apps/${id}`),
  approveApp: (id: number) => requestClient.post<AppRow>(`/admin/apps/${id}/approve`),
  offlineApp: (id: number) => requestClient.post<AppRow>(`/admin/apps/${id}/offline`),
  appCategories: () => requestClient.get<AppCategoryRow[]>('/admin/app-categories'),
  createAppCategory: (body: { name: string; sort?: number }) =>
    requestClient.post<AppCategoryRow>('/admin/app-categories', body),
  updateAppCategory: (id: number, body: { name: string; sort?: number }) =>
    requestClient.put<AppCategoryRow>(`/admin/app-categories/${id}`, body),
  deleteAppCategory: (id: number) => requestClient.delete(`/admin/app-categories/${id}`),
  appVersions: (params: Record<string, number | string | undefined>) =>
    requestClient.get<PageResult<AppVersionRow>>('/admin/app-versions', { params }),
  createAppVersion: (body: {
    app_id: number;
    changelog?: string;
    package_url: string;
    size?: number;
    version_code: number;
    version_name: string;
  }) => requestClient.post<AppVersionRow>('/admin/app-versions', body),
  approveAppVersion: (id: number) =>
    requestClient.post<AppVersionRow>(`/admin/app-versions/${id}/approve`),
  rejectAppVersion: (id: number) =>
    requestClient.post<AppVersionRow>(`/admin/app-versions/${id}/reject`),
  offlineAppVersion: (id: number) =>
    requestClient.post<AppVersionRow>(`/admin/app-versions/${id}/offline`),
  currentAppVersion: (id: number) =>
    requestClient.post<AppVersionRow>(`/admin/app-versions/${id}/current`),
  categories: () =>
    requestClient.get<Array<{ id: number; name: string }>>('/apps/categories'),
  createApp: (body: AppInput) => requestClient.post<AppRow>('/admin/apps', body),
  createBanner: (body: Omit<BannerRow, 'id'>) =>
    requestClient.post<BannerRow>('/admin/ops/banners', body),
  createGrid: (body: Omit<GridRow, 'id'>) =>
    requestClient.post<GridRow>('/admin/ops/grid', body),
  deleteBanner: (id: number) => requestClient.delete(`/admin/ops/banners/${id}`),
  deleteGrid: (id: number) => requestClient.delete(`/admin/ops/grid/${id}`),
  rejectApp: (id: number, reason?: string) =>
    requestClient.post<AppRow>(
      `/admin/apps/${id}/reject`,
      reason ? { reason } : {},
    ),
  updateApp: (id: number, body: AppInput) =>
    requestClient.put<AppRow>(`/admin/apps/${id}`, body),
  updateBanner: (id: number, body: Omit<BannerRow, 'id'>) =>
    requestClient.put<BannerRow>(`/admin/ops/banners/${id}`, body),
  updateGrid: (id: number, body: Omit<GridRow, 'id'>) =>
    requestClient.put<GridRow>(`/admin/ops/grid/${id}`, body),
  uploadImage: (file: File) =>
    requestClient.upload<UploadResult>('/uploads', { file }),
  approvePost: (id: number) => requestClient.post(`/admin/posts/${id}/approve`),
  banners: () => requestClient.get<BannerRow[]>('/admin/ops/banners'),
  recommendations: () =>
    requestClient.get<RecommendationRow[]>('/admin/ops/recommendations'),
  createRecommendation: (body: Omit<RecommendationRow, 'id' | 'target_title'>) =>
    requestClient.post<RecommendationRow>('/admin/ops/recommendations', body),
  updateRecommendation: (
    id: number,
    body: Omit<RecommendationRow, 'id' | 'target_title'>,
  ) => requestClient.put<RecommendationRow>(`/admin/ops/recommendations/${id}`, body),
  deleteRecommendation: (id: number) =>
    requestClient.delete(`/admin/ops/recommendations/${id}`),
  workbench: () => requestClient.get<WorkbenchStats>('/admin/stats/overview'),
  sensitiveWords: () => requestClient.get<SensitiveWordRow[]>('/admin/sensitive-words'),
  saveSensitiveWord: (body: { action: string; word: string }) =>
    requestClient.post<SensitiveWordRow>('/admin/sensitive-words', body),
  updateSensitiveWord: (id: number, body: { action: string; word: string }) =>
    requestClient.put<SensitiveWordRow>(`/admin/sensitive-words/${id}`, body),
  deleteSensitiveWord: (id: number) =>
    requestClient.delete(`/admin/sensitive-words/${id}`),
  siteSettings: () => requestClient.get<SiteSettings>('/admin/system/settings'),
  appShell: () => requestClient.get<AppShellConfig>('/admin/shell'),
  saveAppShell: (body: {
    modules: string[];
    skins: Record<string, string>;
    tabbar: string;
    customPages?: AppShellCustomPage[];
    subpages?: Record<string, AppShellSubpage[]>;
  }) => requestClient.put<AppShellConfig>('/admin/shell', body),
  saveSiteSettings: (body: SiteSettingsInput) =>
    requestClient.put<SiteSettings>('/admin/system/settings', body),
  testSiteMail: (email: string) =>
    requestClient.post<{ sent: boolean }>('/admin/system/settings/mail-test', { email }),
  appReviews: (params: Record<string, number | string | undefined>) =>
    requestClient.get<PageResult<AppReviewRow>>('/admin/app-reviews', { params }),
  updateAppReview: (id: number, status: 'hidden' | 'published') =>
    requestClient.put<AppReviewRow>(`/admin/app-reviews/${id}`, { status }),
  deleteAppReview: (id: number) => requestClient.delete(`/admin/app-reviews/${id}`),
  auditLogs: (page = 1, perPage = 20) =>
    requestClient.get<PageResult<AuditLogRow>>('/admin/audit-logs', {
      params: { page, per_page: perPage },
    }),
  banUser: (id: number) => requestClient.post(`/admin/users/${id}/ban`),
  user: (id: number) => requestClient.get<UserRow>(`/admin/users/${id}`),
  createUser: (body: {
    email: string;
    nickname: string;
    password?: string;
    role: string;
  }) =>
    requestClient.post<{
      emailed: boolean;
      message: string;
      temporary_password: null | string;
      user: UserRow;
    }>('/admin/users', body),
  updateUserEmail: (
    id: number,
    body: { current_password: string; email: string; email_confirmation: string },
  ) => requestClient.request<UserRow>(`/admin/users/${id}/email`, { data: body, method: 'PATCH' }),
  resetUserPassword: (
    id: number,
    body: { current_password: string; password?: string; password_confirmation?: string },
  ) =>
    requestClient.post<{
      emailed: boolean;
      generated?: boolean;
      id: number;
      message: string;
      temporary_password: string;
    }>(`/admin/users/${id}/password`, body),
  boards: () => requestClient.get<BoardRow[]>('/admin/boards'),
  clientVersion: () =>
    requestClient.get<ClientRelease>('/admin/client-release'),
  saveClientRelease: (body: ClientReleaseInput) =>
    requestClient.put<ClientRelease>('/admin/client-release', body),
  syncClientRelease: () =>
    requestClient.post<ClientRelease>('/admin/client-release/sync'),
  serverRelease: (refresh = false) =>
    requestClient.get<ServerReleaseStatus>('/admin/server-release', {
      params: refresh ? { refresh: 1 } : {},
    }),
  saveServerRelease: (body: ServerReleaseInput) =>
    requestClient.put<ServerReleaseStatus>('/admin/server-release', body),
  applyServerRelease: (source?: 'manual' | 'remote') =>
    requestClient.post<ServerApplyResult>(
      '/admin/server-release/apply',
      source ? { source } : {},
    ),
  acknowledgeServerRelease: () =>
    requestClient.post<ServerReleaseStatus>('/admin/server-release/acknowledge'),
  config: () =>
    requestClient.get<{
      register_enabled: boolean;
      site_name: string;
      tenant: null | { name: string; plan_code: string; slug: string };
    }>('/system/config'),
  createBoard: (body: {
    description?: string;
    name: string;
    slug?: string;
    status: string;
  }) => requestClient.post<BoardRow>('/admin/boards', body),
  ensureDefaultBoard: () =>
    requestClient.post<{ board: BoardRow; created: boolean }>(
      '/admin/boards/ensure-default',
    ),
  updateBoard: (
    id: number,
    body: { description?: string; name?: string; status?: string },
  ) => requestClient.put<BoardRow>(`/admin/boards/${id}`, body),
  deleteBoard: (id: number) => requestClient.delete(`/admin/boards/${id}`),
  roles: () =>
    requestClient.get<{ catalog: PermissionItem[]; roles: RoleRow[] }>(
      '/admin/roles',
    ),
  updateRole: (slug: string, body: { name: string; permissions?: string[] }) =>
    requestClient.request<RoleRow>(`/admin/roles/${slug}`, {
      data: body,
      method: 'PATCH',
    }),
  assignRole: (id: number, role: string) =>
    requestClient.request<{ id: number; role: string; role_name: string }>(
      `/admin/users/${id}/role`,
      { data: { role }, method: 'PATCH' },
    ),
  createTenant: (body: { name: string; plan_code: string; slug: string }) =>
    requestClient.post('/admin/tenants', body),
  grid: () => requestClient.get<GridRow[]>('/admin/ops/grid'),
  health: () =>
    requestClient.get<{
      app: string;
      database: string;
      status: string;
      version: string;
    }>('/system/health'),
  me: () =>
    requestClient.get<{ id: number; nickname: string; roles: string[] }>(
      '/auth/me',
    ),
  post: (id: number) => requestClient.get<PostRow>(`/admin/posts/${id}`),
  updatePost: (
    id: number,
    body: {
      board_id: number;
      content: string;
      cover: null | string;
      title: string;
    },
  ) => requestClient.put<PostRow>(`/admin/posts/${id}`, body),
  posts: (params: Record<string, number | string | undefined>) =>
    requestClient.get<PageResult<PostRow>>('/admin/posts', { params }),
  rejectPost: (id: number, reason?: string) =>
    requestClient.post(
      `/admin/posts/${id}/reject`,
      reason ? { reason } : {},
    ),
  comments: (params: Record<string, number | string | undefined>) =>
    requestClient.get<PageResult<CommentRow>>('/admin/comments', { params }),
  approveComment: (id: number) =>
    requestClient.post<CommentRow>(`/admin/comments/${id}/approve`),
  rejectComment: (id: number, reason?: string) =>
    requestClient.post<CommentRow>(
      `/admin/comments/${id}/reject`,
      reason ? { reason } : {},
    ),
  hideComment: (id: number) =>
    requestClient.post<CommentRow>(`/admin/comments/${id}/hide`),
  deleteComment: (id: number) => requestClient.delete(`/admin/comments/${id}`),
  reports: (params: Record<string, number | string | undefined>) =>
    requestClient.get<PageResult<ReportRow>>('/admin/reports', { params }),
  resolveReport: (id: number, action: 'ban' | 'dismiss' | 'takedown', note?: string) =>
    requestClient.post<ReportRow>(`/admin/reports/${id}/resolve`, {
      action,
      note: note || undefined,
    }),
  tenants: () => requestClient.get<PageResult<TenantRow>>('/admin/tenants'),
  unbanUser: (id: number) => requestClient.post(`/admin/users/${id}/unban`),
  users: (params: Record<string, number | string | undefined>) =>
    requestClient.get<PageResult<UserRow>>('/admin/users', { params }),
  mallProducts: () =>
    requestClient.get<PageResult<MallProductRow>>('/admin/mall/products'),
  saveMallProduct: (body: MallProductInput, id?: number) =>
    id
      ? requestClient.put<MallProductRow>(`/admin/mall/products/${id}`, body)
      : requestClient.post<MallProductRow>('/admin/mall/products', body),
  deleteMallProduct: (id: number) =>
    requestClient.delete(`/admin/mall/products/${id}`),
  importMallCards: (id: number, codes: string) =>
    requestClient.post<{ added: number; stock: number }>(
      `/admin/mall/products/${id}/cards`,
      { codes },
    ),
  mallOrders: () =>
    requestClient.get<PageResult<MallOrderRow>>('/admin/mall/orders'),
  redeliverMallOrder: (orderNo: string) =>
    requestClient.post<MallOrderRow>(`/admin/mall/orders/${orderNo}/deliver`),
  refundMallOrder: (orderNo: string, note?: string) =>
    requestClient.post<MallOrderRow>(`/admin/mall/orders/${orderNo}/refund`, {
      note: note || undefined,
    }),
  mallAftersales: () =>
    requestClient.get<PageResult<MallAftersaleRow>>('/admin/mall/aftersales'),
  broadcastNotification: (body: {
    body: string;
    scope: 'all' | 'users';
    title: string;
    user_ids?: number[];
  }) =>
    requestClient.post<{ campaign_id: number; scope: string; sent: number }>(
      '/admin/notifications',
      body,
    ),
  broadcasts: () =>
    requestClient.get<PageResult<BroadcastRow>>('/admin/notifications'),
  plugins: () => requestClient.get<{ items: PluginRow[] }>('/admin/plugins'),
  lotteryDesk: () => requestClient.get<LotteryDesk>('/admin/plugins/lottery/desk'),
  syncPlugins: () =>
    requestClient.post<{ items: PluginRow[] }>('/admin/plugins/sync'),
  installPlugin: (file: File) => {
    const body = new FormData();
    body.append('package', file);
    return requestClient.post<PluginRow>('/admin/plugins/install', body);
  },
  enablePlugin: (slug: string) =>
    requestClient.post<PluginRow>(`/admin/plugins/${slug}/enable`),
  disablePlugin: (slug: string) =>
    requestClient.post<PluginRow>(`/admin/plugins/${slug}/disable`),
  savePluginConfig: (slug: string, config: Record<string, unknown>) =>
    requestClient.put<PluginRow>(`/admin/plugins/${slug}/config`, { config }),
};

export function formatTime(value: null | string | undefined) {
  if (!value) return '—';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;
  const pad = (part: number) => String(part).padStart(2, '0');
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())} ${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

export interface CommentRow {
  author: null | { nickname: string };
  content: string;
  created_at: null | string;
  id: number;
  post: null | { id: number; title: string };
  post_id: number;
  status: string;
}

export interface ReportRow {
  action: null | string;
  created_at: null | string;
  detail: null | string;
  id: number;
  note: null | string;
  reason_code: string;
  reason_label: string;
  reporter: null | { id: number; nickname: string };
  status: string;
  target_id: number;
  target_type: string;
}

export const reportStatusText: Record<string, string> = {
  pending: '待处理',
  rejected: '已驳回',
  resolved: '已处理',
};

export const reportTargetText: Record<string, string> = {
  app: '软件',
  comment: '评论',
  post: '笔记',
  user: '用户',
};

export const commentStatusText: Record<string, string> = {
  hidden: '已屏蔽',
  pending: '待审',
  published: '已公开',
  rejected: '已拒绝',
};

export const postStatusText: Record<string, string> = {
  pending: '待审',
  published: '已发布',
  rejected: '已拒绝',
};

export const appStatusText: Record<string, string> = {
  draft: '待审',
  offline: '已下架',
  published: '已上架',
  rejected: '已驳回',
};

export const linkTypeText: Record<string, string> = {
  app: '软件',
  board: '版块',
  category: '软件分类',
  checkin: '签到',
  mall: '虚拟商城',
  post: '帖子',
  url: '链接',
};

export const roleText: Record<string, string> = {
  board_admin: '审核员',
  member: '普通用户',
  platform_super_admin: '超级管理员',
  tenant_admin: '管理员',
};

export interface RoleRow {
  locked?: boolean;
  name: string;
  permissions: string[];
  scope?: string;
  slug: string;
  summary?: string;
  system?: boolean;
}

export interface PermissionItem {
  key: string;
  label: string;
}

export interface ClientRelease {
  changelog: string;
  download_url: null | string;
  force_update: boolean;
  latest_version: string;
  min_version: string;
  version_code: number;
  version_name: string;
}

export interface ServerReleaseCandidate {
  changelog: string;
  download_url: string;
  sha256: null | string;
  source: 'manual' | 'remote';
  version_code: number;
  version_name: string;
}

export interface ServerReleaseStatus {
  installed: { version_code: number; version_name: string };
  last_apply?: null | {
    applied_at?: null | string;
    applied_version?: null | string;
    duration_ms?: null | number;
    files_written?: null | number;
    last_error?: null | string;
    status?: string;
  };
  latest: null | ServerReleaseCandidate;
  manifest_error: null | string;
  manifest_url: string;
  manual: null | ServerReleaseCandidate;
  message?: string;
  remote: null | ServerReleaseCandidate;
  token_configured: boolean;
  update_available: boolean;
}

export interface ServerReleaseInput {
  changelog?: string;
  download_url: string;
  sha256: string;
  version_code: number;
  version_name: string;
}

export interface ServerApplyResult {
  files: number;
  message: string;
  ok: boolean;
  version_name: string;
}

export interface MallProductRow {
  fulfillment?: string;
  id: number;
  price: string;
  price_cents: number;
  status: string;
  stock: number;
  summary: null | string;
  title: string;
  type: string;
  unused_count?: number;
}

export interface MallProductInput {
  fulfillment?: string;
  price?: number;
  price_cents?: number;
  status?: string;
  summary?: string;
  title: string;
  type?: string;
}

export interface MallOrderRow {
  amount_cents: number;
  created_at?: null | string;
  delivered_payload: null | string;
  delivery_status?: string;
  fulfillment?: string;
  order_no: string;
  pay_channel?: null | string;
  pay_method?: null | string;
  payload_voided?: boolean;
  price: string;
  product: { title: string };
  refund_note?: null | string;
  status: string;
  trade_no?: null | string;
  user?: null | { id: number; nickname: string };
}

export interface MallAftersaleRow {
  created_at?: null | string;
  id: number;
  message?: string;
  order_no?: string;
  phase: number;
  reason: string;
  status: string;
  type: string;
}

export interface BroadcastRow {
  actor?: null | { id: number; nickname: string };
  body: string;
  created_at: null | string;
  id: number;
  scope: 'all' | 'users' | string;
  sent: number;
  title: string;
}

export interface PluginSetting {
  default?: boolean | number | string;
  key: string;
  label: string;
  max?: null | number;
  min?: null | number;
  required?: boolean;
  type: 'number' | 'switch' | 'text' | 'textarea';
}

export interface LotteryRecord {
  at: string;
  nickname: string;
  prize: string;
}

export interface LotteryDesk {
  daily_limit: number;
  enabled: boolean;
  name: string;
  prizes: string[];
  records: LotteryRecord[];
  slug: string;
  title: string;
  today_count: number;
}

export interface PluginRow {
  config: Record<string, unknown>;
  enabled: boolean;
  errors: string[];
  generated?: boolean;
  installed: boolean;
  name: string;
  settings: PluginSetting[];
  slug: string;
  summary: string;
  version: string;
}

export interface ClientReleaseInput {
  changelog?: string;
  download_url: string;
  force_update: boolean;
  version_code: number;
  version_name: string;
}
