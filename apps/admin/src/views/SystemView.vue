<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { api, ApiError, tenantSlug } from '../api'
import type { ClientVersion, Health, SiteConfig } from '../types'

const loading = ref(true)
const error = ref('')
const health = ref<Health | null>(null)
const config = ref<SiteConfig | null>(null)
const client = ref<ClientVersion | null>(null)

onMounted(async () => {
  try {
    const [healthRes, configRes, clientRes] = await Promise.all([
      api<Health>('/api/v1/system/health'),
      api<SiteConfig>('/api/v1/system/config'),
      api<ClientVersion>('/api/v1/system/client-version'),
    ])
    health.value = healthRes.data
    config.value = configRes.data
    client.value = clientRes.data
  } catch (cause) {
    error.value = cause instanceof ApiError ? cause.message : '读取失败'
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div class="page-head">
    <div>
      <h1>系统</h1>
      <p>健康检查和当前租户配置。商城、支付、私信、短视频仍留在二期。</p>
    </div>
  </div>
  <a-spin :spinning="loading">
    <a-alert v-if="error" type="error" :message="error" show-icon />
    <div v-else class="stack">
      <a-card title="运行状态">
        <a-descriptions bordered :column="1" size="small">
          <a-descriptions-item label="应用">{{ health?.app }} {{ health?.version }}</a-descriptions-item>
          <a-descriptions-item label="接口">{{ health?.status === 'ok' ? '正常' : health?.status }}</a-descriptions-item>
          <a-descriptions-item label="数据库">{{ health?.database === 'ok' ? '可连接' : '异常' }}</a-descriptions-item>
          <a-descriptions-item label="站点名">{{ config?.site_name }}</a-descriptions-item>
          <a-descriptions-item label="请求租户">{{ tenantSlug() || '未指定（平台视角）' }}</a-descriptions-item>
          <a-descriptions-item label="解析到的租户">
            {{ config?.tenant ? `${config.tenant.name}（${config.tenant.slug} / ${config.tenant.plan_code}）` : '无' }}
          </a-descriptions-item>
          <a-descriptions-item label="开放注册">{{ config?.register_enabled ? '是' : '否' }}</a-descriptions-item>
          <a-descriptions-item label="客户端版本">
            {{ client?.latest_version }}（最低 {{ client?.min_version }}）
          </a-descriptions-item>
        </a-descriptions>
      </a-card>
      <a-card title="还没做的部分">
        <ul>
          <li>商城、支付、私信、短视频</li>
          <li>评论、赞藏、下载签发、举报工单、站内通知</li>
          <li>软件上下架写入、运营位写入、头像上传</li>
        </ul>
      </a-card>
    </div>
  </a-spin>
</template>
