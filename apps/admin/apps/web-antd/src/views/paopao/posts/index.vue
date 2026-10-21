<script lang="ts" setup>
import type { VbenFormProps } from '#/adapter/form';
import type { VxeTableGridOptions } from '#/adapter/vxe-table';

import { ref } from 'vue';

import { Page } from '@vben/common-ui';

import { Button, Drawer, Input, message, Modal, Tag } from 'ant-design-vue';

import { useVbenVxeGrid } from '#/adapter/vxe-table';

import {
  formatTime,
  paopaoApi,
  postStatusText,
  type PostRow,
} from '../api';

const open = ref(false);
const detail = ref<null | PostRow>(null);
const rejectOpen = ref(false);
const rejectId = ref(0);
const reason = ref('');

const formOptions: VbenFormProps = {
  schema: [
    {
      component: 'Select',
      componentProps: {
        allowClear: true,
        options: [
          { label: '待审', value: 'pending' },
          { label: '已发布', value: 'published' },
          { label: '已拒绝', value: 'rejected' },
        ],
        placeholder: '全部状态',
      },
      fieldName: 'status',
      label: '状态',
    },
  ],
  showCollapseButton: false,
  submitOnChange: true,
  submitOnEnter: true,
};

const gridOptions: VxeTableGridOptions<PostRow> = {
  columns: [
    { field: 'title', minWidth: 220, title: '标题' },
    { field: 'board', slots: { default: 'board' }, title: '版块', width: 140 },
    { field: 'author', slots: { default: 'author' }, title: '作者', width: 140 },
    { field: 'status', slots: { default: 'status' }, title: '状态', width: 110 },
    { field: 'created_at', slots: { default: 'created' }, title: '提交时间', width: 170 },
    { field: 'action', slots: { default: 'action' }, title: '操作', width: 220 },
  ],
  height: 'auto',
  keepSource: true,
  pagerConfig: {},
  proxyConfig: {
    ajax: {
      query: async ({ page }, formValues) => {
        const result = await paopaoApi.posts({
          page: page.currentPage,
          per_page: page.pageSize,
          status: formValues?.status || undefined,
        });
        return { items: result.items, total: result.meta.total };
      },
    },
  },
  rowConfig: { keyField: 'id' },
  toolbarConfig: { refresh: true, search: true, zoom: true },
};

const [Grid, gridApi] = useVbenVxeGrid({ formOptions, gridOptions });

async function show(row: PostRow) {
  open.value = true;
  detail.value = await paopaoApi.post(row.id);
}

async function approve(row: PostRow) {
  await paopaoApi.approvePost(row.id);
  message.success('已通过，用户端公开列表可以看到这篇帖子');
  if (detail.value?.id === row.id) {
    detail.value = await paopaoApi.post(row.id);
  }
  gridApi.query();
}

function askReject(row: PostRow) {
  rejectId.value = row.id;
  reason.value = '';
  rejectOpen.value = true;
}

async function submitReject() {
  await paopaoApi.rejectPost(rejectId.value, reason.value.trim());
  message.success('已拒绝');
  rejectOpen.value = false;
  if (detail.value?.id === rejectId.value) {
    detail.value = await paopaoApi.post(rejectId.value);
  }
  gridApi.query();
}

function statusColor(status: string) {
  if (status === 'pending') return 'gold';
  if (status === 'published') return 'green';
  return 'red';
}
</script>

<template>
  <Page auto-content-height description="成员发帖后先停在待审。通过之后才会出现在 App 和 H5。" title="帖子审核">
    <Grid>
      <template #board="{ row }">{{ row.board?.name || '—' }}</template>
      <template #author="{ row }">{{ row.author?.nickname || '—' }}</template>
      <template #status="{ row }">
        <Tag :color="statusColor(row.status)">{{ postStatusText[row.status] || row.status }}</Tag>
      </template>
      <template #created="{ row }">{{ formatTime(row.created_at) }}</template>
      <template #action="{ row }">
        <Button size="small" type="link" @click="show(row)">查看</Button>
        <Button v-if="row.status === 'pending'" size="small" type="link" @click="approve(row)">通过</Button>
        <Button v-if="row.status !== 'rejected'" danger size="small" type="link" @click="askReject(row)">拒绝</Button>
      </template>
    </Grid>
    <Drawer v-model:open="open" :width="480" title="帖子正文">
      <template v-if="detail">
        <Tag :color="statusColor(detail.status)">{{ postStatusText[detail.status] || detail.status }}</Tag>
        <h2 class="mt-3 text-lg font-semibold">{{ detail.title }}</h2>
        <p class="mt-1 text-sm text-black/45">
          {{ detail.board?.name || '未分区' }} · {{ detail.author?.nickname || '—' }} · {{ formatTime(detail.created_at) }}
        </p>
        <p class="mt-4 whitespace-pre-wrap leading-7">{{ detail.content }}</p>
        <div v-if="detail.status === 'pending'" class="mt-6 flex gap-2">
          <Button type="primary" @click="approve(detail)">通过</Button>
          <Button danger @click="askReject(detail)">拒绝</Button>
        </div>
      </template>
    </Drawer>
    <Modal v-model:open="rejectOpen" title="拒绝这篇帖子" @ok="submitReject">
      <Input.TextArea v-model:value="reason" :maxlength="200" :rows="3" placeholder="原因，可留空" />
    </Modal>
  </Page>
</template>
