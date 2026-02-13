import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import type { RouteLocationNormalized } from 'vue-router'

export interface Tab {
  path: string
  name: string
  title: string
  affix?: boolean
}

export const useTabsStore = defineStore('tabs', () => {
  // tabs 列表
  const tabs = ref<Tab[]>([])

  // 当前激活的 tab
  const activeTab = ref('')

  // 获取所有 tabs
  const getTabs = computed(() => tabs.value)

  // 获取当前激活的 tab
  const getActiveTab = computed(() => activeTab.value)

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
      affix: meta?.affix === true
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

    // 如果移除的是固定 tab，不允许移除
    if (targetTab.affix) {
      return
    }

    tabs.value.splice(targetIndex, 1)

    // 如果移除的是当前激活的 tab，激活相邻的 tab
    if (activeTab.value === targetPath) {
      if (tabs.value.length > 0) {
        // 激活右侧的 tab，如果没有则激活左侧
        const nextIndex = targetIndex >= tabs.value.length ? tabs.value.length - 1 : targetIndex
        activeTab.value = tabs.value[nextIndex].path
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
    tabs.value = tabs.value.filter((tab) => tab.path === targetPath || tab.affix)
    if (!tabs.value.find((tab) => tab.path === targetPath)) {
      activeTab.value = targetPath
    }
  }

  /**
   * 关闭所有 tabs（保留固定的）
   */
  const closeAllTabs = () => {
    tabs.value = tabs.value.filter((tab) => tab.affix)
    if (tabs.value.length > 0) {
      activeTab.value = tabs.value[0].path
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
    getTabs,
    getActiveTab,
    addTab,
    removeTab,
    setActiveTab,
    closeOtherTabs,
    closeAllTabs,
    closeLeftTabs,
    closeRightTabs
  }
})
