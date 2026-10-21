<script setup lang="ts">
import { computed } from 'vue'
import { waterfall } from '../../utils/format'
import type { PostItem } from '../../utils/types'
import NoteCard from '../note-card/note-card.vue'

const props = defineProps<{
  items: PostItem[]
  loading: boolean
  error: string
  emptyTitle: string
  emptyText: string
  showStatus?: boolean
}>()

const emit = defineEmits<{ retry: []; open: [id: number] }>()
const columns = computed(() => waterfall(props.items))
</script>

<template>
  <view v-if="loading" class="skeleton">
    <view class="col">
      <view class="bone tall" />
      <view class="bone" />
    </view>
    <view class="col">
      <view class="bone" />
      <view class="bone tall" />
    </view>
  </view>
  <view v-else-if="error" class="state">
    <view class="mark">!</view>
    <text class="state-title">没读出来</text>
    <text class="state-text">{{ error }}</text>
    <button class="btn" @click="emit('retry')">重试</button>
  </view>
  <view v-else-if="items.length === 0" class="state">
    <view class="mark soft">记</view>
    <text class="state-title">{{ emptyTitle }}</text>
    <text class="state-text">{{ emptyText }}</text>
  </view>
  <view v-else class="cols">
    <view class="col">
      <note-card v-for="item in columns.left" :key="item.id" :post="item" :show-status="showStatus" @open="emit('open', $event)" />
    </view>
    <view class="col">
      <note-card v-for="item in columns.right" :key="item.id" :post="item" :show-status="showStatus" @open="emit('open', $event)" />
    </view>
  </view>
  <view v-if="!loading && !error && items.length" class="end">已经到底了</view>
</template>

<style scoped>
.skeleton,
.cols {
  display: flex;
  gap: 8px;
  align-items: flex-start;
  width: 96%;
  margin: 0 auto;
}

.col {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.bone {
  height: 210px;
  border-radius: 10px;
  background: linear-gradient(180deg, #ffffff, #eef2f6);
  border: 1px solid #f0f0f0;
}

.bone.tall {
  height: 252px;
}

.state {
  padding: 64px 24px 40px;
  text-align: center;
}

.mark {
  width: 64px;
  height: 64px;
  margin: 0 auto 12px;
  border-radius: 50%;
  background: #fff;
  border: 1px solid #f0f0f0;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
  color: #ff2442;
  font-size: 22px;
  font-weight: 700;
  line-height: 64px;
}

.mark.soft {
  color: #16160e;
  font-size: 18px;
}

.state-title {
  display: block;
  font-size: 16px;
  font-weight: 650;
  color: #16160e;
}

.state-text {
  display: block;
  margin-top: 6px;
  color: #909399;
  font-size: 13px;
  line-height: 1.5;
}

.btn {
  margin-top: 16px;
  width: 120px;
  background: #ff2442;
  color: #fff;
}

.end {
  padding: 16px 0 8px;
  text-align: center;
  color: #bfbfbf;
  font-size: 12px;
}
</style>
