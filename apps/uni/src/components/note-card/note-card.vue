<script setup lang="ts">
import { computed } from 'vue'
import type { PostItem } from '../../utils/types'

const props = defineProps<{ post: PostItem; showStatus?: boolean }>()
const emit = defineEmits<{ open: [id: number] }>()

const palettes = ['#ff8a65', '#f4a261', '#e07a5f', '#7c6cff', '#f2b6c6', '#2f2a28', '#e8c547']

const color = computed(() => palettes[props.post.id % palettes.length])
const tall = computed(() => props.post.id % 3 === 0)
const board = computed(() => props.post.board?.name || '笔记')
const likes = computed(() => props.post.stats?.like ?? 0)
</script>

<template>
  <view class="card" @click="emit('open', post.id)">
    <image v-if="post.cover" class="photo" :class="{ tall }" :src="post.cover" mode="aspectFill" />
    <view v-else class="cover" :class="{ tall }" :style="{ background: color }">
      <text class="cover-label">{{ board }}</text>
    </view>
    <view class="body">
      <text class="title">{{ post.title }}</text>
      <view class="meta">
        <text class="author">{{ post.author?.nickname || '匿名' }}</text>
        <view class="like">
          <uni-icons type="heart" size="14" color="#6f675f" />
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
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 8px 24px rgba(40, 24, 16, 0.06);
}

.cover,
.photo {
  height: 148px;
  width: 100%;
}

.cover.tall,
.photo.tall {
  height: 196px;
}

.cover {
  display: flex;
  align-items: flex-end;
  padding: 12px;
  box-sizing: border-box;
}

.cover-label {
  color: #fff;
  font-size: 13px;
  font-weight: 650;
}

.body {
  padding: 10px 12px 12px;
}

.title {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  font-size: 14px;
  font-weight: 650;
  line-height: 1.4;
  color: #1f1a17;
}

.meta {
  margin-top: 8px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
}

.author,
.like {
  color: #6f675f;
  font-size: 12px;
}

.like {
  display: flex;
  align-items: center;
  gap: 3px;
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
