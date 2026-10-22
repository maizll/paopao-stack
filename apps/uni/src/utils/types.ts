export interface SiteConfig {
  site_name: string
  tenant: { id: number; name: string; slug: string } | null
}

export interface AuthUser {
  id: number
  nickname: string
  avatar: string | null
  roles: string[]
  status?: string
}

export interface AuthResult {
  access_token: string
  token_type: string
  expires_in: number
  user: AuthUser
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
  cover: string | null
  content?: string
  author: { id?: number; nickname: string } | null
  board: { id: number; name: string } | null
  stats?: { view: number; like: number; comment: number; favorite: number }
  status?: string
  created_at: string | null
}

export interface SoftwareItem {
  id: number
  name: string
  icon: string | null
  summary: string | null
  download_count: number
  category: { id: number; name: string } | null
  latest_version: { version_name: string } | null
}

export interface Banner {
  id: number
  title: string | null
  image: string
  link_type: string
  link_value: string | null
}

export interface GridItem {
  id: number
  title: string
  icon: string | null
  link_type: string
  link_value: string | null
  badge: string | null
}

export interface Category {
  id: number
  name: string
}

export interface Page<T> {
  items: T[]
  meta: { page: number; per_page: number; total: number; has_more: boolean }
}
