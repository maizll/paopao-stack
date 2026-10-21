<script lang="ts" setup>
import { VbenAvatar } from '@vben-core/shadcn-ui';

interface Props {
  alt?: string;
  avatar?: string;
  stats?: { label: string; value: number | string }[];
}

defineOptions({
  name: 'WorkbenchHeader',
});

withDefaults(defineProps<Props>(), {
  alt: '',
  avatar: '',
  stats: () => [],
});
</script>
<template>
  <div class="card-box p-4 py-6 lg:flex">
    <VbenAvatar :alt="alt" :src="avatar" class="size-20" />
    <div
      v-if="$slots.title || $slots.description"
      class="flex flex-col justify-center md:mt-0 md:ml-6"
    >
      <h1 v-if="$slots.title" class="text-md font-semibold md:text-xl">
        <slot name="title"></slot>
      </h1>
      <span v-if="$slots.description" class="mt-1 text-foreground/80">
        <slot name="description"></slot>
      </span>
    </div>
    <div class="mt-4 flex flex-1 justify-end md:mt-0">
      <div
        v-for="item in stats"
        :key="item.label"
        class="mr-4 flex flex-col justify-center text-right md:mr-12"
      >
        <span class="text-foreground/80">{{ item.label }}</span>
        <span class="text-2xl">{{ item.value }}</span>
      </div>
    </div>
  </div>
</template>
