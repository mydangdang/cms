import { usePermissionStore } from '@/store/modules/permission'

/**
 * 权限检查组合式函数
 * Story 2.8: 按钮权限与路由守卫
 *
 * 用于在组件中检查权限
 */
export const usePermission = () => {
  const permissionStore = usePermissionStore()

  /**
   * 检查是否有指定按钮权限
   */
  const hasPermission = (code: string): boolean => {
    return permissionStore.hasButtonPermission(code)
  }

  /**
   * 检查是否有任意一个权限
   */
  const hasAnyPermission = (codes: string[]): boolean => {
    return permissionStore.hasAnyPermission(codes)
  }

  /**
   * 检查是否有所有权限
   */
  const hasAllPermissions = (codes: string[]): boolean => {
    return permissionStore.hasAllPermissions(codes)
  }

  return {
    hasPermission,
    hasAnyPermission,
    hasAllPermissions
  }
}
