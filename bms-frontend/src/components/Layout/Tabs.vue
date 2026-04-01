<template>
  <div class="tabs-bar" v-if="tabsStore.getTabs.length > 0">
    <div class="tabs-nav">
      <div
        v-for="tab in tabsStore.getTabs"
        :key="tab.path"
        class="tab-item"
        :class="{ 'is-active': tab.path === tabsStore.getActiveTab, 'is-affix': tab.affix }"
        @click="handleTabClick(tab)"
      >
        <span class="tab-title">{{ tab.title }}</span>
        <span v-if="!tab.affix" class="tab-close" @click.stop="handleTabClose(tab)">
          <el-icon><Close /></el-icon>
        </span>
      </div>
    </div>

    <!-- 操作按钮 -->
    <div class="tabs-actions">
      <el-dropdown @command="handleCommand" trigger="click">
        <el-icon class="tabs-action-icon">
          <ArrowDown />
        </el-icon>
        <template #dropdown>
          <el-dropdown-menu>
            <el-dropdown-item command="closeOther">关闭其他</el-dropdown-item>
            <el-dropdown-item command="closeAll">关闭所有</el-dropdown-item>
            <el-dropdown-item command="closeLeft">关闭左侧</el-dropdown-item>
            <el-dropdown-item command="closeRight">关闭右侧</el-dropdown-item>
          </el-dropdown-menu>
        </template>
      </el-dropdown>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useRouter, useRoute } from 'vue-router'
import { Close, ArrowDown } from '@element-plus/icons-vue'
import { useTabsStore } from '@/store/modules/tabs'

const router = useRouter()
const route = useRoute()
const tabsStore = useTabsStore()

/**
 * 点击 tab
 */
const handleTabClick = (tab: { path: string; title: string }) => {
  if (tab.path !== route.path) {
    router.push(tab.path)
  }
}

/**
 * 关闭 tab
 */
const handleTabClose = (tab: { path: string; affix?: boolean }) => {
  // 如果是固定的 tab，不允许关闭
  if (tab.affix) {
    return
  }

  tabsStore.removeTab(tab.path)

  // 导航到新的激活 tab
  const newActiveTab = tabsStore.getActiveTab
  if (newActiveTab && newActiveTab !== route.path) {
    router.push(newActiveTab)
  }
}

/**
 * 处理下拉菜单命令
 */
const handleCommand = (command: string) => {
  const currentPath = route.path

  switch (command) {
    case 'closeOther':
      tabsStore.closeOtherTabs(currentPath)
      break
    case 'closeAll':
      tabsStore.closeAllTabs()
      // 导航到第一个 tab 或首页
      {
        const firstTab = tabsStore.getTabs[0]
        if (firstTab) {
          router.push(firstTab.path)
        } else {
          router.push('/')
        }
      }
      break
    case 'closeLeft':
      tabsStore.closeLeftTabs(currentPath)
      break
    case 'closeRight':
      tabsStore.closeRightTabs(currentPath)
      break
  }
}
</script>

<style scoped lang="scss">
.tabs-bar {
  display: flex;
  align-items: center;
  height: 40px;
  padding: 0 20px;
  background-color: #fff;
  border-bottom: none;
  box-shadow: none;

  .tabs-nav {
    flex: 1;
    display: flex;
    align-items: center;
    overflow-x: auto;
    overflow-y: hidden;
    gap: 6px;

    // 隐藏滚动条
    &::-webkit-scrollbar {
      display: none;
    }
    scrollbar-width: none;

    .tab-item {
      position: relative;
      display: flex;
      align-items: center;
      height: 24px;
      padding: 0 22px;
      margin-right: 5px;
      font-size: 13px;
      color: #606266;
      background-color: #f5f7fa;
      border: 1px solid #e4e7ed;
      border-radius: 4px;
      cursor: pointer;
      user-select: none;
      white-space: nowrap;
      transition: all 0.2s;

      &:hover {
        background-color: #ecf5ff;
        border-color: #b3d8ff;
        color: #409eff;
      }

      &.is-active {
        background-color: #409cfd;
        border-color: #409cfd;
        color: #fff;
      }

      &.is-affix {
        .tab-close {
          display: none;
        }
      }

      .tab-title {
        max-width: 120px;
        overflow: hidden;
        text-overflow: ellipsis;
      }

      .tab-close {
        position: absolute;
        right: 5px;
        top: 50%;
        transform: translateY(-50%);
        display: flex;
        align-items: center;
        justify-content: center;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        transition: all 0.2s;

        &:hover {
          background-color: rgba(0, 0, 0, 0.1);
        }

        .el-icon {
          font-size: 12px;
        }
      }
    }
  }

  .tabs-actions {
    margin-left: 10px;

    .tabs-action-icon {
      font-size: 16px;
      color: #606266;
      cursor: pointer;
      padding: 4px;
      border-radius: 4px;
      transition: all 0.2s;

      &:hover {
        background-color: #f5f7fa;
        color: #409eff;
      }
    }
  }
}
</style>
