<script lang="ts" setup>
import { onMounted, reactive, ref } from 'vue';

import { Page } from '@vben/common-ui';

import {
  Button,
  Card,
  Form,
  Input,
  InputNumber,
  message,
  Modal,
  Select,
  Table,
  Tag,
  Upload,
} from 'ant-design-vue';

import { linkTypeText, paopaoApi, type BannerRow, type GridRow } from '../api';

const banners = ref<BannerRow[]>([]);
const grid = ref<GridRow[]>([]);
const error = ref('');
const loading = ref(false);
const bannerOpen = ref(false);
const gridOpen = ref(false);
const saving = ref(false);

const linkOptions = [
  { label: '链接', value: 'url' },
  { label: '软件', value: 'app' },
  { label: '帖子', value: 'post' },
  { label: '版块', value: 'board' },
];
const statusOptions = [
  { label: '已发布', value: 'published' },
  { label: '草稿', value: 'draft' },
];

const bannerForm = reactive({
  id: 0,
  image: '',
  link_type: 'url',
  link_value: '',
  sort: 10,
  status: 'published',
  title: '',
});
const gridForm = reactive({
  badge: '',
  icon: '',
  id: 0,
  link_type: 'board',
  link_value: '',
  sort: 10,
  status: 'published',
  title: '',
});

const bannerColumns = [
  { dataIndex: 'image', title: '图片', width: 96 },
  { dataIndex: 'title', title: '标题' },
  { dataIndex: 'link_type', title: '跳转', width: 88 },
  { dataIndex: 'sort', title: '排序', width: 72 },
  { dataIndex: 'status', title: '状态', width: 96 },
  { dataIndex: 'action', title: '操作', width: 140 },
];
const gridColumns = [
  { dataIndex: 'title', title: '标题' },
  { dataIndex: 'badge', title: '角标', width: 80 },
  { dataIndex: 'link_type', title: '跳转', width: 88 },
  { dataIndex: 'sort', title: '排序', width: 72 },
  { dataIndex: 'status', title: '状态', width: 96 },
  { dataIndex: 'action', title: '操作', width: 140 },
];

async function load() {
  loading.value = true;
  error.value = '';
  try {
    const [bannerRows, gridRows] = await Promise.all([
      paopaoApi.banners(),
      paopaoApi.grid(),
    ]);
    banners.value = bannerRows;
    grid.value = gridRows;
  } catch (cause) {
    error.value = cause instanceof Error ? cause.message : '读取失败';
  } finally {
    loading.value = false;
  }
}

function openBanner(row?: BannerRow) {
  bannerForm.id = row?.id || 0;
  bannerForm.title = row?.title || '';
  bannerForm.image = row?.image || '';
  bannerForm.link_type = row?.link_type || 'url';
  bannerForm.link_value = row?.link_value || '';
  bannerForm.sort = row?.sort ?? 10;
  bannerForm.status = row?.status || 'published';
  bannerOpen.value = true;
}

function openGrid(row?: GridRow) {
  gridForm.id = row?.id || 0;
  gridForm.title = row?.title || '';
  gridForm.icon = row?.icon || '';
  gridForm.badge = row?.badge || '';
  gridForm.link_type = row?.link_type || 'board';
  gridForm.link_value = row?.link_value || '';
  gridForm.sort = row?.sort ?? 10;
  gridForm.status = row?.status || 'published';
  gridOpen.value = true;
}

async function uploadTo(target: 'banner' | 'grid', file: File) {
  const result = await paopaoApi.uploadImage(file);
  if (target === 'banner') bannerForm.image = result.url;
  else gridForm.icon = result.url;
  message.success('图片已上传');
}

