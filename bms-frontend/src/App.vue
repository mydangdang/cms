<script setup lang="ts">
import { RouterView, useRoute } from 'vue-router'
import { computed, onMounted, watch } from 'vue'
import Header from '@/components/Layout/Header.vue'
import Sidebar from '@/components/Layout/Sidebar.vue'
import Tabs from '@/components/Layout/Tabs.vue'
import { useTabsStore, type Tab } from '@/store/modules/tabs'

const route = useRoute()
const tabsStore = useTabsStore()

// 只在非登录页显示布局（使用 route.meta 更可靠）
const showLayout = computed(() => {
  return route.meta.requiresAuth !== false && route.path !== '/login'
})

// 监听路由变化，自动添加 tab
watch(
  () => route.path,
  (newPath) => {
    if (showLayout.value) {
      // 只在路由信息完整时添加标签（避免刷新时创建格式错误的标签）
      if (route.meta?.title && route.name) {
        tabsStore.addTab(route)
      }
      tabsStore.setActiveTab(newPath)
    }
  }
)

// 初始化：确保首页标签存在且标题正确
onMounted(() => {
  const token = localStorage.getItem('token')
  if (token && showLayout.value) {
    try {
      // 定义首页标签（正确格式）
      const homeTab: Tab = {
        path: '/',
        name: 'Dashboard',
        title: '首页',
        affix: true
      }

      // 查找首页标签是否已存在
      const existingIndex = tabsStore.tabs.findIndex((t: Tab) => t.path === '/')

      if (existingIndex === -1) {
        // 不存在，添加到列表开头
        tabsStore.tabs.unshift(homeTab)
      } else {
        // 已存在，更新其属性（确保格式正确）
        tabsStore.tabs[existingIndex] = homeTab
      }

      // 激活当前页面标签
      tabsStore.setActiveTab(route.path)
    } catch (error) {
      console.error('标签初始化失败:', error)
    }
  }
})
</script>

<template>
  <div class="app-container">
    <!-- 登录页 -->
    <RouterView v-if="!showLayout" />

    <!-- 主布局 -->
    <div v-else class="layout-container">
      <!-- 侧边栏 -->
      <Sidebar />

      <!-- 右侧内容区域 -->
      <div class="layout-main">
        <!-- 顶部导航栏 -->
        <Header />

        <!-- 标签页 -->
        <Tabs />

        <!-- 主内容区域 -->
        <div class="layout-content">
          <RouterView />
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.app-container {
  height: 100vh;
  overflow: hidden;
}

.layout-container {
  display: flex;
  height: 100vh;
  overflow: hidden;
}

.layout-main {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.layout-content {
  flex: 1;
  padding: 20px;
  overflow: auto;
  background-color: #f0f2f5;
}
</style>
