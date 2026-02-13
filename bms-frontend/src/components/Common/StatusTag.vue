<template>
  <el-tag :type="getStatusTagType(status)">
    {{ getStatusText(status) }}
  </el-tag>
</template>

<script setup lang="ts">
import { getStatusTagType, getStatusText } from '@/utils/commonUtils'

/**
 * 状态标签组件
 * 用于显示启用/禁用状态的统一组件
 *
 * @example
 * <StatusTag :status="1" />  // 显示：启用 (绿色)
 * <StatusTag :status="0" />  // 显示：禁用 (红色)
 */

defineProps<{
  /** 状态值：0=禁用, 1=启用 */
  status: number
}>()
</script>

<style scoped>
/* 可根据需要添加自定义样式 */
</style>
