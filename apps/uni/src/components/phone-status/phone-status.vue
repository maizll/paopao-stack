<script setup lang="ts">
import { onMounted, ref } from 'vue'

withDefaults(defineProps<{ tone?: 'dark' | 'light' }>(), { tone: 'dark' })

const info = uni.getSystemInfoSync()
const native = info.statusBarHeight || 0
const faux = native < 12
const barHeight = faux ? 28 : native
const time = ref('9:41')

onMounted(() => {
  const now = new Date()
  time.value = `${now.getHours()}:${String(now.getMinutes()).padStart(2, '0')}`
})
</script>

<template>
  <view class="status" :class="tone" :style="{ height: barHeight + 'px' }">
    <template v-if="faux">
      <text class="time">{{ time }}</text>
      <view class="signals">
        <view class="bars">
          <view class="bar" />
          <view class="bar" />
          <view class="bar" />
          <view class="bar" />
        </view>
        <view class="wifi" />
        <view class="battery"><view class="level" /></view>
      </view>
    </template>
  </view>
</template>

<style scoped>
.status {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 16px;
  box-sizing: border-box;
}

.time {
  font-size: 14px;
  font-weight: 650;
  letter-spacing: 0.02em;
}

.dark .time {
  color: #16160e;
}

.light .time {
  color: #fff;
}

.signals {
  display: flex;
  align-items: center;
  gap: 6px;
}

.bars {
  display: flex;
  align-items: flex-end;
  gap: 1px;
  height: 10px;
}

.bar {
  width: 3px;
  border-radius: 1px;
  background: currentColor;
}

.dark .bars {
  color: #16160e;
}

.light .bars {
  color: #fff;
}

.bar:nth-child(1) {
  height: 3px;
}
.bar:nth-child(2) {
  height: 5px;
}
.bar:nth-child(3) {
  height: 7px;
}
.bar:nth-child(4) {
  height: 10px;
}

.wifi {
  width: 12px;
  height: 9px;
  border: 2px solid transparent;
  border-top-color: currentColor;
  border-radius: 12px 12px 0 0;
  position: relative;
}

.dark .wifi,
.dark .battery {
  color: #16160e;
}

.light .wifi,
.light .battery {
  color: #fff;
}

.wifi::after {
  content: '';
  position: absolute;
  left: 3px;
  bottom: -1px;
  width: 4px;
  height: 4px;
  border-radius: 50%;
  background: currentColor;
}

.battery {
  width: 22px;
  height: 10px;
  border: 1.5px solid currentColor;
  border-radius: 2px;
  padding: 1px;
  box-sizing: border-box;
  position: relative;
}

.battery::after {
  content: '';
  position: absolute;
  right: -3px;
  top: 2px;
  width: 2px;
  height: 4px;
  border-radius: 0 1px 1px 0;
  background: currentColor;
}

.level {
  width: 70%;
  height: 100%;
  border-radius: 1px;
  background: currentColor;
}
</style>
