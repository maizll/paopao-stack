<script lang="ts" setup>
import type { VxeTableGridOptions } from '#/adapter/vxe-table';

import { reactive, ref } from 'vue';

import { Page } from '@vben/common-ui';

import { Button, Form, Input, message, Modal, Tag } from 'ant-design-vue';

import { useVbenVxeGrid } from '#/adapter/vxe-table';

import { paopaoApi, type BoardRow } from '../api';

const creating = ref(false);
const form = reactive({ name: '', slug: '' });

const gridOptions: VxeTableGridOptions<BoardRow> = {
  columns: [
    { field: 'name', title: '名称' },
    { field: 'slug', title: '标识' },
    { field: 'post_count', title: '帖子', width: 100 },
    { field: 'status', slots: { default: 'status' }, title: '状态', width: 120 },
  ],
  height: 'auto',
  pagerConfig: { enabled: false },
  proxyConfig: {
    ajax: {
      query: async () => {
        const items = await paopaoApi.boards();
        return { items, total: items.length };
      },
    },
  },
  rowConfig: { keyField: 'id' },
  toolbarConfig: { refresh: true, zoom: true },
};

const [Grid, gridApi] = useVbenVxeGrid({ gridOptions });

async function createBoard() {
  await paopaoApi.createBoard({
    name: form.name.trim(),
    slug: form.slug.trim(),
    status: 'published',
  });
  message.success('版块已创建');
  creating.value = false;
  form.name = '';
  form.slug = '';
  gridApi.reload();
}
</script>

<template>
  <Page auto-content-height description="版块是用户端社区的分区。这里只维护结构，不展示帖子正文。" title="版块">
    <Grid>
      <template #toolbar-tools>
        <Button type="primary" @click="creating = true">新建版块</Button>
      </template>
      <template #status="{ row }">
        <Tag :color="row.status === 'published' ? 'green' : 'default'">
          {{ row.status === 'published' ? '已发布' : row.status }}
        </Tag>
      </template>
    </Grid>
    <Modal v-model:open="creating" title="新建版块" @ok="createBoard">
      <Form layout="vertical">
        <Form.Item label="名称" required>
          <Input v-model:value="form.name" placeholder="例如 反馈" />
        </Form.Item>
        <Form.Item label="标识" required>
          <Input v-model:value="form.slug" placeholder="例如 feedback" />
        </Form.Item>
      </Form>
    </Modal>
  </Page>
</template>
