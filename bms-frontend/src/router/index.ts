import { createRouter, createWebHistory } from 'vue-router'
import type { RouteRecordRaw } from 'vue-router'
import { ElMessage } from 'element-plus'
import { usePermissionStore } from '@/store/modules/permission'
import { useAdminStore } from '@/store/modules/admin'
import type { Permission } from '@/api/permission'

/**
 * 路由配置
 * Story 1.6: 创建路由系统
 * Story 2.7: 动态路由注册
 */

// 静态路由（不需要权限的路由）
const staticRoutes: RouteRecordRaw[] = [
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/auth/Login.vue'),
    meta: {
      title: '管理员登录',
      requiresAuth: false,
    },
  },
  // 注意：通配路由将在动态路由加载后添加
]

// 初始路由（包含首页）
const initialRoutes: RouteRecordRaw[] = [
  ...staticRoutes,
  {
    path: '/',
    name: 'Dashboard',
    component: () => import('@/views/Dashboard.vue'),
    meta: {
      title: '首页',
      affix: true,
      noCache: false, // 首页默认开启缓存
      requiresAuth: true,
    },
  },
]

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: initialRoutes,
})

/**
 * 动态加载路由
 * Story 2.7: 动态路由注册
 * Story 2.4: 根据用户权限动态注册路由
 */
const loadDynamicRoutes = async () => {
  const permissionStore = usePermissionStore()

  // 如果权限还未加载，先加载权限并等待完成
  // loadPermissions 现在会返回正在进行的 Promise，确保数据就绪
  if (!permissionStore.loaded) {
    await permissionStore.loadPermissions()
  }

  // 生成动态路由
  const dynamicRoutes = generateRoutes(permissionStore.routes)

  // 注册动态路由
  dynamicRoutes.forEach((route) => {
    // 避免重复注册
    if (!router.hasRoute(route.name!)) {
      router.addRoute(route)
    }
  })

  // 在动态路由注册后，添加通配路由（404处理）
  // 通配路由必须最后添加，确保不会拦截动态路由
  if (!router.hasRoute('NotFound')) {
    router.addRoute({
      path: '/:pathMatch(.*)*',
      name: 'NotFound',
      redirect: '/',
    })
  }
}

/**
 * 根据权限生成路由配置
 */
const generateRoutes = (permissions: Permission[]): RouteRecordRaw[] => {
  const routes: RouteRecordRaw[] = []

  permissions.forEach((permission) => {
    // 只处理菜单类型的权限（type=2）
    if (permission.type === 2 && permission.path && permission.component) {
      const route: RouteRecordRaw = {
        path: permission.path,
        name: permission.code || `route-${permission.permission_id}`,
        component: loadView(permission.component),
        meta: {
          title: permission.title,
          icon: permission.icon,
          hidden: permission.is_hidden === 1,
          affix: permission.is_affix === 1,
          noCache: permission.is_cache !== 1, // is_cache=1 时 noCache=false（启用缓存）
          permission_id: permission.permission_id,
        },
      }
      routes.push(route)
    }
  })

  return routes
}

/**
 * 动态加载视图组件
 * 处理 component 路径字符串
 *
 * 数据库中存储的格式：views/system/AdminList.vue
 * 需要转换为：../views/system/AdminList.vue
 */
const loadView = (componentPath: string) => {
  // 添加 .vue 扩展名（如果不存在）
  const addVueExtension = (path: string) => {
    return path.endsWith('.vue') ? path : path + '.vue'
  }

  // 处理 @/views 开头的路径
  if (componentPath.startsWith('@/views/')) {
    const path = componentPath.replace('@/', '')
    return () => import(`../${addVueExtension(path)}`)
  }

  // 处理 views/ 开头的路径（数据库中的格式）
  if (componentPath.startsWith('views/')) {
    const path = componentPath.replace(/^views\//, '')
    return () => import(`../views/${addVueExtension(path)}`)
  }

  // 其他情况，默认为 views/ 目录下的文件
  return () => import(`../views/${addVueExtension(componentPath)}`)
}

/**
 * 路由守卫 - 检查登录状态和动态加载路由
 * Story 2.7: 动态路由注册
 */
router.beforeEach(async (to, _from, next) => {
  const adminStore = useAdminStore()
  const permissionStore = usePermissionStore()
  const token = localStorage.getItem('token')

  // 设置页面标题
  if (to.meta.title) {
    document.title = to.meta.title as string
  }

  // 访问登录页
  if (to.path === '/login') {
    if (token) {
      // 已登录，跳转到首页
      next('/')
    } else {
      next()
    }
    return
  }

  // 检查是否需要登录
  if (to.meta.requiresAuth !== false && !token) {
    // 未登录，跳转到登录页
    next('/login')
    return
  }

  // 检查是否为静态路由（首页、登录页等）
  const staticRoutes = ['/', '/login', 'Dashboard', 'Login']
  const isStaticRoute = staticRoutes.includes(to.path) || staticRoutes.includes(to.name as string)

  // 判断是否为系统路由路径（动态路由的特征）
  const isSystemRoute = to.path.startsWith('/system/')

  // 需要加载路由的场景：
  // 1. 权限未加载（首次访问或刷新页面）
  // 2. 权限已加载但动态路由未注册（极端情况，如首次 loadDynamicRoutes 出现竞态）
  const needLoadRoutes =
    token &&
    (!permissionStore.loaded || (!isStaticRoute && isSystemRoute && to.matched.length === 0))

  if (needLoadRoutes) {
    try {
      await loadDynamicRoutes()
      // 设置当前激活的顶级目录
      permissionStore.setActiveTopMenuByPath(to.path)
      // 只有当路由仍未匹配时才重新导航，避免重复挂载组件
      if (to.matched.length === 0) {
        next({ ...to, replace: true })
        return
      }
    } catch (error) {
      console.error('加载路由失败:', error)
      ElMessage.error('权限加载失败，请重新登录')
      localStorage.removeItem('token')
      adminStore.resetState()
      permissionStore.clearPermissions()
      next('/login')
      return
    }
  }

  // 权限检查（Story 2.8: 路由守卫）
  // Story 2.7 AC7: 无权限页面访问时显示提示并跳转到首页
  if (token && to.meta.permission_id) {
    const hasPermission = permissionStore.routes.some(
      (p) => p.permission_id === to.meta.permission_id
    )

    if (!hasPermission) {
      ElMessage.warning('您没有访问该页面的权限')
      next('/')
      return
    }
  }

  next()
})

export default router

/**
 * 导出加载动态路由的方法
 * 用于权限变更后重新加载路由
 */
export const reloadRoutes = async () => {
  const permissionStore = usePermissionStore()
  permissionStore.clearPermissions()
  await loadDynamicRoutes()
}

/**
 * 导出 loadDynamicRoutes 供其他组件使用
 */
export { loadDynamicRoutes }
