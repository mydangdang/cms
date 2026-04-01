<template>
  <div class="sidebar" :class="{ 'is-collapsed': isCollapsed }">
    <!-- 系统标题 + 当前顶级目录 -->
    <div class="sidebar__header">
      <div class="header-top">
        <span class="system-title" v-show="!isCollapsed">{{ appTitle }}</span>
        <el-icon class="toggle-icon" @click="toggleCollapse">
          <component :is="isCollapsed ? Expand : Fold" />
        </el-icon>
      </div>
      <div class="header-sub" v-show="!isCollapsed">{{ currentTopMenuTitle }}</div>
    </div>

    <!-- 菜单 -->
    <el-menu
      v-if="sidebarMenus.length > 0"
      key="menu"
      :default-active="activeMenu"
      :default-openeds="defaultOpenedMenus"
      :collapse="isCollapsed"
      :unique-opened="true"
      mode="vertical"
      background-color="#2d3e53"
      text-color="#bfcbd9"
      active-text-color="#409EFF"
    >
      <!-- 二级目录（有子菜单）- 可折叠 -->
      <template v-for="menu in sidebarMenus" :key="menu.permission_id">
        <el-sub-menu
          v-if="menu.children && menu.children.length > 0"
          :index="String(menu.permission_id)"
        >
          <template #title>
            <el-icon v-if="menu.icon">
              <component :is="getIcon(menu.icon)" />
            </el-icon>
            <span>{{ menu.title }}</span>
          </template>

          <!-- 三级菜单 -->
          <el-menu-item
            v-for="child in menu.children"
            :key="child.permission_id"
            :index="child.path"
            @click="handleMenuClick(child)"
          >
            <el-icon v-if="child.icon">
              <component :is="getIcon(child.icon)" />
            </el-icon>
            <span>{{ child.title }}</span>
          </el-menu-item>
        </el-sub-menu>

        <!-- 二级菜单（无子菜单）- 直接点击 -->
        <el-menu-item v-else-if="menu.type === 2" :index="menu.path" @click="handleMenuClick(menu)">
          <el-icon v-if="menu.icon">
            <component :is="getIcon(menu.icon)" />
          </el-icon>
          <span>{{ menu.title }}</span>
        </el-menu-item>
      </template>
    </el-menu>

    <!-- 空状态提示 -->
    <div v-else class="sidebar-empty">
      <span v-show="!isCollapsed">请选择顶部菜单</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { Expand, Fold } from '@element-plus/icons-vue'
import { usePermissionStore } from '@/store/modules/permission'
import type { Permission } from '@/api/permission'
import * as ElementPlusIconsVue from '@element-plus/icons-vue'
import { loadDynamicRoutes } from '@/router'

/**
 * 侧边栏菜单组件
 *
 * 功能：
 * - 显示当前选中顶级目录下的二级和三级菜单
 * - 二级菜单如果有子菜单则可折叠
 * - 支持折叠/展开
 */

const router = useRouter()
const route = useRoute()
const permissionStore = usePermissionStore()

// 系统名称（从环境变量读取）
const appTitle = import.meta.env.VITE_APP_TITLE

// 侧边栏折叠状态
const isCollapsed = ref(false)

// 当前激活的菜单
const activeMenu = computed(() => route.path)

// 当前顶级菜单标题（如果没有选中则显示默认提示）
const currentTopMenuTitle = computed(() => {
  const topMenu = permissionStore.topMenus.find(
    (m) => m.permission_id === permissionStore.activeTopMenuId
  )
  return topMenu?.title || '请选择菜单'
})

// 侧边栏菜单（二级和三级）
const sidebarMenus = computed(() => {
  return permissionStore.sidebarMenus
})

// 默认展开的菜单（根据当前路由匹配）
const defaultOpenedMenus = computed(() => {
  const currentPath = route.path
  const opened: string[] = []

  // 递归查找包含当前路径的菜单
  const findParentMenu = (menus: Permission[], path: string): string | null => {
    for (const menu of menus) {
      if (menu.path === path) {
        return null // 找到了，这是叶子节点
      }
      if (menu.children && menu.children.length > 0) {
        const found = findParentMenu(menu.children, path)
        if (found !== null || menu.children.some((child: Permission) => child.path === path)) {
          return String(menu.permission_id) // 返回父菜单的 ID
        }
      }
    }
    return null
  }

  const parentId = findParentMenu(sidebarMenus.value, currentPath)
  if (parentId) {
    opened.push(parentId)
  }

  return opened
})

