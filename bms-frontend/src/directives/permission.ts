import type { Directive, DirectiveBinding } from 'vue'
import { usePermissionStore } from '@/store/modules/permission'

/**
 * 权限指令
 * Story 2.8: 按钮权限与路由守卫
 *
 * 用法：
 * <el-button v-permission="'system:admin:add'">新增</el-button>
 * <el-button v-permission="['system:admin:add', 'system:admin:edit']">操作</el-button>
 */
const permission: Directive = {
  mounted(el: HTMLElement, binding: DirectiveBinding) {
    const { value } = binding
    const permissionStore = usePermissionStore()

    if (value) {
      let hasPermission = false

      if (typeof value === 'string') {
        // 单个权限码
        hasPermission = permissionStore.hasButtonPermission(value)
      } else if (Array.isArray(value)) {
        // 多个权限码（满足任意一个即可）
        hasPermission = permissionStore.hasAnyPermission(value)
      }

      // 无权限时移除元素
      if (!hasPermission && el.parentNode) {
        el.parentNode.removeChild(el)
      }
    } else {
      throw new Error('权限指令需要提供权限码')
    }
  }
}

export default permission
