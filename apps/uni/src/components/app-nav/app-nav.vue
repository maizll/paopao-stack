<script setup lang="ts">
withDefaults(defineProps<{ title?: string; tone?: 'dark' | 'light'; solid?: boolean }>(), {
  tone: 'dark',
  solid: true,
})
</script>

<template>
  <view class="wrap" :class="[tone, { solid }]">
    <phone-status :tone="tone" />
    <view class="nav">
      <view class="side">
        <slot name="left" />
      </view>
      <view class="center">
        <slot>
          <text v-if="title" class="title">{{ title }}</text>
        </slot>
      </view>
      <view class="side right">
        <slot name="right" />
      </view>
    </view>
  </view>
</template>

<style scoped>
.wrap.solid {
  background: #fff;
}

.nav {
  height: 44px;
  display: flex;
  align-items: center;
  padding: 0 8px;
}

.side {
  min-width: 56px;
  display: flex;
  align-items: center;
}

.side.right {
  justify-content: flex-end;
}

.center {
  flex: 1;
  min-width: 0;
  display: flex;
  align-items: center;
  justify-content: center;
}

.title {
  font-size: 17px;
  font-weight: 650;
  color: #16160e;
}

.light .title {
  color: #fff;
}
</style>
