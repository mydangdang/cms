<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import { usePermission } from '@/composables/usePermission'

const props = defineProps<{
  modelValue: number | string
  sortApi?: (id: number, sortOrder: number) => Promise<any>
  idField?: string
  rowData?: Record<string, any>
  permission?: string
}>()

const emit = defineEmits<{
  'update:modelValue': [val: number | string]
  success: []
  error: [msg: string]
}>()

const { hasPermission } = usePermission()

// 权限检查
const hasSortPermission = computed(() => {
  if (props.permission) {
    return hasPermission(props.permission)
  }
  // 默认有 system:xxx:sort 权限
  return true
})

const localValue = ref(props.modelValue)
const loading = ref(false)

// 监听外部值变化
watch(
  () => props.modelValue,
  (newVal) => {
    localValue.value = newVal
  }
)

// 失去焦点时触发更新
const handleBlur = async () => {
  // 如果值没变，不调用 API
  if (localValue.value === props.modelValue) {
    return
  }

  // 如果没有排序 API 或没有权限，不处理
  if (!props.sortApi || !hasSortPermission.value) {
    return
  }

  loading.value = true

  try {
    // 获取行数据的 ID
    const id = props.rowData?.[props.idField || 'id']
    if (!id) {
      console.error('未找到行数据 ID')
      return
    }

    await props.sortApi(id, Number(localValue.value))

    emit('update:modelValue', localValue.value)
    emit('success')
  } catch (error: any) {
    // 失败时恢复原值
    localValue.value = props.modelValue
    emit('error', error?.msg || '排序更新失败')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="sortable-input">
    <!-- 有权限：显示输入框 -->
    <el-input
      v-if="hasSortPermission"
      v-model="localValue"
      :disabled="loading"
      size="small"
      @blur="handleBlur"
      @keyup.enter="handleBlur"
      class="sort-input"
    />
    <!-- 无权限：只显示文本 -->
    <span v-else class="sort-text">{{ modelValue }}</span>
  </div>
</template>

<style scoped lang="scss">
.sortable-input {
  width: 100%;

  .sort-input {
    width: 100%;

    :deep(.el-input__wrapper) {
      width: 100%;
    }
  }

  .sort-text {
    display: inline-block;
    min-width: 30px;
    padding: 0 4px;
  }
}
</style>
