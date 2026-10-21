import type { PostItem } from './types'

const covers = [
  '/static/covers/cover-notes.jpg',
  '/static/covers/cover-chat.jpg',
  '/static/covers/cover-desk.jpg',
  '/static/covers/cover-app.jpg',
  '/static/covers/cover-city.jpg',
  '/static/covers/cover-soft.jpg',
]

const heights = [196, 238, 168, 220, 184, 252]

export function formatTime(value: string | null | undefined): string {
  if (!value) return ''
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return value
  const pad = (part: number) => String(part).padStart(2, '0')
  return `${pad(date.getMonth() + 1)}-${pad(date.getDate())} ${pad(date.getHours())}:${pad(date.getMinutes())}`
}

export const postStatusText: Record<string, string> = {
  pending: '待审',
  published: '已发布',
  rejected: '未通过',
}

export function localCover(id: number): string {
  return covers[Math.abs(id) % covers.length]
}

export function noteCover(post: Pick<PostItem, 'id' | 'cover'>): string {
  const cover = post.cover || ''
  if (!cover || /example\.com|localhost|127\.0\.0\.1/i.test(cover)) return localCover(post.id)
  return cover
}

export function noteHeight(id: number): number {
  return heights[Math.abs(id) % heights.length]
}

export function waterfall<T extends { id: number }>(items: T[]): { left: T[]; right: T[] } {
  const left: T[] = []
  const right: T[] = []
  let leftHeight = 0
  let rightHeight = 0
  for (const item of items) {
    const block = noteHeight(item.id) + 96
    if (leftHeight <= rightHeight) {
      left.push(item)
      leftHeight += block
    } else {
      right.push(item)
      rightHeight += block
    }
  }
  return { left, right }
}
