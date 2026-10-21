<script lang="ts" setup>
import { onMounted, ref } from 'vue';

import { Page } from '@vben/common-ui';

import { Alert, Card, Table } from 'ant-design-vue';

import { paopaoApi, type BannerRow, type GridRow } from '../api';

const banners = ref<BannerRow[]>([]);
const grid = ref<GridRow[]>([]);
const error = ref('');

const bannerColumns = [
  { dataIndex: 'title', title: '标题' },
  { dataIndex: 'link_type', title: '链接类型', width: 160 },
  { dataIndex: 'sort', title: '排序', width: 100 },
];

const gridColumns = [
  { dataIndex: 'title', title: '标题' },
  { dataIndex: 'badge', title: '角标', width: 140 },
  { dataIndex: 'sort', title: '排序', width: 100 },
];

onMounted(async () => {
  try {
    const [bannerRows, gridRows] = await Promise.all([
      paopaoApi.banners(),
      paopaoApi.grid(),
    ]);
    banners.value = bannerRows;
    grid.value = gridRows;
  } catch (cause) {
    error.value = cause instanceof Error ? cause.message : '读取失败';
  }
});
</script>

<template>
  <Page description="Banner 和九宫格会出现在用户端首页。写入接口仍是骨架占位，这里只核对现有配置。" title="运营位">
    <Alert v-if="error" :message="error" class="mb-4" show-icon type="error" />
    <div class="grid gap-5 lg:grid-cols-2">
      <Card title="Banner">
        <Table
          :columns="bannerColumns"
          :data-source="banners"
          :pagination="false"
          row-key="id"
          size="middle"
        >
          <template #bodyCell="{ column, record }">
            <template v-if="column.dataIndex === 'title'">{{ record.title || '未命名' }}</template>
          </template>
        </Table>
      </Card>
      <Card title="九宫格">
        <Table
          :columns="gridColumns"
          :data-source="grid"
          :pagination="false"
          row-key="id"
          size="middle"
        >
          <template #bodyCell="{ column, record }">
            <template v-if="column.dataIndex === 'badge'">{{ record.badge || '—' }}</template>
          </template>
        </Table>
      </Card>
    </div>
  </Page>
</template>
