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
      url: `/api/v1${path}`,
      method,
      data,
      header,
      success(res) {
        const body = res.data as Envelope<T>
        if (res.statusCode >= 200 && res.statusCode < 300 && body && body.code === 0) {
          resolve(body.data)
          return
        }
        reject(new ApiError(body?.message || '请求失败', body?.code || res.statusCode))
      },
      fail() {
        reject(new ApiError('连不上 API。请确认后端正在 43125 端口运行。', 50001))
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
      url: '/api/v1/uploads',
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
          resolve(body.data)
          return
        }
        reject(new ApiError(body?.message || '上传失败', body?.code || res.statusCode))
      },
      fail() {
        reject(new ApiError('图片没有传上去。请确认后端正在 43125 端口运行。', 50001))
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
