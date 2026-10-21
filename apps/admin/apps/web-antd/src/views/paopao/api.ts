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
  category: null | { name: string };
  download_count: number;
  id: number;
  latest_version: null | { version_name: string };
  name: string;
  status: string;
  summary: null | string;
}

export interface BannerRow {
  id: number;
  link_type: string;
  sort: number;
  title: null | string;
}

export interface GridRow {
  badge: null | string;
  id: number;
  sort: number;
  title: string;
}

export const paopaoApi = {
  apps: (params: Record<string, number | string | undefined>) =>
    requestClient.get<PageResult<AppRow>>('/admin/apps', { params }),
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

export const roleText: Record<string, string> = {
  member: '成员',
  platform_super_admin: '平台超管',
  tenant_admin: '租户管理员',
};
