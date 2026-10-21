<script lang="ts" setup>
import type { VxeTableGridOptions } from '#/adapter/vxe-table';

import { Page } from '@vben/common-ui';

import { Alert, Tag } from 'ant-design-vue';

import { useVbenVxeGrid } from '#/adapter/vxe-table';

import { paopaoApi, type AppRow } from '../api';

const gridOptions: VxeTableGridOptions<AppRow> = {
  columns: [
    { field: 'name', minWidth: 160, title: '名称' },
    { field: 'category', slots: { default: 'category' }, title: '分类', width: 140 },
    { field: 'version', slots: { default: 'version' }, title: '版本', width: 120 },
    { field: 'download_count', title: '下载', width: 100 },
    { field: 'status', slots: { default: 'status' }, title: '状态', width: 120 },
  ],
  height: 'auto',
  pagerConfig: {},
  proxyConfig: {
    ajax: {
      query: async ({ page }) => {
        const result = await paopaoApi.apps({
          page: page.currentPage,
          per_page: page.pageSize,
        });
        return { items: result.items, total: result.meta.total };
      },
    },
  },
  rowConfig: { keyField: 'id' },
  toolbarConfig: { refresh: true, zoom: true },
};

const [Grid] = useVbenVxeGrid({ gridOptions });
</script>

<template>
  <Page auto-content-height description="当前租户已登记的软件。创建、上下架和版本发布还是骨架占位，本页只读。" title="软件审核">
    <Alert
      class="mb-4"
      message="写入接口返回「骨架占位」。审核列表可以看，不能在这里上架新包。"
      show-icon
      type="info"
    />
    <Grid>
      <template #category="{ row }">{{ row.category?.name || '—' }}</template>
      <template #version="{ row }">{{ row.latest_version?.version_name || '—' }}</template>
      <template #status="{ row }">
        <Tag>{{ row.status === 'published' ? '已发布' : row.status }}</Tag>
      </template>
    </Grid>
  </Page>
</template>
