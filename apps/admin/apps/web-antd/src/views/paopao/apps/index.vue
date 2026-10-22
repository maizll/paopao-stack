<script lang="ts" setup>
import type { VbenFormProps } from '#/adapter/form';
import type { VxeTableGridOptions } from '#/adapter/vxe-table';

import { reactive, ref } from 'vue';

import { Page } from '@vben/common-ui';

import {
  Button,
  Drawer,
  Form,
  Input,
  message,
  Modal,
  Select,
  Tag,
  Upload,
} from 'ant-design-vue';

import { useVbenVxeGrid } from '#/adapter/vxe-table';

import {
  appStatusText,
  paopaoApi,
  type AppRow,
} from '../api';

const open = ref(false);
const detail = ref<AppRow | null>(null);
const editorOpen = ref(false);
const saving = ref(false);
const rejectOpen = ref(false);
const rejectId = ref(0);
const reason = ref('');
const categories = ref<Array<{ id: number; name: string }>>([]);
const form = reactive({
  category_id: undefined as number | undefined,
  description: '',
  icon: '',
  id: 0,
  name: '',
  summary: '',
});

const formOptions: VbenFormProps = {
  schema: [
    {
      component: 'Select',
      componentProps: {
        allowClear: true,
        options: [
          { label: '待审', value: 'draft' },
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

const gridOptions: VxeTableGridOptions<AppRow> = {
  columns: [
    { field: 'name', minWidth: 160, title: '名称' },
    { field: 'category', slots: { default: 'category' }, title: '分类', width: 140 },
    { field: 'version', slots: { default: 'version' }, title: '版本', width: 110 },
    { field: 'download_count', title: '获取', width: 90 },
    { field: 'status', slots: { default: 'status' }, title: '状态', width: 110 },
    { field: 'action', fixed: 'right', slots: { default: 'action' }, title: '操作', width: 280 },
  ],
  height: 'auto',
  pagerConfig: {},
  proxyConfig: {
    ajax: {
      query: async ({ page }, formValues) => {
        const result = await paopaoApi.apps({
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

async function ensureCategories() {
  if (categories.value.length) return;
  categories.value = await paopaoApi.categories();
}

async function show(row: AppRow) {
  open.value = true;
  detail.value = await paopaoApi.app(row.id);
}

function blankForm() {
  form.id = 0;
  form.name = '';
  form.summary = '';
  form.description = '';
  form.icon = '';
  form.category_id = categories.value[0]?.id;
}

async function create() {
  await ensureCategories();
  blankForm();
  editorOpen.value = true;
}

async function edit(row: AppRow) {
  await ensureCategories();
  const item = await paopaoApi.app(row.id);
  form.id = item.id;
  form.name = item.name;
  form.summary = item.summary || '';
  form.description = item.description || '';
  form.icon = item.icon || '';
  form.category_id = item.category?.id;
  editorOpen.value = true;
}

async function uploadIcon(options: {
  file: File;
  onError?: (error: Error) => void;
  onSuccess?: (body: unknown) => void;
}) {
  try {
    const result = await paopaoApi.uploadImage(options.file);
    form.icon = result.url;
    options.onSuccess?.(result);
    message.success('图标已上传');
  } catch (cause) {
    const error = cause instanceof Error ? cause : new Error('上传失败');
    options.onError?.(error);
    message.error(error.message);
  }
}

async function save() {
  if (!form.name.trim()) {
    message.warning('请填写软件名称');
    return;
  }
  saving.value = true;
  try {
    const body = {
      category_id: form.category_id || null,
      description: form.description.trim() || null,
      icon: form.icon.trim() || null,
      name: form.name.trim(),
      summary: form.summary.trim() || null,
    };
    const saved = form.id
      ? await paopaoApi.updateApp(form.id, body)
      : await paopaoApi.createApp(body);
    message.success(form.id ? '已保存' : '已提交，状态为待审');
    editorOpen.value = false;
    if (detail.value?.id === saved.id) detail.value = saved;
    gridApi.query();
  } finally {
    saving.value = false;
  }
}

async function approve(row: AppRow) {
  const saved = await paopaoApi.approveApp(row.id);
  message.success('已通过，发现页可以看到这款软件');
  if (detail.value?.id === row.id) detail.value = saved;
  gridApi.query();
}

function askReject(row: AppRow) {
  rejectId.value = row.id;
  reason.value = '';
  rejectOpen.value = true;
}

async function submitReject() {
  const saved = await paopaoApi.rejectApp(rejectId.value, reason.value.trim());
  message.success('已拒绝');
  rejectOpen.value = false;
  if (detail.value?.id === rejectId.value) detail.value = saved;
  gridApi.query();
}

function statusColor(status: string) {
  if (status === 'draft') return 'gold';
  if (status === 'published') return 'green';
  return 'red';
}
</script>

<template>
  <Page auto-content-height description="待审软件通过后才会出现在用户端发现页。编辑不改变审核状态。" title="软件审核">
    <template #extra>
      <Button type="primary" @click="create">新建软件</Button>
    </template>
    <Grid>
      <template #category="{ row }">{{ row.category?.name || '—' }}</template>
      <template #version="{ row }">{{ row.latest_version?.version_name || '—' }}</template>
      <template #status="{ row }">
        <Tag :color="statusColor(row.status)">{{ appStatusText[row.status] || row.status }}</Tag>
      </template>
      <template #action="{ row }">
        <div class="flex flex-wrap gap-1">
          <Button size="small" type="link" @click="show(row)">查看</Button>
          <Button size="small" type="link" @click="edit(row)">编辑</Button>
          <Button v-if="row.status !== 'published'" size="small" type="link" @click="approve(row)">通过</Button>
          <Button v-if="row.status !== 'rejected'" size="small" type="link" @click="askReject(row)">拒绝</Button>
        </div>
      </template>
    </Grid>

    <Drawer v-model:open="open" :width="'min(440px, 100vw)'" title="软件详情">
      <template v-if="detail">
        <img
          v-if="detail.icon"
          :src="detail.icon"
          alt=""
          class="mb-4 h-16 w-16 rounded-xl object-cover"
        />
        <div class="mb-3 flex items-center gap-2">
          <span class="text-base font-semibold">{{ detail.name }}</span>
          <Tag :color="statusColor(detail.status)">{{ appStatusText[detail.status] || detail.status }}</Tag>
        </div>
        <p class="mb-2 text-sm text-black/65">{{ detail.category?.name || '未分类' }} · {{ detail.latest_version?.version_name || '未标版本' }} · {{ detail.download_count }} 次获取</p>
        <p class="mb-3 text-sm leading-6">{{ detail.summary || '暂无简介' }}</p>
        <p class="whitespace-pre-wrap text-sm leading-6 text-black/80">{{ detail.description || '暂无介绍' }}</p>
        <div class="mt-5 flex flex-wrap gap-2">
          <Button @click="edit(detail)">编辑</Button>
          <Button v-if="detail.status !== 'published'" type="primary" @click="approve(detail)">通过</Button>
          <Button v-if="detail.status !== 'rejected'" danger @click="askReject(detail)">拒绝</Button>
        </div>
      </template>
    </Drawer>

    <Modal
      v-model:open="editorOpen"
      :confirm-loading="saving"
      :width="520"
      wrap-class-name="paopao-sheet"
      :title="form.id ? '编辑软件' : '新建软件'"
      ok-text="保存"
      @ok="save"
    >
      <Form class="min-w-0" layout="vertical">
        <Form.Item label="名称" required>
          <Input v-model:value="form.name" :maxlength="40" placeholder="软件名称" />
        </Form.Item>
        <Form.Item label="分类">
          <Select
            v-model:value="form.category_id"
            :options="categories.map((item) => ({ label: item.name, value: item.id }))"
            allow-clear
            placeholder="选择分类"
          />
        </Form.Item>
        <Form.Item label="简介">
          <Input.TextArea v-model:value="form.summary" :maxlength="120" :rows="2" placeholder="一句话介绍" />
        </Form.Item>
        <Form.Item label="介绍">
          <Input.TextArea v-model:value="form.description" :maxlength="4000" :rows="4" placeholder="给审核和用户看的说明" />
        </Form.Item>
        <Form.Item label="图标">
          <div class="flex min-w-0 items-center gap-3">
            <img v-if="form.icon" :src="form.icon" alt="" class="h-12 w-12 shrink-0 rounded-lg object-cover" />
            <Upload :custom-request="uploadIcon" :show-upload-list="false" accept="image/jpeg,image/png,image/webp,image/gif">
              <Button>上传图标</Button>
            </Upload>
          </div>
        </Form.Item>
      </Form>
    </Modal>

    <Modal v-model:open="rejectOpen" :width="480" ok-text="拒绝" title="拒绝软件" wrap-class-name="paopao-sheet" @ok="submitReject">
      <Input.TextArea v-model:value="reason" :maxlength="200" :rows="3" placeholder="原因选填，最多 200 字" />
    </Modal>
  </Page>
</template>

<style>
.paopao-sheet .ant-modal {
  max-width: calc(100vw - 24px);
  margin-inline: auto;
  padding-bottom: 0;
}
</style>
