<script lang="ts" setup>
import type { VbenFormProps } from '#/adapter/form';
import type { VxeTableGridOptions } from '#/adapter/vxe-table';

import { ref } from 'vue';
import { useRouter } from 'vue-router';

import { Page } from '@vben/common-ui';

import { Button, Drawer, Input, message, Modal, Tag } from 'ant-design-vue';

import { useVbenVxeGrid } from '#/adapter/vxe-table';

import {
  formatTime,
  paopaoApi,
  postStatusText,
  type PostRow,
} from '../api';

const router = useRouter();
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
    { field: 'action', fixed: 'right', slots: { default: 'action' }, title: '操作', width: 240 },
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

function looksLikeHtml(value: string | undefined) {
  return !!value && /<[a-z][\s\S]*>/i.test(value);
}

async function show(row: PostRow) {
  open.value = true;
  detail.value = await paopaoApi.post(row.id);
}

function edit(row: PostRow) {
  router.push(`/posts/${row.id}/edit`);
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
  <Page auto-content-height description="成员发帖后先停在待审。编辑会打开整页表单；通过和拒绝留在这里。" title="帖子审核">
    <Grid>
      <template #board="{ row }">{{ row.board?.name || '—' }}</template>
      <template #author="{ row }">{{ row.author?.nickname || '—' }}</template>
      <template #status="{ row }">
        <Tag :color="statusColor(row.status)">{{ postStatusText[row.status] || row.status }}</Tag>
      </template>
      <template #created="{ row }">{{ formatTime(row.created_at) }}</template>
      <template #action="{ row }">
        <Button size="small" type="link" @click="show(row)">查看</Button>
        <Button size="small" type="link" @click="edit(row)">编辑</Button>
        <Button v-if="row.status === 'pending'" size="small" type="link" @click="approve(row)">通过</Button>
        <Button v-if="row.status !== 'rejected'" danger size="small" type="link" @click="askReject(row)">拒绝</Button>
      </template>
    </Grid>
    <Drawer v-model:open="open" :width="560" title="帖子正文">
      <template v-if="detail">
        <div class="mb-4 flex flex-wrap items-center gap-2 text-sm text-black/45">
          <Tag :color="statusColor(detail.status)">{{ postStatusText[detail.status] || detail.status }}</Tag>
          <span>{{ detail.author?.nickname || '—' }}</span>
          <span>·</span>
          <span>{{ formatTime(detail.created_at) }}</span>
        </div>
        <h2 class="text-lg font-semibold">{{ detail.title }}</h2>
        <p class="mt-1 text-sm text-black/45">{{ detail.board?.name || '未分区' }}</p>
        <p v-if="detail.cover" class="mt-2 break-all text-sm text-black/45">封面 {{ detail.cover }}</p>
        <div v-if="looksLikeHtml(detail.content)" class="post-body mt-4" v-html="detail.content" />
        <p v-else class="mt-4 whitespace-pre-wrap leading-7">{{ detail.content }}</p>
        <div class="mt-6 flex gap-2">
          <Button type="primary" @click="edit(detail)">编辑</Button>
          <Button v-if="detail.status === 'pending'" @click="approve(detail)">通过</Button>
          <Button v-if="detail.status !== 'rejected'" danger @click="askReject(detail)">拒绝</Button>
        </div>
      </template>
    </Drawer>
    <Modal v-model:open="rejectOpen" title="拒绝这篇帖子" @ok="submitReject">
      <Input.TextArea v-model:value="reason" :maxlength="200" :rows="3" placeholder="原因，可留空" />
    </Modal>
  </Page>
</template>

<style scoped>
.post-body :deep(p) {
  margin: 0.6em 0;
  line-height: 1.75;
}

.post-body :deep(img) {
  max-width: 100%;
}
</style>
