import { defineStore } from 'pinia'
import { ref } from 'vue'
import type { Permission, PermissionType } from '@/api/permission'
import { getUserPermissions } from '@/api/permission'

/**
 * 权限 Store
 * Story 2.6: 动态菜单生成
 */
export const usePermissionStore = defineStore('permission', () => {
  // ========== State ==========
  /**
   * 所有权限（树形结构）
   */
  const permissions = ref<Permission[]>([])

  /**
   * 菜单权限（type=1,2）
   */
  const menus = ref<Permission[]>([])

  /**
   * 路由权限（type=2）
   */
  const routes = ref<Permission[]>([])

  /**
   * 按钮权限编码列表（type=3）
   */
  const buttonCodes = ref<string[]>([])

  /**
   * 接口权限编码列表（type=4）
   */
  const apiCodes = ref<string[]>([])

  /**
   * 权限是否已加载
   */
  const loaded = ref(false)

  /**
   * 权限是否正在加载
   */
  const loading = ref(false)

  /**
   * 存储正在进行的加载 Promise
   * 用于避免并发请求和等待正在进行的加载完成
   */
  let loadPromise: Promise<void> | null = null

  // ========== Actions ==========

  /**
   * 加载用户权限
   * Story 2.4: 获取用户权限 API
   * Story 2.6: 动态菜单生成
   *
   * 如果已加载完成，立即返回
   * 如果正在加载，等待现有的加载完成
   * 如果未加载，开始新的加载
   */
  const loadPermissions = async () => {
    // 如果已加载完成，直接返回
    if (loaded.value) {
      return
    }

    // 如果正在加载，等待现有的 Promise
    if (loading.value && loadPromise) {
      return loadPromise
    }

    // 开始新的加载
    loading.value = true

    loadPromise = (async () => {
      try {
        const response = await getUserPermissions()
        permissions.value = response.data || []

        // 解析权限
        parsePermissions(permissions.value)

        loaded.value = true
      } catch (error) {
        console.error('加载权限失败:', error)
        // 失败时设置为空数组
        permissions.value = []
        menus.value = []
        routes.value = []
        buttonCodes.value = []
        apiCodes.value = []
      } finally {
        loading.value = false
        loadPromise = null
      }
    })()

    return loadPromise
  }

  /**
   * 解析权限数据
   * 将树形权限分类为菜单、路由、按钮、接口权限
   * Story 2.6 AC3: menus 保持树形结构，routes 扁平化用于动态路由注册
   */
  const parsePermissions = (permissionTree: Permission[]) => {
    const flatPermissions = flattenPermissions(permissionTree)

    // 菜单权限（type=1,2）保持树形结构 - Story 2.6 AC3
    menus.value = filterMenusTree(permissionTree)

    // 路由权限（type=2）扁平化用于动态注册
    routes.value = filterByTypes(flatPermissions, [2])

    // 提取按钮权限编码（type=3），过滤空值
    buttonCodes.value = flatPermissions
      .filter((p) => p.type === 3)
      .map((p) => p.code)
      .filter((code): code is string => !!code)

    // 提取接口权限编码（type=4），过滤空值
    apiCodes.value = flatPermissions
      .filter((p) => p.type === 4)
      .map((p) => p.code)
      .filter((code): code is string => !!code)
  }

  /**
   * 过滤菜单权限并保持树形结构
   * Story 2.6 AC3: 递归过滤 type=1（目录）和 type=2（菜单），保留 children
   */
  const filterMenusTree = (permissions: Permission[]): Permission[] => {
    return permissions
      .filter((p) => p.status === 1) // 只取启用的权限
      .filter((p) => p.type === 1 || p.type === 2) // 只取目录和菜单
      .map((p) => ({
        ...p,
        children: p.children && p.children.length > 0
          ? filterMenusTree(p.children)
          : undefined
      }))
  }

  /**
   * 将树形权限展平
   */
  const flattenPermissions = (tree: Permission[]): Permission[] => {
    const result: Permission[] = []

    const traverse = (nodes: Permission[]) => {
      for (const node of nodes) {
        result.push(node)
        if (node.children && node.children.length > 0) {
          traverse(node.children)
        }
      }
    }

    traverse(tree)
    return result
  }

  /**
   * 按类型过滤权限
   */
  const filterByTypes = (permissions: Permission[], types: PermissionType[]): Permission[] => {
    return permissions.filter((p) => types.includes(p.type))
  }

  /**
   * 检查是否有指定按钮权限
   * Story 2.8: 按钮权限与路由守卫
   */
  const hasButtonPermission = (code: string): boolean => {
    return buttonCodes.value.includes(code)
  }

  /**
   * 检查是否有任意一个权限
   */
  const hasAnyPermission = (codes: string[]): boolean => {
    return codes.some((code) => buttonCodes.value.includes(code))
  }

  /**
   * 检查是否有所有权限
   */
  const hasAllPermissions = (codes: string[]): boolean => {
    return codes.every((code) => buttonCodes.value.includes(code))
  }

  /**
   * 清除权限缓存
   * Story 2.7 AC10: 登出时清除权限数据和动态路由
   */
  const clearPermissions = () => {
    permissions.value = []
    menus.value = []
    routes.value = []
    buttonCodes.value = []
    apiCodes.value = []
    loaded.value = false
  }

  /**
   * 清除动态路由
   * Story 2.7 AC10: 需要动态导入 Router 实例来清除路由
   * 注意：此方法需要在有 Router 上下文的地方调用
   */
  const clearDynamicRoutes = async () => {
    // 动态导入 Router
    const { default: router } = await import('@/router')

    // 清除所有动态注册的路由（排除静态路由）
    const staticRouteNames = ['Login', 'Dashboard']

    // 获取所有已注册的路由
    const routes = router.getRoutes()

    // 移除动态注册的路由
    routes.forEach((route) => {
      if (route.name && !staticRouteNames.includes(route.name as string)) {
        router.removeRoute(route.name as string)
      }
    })
  }

  return {
    // State
    permissions,
    menus,
    routes,
    buttonCodes,
    apiCodes,
    loaded,
    loading,
    // Actions
    loadPermissions,
    parsePermissions,
    hasButtonPermission,
    hasAnyPermission,
    hasAllPermissions,
    clearPermissions,
    clearDynamicRoutes
  }
})
