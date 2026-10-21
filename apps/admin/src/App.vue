<script setup lang="ts">
import zhCN from 'ant-design-vue/es/locale/zh_CN'
</script>

<template>
  <a-config-provider
    :locale="zhCN"
    :theme="{
      token: {
        colorPrimary: '#1677ff',
        borderRadius: 6,
        fontFamily: `'PingFang SC', 'Noto Sans SC', 'Segoe UI', sans-serif`,
      },
    }"
  >
    <router-view />
  </a-config-provider>
</template>
