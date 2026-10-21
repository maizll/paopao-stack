<script lang="ts" setup>
import type { VbenFormSchema } from '@vben/common-ui';

import { computed } from 'vue';

import { AuthenticationLogin, z } from '@vben/common-ui';

import { useAuthStore } from '#/store';

defineOptions({ name: 'Login' });

const authStore = useAuthStore();

const accounts = [
  {
    label: '租户管理员',
    password: 'password',
    tenant: 'demo',
    username: 'admin@demo.paopao.local',
  },
  {
    label: '平台超管',
    password: 'password',
    tenant: '',
    username: 'super@paopao.local',
  },
];

const formSchema = computed((): VbenFormSchema[] => {
  return [
    {
      component: 'VbenSelect',
      componentProps: {
        options: accounts.map((item) => ({
          label: item.label,
          value: item.username,
        })),
        placeholder: '选择演示账号',
      },
      defaultValue: 'admin@demo.paopao.local',
      fieldName: 'selectAccount',
      label: '演示账号',
    },
    {
      component: 'VbenInput',
      componentProps: {
        placeholder: '平台超管可留空，审核 demo 内容时填 demo',
      },
      defaultValue: 'demo',
      dependencies: {
        trigger(values, form) {
          const found = accounts.find(
            (item) => item.username === values.selectAccount,
          );
          if (found) {
            form.setValues({
              password: found.password,
              tenant: found.tenant,
              username: found.username,
            });
          }
        },
        triggerFields: ['selectAccount'],
      },
      fieldName: 'tenant',
      label: '租户标识',
    },
    {
      component: 'VbenInput',
      componentProps: {
        placeholder: '邮箱账号',
      },
      defaultValue: 'admin@demo.paopao.local',
      fieldName: 'username',
      label: '账号',
      rules: z.string().min(1, { message: '请填写账号' }),
    },
    {
      component: 'VbenInputPassword',
      componentProps: {
        placeholder: '密码',
      },
      defaultValue: 'password',
      fieldName: 'password',
      label: '密码',
      rules: z.string().min(1, { message: '请填写密码' }),
    },
  ];
});

function onSubmit(values: Record<string, string>) {
  localStorage.setItem('paopao.admin.tenant', (values.tenant || '').trim());
  authStore.authLogin(values);
}
</script>

<template>
  <AuthenticationLogin
    :form-schema="formSchema"
    :loading="authStore.loginLoading"
    :show-code-login="false"
    :show-forget-password="false"
    :show-qrcode-login="false"
    :show-register="false"
    :show-third-party-login="false"
    sub-title="审核帖子和软件，开通租户。社区内容在 App 里看，不在这里浏览。"
    title="泡泡栈运营后台"
    @submit="onSubmit"
  />
</template>
