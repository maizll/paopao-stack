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
  email: null | string;
  id: number;
  nickname: string;
  phone: null | string;
  role: string;
  status: string;
}

export interface BoardRow {
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
  status: string;
  summary: null | string;
}

export interface AppInput {
  category_id?: null | number;
  description?: null | string;
  icon?: null | string;
  name: string;
  summary?: null | string;
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

export interface UploadResult {
  path: string;
  url: string;
}

export const paopaoApi = {
  apps: (params: Record<string, number | string | undefined>) =>
    requestClient.get<PageResult<AppRow>>('/admin/apps', { params }),
  app: (id: number) => requestClient.get<AppRow>(`/admin/apps/${id}`),
  approveApp: (id: number) => requestClient.post<AppRow>(`/admin/apps/${id}/approve`),
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
  banUser: (id: number) => requestClient.post(`/admin/users/${id}/ban`),
  boards: () => requestClient.get<BoardRow[]>('/admin/boards'),
  clientVersion: () =>
    requestClient.get<{ latest_version: string; min_version: string }>(
      '/system/client-version',
    ),
  config: () =>
    requestClient.get<{
      register_enabled: boolean;
      site_name: string;
      tenant: null | { name: string; plan_code: string; slug: string };
    }>('/system/config'),
  createBoard: (body: { name: string; slug: string; status: string }) =>
    requestClient.post('/admin/boards', body),
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
  tenants: () => requestClient.get<PageResult<TenantRow>>('/admin/tenants'),
  unbanUser: (id: number) => requestClient.post(`/admin/users/${id}/unban`),
  users: (params: Record<string, number | string | undefined>) =>
    requestClient.get<PageResult<UserRow>>('/admin/users', { params }),
};

export function formatTime(value: null | string | undefined) {
  if (!value) return '—';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;
  const pad = (part: number) => String(part).padStart(2, '0');
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())} ${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

export const postStatusText: Record<string, string> = {
  pending: '待审',
  published: '已发布',
  rejected: '已拒绝',
};

export const appStatusText: Record<string, string> = {
  draft: '待审',
  published: '已发布',
  rejected: '已拒绝',
};

export const linkTypeText: Record<string, string> = {
  app: '软件',
  board: '版块',
  post: '帖子',
  url: '链接',
};

export const roleText: Record<string, string> = {
  member: '成员',
  platform_super_admin: '平台超管',
  tenant_admin: '租户管理员',
};
