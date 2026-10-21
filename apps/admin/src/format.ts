export function formatTime(value: string | null | undefined): string {
  if (!value) return '—'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return value
  const pad = (part: number) => String(part).padStart(2, '0')
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())} ${pad(date.getHours())}:${pad(date.getMinutes())}`
}

export const postStatusText: Record<string, string> = {
  pending: '待审',
  published: '已发布',
  rejected: '已拒绝',
}

export const postStatusColor: Record<string, string> = {
  pending: 'gold',
  published: 'green',
  rejected: 'red',
}

export const roleText: Record<string, string> = {
  platform_super_admin: '平台超管',
  tenant_admin: '租户管理员',
  member: '成员',
}

export const userStatusText: Record<string, string> = {
  active: '正常',
  banned: '已封禁',
}

export const publishStatusText: Record<string, string> = {
  published: '已发布',
  hidden: '已隐藏',
  pending: '待审',
  active: '正常',
  suspended: '已停用',
}
