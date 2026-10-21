import {
  defineOverridesPreferences,
  definePreferencesExtension,
} from '@vben/preferences';

interface WebAntdPreferencesExtension {
  defaultTableSize: number;
  enableFormFullscreen: boolean;
  reportTitle: string;
  tenantMode: 'multi' | 'single';
}

/**
 * 只覆盖泡泡栈需要的项。布局、主题、标签栏仍用 Vben 默认。
 * 改完后如果界面没变，清一次本站 localStorage（命名空间 paopao-admin）。
 */
export const overridesPreferences = defineOverridesPreferences({
  app: {
    defaultHomePath: '/overview',
    enableCheckUpdates: false,
    locale: 'zh-CN',
    name: import.meta.env.VITE_APP_TITLE,
  },
  copyright: {
    companyName: '泡泡栈',
    companySiteLink: 'https://github.com/vbenjs/vue-vben-admin',
    date: '2026',
    enable: true,
  },
  logo: {
    source: '/paopao-logo.svg',
  },
});

export const preferencesExtension =
  definePreferencesExtension<WebAntdPreferencesExtension>({
    tabLabel: 'preferences.antd.tabLabel',
    title: 'preferences.antd.title',
    fields: [
      {
        component: 'switch',
        defaultValue: true,
        key: 'enableFormFullscreen',
        label: 'preferences.antd.fields.enableFormFullscreen.label',
        tip: 'preferences.antd.fields.enableFormFullscreen.tip',
      },
      {
        component: 'select',
        defaultValue: 'multi',
        key: 'tenantMode',
        label: 'preferences.antd.fields.tenantMode.label',
        options: [
          {
            label: 'preferences.antd.fields.tenantMode.options.single.label',
            value: 'single',
          },
          {
            label: 'preferences.antd.fields.tenantMode.options.multi.label',
            value: 'multi',
          },
        ],
      },
      {
        component: 'number',
        componentProps: {
          max: 200,
          min: 10,
          step: 10,
        },
        defaultValue: 20,
        key: 'defaultTableSize',
        label: 'preferences.antd.fields.defaultTableSize.label',
      },
      {
        component: 'input',
        defaultValue: '',
        key: 'reportTitle',
        label: 'preferences.antd.fields.reportTitle.label',
        placeholder: 'preferences.antd.fields.reportTitle.placeholder',
      },
    ],
  });
