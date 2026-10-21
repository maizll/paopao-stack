<script setup lang="ts">
import { computed } from 'vue'
import type { PostItem } from '../../utils/types'

const props = defineProps<{ post: PostItem; showStatus?: boolean }>()
const emit = defineEmits<{ open: [id: number] }>()

const covers = [
  '/static/covers/cover-notes.jpg',
  '/static/covers/cover-chat.jpg',
  '/static/covers/cover-desk.jpg',
  '/static/covers/cover-app.jpg',
  '/static/covers/cover-city.jpg',
  '/static/covers/cover-soft.jpg',
]
const heights = [214, 168, 246, 188, 228, 176]

const avatarColors = ['#ff5c39', '#2f2a28', '#7c6cff', '#e07a5f', '#c2410c', '#3d6b8c']

const cover = computed(() => props.post.cover || covers[props.post.id % covers.length])
const photoHeight = computed(() => `${heights[props.post.id % heights.length]}px`)
const author = computed(() => props.post.author?.nickname || '匿名')
const initial = computed(() => author.value.slice(0, 1))
const avatarColor = computed(() => avatarColors[props.post.id % avatarColors.length])
const likes = computed(() => props.post.stats?.like ?? 0)
const boardName = computed(() => props.post.board?.name || '')
</script>

<template>
  <view class="card" @click="emit('open', post.id)">
    <view class="photo-wrap">
      <image class="photo" :src="cover" mode="aspectFill" :style="{ height: photoHeight }" />
      <text v-if="boardName" class="board">{{ boardName }}</text>
    </view>
    <view class="body">
      <text class="title">{{ post.title }}</text>
      <view class="meta">
        <view class="who">
          <view class="avatar" :style="{ background: avatarColor }">{{ initial }}</view>
          <text class="name">{{ author }}</text>
        </view>
        <view class="like">
          <uni-icons type="heart-filled" size="14" color="#ff5c39" />
          <text>{{ likes }}</text>
        </view>
      </view>
      <text v-if="showStatus && post.status && post.status !== 'published'" class="flag">
        {{ post.status === 'pending' ? '待审' : '未通过' }}
      </text>
    </view>
  </view>
</template>

<style scoped>
.card {
  background: #fff;
  border-radius: 12px;
  overflow: hidden;
  box-shadow: 0 8px 18px rgba(47, 32, 24, 0.06);
}

.photo-wrap {
  position: relative;
}

.photo {
  width: 100%;
  display: block;
  background: #efe8e2;
}

.board {
  position: absolute;
  left: 8px;
  bottom: 8px;
  max-width: calc(100% - 16px);
  padding: 2px 8px;
  border-radius: 999px;
  background: rgba(20, 12, 8, 0.55);
  color: #fff;
  font-size: 11px;
  line-height: 16px;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.body {
  padding: 8px 10px 10px;
}

.title {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  font-size: 14px;
  font-weight: 650;
  line-height: 1.35;
  color: #1f1a17;
  min-height: 38px;
}

.meta {
  margin-top: 8px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}

.who {
  display: flex;
  align-items: center;
  gap: 6px;
  min-width: 0;
}

.avatar {
  width: 20px;
  height: 20px;
  border-radius: 50%;
  color: #fff;
  font-size: 11px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  flex: none;
}

.name,
.like {
  color: #6f675f;
  font-size: 12px;
}

.name {
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.like {
  display: flex;
  align-items: center;
  gap: 3px;
  flex: none;
}

.flag {
  display: inline-block;
  margin-top: 8px;
  padding: 2px 8px;
  border-radius: 999px;
  background: #fff1ec;
  color: #c2410c;
  font-size: 12px;
}
</style>
