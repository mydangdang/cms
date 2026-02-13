import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { RouteLocationNormalized } from 'vue-router'

export interface Tab {
  path: string
  name: string
  title: string
  affix?: boolean
  noCache?: boolean // 是否禁用缓存
}

export const useTabsStore = defineStore('tabs', () => {
  // tabs 列表
  const tabs = ref<Tab[]>([])

  // 当前激活的 tab
  const activeTab = ref('')

  // Keep-Alive 缓存的组件名称列表
  const cachedViews = ref<string[]>([])

  // 获取所有 tabs
  const getTabs = computed(() => tabs.value)

  // 获取当前激活的 tab
  const getActiveTab = computed(() => activeTab.value)

  // 获取缓存的组件名称列表
  const getCachedViews = computed(() => cachedViews.value)

  /**
   * 添加缓存视图
   */
  const addCachedView = (name: string) => {
    if (name && !cachedViews.value.includes(name)) {
      cachedViews.value.push(name)
    }
  }

  /**
   * 移除缓存视图
   */
  const removeCachedView = (name: string) => {
    const index = cachedViews.value.indexOf(name)
    if (index > -1) {
      cachedViews.value.splice(index, 1)
    }
  }

  /**
   * 添加 tab
   */
  const addTab = (route: RouteLocationNormalized) => {
    const { path, name, meta } = route

    // 检查 tab 是否已存在
    const existingIndex = tabs.value.findIndex((tab) => tab.path === path)
    if (existingIndex > -1) {
      // tab 已存在，激活它
      activeTab.value = path
      return
    }

    // 创建新 tab
    const newTab: Tab = {
      path,
      name: String(name || path),
      title: String(meta?.title || '未命名'),
      affix: meta?.affix === true,
      noCache: meta?.noCache === true
    }

    tabs.value.push(newTab)
    activeTab.value = path
  }

  /**
   * 移除 tab
   */
  const removeTab = (targetPath: string) => {
    const targetIndex = tabs.value.findIndex((tab) => tab.path === targetPath)
    if (targetIndex === -1) return

    const targetTab = tabs.value[targetIndex]
    if (!targetTab) return

    // 如果移除的是固定 tab，不允许移除
    if (targetTab.affix) {
      return
    }

    // 从缓存中移除
    removeCachedView(targetTab.name)

    tabs.value.splice(targetIndex, 1)

    // 如果移除的是当前激活的 tab，激活相邻的 tab
    if (activeTab.value === targetPath) {
      if (tabs.value.length > 0) {
        // 激活右侧的 tab，如果没有则激活左侧
        const nextIndex = targetIndex >= tabs.value.length ? tabs.value.length - 1 : targetIndex
        const nextTab = tabs.value[nextIndex]
        if (nextTab) {
          activeTab.value = nextTab.path
        }
      } else {
        activeTab.value = ''
      }
    }
  }

  /**
   * 激活 tab
   */
  const setActiveTab = (path: string) => {
    activeTab.value = path
  }

  /**
   * 关闭其他 tabs
   */
  const closeOtherTabs = (targetPath: string) => {
    // 先移除需要关闭的 tab 的缓存
    tabs.value.forEach((tab) => {
      if (tab.path !== targetPath && !tab.affix) {
        removeCachedView(tab.name)
      }
    })

    tabs.value = tabs.value.filter((tab) => tab.path === targetPath || tab.affix)
    if (!tabs.value.find((tab) => tab.path === targetPath)) {
      activeTab.value = targetPath
    }
  }

  /**
   * 关闭所有 tabs（保留固定的）
   */
  const closeAllTabs = () => {
    // 先移除所有非固定 tab 的缓存
    tabs.value.forEach((tab) => {
      if (!tab.affix) {
        removeCachedView(tab.name)
      }
    })

    tabs.value = tabs.value.filter((tab) => tab.affix)
    const firstTab = tabs.value[0]
    if (firstTab) {
      activeTab.value = firstTab.path
    } else {
      activeTab.value = ''
    }
  }

  /**
   * 关闭左侧 tabs
   * 逻辑：保留固定 tab + 目标 tab 及其右侧的 tab
   */
  const closeLeftTabs = (targetPath: string) => {
    const targetIndex = tabs.value.findIndex((tab) => tab.path === targetPath)
    if (targetIndex === -1) return

    // 获取所有固定 tab
    const affixTabs = tabs.value.filter((tab) => tab.affix)

    // 获取目标 tab 及其右侧的所有 tab
    const rightTabs = tabs.value.slice(targetIndex)

    // 固定 tab 的路径集合，用于去重
    const affixPaths = new Set(affixTabs.map((tab) => tab.path))

    // 移除需要关闭的 tab 的缓存（目标 tab 左侧且非固定的）
    tabs.value.slice(0, targetIndex).forEach((tab) => {
      if (!tab.affix) {
        removeCachedView(tab.name)
      }
    })

    // 合并：固定 tab + 目标 tab 及其右侧的 tab（排除已在固定 tab 中的）
    tabs.value = [
      ...affixTabs,
      ...rightTabs.filter((tab) => !affixPaths.has(tab.path))
    ]

    // 如果当前激活的 tab 被关闭了，激活目标 tab
    if (!tabs.value.find((tab) => tab.path === activeTab.value)) {
      activeTab.value = targetPath
    }
  }

  /**
   * 关闭右侧 tabs
   * 逻辑：保留固定 tab + 目标 tab 及其左侧的 tab
   */
  const closeRightTabs = (targetPath: string) => {
    const targetIndex = tabs.value.findIndex((tab) => tab.path === targetPath)
    if (targetIndex === -1) return

    // 获取所有固定 tab
    const affixTabs = tabs.value.filter((tab) => tab.affix)

    // 获取目标 tab 及其左侧的所有 tab
    const leftTabs = tabs.value.slice(0, targetIndex + 1)

    // 固定 tab 的路径集合，用于去重
    const affixPaths = new Set(affixTabs.map((tab) => tab.path))

    // 移除需要关闭的 tab 的缓存（目标 tab 右侧且非固定的）
    tabs.value.slice(targetIndex + 1).forEach((tab) => {
      if (!tab.affix) {
        removeCachedView(tab.name)
      }
    })

    // 合并：固定 tab + 目标 tab 及其左侧的 tab（排除已在固定 tab 中的）
    tabs.value = [
      ...affixTabs,
      ...leftTabs.filter((tab) => !affixPaths.has(tab.path))
    ]

    // 如果当前激活的 tab 被关闭了，激活目标 tab
    if (!tabs.value.find((tab) => tab.path === activeTab.value)) {
      activeTab.value = targetPath
    }
  }

  return {
    tabs,
    activeTab,
    cachedViews,
    getTabs,
    getActiveTab,
    getCachedViews,
    addTab,
    addCachedView,
    removeCachedView,
    removeTab,
    setActiveTab,
    closeOtherTabs,
    closeAllTabs,
    closeLeftTabs,
    closeRightTabs
  }
})