// 监听路由变化，更新激活的顶级目录
watch(
  () => route.path,
  (newPath) => {
    if (permissionStore.loaded) {
      permissionStore.setActiveTopMenuByPath(newPath)
    }
  },
  { immediate: true }
)

// 监听权限加载完成，初始化激活的顶级目录
watch(
  () => permissionStore.loaded,
  (loaded) => {
    if (loaded && route.path) {
      permissionStore.setActiveTopMenuByPath(route.path)
    }
  }
)

/**
 * 获取图标组件
 */
const getIcon = (iconName: string) => {
  if (!iconName) return null
  return (ElementPlusIconsVue as Record<string, unknown>)[iconName] || null
}

/**
 * 切换折叠状态
 */
const toggleCollapse = () => {
  isCollapsed.value = !isCollapsed.value
}

/**
 * 处理菜单点击
 * 确保动态路由已加载后再导航
 */
const handleMenuClick = async (menu: Permission) => {
  if (!menu.path) return

  // 确保权限和路由已加载
  if (!permissionStore.loaded) {
    await permissionStore.loadPermissions()
    await loadDynamicRoutes()
  }

  // 导航到目标页面
  router.push(menu.path).catch((err) => {
    console.error('Router push error:', err)
  })
}
</script>

<style scoped lang="scss">
.sidebar {
  width: 240px;
  height: 100vh;
  background-color: #2d3e53;
  transition: width 0.3s;
  overflow-x: hidden;
  overflow-y: auto;

  &.is-collapsed {
    width: 64px;

    .system-title,
    .header-sub {
      display: none;
    }

    .toggle-icon {
      position: absolute;
      left: 50%;
      top: 50%;
      transform: translate(-50%, -50%);
    }
  }

  &__header {
    position: relative;
    padding: 0 20px;
    color: #fff;
    background-color: #1b2c41;
    border-bottom: 1px solid #152238;

    .header-top {
      display: flex;
      align-items: center;
      justify-content: space-between;
      height: 50px;
    }

    .system-title {
      flex: 1;
      font-size: 16px;
      font-weight: 600;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .toggle-icon {
      font-size: 18px;
      cursor: pointer;
      padding: 4px;
      border-radius: 4px;
      transition: background-color 0.3s;

      &:hover {
        background-color: #425268;
      }

      .el-icon {
        font-size: 18px;
      }
    }

    .header-sub {
      padding: 8px 0 12px;
      font-size: 13px;
      color: #909399;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      border-top: 1px solid #2d3e53;
    }
  }

  .sidebar-empty {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100px;
    color: #909399;
    font-size: 14px;
  }

  // 二级目录菜单（可折叠）
  :deep(.el-sub-menu__title) {
    background-color: #2d3e53 !important;
    color: #bfcbd9 !important;
    margin-bottom: 1px;

    &:hover {
      background-color: #1c887b !important;
      color: #fff !important;
    }
  }

  // 菜单项
  :deep(.el-menu-item) {
    background-color: #2d3e53 !important;
    color: #bfcbd9 !important;
    margin-bottom: 1px;

    &:hover {
      background-color: #1c887b !important;
      color: #fff !important;
    }

    &.is-active {
      background-color: #1c887b !important;
      color: #fff !important;
    }
  }

  // 子菜单背景
  :deep(.el-menu--inline .el-menu-item) {
    background-color: #1b2c41 !important;
    color: #bfcbd9 !important;
    padding-left: 50px !important;

    &:hover {
      background-color: #1c887b !important;
      color: #fff !important;
    }

    &.is-active {
      background-color: #1c887b !important;
      color: #fff !important;
    }
  }

  :deep(.el-menu) {
    border-right: none;
  }

  // 展开子菜单的容器背景
  :deep(.el-sub-menu .el-menu) {
    background-color: #2d3e53 !important;
  }
}
</style>