async function saveBanner() {
  if (!bannerForm.image.trim()) {
    message.warning('请先上传 Banner 图片');
    return;
  }
  saving.value = true;
  try {
    const body = {
      image: bannerForm.image.trim(),
      link_type: bannerForm.link_type,
      link_value: bannerForm.link_value.trim() || null,
      sort: bannerForm.sort,
      status: bannerForm.status,
      title: bannerForm.title.trim() || null,
    };
    if (bannerForm.id) await paopaoApi.updateBanner(bannerForm.id, body);
    else await paopaoApi.createBanner(body);
    message.success('Banner 已保存');
    bannerOpen.value = false;
    await load();
  } finally {
    saving.value = false;
  }
}

async function saveGrid() {
  if (!gridForm.title.trim()) {
    message.warning('请填写宫格标题');
    return;
  }
  saving.value = true;
  try {
    const body = {
      badge: gridForm.badge.trim() || null,
      icon: gridForm.icon.trim() || null,
      link_type: gridForm.link_type,
      link_value: gridForm.link_value.trim() || null,
      sort: gridForm.sort,
      status: gridForm.status,
      title: gridForm.title.trim(),
    };
    if (gridForm.id) await paopaoApi.updateGrid(gridForm.id, body);
    else await paopaoApi.createGrid(body);
    message.success('宫格已保存');
    gridOpen.value = false;
    await load();
  } finally {
    saving.value = false;
  }
}

async function removeBanner(row: BannerRow) {
  await paopaoApi.deleteBanner(row.id);
  message.success('已删除');
  await load();
}

async function removeGrid(row: GridRow) {
  await paopaoApi.deleteGrid(row.id);
  message.success('已删除');
  await load();
}

function statusColor(status: string) {
  return status === 'published' ? 'green' : 'default';
}

onMounted(load);
</script>

