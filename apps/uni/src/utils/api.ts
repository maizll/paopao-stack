import { API_BASE_URL } from '../config'
import type { Page } from './types'

interface Envelope<T> {
  code: number
  message: string
  data: T
}

export class ApiError extends Error {
  code: number

  constructor(message: string, code: number) {
    super(message)
    this.code = code
  }
}

const TENANT_KEY = 'paopao.uni.tenant'
const TOKEN_KEY = 'paopao.uni.token'
const LOOPBACK = /^https?:\/\/(?:127\.0\.0\.1|localhost)(?::\d+)?(?=\/|$)/i

/** 生产包使用 config.ts 的公网地址；H5 开发留空，请求走当前站点的 /api 代理。 */
export function apiOrigin(): string {
  // #ifdef H5
  if (import.meta.env.DEV) return ''
  // #endif
  return API_BASE_URL.trim().replace(/\/$/, '')
}

function apiUrl(path: string): string {
  return `${apiOrigin()}${path}`
}

function rewriteMediaValue(value: string): string {
  const origin = apiOrigin()
  if (!origin) return value
  if (value.startsWith('/storage/')) return `${origin}${value}`
  return value.replace(LOOPBACK, origin)
}

function rewriteMedia<T>(value: T): T {
  if (!apiOrigin()) return value
  if (typeof value === 'string') return rewriteMediaValue(value) as T
  if (Array.isArray(value)) return value.map((item) => rewriteMedia(item)) as T
  if (value && typeof value === 'object') {
    const next: Record<string, unknown> = {}
    for (const [key, item] of Object.entries(value)) next[key] = rewriteMedia(item)
    return next as T
  }
  return value
}

type Query = Record<string, string | number | undefined>

export function tenantSlug(): string {
  return uni.getStorageSync(TENANT_KEY) || 'demo'
}

export function setTenantSlug(slug: string): void {
  uni.setStorageSync(TENANT_KEY, slug.trim())
}

export function token(): string {
  return uni.getStorageSync(TOKEN_KEY) || ''
}

export function setToken(value: string): void {
  if (value) uni.setStorageSync(TOKEN_KEY, value)
  else uni.removeStorageSync(TOKEN_KEY)
}

export function request<T>(
  path: string,
  options: { method?: 'GET' | 'POST' | 'PATCH' | 'DELETE'; data?: Query } = {},
): Promise<T> {
  const method = options.method || 'GET'
  const header: Record<string, string> = { Accept: 'application/json' }
  const slug = tenantSlug()
  if (slug) header['X-Tenant-Id'] = slug
  const auth = token()
  if (auth) header.Authorization = `Bearer ${auth}`
  if (options.data && method !== 'GET') header['Content-Type'] = 'application/json'

  const data: Record<string, string | number> = {}
  if (options.data) {
    for (const [key, value] of Object.entries(options.data)) {
      if (value !== undefined && value !== '') data[key] = value
    }
  }

  return new Promise((resolve, reject) => {
    uni.request({
      url: apiUrl(`/api/v1${path}`),
      method,
      data,
      header,
      success(res) {
        const body = res.data as Envelope<T>
        if (res.statusCode >= 200 && res.statusCode < 300 && body && body.code === 0) {
          resolve(rewriteMedia(body.data))
          return
        }
        reject(new ApiError(body?.message || '请求失败', body?.code || res.statusCode))
      },
      fail() {
        reject(new ApiError('连不上 API。请确认公网接口或本机 43125 端口仍在运行。', 50001))
      },
    })
  })
}

export function uploadImage(filePath: string): Promise<{ path: string; url: string }> {
  return new Promise((resolve, reject) => {
    const header: Record<string, string> = { Accept: 'application/json' }
    const slug = tenantSlug()
    if (slug) header['X-Tenant-Id'] = slug
    const auth = token()
    if (auth) header.Authorization = `Bearer ${auth}`
    uni.uploadFile({
      url: apiUrl('/api/v1/uploads'),
      filePath,
      name: 'file',
      header,
      success(res) {
        let body: Envelope<{ path: string; url: string }> | null = null
        try {
          body = JSON.parse(res.data) as Envelope<{ path: string; url: string }>
        } catch {
          body = null
        }
        if (res.statusCode >= 200 && res.statusCode < 300 && body && body.code === 0) {
          resolve(rewriteMedia(body.data))
          return
        }
        reject(new ApiError(body?.message || '上传失败', body?.code || res.statusCode))
      },
      fail() {
        reject(new ApiError('图片没有传上去。请确认公网接口或本机 43125 端口仍在运行。', 50001))
      },
    })
  })
}

export function splitColumns<T>(items: T[]): { left: T[]; right: T[] } {
  const left: T[] = []
  const right: T[] = []
  items.forEach((item, index) => {
    if (index % 2 === 0) left.push(item)
    else right.push(item)
  })
  return { left, right }
}

export type { Page }
