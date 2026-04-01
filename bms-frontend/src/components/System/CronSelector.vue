<template>
  <div class="cron-selector">
    <!-- 模式切换 -->
    <div class="cron-selector__mode-tabs">
      <el-radio-group v-model="mode" size="small">
        <el-radio-button value="simple">简单模式</el-radio-button>
        <el-radio-button value="advanced">高级模式</el-radio-button>
      </el-radio-group>
    </div>

    <!-- 简单模式：预设模板 -->
    <div v-show="mode === 'simple'" class="cron-selector__simple-mode">
      <div class="cron-selector__templates">
        <el-tag
          v-for="template in presets"
          :key="template.value"
          :type="currentValue === template.value ? 'primary' : 'info'"
          :effect="currentValue === template.value ? 'dark' : 'plain'"
          class="cron-selector__template-tag"
          @click="selectTemplate(template.value)"
        >
          {{ template.label }}
        </el-tag>
      </div>

      <!-- 当前选中表达式 -->
      <div class="cron-selector__current">
        <span class="cron-selector__label">当前表达式：</span>
        <el-tag type="success" effect="plain" size="large">
          <code class="cron-selector__code">{{ currentValue || '请选择模板' }}</code>
        </el-tag>
      </div>

      <!-- 下次执行时间预览 -->
      <div v-if="nextExecuteTime" class="cron-selector__next-time">
        <el-icon class="cron-selector__next-icon"><Clock /></el-icon>
        <span>下次执行：{{ nextExecuteTime }}</span>
      </div>
    </div>

    <!-- 高级模式：直接编辑 -->
    <div v-show="mode === 'advanced'" class="cron-selector__advanced-mode">
      <el-input
        v-model="advancedValue"
        placeholder="请输入 Cron 表达式，如: 0 2 * * *"
        clearable
        @input="handleAdvancedInput"
        @blur="validateCron"
      >
        <template #prepend>Cron</template>
      </el-input>

      <!-- 格式说明 -->
      <div class="cron-selector__format-hint">
        <span class="cron-selector__hint-label">格式说明：</span>
        <code>* * * * *</code>
        <span class="cron-selector__hint-detail"> 分 时 日 月 周 </span>
      </div>

      <!-- 快捷示例 -->
      <div class="cron-selector__examples">
        <div class="cron-selector__example-title">常用示例：</div>
        <div class="cron-selector__example-list">
          <el-link
            v-for="example in examples"
            :key="example.value"
            type="primary"
            :underline="false"
            @click="selectExample(example.value)"
          >
            {{ example.label }}: <code>{{ example.value }}</code>
          </el-link>
        </div>
      </div>

      <!-- 下次执行时间预览 -->
      <div v-if="nextExecuteTime" class="cron-selector__next-time">
        <el-icon class="cron-selector__next-icon"><Clock /></el-icon>
        <span>下次执行：{{ nextExecuteTime }}</span>
      </div>

      <!-- 验证错误提示 -->
      <div v-if="validationError" class="cron-selector__error">
        <el-icon class="cron-selector__error-icon"><Warning /></el-icon>
        <span>{{ validationError }}</span>
      </div>
    </div>

    <!-- Cron 表达式预览（始终显示） -->
    <div class="cron-selector__preview">
      <el-descriptions :column="1" size="small" border>
        <el-descriptions-item label="Cron 表达式">
          <code class="cron-selector__preview-code">{{ currentValue || '-' }}</code>
        </el-descriptions-item>
        <el-descriptions-item label="执行说明">
          {{ getExecuteDescription() }}
        </el-descriptions-item>
      </el-descriptions>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { Clock, Warning } from '@element-plus/icons-vue'
import { validateCron as validateCronApi } from '@/api/crontab'

/**
 * Cron 表达式选择器组件
 * Story 4.2: Cron 表达式选择器组件
 *
 * 功能：
 * - 简单模式：预设模板选择
 * - 高级模式：直接编辑 Cron 表达式
 * - 实时显示：下次执行时间预览
 * - 验证提示：Cron 格式错误时显示提示
 */

