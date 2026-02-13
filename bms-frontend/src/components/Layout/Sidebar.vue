<template>
  <div class="sidebar" :class="{ 'is-collapsed': isCollapsed }">
    <!-- 折叠按钮和网站标题 -->
    <div class="sidebar__toggle" @click="toggleCollapse">
      <span class="website-title">BMS 后台管理系统</span>
      <el-icon class="toggle-icon">
        <component :is="isCollapsed ? Expand : Fold" />
      </el-icon>
    </div>

    <!-- 菜单 -->
    <el-menu
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
      <template v-for="menu in menuTree" :key="menu.permission_id">
        <!-- 目录类型 (有子菜单) -->
        <el-sub-menu v-if="menu.children && menu.children.length > 0" :index="String(menu.permission_id)">
          <template #title>
            <el-icon v-if="menu.icon">
              <component :is="getIcon(menu.icon)" />
            </el-icon>
            <span>{{ menu.title }}</span>
          </template>

          <!-- 子菜单 -->
          <template v-for="subMenu in menu.children" :key="subMenu.permission_id">
            <el-menu-item
              v-if="subMenu.type === 2"
              :index="subMenu.path"
              @click="handleMenuClick(subMenu)"
            >
              <el-icon v-if="subMenu.icon">
                <component :is="getIcon(subMenu.icon)" />
              </el-icon>
              <span>{{ subMenu.title }}</span>
            </el-menu-item>

            <!-- 三级目录（如果有） -->
            <el-sub-menu
              v-else-if="subMenu.children && subMenu.children.length > 0"
              :index="String(subMenu.permission_id)"
            >
              <template #title>
                <el-icon v-if="subMenu.icon">
                  <component :is="getIcon(subMenu.icon)" />
                </el-icon>
                <span>{{ subMenu.title }}</span>
              </template>

              <el-menu-item
                v-for="child in subMenu.children"
                :key="child.permission_id"
                :index="child.path"
              >
                <el-icon v-if="child.icon">
                  <component :is="getIcon(child.icon)" />
                </el-icon>
                <span>{{ child.title }}</span>
              </el-menu-item>
            </el-sub-menu>
          </template>
        </el-sub-menu>

        <!-- 菜单类型 (无子菜单) -->
        <el-menu-item
          v-else-if="menu.type === 2"
          :index="menu.path"
          @click="handleMenuClick(menu)"
        >
          <el-icon v-if="menu.icon">
            <component :is="getIcon(menu.icon)" />
          </el-icon>
          <span>{{ menu.title }}</span>
        </el-menu-item>
      </template>
    </el-menu>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { Expand, Fold } from '@element-plus/icons-vue'
import { usePermissionStore } from '@/store/modules/permission'
import type { Permission } from '@/api/permission'
import * as ElementPlusIconsVue from '@element-plus/icons-vue'
import { loadDynamicRoutes } from '@/router'

/**
 * 侧边栏菜单组件
 * Story 2.6: 动态菜单生成
 *
 * 功能：
 * - 根据用户权限动态生成菜单
 * - 只显示 type=1（目录）和 type=2（菜单）的权限
 * - 支持折叠/展开
 * - 支持多级菜单
 * - 使用 Element Plus 内置路由导航
 */

const router = useRouter()
const route = useRoute()
const permissionStore = usePermissionStore()

// 侧边栏折叠状态
const isCollapsed = ref(false)

// 当前激活的菜单
const activeMenu = computed(() => route.path)

// 默认展开的菜单（根据当前路由匹配）
const defaultOpenedMenus = computed(() => {
  const currentPath = route.path
  const opened: string[] = []

  // 递归查找包含当前路径的菜单
  const findParentMenu = (menus: Permission[], path: string): string | null => {
    for (const menu of menus) {
      if (menu.path === path) {
        return null // 找到了，这是叶子节点，返回 null
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

  const parentId = findParentMenu(permissionStore.menus, currentPath)
  if (parentId) {
    opened.push(parentId)
  }

  return opened
})

// 菜单树（只包含 type=1 和 type=2）
const menuTree = computed(() => {
  // permissionStore.menus 已经是树形结构，直接返回
  return permissionStore.menus
})

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
  router.push(menu.path).catch(err => {
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

    .website-title {
      display: none;
    }

    .toggle-icon {
      position: absolute;
      left: 50%;
      top: 50%;
      transform: translate(-50%, -50%);
    }
  }

  &__toggle {
    position: relative;
    display: flex;
    align-items: center;
    height: 50px;
    padding: 0 20px;
    color: #bfcbd9;
    cursor: pointer;
    transition: background-color 0.3s;

    &:hover {
      background-color: #263445;
    }

    .website-title {
      flex: 1;
      color: #fff;
      font-size: 18px;
      font-weight: 500;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .toggle-icon {
      position: absolute;
      right: 10px;
      top: 50%;
      transform: translateY(-50%);
      font-size: 18px;

      .el-icon {
        font-size: 18px;
      }
    }
  }

  // 第一大类菜单 (type=1 目录) - 一级分类
  :deep(.el-sub-menu__title) {
    background-color: #1b2c41 !important;
    color: #bfcbd9 !important;
    margin-bottom: 1px;

    &:hover {
      background-color: #1c2e45 !important;
    }
  }

  // 第二大类菜单 (type=2 菜单) - 激活背景色
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

  // 子菜单背景 - 激活背景色
  :deep(.el-menu--inline .el-menu-item) {
    background-color: #2d3e53 !important;
    color: #bfcbd9 !important;

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
