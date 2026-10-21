<script lang="ts" setup>
import type { VxeTableGridOptions } from '#/adapter/vxe-table';

import { computed, reactive, ref } from 'vue';

import { Page } from '@vben/common-ui';
import { useUserStore } from '@vben/stores';

import { Alert, Button, Form, Input, message, Modal, Tag } from 'ant-design-vue';

import { useVbenVxeGrid } from '#/adapter/vxe-table';

import { paopaoApi, type TenantRow } from '../api';

const userStore = useUserStore();
const isPlatform = computed(() =>
  Boolean(userStore.userInfo?.roles?.includes('platform_super_admin')),
);
const creating = ref(false);
const form = reactive({ name: '', slug: '' });

const gridOptions: VxeTableGridOptions<TenantRow> = {
  columns: [
    { field: 'name', title: '名称' },
    { field: 'slug', title: '标识' },
    { field: 'status', slots: { default: 'status' }, title: '状态', width: 120 },
    { field: 'plan_code', title: '套餐', width: 120 },
  ],
  height: 'auto',
  pagerConfig: { enabled: false },
  proxyConfig: {
    ajax: {
      query: async () => {
        if (!isPlatform.value) return { items: [], total: 0 };
        const result = await paopaoApi.tenants();
        return { items: result.items, total: result.meta.total };
      },
    },
  },
  rowConfig: { keyField: 'id' },
  toolbarConfig: { refresh: true, zoom: true },
};

const [Grid, gridApi] = useVbenVxeGrid({ gridOptions });

async function createTenant() {
  await paopaoApi.createTenant({
    name: form.name.trim(),
    plan_code: 'free',
    slug: form.slug.trim(),
  });
  message.success('租户已开通');
  creating.value = false;
  form.name = '';
  form.slug = '';
  gridApi.reload();
}
</script>

<template>
  <Page auto-content-height description="平台超管开通圈子。租户管理员只能管理自己的租户。" title="租户">
    <Alert
      v-if="!isPlatform"
      message="租户开通只对平台超管开放。请用 super@paopao.local 登录，租户标识留空。"
      show-icon
      type="info"
    />
    <Grid v-else>
      <template #toolbar-tools>
        <Button type="primary" @click="creating = true">开通租户</Button>
      </template>
      <template #status="{ row }">
        <Tag :color="row.status === 'active' ? 'green' : 'default'">
          {{ row.status === 'active' ? '正常' : row.status }}
        </Tag>
      </template>
    </Grid>
    <Modal v-model:open="creating" title="开通租户" @ok="createTenant">
      <Form layout="vertical">
        <Form.Item label="名称" required>
          <Input v-model:value="form.name" placeholder="例如 海边论坛" />
        </Form.Item>
        <Form.Item label="标识" required>
          <Input v-model:value="form.slug" placeholder="小写字母，例如 seaside" />
        </Form.Item>
      </Form>
    </Modal>
  </Page>
</template>
