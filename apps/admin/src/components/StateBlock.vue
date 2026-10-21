<script setup lang="ts">
defineProps<{
  loading: boolean
  error: string
  empty: boolean
  emptyText: string
}>()
</script>

<template>
  <p v-if="loading" class="state">正在读取…</p>
  <p v-else-if="error" class="state error">{{ error }}</p>
  <p v-else-if="empty" class="state">{{ emptyText }}</p>
  <slot v-else />
</template>