<template>
  <Page description="已发布的 Banner 和宫格会出现在用户端首页。草稿只在这里可见。" title="运营位">
    <p v-if="error" class="mb-4 text-sm text-[#b42318]">{{ error }}</p>
    <div class="grid min-w-0 gap-4">
      <Card class="min-w-0 overflow-hidden" title="Banner">
        <template #extra>
          <Button type="primary" @click="openBanner()">新建</Button>
        </template>
        <Table
          :columns="bannerColumns"
          :data-source="banners"
          :loading="loading"
          :pagination="false"
          :scroll="{ x: 680 }"
          row-key="id"
          size="middle"
        >
          <template #bodyCell="{ column, record }">
            <template v-if="column.dataIndex === 'image'">
              <img :src="record.image" alt="" class="h-10 w-16 rounded object-cover" />
            </template>
            <template v-else-if="column.dataIndex === 'title'">{{ record.title || '未命名' }}</template>
            <template v-else-if="column.dataIndex === 'link_type'">{{ linkTypeText[record.link_type] || record.link_type }}</template>
            <template v-else-if="column.dataIndex === 'status'">
              <Tag :color="statusColor(record.status)">{{ record.status === 'published' ? '已发布' : '草稿' }}</Tag>
            </template>
            <template v-else-if="column.dataIndex === 'action'">
              <div class="flex gap-1">
                <Button size="small" type="link" @click="openBanner(record)">编辑</Button>
                <Button danger size="small" type="link" @click="removeBanner(record)">删除</Button>
              </div>
            </template>
          </template>
        </Table>
      </Card>

      <Card class="min-w-0 overflow-hidden" title="宫格">
        <template #extra>
          <Button type="primary" @click="openGrid()">新建</Button>
        </template>
        <Table
          :columns="gridColumns"
          :data-source="grid"
          :loading="loading"
          :pagination="false"
          :scroll="{ x: 640 }"
          row-key="id"
          size="middle"
        >
          <template #bodyCell="{ column, record }">
            <template v-if="column.dataIndex === 'badge'">{{ record.badge || '—' }}</template>
            <template v-else-if="column.dataIndex === 'link_type'">{{ linkTypeText[record.link_type] || record.link_type }}</template>
            <template v-else-if="column.dataIndex === 'status'">
              <Tag :color="statusColor(record.status)">{{ record.status === 'published' ? '已发布' : '草稿' }}</Tag>
            </template>
            <template v-else-if="column.dataIndex === 'action'">
              <div class="flex gap-1">
                <Button size="small" type="link" @click="openGrid(record)">编辑</Button>
                <Button danger size="small" type="link" @click="removeGrid(record)">删除</Button>
              </div>
            </template>
          </template>
        </Table>
      </Card>
    </div>

    <Modal v-model:open="bannerOpen" :confirm-loading="saving" :title="bannerForm.id ? '编辑 Banner' : '新建 Banner'" :width="520" ok-text="保存" wrap-class-name="paopao-sheet" @ok="saveBanner">
      <Form class="min-w-0" layout="vertical">
        <Form.Item label="标题">
          <Input v-model:value="bannerForm.title" :maxlength="40" placeholder="显示在图片上的标题" />
        </Form.Item>
        <Form.Item label="图片" required>
          <div class="flex min-w-0 flex-col gap-2">
            <img v-if="bannerForm.image" :src="bannerForm.image" alt="" class="h-24 w-full max-w-full rounded-lg object-cover" />
            <Upload
              :custom-request="(options: any) => uploadTo('banner', options.file as File).then(() => options.onSuccess?.({})).catch((error: Error) => options.onError?.(error))"
              :show-upload-list="false"
              accept="image/jpeg,image/png,image/webp,image/gif"
            >
              <Button>上传图片</Button>
            </Upload>
          </div>
        </Form.Item>
        <Form.Item label="跳转">
          <div class="grid min-w-0 grid-cols-1 gap-2 sm:grid-cols-2">
            <Select v-model:value="bannerForm.link_type" :options="linkOptions" />
            <Input v-model:value="bannerForm.link_value" placeholder="链接、软件、帖子或版块编号" />
          </div>
        </Form.Item>
        <div class="grid grid-cols-2 gap-3">
          <Form.Item label="排序">
            <InputNumber v-model:value="bannerForm.sort" :max="9999" :min="0" class="w-full" />
          </Form.Item>
          <Form.Item label="状态">
            <Select v-model:value="bannerForm.status" :options="statusOptions" />
          </Form.Item>
        </div>
      </Form>
    </Modal>

    <Modal v-model:open="gridOpen" :confirm-loading="saving" :title="gridForm.id ? '编辑宫格' : '新建宫格'" :width="520" ok-text="保存" wrap-class-name="paopao-sheet" @ok="saveGrid">
      <Form class="min-w-0" layout="vertical">
        <Form.Item label="标题" required>
          <Input v-model:value="gridForm.title" :maxlength="12" placeholder="四个字以内更整齐" />
        </Form.Item>
        <Form.Item label="角标">
          <Input v-model:value="gridForm.badge" :maxlength="8" placeholder="可留空" />
        </Form.Item>
        <Form.Item label="图标">
          <div class="flex min-w-0 items-center gap-3">
            <img v-if="gridForm.icon && gridForm.icon.startsWith('http')" :src="gridForm.icon" alt="" class="h-10 w-10 rounded-lg object-cover" />
            <Upload
              :custom-request="(options: any) => uploadTo('grid', options.file as File).then(() => options.onSuccess?.({})).catch((error: Error) => options.onError?.(error))"
              :show-upload-list="false"
              accept="image/jpeg,image/png,image/webp,image/gif"
            >
              <Button>上传图标</Button>
            </Upload>
          </div>
        </Form.Item>
        <Form.Item label="跳转">
          <div class="grid min-w-0 grid-cols-1 gap-2 sm:grid-cols-2">
            <Select v-model:value="gridForm.link_type" :options="linkOptions" />
            <Input v-model:value="gridForm.link_value" placeholder="编号或网址" />
          </div>
        </Form.Item>
        <div class="grid grid-cols-2 gap-3">
          <Form.Item label="排序">
            <InputNumber v-model:value="gridForm.sort" :max="9999" :min="0" class="w-full" />
          </Form.Item>
          <Form.Item label="状态">
            <Select v-model:value="gridForm.status" :options="statusOptions" />
          </Form.Item>
        </div>
      </Form>
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
