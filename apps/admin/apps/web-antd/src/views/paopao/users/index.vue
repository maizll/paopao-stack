<script lang="ts" setup>
import type { VxeTableGridOptions } from '#/adapter/vxe-table';

import { useUserStore } from '@vben/stores';

import { Button, message, Modal, Tag } from 'ant-design-vue';
import { Page } from '@vben/common-ui';

import { useVbenVxeGrid } from '#/adapter/vxe-table';

import { paopaoApi, roleText, type UserRow } from '../api';

const userStore = useUserStore();

const gridOptions: VxeTableGridOptions<UserRow> = {
  columns: [
    { field: 'nickname', minWidth: 140, title: '昵称' },
    { field: 'email', slots: { default: 'email' }, title: '邮箱' },
    { field: 'role', slots: { default: 'role' }, title: '角色', width: 140 },
    { field: 'status', slots: { default: 'status' }, title: '状态', width: 110 },
    { field: 'action', slots: { default: 'action' }, title: '操作', width: 120 },
  ],
  height: 'auto',
  pagerConfig: {},
  proxyConfig: {
    ajax: {
      query: async ({ page }) => {
        const result = await paopaoApi.users({
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

const [Grid, gridApi] = useVbenVxeGrid({ gridOptions });

function confirmBan(row: UserRow) {
  Modal.confirm({
    content: '封禁后该账号不能再登录当前租户。',
    okText: '封禁',
    okType: 'danger',
    title: `封禁 ${row.nickname}？`,
    onOk: async () => {
      await paopaoApi.banUser(row.id);
      message.success('已封禁');
      gridApi.query();
    },
  });
}

async function unban(row: UserRow) {
  await paopaoApi.unbanUser(row.id);
  message.success('已解除封禁');
  gridApi.query();
}
</script>

<template>
  <Page auto-content-height description="当前租户的账号。封禁只影响登录，不删除已经发布的帖子。" title="用户">
    <Grid>
      <template #email="{ row }">{{ row.email || row.phone || '—' }}</template>
      <template #role="{ row }">{{ roleText[row.role] || row.role }}</template>
      <template #status="{ row }">
        <Tag :color="row.status === 'banned' ? 'red' : 'green'">
          {{ row.status === 'banned' ? '已封禁' : '正常' }}
        </Tag>
      </template>
      <template #action="{ row }">
        <Button v-if="row.status === 'banned'" size="small" type="link" @click="unban(row)">解封</Button>
        <span v-else-if="String(row.id) === userStore.userInfo?.userId">当前账号</span>
        <Button v-else danger size="small" type="link" @click="confirmBan(row)">封禁</Button>
      </template>
    </Grid>
  </Page>
</template>
