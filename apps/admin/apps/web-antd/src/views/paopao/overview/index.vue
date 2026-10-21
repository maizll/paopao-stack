<script lang="ts" setup>
import type { AnalysisOverviewItem, WorkbenchQuickNavItem } from '@vben/common-ui';

import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';

import {
  AnalysisOverview,
  Page,
  WorkbenchHeader,
  WorkbenchQuickNav,
} from '@vben/common-ui';
import {
  SvgBellIcon,
  SvgCakeIcon,
  SvgCardIcon,
  SvgDownloadIcon,
} from '@vben/icons';
import { useUserStore } from '@vben/stores';

import { Button, Card, Table } from 'ant-design-vue';

import { formatTime, paopaoApi, type PostRow } from '../api';

const router = useRouter();
const userStore = useUserStore();
const pending = ref<PostRow[]>([]);
const headerStats = ref([
  { label: '待审', value: 0 },
  { label: '已发布', value: 0 },
  { label: '版块', value: 0 },
]);
const items = ref<AnalysisOverviewItem[]>([
  { icon: SvgBellIcon, title: '待审帖子', totalTitle: '需要处理', totalValue: 0, value: 0 },
  { icon: SvgCardIcon, title: '已发布帖子', totalTitle: '公开可见', totalValue: 0, value: 0 },
  { icon: SvgCakeIcon, title: '版块', totalTitle: '当前租户', totalValue: 0, value: 0 },
  { icon: SvgDownloadIcon, title: '软件', totalTitle: '已登记', totalValue: 0, value: 0 },
]);

const quickNavItems: WorkbenchQuickNavItem[] = [
  { color: '#1677ff', icon: 'lucide:file-check-2', title: '帖子审核', url: '/posts' },
  { color: '#13c2c2', icon: 'lucide:users', title: '用户', url: '/users' },
  { color: '#722ed1', icon: 'lucide:panels-top-left', title: '版块', url: '/boards' },
  { color: '#fa8c16', icon: 'lucide:package', title: '软件审核', url: '/apps' },
  { color: '#eb2f96', icon: 'lucide:gallery-horizontal', title: '运营位', url: '/ops' },
  { color: '#52c41a', icon: 'lucide:building-2', title: '租户', url: '/tenants' },
];

const columns = [
  { dataIndex: 'title', title: '标题' },
  { dataIndex: 'author', title: '作者', width: 140 },
  { dataIndex: 'created_at', title: '提交时间', width: 180 },
  { dataIndex: 'action', title: '操作', width: 90 },
];

function navTo(item: WorkbenchQuickNavItem) {
  if (item.url?.startsWith('/')) {
    router.push(item.url);
  }
}

onMounted(async () => {
  const [pendingPage, publishedPage, boards, apps] = await Promise.all([
    paopaoApi.posts({ page: 1, per_page: 6, status: 'pending' }),
    paopaoApi.posts({ page: 1, per_page: 1, status: 'published' }),
    paopaoApi.boards(),
    paopaoApi.apps({ page: 1, per_page: 1 }),
  ]);
  pending.value = pendingPage.items;
  const pendingTotal = pendingPage.meta.total;
  headerStats.value = [
    { label: '待审', value: pendingTotal },
    { label: '已发布', value: publishedPage.meta.total },
    { label: '版块', value: boards.length },
  ];
  const publishedTotal = publishedPage.meta.total;
  items.value = [
    { icon: SvgBellIcon, title: '待审帖子', totalTitle: '队列中', totalValue: pendingTotal, value: pendingTotal },
    { icon: SvgCardIcon, title: '已发布帖子', totalTitle: '公开可见', totalValue: publishedTotal, value: publishedTotal },
    { icon: SvgCakeIcon, title: '版块', totalTitle: '当前租户', totalValue: boards.length, value: boards.length },
    { icon: SvgDownloadIcon, title: '软件', totalTitle: '已登记', totalValue: apps.meta.total, value: apps.meta.total },
  ];
});
</script>

<template>
  <Page>
    <WorkbenchHeader
      :alt="userStore.userInfo?.realName || '运营'"
      :avatar="userStore.userInfo?.avatar || ''"
      :stats="headerStats"
    >
      <template #title>
        早安，{{ userStore.userInfo?.realName || '运营' }}
      </template>
      <template #description>
        今天先清待审帖子。通过之后才会出现在 App 和 H5。
      </template>
    </WorkbenchHeader>
    <AnalysisOverview :items="items" class="mt-5" />
    <div class="mt-5 flex flex-col gap-5 lg:flex-row">
      <Card class="min-w-0 flex-1" title="待审队列">
        <template #extra>
          <Button type="primary" @click="router.push('/posts')">处理帖子</Button>
        </template>
        <Table
          :columns="columns"
          :data-source="pending"
          :pagination="false"
          row-key="id"
          size="middle"
        >
          <template #bodyCell="{ column, record }">
            <template v-if="column.dataIndex === 'author'">
              {{ record.author?.nickname || '—' }}
            </template>
            <template v-else-if="column.dataIndex === 'created_at'">
              {{ formatTime(record.created_at) }}
            </template>
            <template v-else-if="column.dataIndex === 'action'">
              <Button type="link" @click="router.push('/posts')">审核</Button>
            </template>
          </template>
        </Table>
      </Card>
      <WorkbenchQuickNav
        :items="quickNavItems"
        class="lg:w-80"
        title="去处理"
        @click="navTo"
      />
    </div>
  </Page>
</template>
