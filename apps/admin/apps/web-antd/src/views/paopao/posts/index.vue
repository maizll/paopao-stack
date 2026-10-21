<script lang="ts" setup>
import type { VbenFormProps } from '#/adapter/form';
import type { VxeTableGridOptions } from '#/adapter/vxe-table';

import { reactive, ref } from 'vue';

import { Page } from '@vben/common-ui';

import { Button, Drawer, Form, Input, message, Modal, Select, Tag } from 'ant-design-vue';

import { useVbenVxeGrid } from '#/adapter/vxe-table';

import {
  formatTime,
  paopaoApi,
  postStatusText,
  type BoardRow,
  type PostRow,
} from '../api';

const open = ref(false);
const editing = ref(false);
const saving = ref(false);
const detail = ref<null | PostRow>(null);
const boards = ref<BoardRow[]>([]);
const rejectOpen = ref(false);
const rejectId = ref(0);
const reason = ref('');

const form = reactive({
  board_id: undefined as number | undefined,
  content: '',
  cover: '',
  title: '',
});

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

function fillForm(post: PostRow) {
  form.title = post.title;
  form.content = post.content || '';
  form.cover = post.cover || '';
  form.board_id = post.board?.id;
}

async function loadBoards() {
  if (boards.value.length > 0) return;
  boards.value = await paopaoApi.boards();
}

async function show(row: PostRow) {
  editing.value = false;
  open.value = true;
  detail.value = await paopaoApi.post(row.id);
}

async function edit(row: PostRow) {
  open.value = true;
  editing.value = true;
  const [post] = await Promise.all([paopaoApi.post(row.id), loadBoards()]);
  detail.value = post;
  fillForm(post);
}

async function save() {
  if (!detail.value || !form.board_id) {
    message.warning('请选择版块');
    return;
  }
  if (!form.title.trim() || !form.content.trim()) {
    message.warning('标题和正文都要填');
    return;
  }
  saving.value = true;
  try {
    detail.value = await paopaoApi.updatePost(detail.value.id, {
      board_id: form.board_id,
      content: form.content.trim(),
      cover: form.cover.trim() || null,
      title: form.title.trim(),
    });
    editing.value = false;
    message.success('已保存，可以继续审核');
    gridApi.query();
  } finally {
    saving.value = false;
  }
}

function cancelEdit() {
  editing.value = false;
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
  <Page auto-content-height description="成员发帖后先停在待审。可以先改标题、正文、版块和封面，再通过或拒绝。状态不会在编辑里改掉。" title="帖子审核">
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
    <Drawer v-model:open="open" :width="560" :title="editing ? '编辑帖子' : '帖子正文'">
      <template v-if="detail">
        <div class="mb-4 flex flex-wrap items-center gap-2 text-sm text-black/45">
          <Tag :color="statusColor(detail.status)">{{ postStatusText[detail.status] || detail.status }}</Tag>
          <span>{{ detail.author?.nickname || '—' }}</span>
          <span>·</span>
          <span>{{ formatTime(detail.created_at) }}</span>
        </div>
        <Form v-if="editing" layout="vertical">
          <Form.Item label="标题" required>
            <Input v-model:value="form.title" :maxlength="80" placeholder="标题" />
          </Form.Item>
          <Form.Item label="版块" required>
            <Select
              v-model:value="form.board_id"
              :options="boards.map((board) => ({ label: board.name, value: board.id }))"
              placeholder="选择版块"
            />
          </Form.Item>
          <Form.Item label="封面地址">
            <Input v-model:value="form.cover" :maxlength="255" placeholder="图片 URL，可留空" />
          </Form.Item>
          <Form.Item label="正文" required>
            <Input.TextArea v-model:value="form.content" :maxlength="20000" :rows="10" placeholder="正文" />
          </Form.Item>
          <p class="mb-4 text-xs text-black/45">状态、作者和提交时间只读。保存后仍可在这篇上通过或拒绝。</p>
          <div class="flex gap-2">
            <Button :loading="saving" type="primary" @click="save">保存</Button>
            <Button @click="cancelEdit">取消</Button>
          </div>
        </Form>
        <template v-else>
          <h2 class="text-lg font-semibold">{{ detail.title }}</h2>
          <p class="mt-1 text-sm text-black/45">{{ detail.board?.name || '未分区' }}</p>
          <p v-if="detail.cover" class="mt-2 break-all text-sm text-black/45">封面 {{ detail.cover }}</p>
          <p class="mt-4 whitespace-pre-wrap leading-7">{{ detail.content }}</p>
          <div class="mt-6 flex gap-2">
            <Button @click="edit(detail)">编辑</Button>
            <Button v-if="detail.status === 'pending'" type="primary" @click="approve(detail)">通过</Button>
            <Button v-if="detail.status !== 'rejected'" danger @click="askReject(detail)">拒绝</Button>
          </div>
        </template>
      </template>
    </Drawer>
    <Modal v-model:open="rejectOpen" title="拒绝这篇帖子" @ok="submitReject">
      <Input.TextArea v-model:value="reason" :maxlength="200" :rows="3" placeholder="原因，可留空" />
    </Modal>
  </Page>
</template>