interface Props {
  modelValue?: string
  disabled?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: '',
  disabled: false,
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: string): void
  (e: 'change', value: string): void
  (e: 'validate', valid: boolean, error?: string): void
}>()

// 模式: simple=简单模式, advanced=高级模式
const mode = ref<'simple' | 'advanced'>('simple')

// 当前值
const currentValue = ref(props.modelValue)

// 高级模式输入值
const advancedValue = ref(props.modelValue)

// 下次执行时间
const nextExecuteTime = ref('')

// 验证错误信息
const validationError = ref('')

// 防抖定时器
let validateTimer: ReturnType<typeof setTimeout> | null = null

/**
 * 预设模板
 */
const presets = [
  { label: '每分钟', value: '* * * * *', desc: '每分钟执行一次' },
  { label: '每 5 分钟', value: '*/5 * * * *', desc: '每 5 分钟执行一次' },
  { label: '每小时', value: '0 * * * *', desc: '每小时的第 0 分钟执行' },
  { label: '每天凌晨 2 点', value: '0 2 * * *', desc: '每天凌晨 2 点执行' },
  { label: '每周一凌晨', value: '0 0 * * 1', desc: '每周一凌晨 0 点执行' },
  { label: '每月 1 号凌晨', value: '0 0 1 * *', desc: '每月 1 号凌晨 0 点执行' },
]

/**
 * 高级模式示例
 */
const examples = [
  { label: '每 10 分钟', value: '*/10 * * * *' },
  { label: '每天 12 点', value: '0 12 * * *' },
  { label: '工作日 9 点', value: '0 9 * * 1-5' },
  { label: '每月 15 号', value: '0 0 15 * *' },
  { label: '每季度 1 号', value: '0 0 1 1,4,7,10 *' },
]

/**
 * 选择模板
 */
const selectTemplate = (value: string) => {
  currentValue.value = value
  advancedValue.value = value
  emitValue(value)
  validateAndUpdate(value)
}

/**
 * 选择示例
 */
const selectExample = (value: string) => {
  advancedValue.value = value
  currentValue.value = value
  emitValue(value)
  validateAndUpdate(value)
}

/**
 * 高级模式输入处理
 */
const handleAdvancedInput = (value: string) => {
  currentValue.value = value
  emitValue(value)

  // 防抖验证
  if (validateTimer) {
    clearTimeout(validateTimer)
  }
  validateTimer = setTimeout(() => {
    validateAndUpdate(value)
  }, 500)
}

/**
 * 发出值变化事件
 */
const emitValue = (value: string) => {
  emit('update:modelValue', value)
  emit('change', value)
}

/**
 * 验证 Cron 表达式并更新预览
 */
const validateAndUpdate = async (cron: string) => {
  if (!cron || !cron.trim()) {
    nextExecuteTime.value = ''
    validationError.value = ''
    emit('validate', false, 'Cron 表达式不能为空')
    return
  }

  try {
    const res = await validateCronApi(cron.trim())

    if (res.code === 200) {
      nextExecuteTime.value = res.data?.next_execute_time_text || ''
      validationError.value = ''
      emit('validate', true)
    } else {
      nextExecuteTime.value = ''
      validationError.value = res.msg || 'Cron 表达式格式错误'
      emit('validate', false, validationError.value)
    }
  } catch (error: any) {
    nextExecuteTime.value = ''
    validationError.value = error.msg || '验证失败'
    emit('validate', false, validationError.value)
  }
}

/**
 * 高级模式失去焦点时验证
 */
const validateCron = () => {
  validateAndUpdate(advancedValue.value)
}

/**
 * 获取执行说明
 */
