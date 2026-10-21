<script setup lang="ts">
import { computed, ref } from 'vue'
import { localCover, noteCover, noteHeight, postStatusText } from '../../utils/format'
import type { PostItem } from '../../utils/types'

const props = defineProps<{ post: PostItem; showStatus?: boolean }>()
const emit = defineEmits<{ open: [id: number] }>()

const broken = ref(false)
const cover = computed(() => (broken.value ? localCover(props.post.id) : noteCover(props.post)))
const photoHeight = computed(() => `${noteHeight(props.post.id)}px`)
const author = computed(() => props.post.author?.nickname || '匿名')
const initial = computed(() => author.value.slice(0, 1))
const likes = computed(() => props.post.stats?.like ?? 0)
const liked = computed(() => likes.value > 0)
const statusLabel = computed(() => {
  if (!props.showStatus || !props.post.status || props.post.status === 'published') return ''
  return postStatusText[props.post.status] || props.post.status
})

function fail(): void {
  broken.value = true
}
</script>

<!--
  卡片几何对齐 HongShu-Ui（MIT）hongshu-app/components/hs-note-card：
  白底、20rpx 圆角、细边、轻阴影、双行标题、头像昵称、心形赞。
  未引入 thorui-uni，心形用 uni-icons。
-->
<template>
  <view class="card" @click="emit('open', post.id)">
    <view class="photo-wrap">
      <image class="photo" :src="cover" mode="aspectFill" :style="{ height: photoHeight }" @error="fail" />
      <text v-if="statusLabel" class="flag">{{ statusLabel }}</text>
    </view>
    <text class="title">{{ post.title }}</text>
    <view class="meta">
      <view class="user">
        <view class="avatar">{{ initial }}</view>
        <text class="name">{{ author }}</text>
      </view>
      <view class="like">
        <uni-icons :type="liked ? 'heart-filled' : 'heart'" size="14" :color="liked ? '#ff2442' : '#909399'" />
        <text class="like-num" :class="{ hot: liked }">{{ likes > 0 ? likes : '赞' }}</text>
      </view>
    </view>
  </view>
</template>

<style scoped>
.card {
  background: #fff;
  border-radius: 10px;
  border: 1px solid #f0f0f0;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  overflow: hidden;
}

.photo-wrap {
  position: relative;
  background: #f5f5f5;
}

.photo {
  display: block;
  width: 100%;
  max-height: 250px;
}

.flag {
  position: absolute;
  left: 8px;
  top: 8px;
  padding: 2px 8px;
  border-radius: 999px;
  background: rgba(22, 22, 14, 0.62);
  color: #fff;
  font-size: 11px;
  line-height: 16px;
}

.title {
  display: -webkit-box;
  padding: 8px 8px 0;
  font-size: 15px;
  color: #16160e;
  line-height: 1.4;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
  overflow: hidden;
}

.meta {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 8px;
  gap: 6px;
}

.user {
  display: flex;
  align-items: center;
  min-width: 0;
  flex: 1;
}

.avatar {
  width: 22px;
  height: 22px;
  border-radius: 50%;
  background: #f3f4f6;
  color: #16160e;
  font-size: 11px;
  font-weight: 650;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.name {
  margin-left: 4px;
  font-size: 12px;
  color: #16160e;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}

.like {
  display: flex;
  align-items: center;
  flex-shrink: 0;
}

.like-num {
  margin-left: 2px;
  font-size: 11px;
  color: #909399;
}

.like-num.hot {
  color: #ff2442;
}
</style>
