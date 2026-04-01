import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import {
  login as apiLogin,
  logout as apiLogout,
  type LoginParams,
  type AdminInfo,
} from '@/api/auth'
import { usePermissionStore } from './permission'
import type { Router } from 'vue-router'

/**
 * 管理员状态管理 Store
 * Story 1.3: 管理员登录认证
 * Story 1.6: 管理员登出功能
 */
export const useAdminStore = defineStore('admin', () => {
  // ========== 状态 ==========

  /**
   * JWT Token
   * 从 localStorage 初始化
   */
  const token = ref<string>(localStorage.getItem('token') || '')

  /**
   * 管理员信息
   * 从 localStorage 初始化
   */
  const adminInfo = ref<AdminInfo | null>(
    (() => {
      const stored = localStorage.getItem('adminInfo')
      if (!stored || stored === 'undefined' || stored === 'null') {
        return null
      }
      try {
        return JSON.parse(stored)
      } catch (e) {
        console.error('Failed to parse adminInfo from localStorage:', e)
        // 清除损坏的数据
        localStorage.removeItem('adminInfo')
        return null
      }
    })()
  )

  // ========== 计算属性 ==========

  /**
   * 是否已登录
   */
  const isLoggedIn = computed(() => !!token.value)

  /**
   * 是否为超级管理员
   */
  const isSuper = computed(() => adminInfo.value?.is_super === 1)

  // ========== 方法 ==========

  /**
   * 设置 Token
   * @param newToken 新的 JWT Token
   */
  const setToken = (newToken: string) => {
    token.value = newToken
    localStorage.setItem('token', newToken)
  }

  /**
   * 设置管理员信息
   * @param info 管理员信息
   */
  const setAdminInfo = (info: AdminInfo) => {
    adminInfo.value = info
    localStorage.setItem('adminInfo', JSON.stringify(info))
  }

  /**
   * 登录
   * @param params 登录参数
   * @returns Promise<ApiResponse>
   */
  const login = async (params: LoginParams) => {
    const res = await apiLogin(params)
    if (res.code === 200 && res.data) {
      setToken(res.data.token)
      setAdminInfo(res.data.adminInfo)
    }
    return res
  }

  /**
   * 登出
   * Story 1.6: 管理员登出功能
   * Story 2.7 AC10: 登出时清除动态路由和权限数据
   * 调用后端登出接口，清除本地状态，跳转到登录页
   *
   * @param router 可选的 Vue Router 实例，如果提供则使用 router.push，否则使用 window.location
   */
  const logout = async (router?: Router) => {
    try {
      // 调用后端登出接口
      await apiLogout()
    } catch (error) {
      // 即使接口失败，也清除本地状态
      console.error('登出接口调用失败:', error)
    } finally {
      // 清除权限数据和动态路由 - Story 2.7 AC10
      const permissionStore = usePermissionStore()
      permissionStore.clearPermissions()
      permissionStore.clearDynamicRoutes()

      // 清除本地状态
      token.value = ''
      adminInfo.value = null
      localStorage.removeItem('token')
      localStorage.removeItem('adminInfo')

      // 跳转到登录页
      // 如果提供了 router 实例，使用 router.push；否则使用 window.location
      if (router && typeof router.push === 'function') {
        router.push('/login')
      } else {
        // 回退到 window.location，确保跳转始终成功
        window.location.href = '/login'
      }
    }
  }

  /**
   * 获取 Token
   * @returns JWT Token
   */
  const getToken = (): string => {
    return token.value
  }

  /**
   * 获取管理员信息
   * @returns 管理员信息
   */
  const getAdminInfo = (): AdminInfo | null => {
    return adminInfo.value
  }

  /**
   * 重置管理员状态（用于错误处理）
   * 清除 token、管理员信息和本地存储，但不清除权限数据
   */
  const resetState = () => {
    token.value = ''
    adminInfo.value = null
    localStorage.removeItem('token')
    localStorage.removeItem('adminInfo')
  }

  return {
    // 状态
    token,
    adminInfo,

    // 计算属性
    isLoggedIn,
    isSuper,

    // 方法
    setToken,
    setAdminInfo,
    login,
    logout,
    getToken,
    getAdminInfo,
    resetState,
  }
})
