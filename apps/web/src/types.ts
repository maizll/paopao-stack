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

export interface AuthUser {
  id: number
  nickname: string
  avatar: string | null
  roles: string[]
}

export interface Board {
  id: number
  name: string
  slug: string
  description: string | null
  post_count: number
}

export interface PostItem {
  id: number
  title: string
  excerpt: string
  author: { nickname: string } | null
  board: { id: number; name: string } | null
  created_at: string | null
}

export interface SoftwareItem {
  id: number
  name: string
  summary: string | null
  download_count: number
  category: { name: string } | null
  latest_version: { version_name: string } | null
}

export interface Banner {
  id: number
  title: string | null
  link_type: string
}

export interface GridItem {
  id: number
  title: string
  badge: string | null
}

export interface Page<T> {
  items: T[]
  meta: { page: number; per_page: number; total: number; has_more: boolean }
}