const getExecuteDescription = () => {
  if (!currentValue.value) {
    return '请选择或输入 Cron 表达式'
  }

  const preset = presets.find((p) => p.value === currentValue.value)
  if (preset) {
    return preset.desc
  }

  // 解析 Cron 表达式生成说明
  const parts = currentValue.value.split(' ')
  if (parts.length !== 5) {
    return '格式不正确，应为 5 段'
  }

  const [minute, hour] = parts

  let desc = ''

  // 解析分钟
  if (minute === '*') {
    desc += '每分钟'
  } else if (minute?.startsWith('*/')) {
    const interval = minute.replace('*/', '')
    desc += `每 ${interval} 分钟`
  } else {
    desc += `第 ${minute} 分钟`
  }

  // 解析小时
  if (hour === '*') {
    desc += '，每小时'
  } else if (hour?.startsWith('*/')) {
    const interval = hour.replace('*/', '')
    desc += `，每 ${interval} 小时`
  } else {
    desc += `，${hour} 点`
  }

  return desc
}

/**
 * 监听 props.modelValue 变化
 */
watch(
  () => props.modelValue,
  (newVal) => {
    currentValue.value = newVal
    advancedValue.value = newVal
  }
)

/**
 * 组件挂载时验证初始值
 */
onMounted(() => {
  if (props.modelValue) {
    validateAndUpdate(props.modelValue)
  }
})

/**
 * 暴露方法供父组件调用
 */
defineExpose({
  validate: () => validateAndUpdate(currentValue.value),
  getValue: () => currentValue.value,
  setValue: (value: string) => {
    currentValue.value = value
    advancedValue.value = value
    emitValue(value)
    validateAndUpdate(value)
  },
})
</script>

<style scoped lang="scss">
.cron-selector {
  &__mode-tabs {
    margin-bottom: 16px;
  }

  &__simple-mode {
    padding: 16px;
    background-color: var(--el-fill-color-light);
    border-radius: 4px;
  }

  &__templates {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 16px;
  }

  &__template-tag {
    cursor: pointer;
    user-select: none;

    &:hover {
      transform: translateY(-1px);
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
  }

  &__current {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
  }

  &__label {
    font-size: 14px;
    color: var(--el-text-color-secondary);
  }

  &__code {
    font-family: 'Courier New', monospace;
    font-size: 14px;
    font-weight: 600;
  }

  &__advanced-mode {
    padding: 16px;
    background-color: var(--el-fill-color-light);
    border-radius: 4px;
  }

  &__format-hint {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-top: 12px;
    padding: 8px 12px;
    background-color: var(--el-fill-color);
    border-radius: 4px;
    font-size: 13px;

    code {
      font-family: 'Courier New', monospace;
      padding: 2px 6px;
      background-color: var(--el-fill-color-light);
      border-radius: 3px;
    }
  }

  &__hint-label {
    color: var(--el-text-color-secondary);
  }

  &__hint-detail {
    color: var(--el-text-color-regular);
  }

  &__examples {
    margin-top: 16px;
  }

  &__example-title {
    font-size: 13px;
    color: var(--el-text-color-secondary);
    margin-bottom: 8px;
  }

  &__example-list {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;

    code {
      font-family: 'Courier New', monospace;
      font-size: 12px;
      padding: 2px 6px;
      background-color: var(--el-fill-color);
      border-radius: 3px;
    }
  }

  &__next-time {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 12px;
    padding: 8px 12px;
    background-color: var(--el-color-success-light-9);
    border: 1px solid var(--el-color-success-light-5);
    border-radius: 4px;
    color: var(--el-color-success);
    font-size: 13px;
  }

  &__next-icon {
    font-size: 16px;
  }

  &__error {
    display: flex;
    align-items: center;
    gap: 6px;
    margin-top: 12px;
    padding: 8px 12px;
    background-color: var(--el-color-danger-light-9);
    border: 1px solid var(--el-color-danger-light-5);
    border-radius: 4px;
    color: var(--el-color-danger);
    font-size: 13px;
  }

  &__error-icon {
    font-size: 16px;
  }

  &__preview {
    margin-top: 16px;
  }

  &__preview-code {
    font-family: 'Courier New', monospace;
    font-size: 13px;
    padding: 2px 8px;
    background-color: var(--el-fill-color);
    border-radius: 3px;
  }
}
</style>
