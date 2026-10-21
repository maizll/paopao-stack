<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { api, ApiError, tenantSlug } from '../api'
import type { Health, Me, SiteConfig } from '../types'

const loading = ref(true)
const error = ref('')
const health = ref<Health | null>(null)
const config = ref<SiteConfig | null>(null)
const me = ref<Me | null>(null)

onMounted(async () => {
  try {
    const [healthRes, configRes, meRes] = await Promise.all([
      api<Health>('/api/v1/system/health'),
      api<SiteConfig>('/api/v1/system/config'),
      api<Me>('/api/v1/auth/me'),
    ])
    health.value = healthRes.data
    config.value = configRes.data
    me.value = meRes.data
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : '读取失败'
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <section class="panel">
    <h2>概览</h2>
    <p v-if="loading" class="state">正在读取…</p>
    <p v-else-if="error" class="state error">{{ error }}</p>
    <div v-else class="stats">
      <div class="stat">
        <span>服务</span>
        <b>{{ health?.app }} {{ health?.version }}</b>
      </div>
      <div class="stat">
        <span>数据库</span>
        <b>{{ health?.database === 'ok' ? '可连接' : '异常' }}</b>
      </div>
      <div class="stat">
        <span>当前租户</span>
        <b>{{ config?.tenant?.name || (tenantSlug() ? tenantSlug() : '平台视角') }}</b>
      </div>
    </div>
    <p v-if="me" class="state">登录身份：{{ me.nickname }}。商城、支付、私信、短视频仍是二期。</p>
  </section>
</template>
