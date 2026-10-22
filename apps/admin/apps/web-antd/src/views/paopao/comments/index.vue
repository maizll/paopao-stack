<script lang="ts" setup>
import type { VbenFormProps } from '#/adapter/form';
import type { VxeTableGridOptions } from '#/adapter/vxe-table';

import { ref } from 'vue';

import { Page } from '@vben/common-ui';

import { Button, Drawer, Input, message, Modal, Tag } from 'ant-design-vue';

import { useVbenVxeGrid } from '#/adapter/vxe-table';

import {
  commentStatusText,
  formatTime,
  paopaoApi,
  type CommentRow,
} from '../api';

const open = ref(false);
const detail = ref<CommentRow | null>(null);
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
          { label: '已屏蔽', value: 'hidden' },
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

const gridOptions: VxeTableGridOptions<CommentRow> = {
  columns: [
    { field: 'content', minWidth: 220, showOverflow: true, title: '评论' },
    { field: 'post', minWidth: 160, showOverflow: true, slots: { default: 'post' }, title: '笔记' },
    { field: 'author', slots: { default: 'author' }, title: '作者', width: 120 },
    { field: 'status', slots: { default: 'status' }, title: '状态', width: 100 },
    { field: 'created_at', slots: { default: 'created' }, title: '时间', width: 160 },
    { field: 'action', fixed: 'right', slots: { default: 'action' }, title: '操作', width: 250 },
  ],
  height: 'auto',
  keepSource: true,
  pagerConfig: {},
  proxyConfig: {
    ajax: {
      query: async ({ page }, formValues) => {
        const result = await paopaoApi.comments({
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

function show(row: CommentRow) {
  detail.value = row;
  open.value = true;
}

async function approve(row: CommentRow) {
  await paopaoApi.approveComment(row.id);
  message.success('评论已公开');
  if (detail.value?.id === row.id) {
    detail.value = { ...row, status: 'published' };
  }
  gridApi.query();
}

function askReject(row: CommentRow) {
  rejectId.value = row.id;
  reason.value = '';
  rejectOpen.value = true;
}

async function submitReject() {
  await paopaoApi.rejectComment(rejectId.value, reason.value.trim());
  message.success('已拒绝，作者会收到通知');
  rejectOpen.value = false;
  if (detail.value?.id === rejectId.value) {
    detail.value = { ...detail.value, status: 'rejected' };
  }
  gridApi.query();
}

async function hide(row: CommentRow) {
  await paopaoApi.hideComment(row.id);
  message.success('已屏蔽');
  if (detail.value?.id === row.id) {
    detail.value = { ...row, status: 'hidden' };
  }
  gridApi.query();
}

async function remove(row: CommentRow) {
  await paopaoApi.deleteComment(row.id);
  message.success('已删除');
  if (detail.value?.id === row.id) open.value = false;
  gridApi.query();
}

function statusColor(status: string) {
  if (status === 'pending') return 'gold';
  if (status === 'published') return 'green';
  if (status === 'hidden') return 'default';
  return 'red';
}
</script>

<template>
  <Page auto-content-height description="评论和帖子一样先待审。通过后才出现在笔记下，并计入评论数。" title="评论审核">
    <Grid>
      <template #post="{ row }">{{ row.post?.title || '—' }}</template>
      <template #author="{ row }">{{ row.author?.nickname || '—' }}</template>
      <template #status="{ row }">
        <Tag :color="statusColor(row.status)">{{ commentStatusText[row.status] || row.status }}</Tag>
      </template>
      <template #created="{ row }">{{ formatTime(row.created_at) }}</template>
      <template #action="{ row }">
        <Button size="small" type="link" @click="show(row)">查看</Button>
        <Button v-if="row.status !== 'published'" size="small" type="link" @click="approve(row)">通过</Button>
        <Button v-if="row.status !== 'rejected'" danger size="small" type="link" @click="askReject(row)">拒绝</Button>
        <Button v-if="row.status !== 'hidden'" size="small" type="link" @click="hide(row)">屏蔽</Button>
        <Button danger size="small" type="link" @click="remove(row)">删除</Button>
      </template>
    </Grid>
    <Drawer v-model:open="open" :width="'min(480px, 100vw)'" root-class-name="paopao-drawer" title="评论">
      <template v-if="detail">
        <div class="mb-3 flex flex-wrap items-center gap-2 text-sm text-black/45">
          <Tag :color="statusColor(detail.status)">{{ commentStatusText[detail.status] || detail.status }}</Tag>
          <span>{{ detail.author?.nickname || '—' }}</span>
          <span>·</span>
          <span>{{ formatTime(detail.created_at) }}</span>
        </div>
        <p class="text-sm text-black/45">笔记 {{ detail.post?.title || '—' }}</p>
        <p class="mt-4 whitespace-pre-wrap leading-7">{{ detail.content }}</p>
        <div class="mt-6 flex flex-wrap gap-2">
          <Button v-if="detail.status !== 'published'" type="primary" @click="approve(detail)">通过</Button>
          <Button v-if="detail.status !== 'rejected'" danger @click="askReject(detail)">拒绝</Button>
          <Button v-if="detail.status !== 'hidden'" @click="hide(detail)">屏蔽</Button>
          <Button danger @click="remove(detail)">删除</Button>
        </div>
      </template>
    </Drawer>
    <Modal v-model:open="rejectOpen" :width="480" ok-text="拒绝" title="拒绝这条评论" wrap-class-name="paopao-sheet" @ok="submitReject">
      <Input.TextArea v-model:value="reason" :maxlength="200" :rows="3" placeholder="原因会写进作者的通知，可留空" />
    </Modal>
  </Page>
</template>

<style>
.paopao-sheet .ant-modal {
  max-width: calc(100vw - 24px);
  margin-inline: auto;
}

.paopao-drawer .ant-drawer-content-wrapper {
  max-width: 100vw;
}
</style>
