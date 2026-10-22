<script lang="ts" setup>
import { onMounted, ref } from 'vue';

import { Page } from '@vben/common-ui';

import { Alert, Card, Descriptions, DescriptionsItem } from 'ant-design-vue';

import { paopaoApi } from '../api';

const error = ref('');
const health = ref<Awaited<ReturnType<typeof paopaoApi.health>> | null>(null);
const config = ref<Awaited<ReturnType<typeof paopaoApi.config>> | null>(null);
const client = ref<Awaited<ReturnType<typeof paopaoApi.clientVersion>> | null>(
  null,
);

onMounted(async () => {
  try {
    const [healthRes, configRes, clientRes] = await Promise.all([
      paopaoApi.health(),
      paopaoApi.config(),
      paopaoApi.clientVersion(),
    ]);
    health.value = healthRes;
    config.value = configRes;
    client.value = clientRes;
  } catch (cause) {
    error.value = cause instanceof Error ? cause.message : '读取失败';
  }
});
</script>

<template>
  <Page description="健康检查和当前租户配置。商城、支付、私信、短视频仍留在二期。" title="系统">
    <Alert v-if="error" :message="error" class="mb-4" show-icon type="error" />
    <div v-else class="grid gap-5 lg:grid-cols-2">
      <Card title="运行状态">
        <Descriptions :column="1" bordered size="small">
          <DescriptionsItem label="应用">{{ health?.app }} {{ health?.version }}</DescriptionsItem>
          <DescriptionsItem label="接口">{{ health?.status === 'ok' ? '正常' : health?.status }}</DescriptionsItem>
          <DescriptionsItem label="数据库">{{ health?.database === 'ok' ? '可连接' : '异常' }}</DescriptionsItem>
          <DescriptionsItem label="站点名">{{ config?.site_name }}</DescriptionsItem>
          <DescriptionsItem label="租户">
            {{ config?.tenant ? `${config.tenant.name}（${config.tenant.slug} / ${config.tenant.plan_code}）` : '无' }}
          </DescriptionsItem>
          <DescriptionsItem label="开放注册">{{ config?.register_enabled ? '是' : '否' }}</DescriptionsItem>
          <DescriptionsItem label="客户端版本">
            {{ client?.latest_version }}（最低 {{ client?.min_version }}）
          </DescriptionsItem>
        </Descriptions>
      </Card>
      <Card title="还没做的部分">
        <ul class="list-disc space-y-2 pl-5 leading-7">
          <li>商城、支付、私信、短视频</li>
          <li>下载签发、举报工单、关注</li>
          <li>头像专用上传</li>
        </ul>
        <p class="mt-4 text-sm text-black/45">
          后台骨架来自 Vue Vben Admin 5.7.0 的 @vben/web-antd。
        </p>
      </Card>
    </div>
  </Page>
</template>
