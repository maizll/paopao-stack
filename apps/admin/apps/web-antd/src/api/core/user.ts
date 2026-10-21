import type { UserInfo } from '@vben/types';

import { requestClient } from '#/api/request';

const roleText: Record<string, string> = {
  member: '成员',
  platform_super_admin: '平台超管',
  tenant_admin: '租户管理员',
};

interface Me {
  id: number;
  nickname: string;
  permissions?: string[];
  roles: string[];
}

/**
 * 当前登录用户。映射成 Vben 的 UserInfo。
 */
export async function getUserInfoApi() {
  const me = await requestClient.get<Me>('/auth/me');
  const role = me.roles?.[0] ?? '';

  const userInfo: UserInfo = {
    avatar: '',
    desc: roleText[role] || role || '运营',
    homePath: '/overview',
    realName: me.nickname,
    roles: me.roles,
    token: '',
    userId: String(me.id),
    username: me.nickname,
  };

  return userInfo;
}
