export interface ApiEnvelope<T> {
  code: number
  message: string
  data: T
}

export class ApiError extends Error {
  code: number
  status: number

  constructor(message: string, code: number, status: number) {
    super(message)
    this.code = code
    this.status = status
  }
}

const TENANT_KEY = 'paopao.admin.tenant'
const TOKEN_KEY = 'paopao.admin.token'

export function tenantSlug(): string {
  return localStorage.getItem(TENANT_KEY) ?? 'demo'
}

export function setTenantSlug(slug: string): void {
  localStorage.setItem(TENANT_KEY, slug.trim())
}

export function token(): string | null {
  return localStorage.getItem(TOKEN_KEY)
}

export function setToken(value: string | null): void {
  if (value) {
    localStorage.setItem(TOKEN_KEY, value)
  } else {
    localStorage.removeItem(TOKEN_KEY)
  }
}

export async function api<T>(
  path: string,
  options: RequestInit = {},
  withTenant = true,
): Promise<ApiEnvelope<T>> {
  const headers = new Headers(options.headers)
  headers.set('Accept', 'application/json')
  if (options.body && !headers.has('Content-Type')) {
    headers.set('Content-Type', 'application/json')
  }

  const slug = tenantSlug()
  if (withTenant && slug) {
    headers.set('X-Tenant-Id', slug)
  }

  const auth = token()
  if (auth) {
    headers.set('Authorization', `Bearer ${auth}`)
  }

  let response: Response
  try {
    response = await fetch(path, { ...options, headers })
  } catch {
    throw new ApiError('连不上 API。请确认后端正在 43125 端口运行。', 50001, 0)
  }

  const text = await response.text()
  let payload: ApiEnvelope<T>
  try {
    payload = JSON.parse(text) as ApiEnvelope<T>
  } catch {
    throw new ApiError('接口没有返回 JSON', 50001, response.status)
  }

  if (!response.ok || payload.code !== 0) {
    throw new ApiError(payload.message || '请求失败', payload.code, response.status)
  }

  return payload
}
