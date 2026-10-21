export interface Me {
  id: number
  nickname: string
  roles: string[]
  permissions: string[]
  tenant_id: number | null
  status: string
}

export interface Health {
  status: string
  app: string
  version: string
  database: string
}

export interface SiteConfig {
  site_name: string
  register_enabled: boolean
  tenant: { id: number; name: string; slug: string; plan_code: string } | null
}

export interface ClientVersion {
  platform: string
  min_version: string
  latest_version: string
  force_update: boolean
}

export interface TenantItem {
  id: number
  name: string
  slug: string
  status: string
  plan_code: string
}

export interface BoardItem {
  id: number
  name: string
  slug: string
  description: string | null
  post_count: number
  status: string
}

export interface PostItem {
  id: number
  title: string
  excerpt?: string
  status: string
  author: { nickname: string } | null
  board: { name: string } | null
  created_at: string | null
  stats?: { view: number; like: number; comment: number; favorite: number }
}

export interface PostDetail extends PostItem {
  content: string
}

export interface AdminUser {
  id: number
  nickname: string
  email: string | null
  phone: string | null
  role: string
  status: string
}

export interface SoftwareItem {
  id: number
  name: string
  summary: string | null
  status: string
  download_count: number
  category: { name: string } | null
  latest_version: { version_name: string } | null
}

export interface BannerItem {
  id: number
  title: string | null
  link_type: string
  sort: number
}

export interface GridItem {
  id: number
  title: string
  badge: string | null
  sort: number
}

export interface Page<T> {
  items: T[]
  meta: { page: number; per_page: number; total: number; has_more: boolean }
}
