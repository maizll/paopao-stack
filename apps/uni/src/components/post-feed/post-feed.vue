<script setup lang="ts">
import { computed } from 'vue'
import { splitColumns } from '../../utils/api'
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

const columns = computed(() => splitColumns(props.items))

function open(id: number): void {
  emit('open', id)
}
</script>

<template>
  <view v-if="loading" class="skeleton">
    <view class="bone tall" />
    <view class="bone" />
    <view class="bone" />
    <view class="bone tall" />
  </view>
  <view v-else-if="error" class="state">
    <text class="state-title">没读出来</text>
    <text class="state-text">{{ error }}</text>
    <button class="btn" @click="emit('retry')">重试</button>
  </view>
  <view v-else-if="items.length === 0" class="state">
    <text class="state-title">{{ emptyTitle }}</text>
    <text class="state-text">{{ emptyText }}</text>
  </view>
  <view v-else class="cols">
    <view class="col">
      <note-card v-for="item in columns.left" :key="item.id" :post="item" :show-status="showStatus" @open="open" />
    </view>
    <view class="col">
      <note-card v-for="item in columns.right" :key="item.id" :post="item" :show-status="showStatus" @open="open" />
    </view>
  </view>
</template>

<style scoped>
.skeleton {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
}

.bone {
  height: 210px;
  border-radius: 16px;
  background: linear-gradient(180deg, #efe8e2, #e7dfd8);
}

.bone.tall {
  height: 250px;
}

.state {
  padding: 56px 12px;
  text-align: center;
}

.state-title {
  display: block;
  font-size: 16px;
  font-weight: 650;
}

.state-text {
  display: block;
  margin-top: 6px;
  color: #6f675f;
  font-size: 13px;
  line-height: 1.5;
}

.btn {
  margin-top: 16px;
  background: #ff5c39;
  color: #fff;
  border-radius: 999px;
  font-size: 15px;
  line-height: 40px;
  height: 40px;
  border: none;
}

.btn::after {
  border: none;
}

.cols {
  display: flex;
  gap: 10px;
  align-items: flex-start;
}

.col {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 10px;
}
</style>
