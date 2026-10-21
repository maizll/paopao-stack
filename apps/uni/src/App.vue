<script setup lang="ts">
import { onLaunch } from '@dcloudio/uni-app'

onLaunch(() => {
  const current = uni.getStorageSync('paopao.uni.tenant')
  if (!current) uni.setStorageSync('paopao.uni.tenant', 'demo')
})
</script>

<style>
page {
  background: #f8fafc;
  color: #16160e;
  font-family: "PingFang SC", "Hiragino Sans GB", "Noto Sans SC", "Microsoft YaHei", sans-serif;
}

button.btn,
button.publish {
  background: #ff2442;
  color: #ffffff;
  border-radius: 999px;
  border: none;
  font-size: 15px;
  line-height: 44px;
  height: 44px;
}

button.btn::after,
button.publish::after {
  border: none;
}

.ph {
  color: #c0c4cc;
}
</style>
