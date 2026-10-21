<script setup lang="ts">
import { onLaunch } from '@dcloudio/uni-app'

onLaunch(() => {
  const current = uni.getStorageSync('paopao.uni.tenant')
  if (!current) uni.setStorageSync('paopao.uni.tenant', 'demo')
})
</script>

<style>
page {
  background: #f6f3f0;
  color: #1f1a17;
  font-family: "PingFang SC", "Hiragino Sans GB", "Noto Sans SC", "Microsoft YaHei", sans-serif;
}

button.btn {
  background: #ff5c39;
  color: #ffffff;
  border-radius: 999px;
  border: none;
  font-size: 15px;
  line-height: 44px;
  height: 44px;
}

button.btn::after {
  border: none;
}

button.btn.ghost {
  background: #ffffff;
  color: #1f1a17;
}
</style>
