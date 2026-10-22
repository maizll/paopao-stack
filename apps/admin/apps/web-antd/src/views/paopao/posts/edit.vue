<script lang="ts" setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';

import { Page } from '@vben/common-ui';
import { VbenTiptap } from '@vben/plugins/tiptap';

import { Alert, Button, Card, Form, Input, message, Select, Spin, Tag, Upload } from 'ant-design-vue';

import {
  formatTime,
  paopaoApi,
  postStatusText,
  type BoardRow,
  type PostRow,
} from '../api';

const route = useRoute();
const router = useRouter();
const loading = ref(true);
const saving = ref(false);
const error = ref('');
const post = ref<null | PostRow>(null);
const boards = ref<BoardRow[]>([]);

const form = reactive({
  board_id: undefined as number | undefined,
  content: '',
  cover: '',
  title: '',
});

const postId = computed(() => Number(route.params.id));

function looksLikeHtml(value: string) {
  return /<[a-z][\s\S]*>/i.test(value);
}

function toEditorHtml(value: string) {
  if (!value) return '<p></p>';
  if (looksLikeHtml(value)) return value;
  return value
    .split(/\n{2,}/)
    .map((block) => `<p>${escapeHtml(block).replaceAll('\n', '<br>')}</p>`)
    .join('');
}

function escapeHtml(value: string) {
  return value
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;');
}

function plainText(html: string) {
  return html
    .replace(/<[^>]+>/g, '')
    .replaceAll('&nbsp;', ' ')
    .trim();
}

function statusColor(status: string) {
  if (status === 'pending') return 'gold';
  if (status === 'published') return 'green';
  return 'red';
}

async function load() {
  loading.value = true;
  error.value = '';
  try {
    const [detail, boardList] = await Promise.all([
      paopaoApi.post(postId.value),
      paopaoApi.boards(),
    ]);
    post.value = detail;
    boards.value = boardList;
    form.title = detail.title;
    form.board_id = detail.board?.id;
    form.cover = detail.cover || '';
    form.content = toEditorHtml(detail.content || '');
  } catch (cause) {
    error.value = cause instanceof Error ? cause.message : '读取失败';
  } finally {
    loading.value = false;
  }
}

async function save() {
  if (!post.value || !form.board_id) {
    message.warning('请选择版块');
    return;
  }
  if (!form.title.trim() || !plainText(form.content)) {
    message.warning('标题和正文都要填');
    return;
  }
  saving.value = true;
  try {
    post.value = await paopaoApi.updatePost(post.value.id, {
      board_id: form.board_id,
      content: form.content,
      cover: form.cover.trim() || null,
      title: form.title.trim(),
    });
    form.content = toEditorHtml(post.value.content || '');
    message.success('已保存。返回列表后仍可审核通过或拒绝。');
  } finally {
    saving.value = false;
  }
}

function back() {
  router.push('/posts');
}

const imageUpload = {
  accept: 'image/jpeg,image/png,image/webp,image/gif',
  maxSize: 5 * 1024 * 1024,
  upload: async (file: File) => {
    const result = await paopaoApi.uploadImage(file);
    return result.url;
  },
};

async function uploadCover(options: {
  file: File;
  onError?: (error: Error) => void;
  onSuccess?: (body: unknown) => void;
}) {
  try {
    const result = await paopaoApi.uploadImage(options.file);
    form.cover = result.url;
    options.onSuccess?.(result);
    message.success('封面已上传');
  } catch (cause) {
    const error = cause instanceof Error ? cause : new Error('上传失败');
    options.onError?.(error);
    message.error(error.message);
  }
}

onMounted(() => {
  void load();
});
</script>

<template>
  <Page auto-content-height description="改标题、版块、封面和正文。通过或拒绝留在审核列表。" title="编辑帖子">
    <template #extra>
      <div class="flex gap-2">
        <Button @click="back">返回列表</Button>
        <Button :loading="saving" type="primary" @click="save">保存</Button>
      </div>
    </template>

    <Spin :spinning="loading">
      <Alert v-if="error" :message="error" class="mb-4" show-icon type="error" />
      <template v-else-if="post">
        <div class="mb-4 flex flex-wrap items-center gap-2 text-sm text-black/45">
          <Tag :color="statusColor(post.status)">{{ postStatusText[post.status] || post.status }}</Tag>
          <span>{{ post.author?.nickname || '—' }}</span>
          <span>·</span>
          <span>{{ formatTime(post.created_at) }}</span>
          <span>状态只在审核列表里通过或拒绝，这里不改。</span>
        </div>
        <Card class="mb-4" title="基本信息">
          <Form layout="vertical">
            <div class="grid gap-4 md:grid-cols-2">
              <Form.Item class="md:col-span-2" label="标题" required>
                <Input v-model:value="form.title" :maxlength="80" placeholder="标题" />
              </Form.Item>
              <Form.Item label="版块" required>
                <Select
                  v-model:value="form.board_id"
                  :options="boards.map((board) => ({ label: board.name, value: board.id }))"
                  placeholder="选择版块"
                />
              </Form.Item>
              <Form.Item class="min-w-0" label="封面">
                <div class="flex min-w-0 flex-col gap-2">
                  <img
                    v-if="form.cover"
                    :src="form.cover"
                    alt=""
                    class="h-28 w-full max-w-full rounded-lg object-cover"
                  />
                  <div class="flex flex-wrap gap-2">
                    <Upload
                      :custom-request="uploadCover"
                      :show-upload-list="false"
                      accept="image/jpeg,image/png,image/webp,image/gif"
                    >
                      <Button>上传封面</Button>
                    </Upload>
                    <Button v-if="form.cover" @click="form.cover = ''">移除</Button>
                  </div>
                </div>
              </Form.Item>
            </div>
          </Form>
        </Card>
        <Card title="正文">
          <VbenTiptap
            v-model="form.content"
            :image-upload="imageUpload"
            :max-height="720"
            :min-height="480"
            placeholder="写正文。可以加粗、设标题、列清单、插链接，图片从本地上传。"
          />
          <p class="mt-3 text-xs text-black/45">
            正文按 HTML 保存。封面和正文图片都上传到本机存储。
          </p>
        </Card>
      </template>
    </Spin>
  </Page>
</template>
