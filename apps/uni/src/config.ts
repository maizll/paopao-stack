/**
 * 用户端公网 API 的唯一配置。
 *
 * 打包 APK 或生产 H5 之前，只改下面这一行。
 * 页面、请求封装不要再写域名。
 *
 * 正式地址是 Render 上的固定 HTTPS 域名，见 docs/render.md。
 * 当前这一行仍是临时 Quick Tunnel，进程一停就会失效，不能当作最终地址。
 *
 * 本地 `npm run dev:h5` 不使用这个地址，请求走相对路径 /api，
 * 由 Vite 代理到本机 43125。
 */
export const API_BASE_URL = 'https://biggest-retreat-coated-hugo.trycloudflare.com'
