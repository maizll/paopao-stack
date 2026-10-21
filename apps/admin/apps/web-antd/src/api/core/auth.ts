import { requestClient } from '#/api/request';

export namespace AuthApi {
  export interface LoginParams {
    password?: string;
    username?: string;
  }

  export interface LoginResult {
    accessToken: string;
  }
}

/**
 * 泡泡栈管理员登录。响应壳由拦截器拆掉，这里拿到 data。
 */
export async function loginApi(data: AuthApi.LoginParams) {
  const result = await requestClient.post<{ access_token: string }>(
    '/admin/auth/login',
    {
      account: data.username,
      password: data.password,
    },
  );

  return {
    accessToken: result.access_token,
  };
}

export async function logoutApi() {
  return requestClient.post('/admin/auth/logout', {});
}

/**
 * 泡泡栈用 Sanctum 过期重新登录，不刷新令牌。保留函数以免请求层引用断裂。
 */
export async function refreshTokenApi() {
  return { data: '', status: 401 };
}

export async function getAccessCodesApi() {
  const me = await requestClient.get<{ permissions?: string[] }>('/auth/me');
  return me.permissions ?? [];
}
