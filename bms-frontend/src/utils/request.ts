import axios from 'axios'
import { ElMessage } from 'element-plus'
import type { AxiosRequestConfig, AxiosResponse } from 'axios'

/**
 * API响应基础接口
 */
export interface ApiResponse<T = unknown> {
  code: number
  msg: string
  data?: T
}

/**
 * 无需 Token 的白名单接口
 * Story 1.6: 添加 /admin/login/logout 到白名单
 */
const WHITE_LIST = ['/admin/login/submit', '/admin/captcha']

/**
 * 创建axios实例
 * withCredentials: true - 允许跨域请求携带 Cookie（Session 需要）
 */
const instance = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || '/api',
  timeout: 10000,
  withCredentials: true, // 关键：允许跨域携带 Cookie
  headers: {
    'Content-Type': 'application/json',
  },
})

/**
 * 请求拦截器
 * 自动添加 JWT Token 到请求头
 */
instance.interceptors.request.use(
  (config) => {
    // 检查是否在白名单中
    const isInWhiteList = WHITE_LIST.some((path) => config.url?.includes(path))

    if (!isInWhiteList) {
      // 从 localStorage 获取 Token
      const token = localStorage.getItem('token')
      if (token) {
        config.headers.Authorization = `Bearer ${token}`
      }
    }

    return config
  },
  (error) => {
    console.error('Request error:', error)
    return Promise.reject(error)
  }
)

/**
 * 响应拦截器
 */
instance.interceptors.response.use(
  // @ts-expect-error - 我们返回 ApiResponse 而不是 AxiosResponse
  (response: AxiosResponse): ApiResponse | Promise<never> => {
    const data = response.data as ApiResponse

    // 检查业务状态码
    if (data.code === 200) {
      // 成功响应统一返回数据，不在此处显示消息提示
      // 具体操作（删除、编辑等）的成功提示由前端页面组件自行处理
      return data
    } else {
      ElMessage.error(data.msg || '请求失败')
      return Promise.reject(new Error(data.msg || '请求失败'))
    }
  },
  (error) => {
    console.error('Response error:', error)

    // 处理HTTP错误状态码
    if (error.response) {
      const { status } = error.response
      switch (status) {
        case 400:
          ElMessage.error(error.response.data?.msg || '请求参数错误')
          break
        case 401:
          ElMessage.error('登录已过期，请重新登录')
          // 清除 Token 和管理员信息
          localStorage.removeItem('token')
          localStorage.removeItem('adminInfo')
          // 跳转到登录页（使用 window.location 避免路由循环依赖）
          if (window.location.pathname !== '/login') {
            window.location.href = '/login'
          }
          break
        case 403:
          ElMessage.error('拒绝访问')
          break
        case 404:
          ElMessage.error('请求的资源不存在')
          break
        case 500:
          ElMessage.error('服务器内部错误')
          break
        default:
          ElMessage.error(error.response.data?.msg || '网络错误')
      }
    } else if (error.request) {
      ElMessage.error('网络连接失败，请检查网络')
    } else {
      ElMessage.error(error.message || '请求失败')
    }

    return Promise.reject(error)
  }
)

/**
 * 请求方法封装
 */
const request = {
  get<T = unknown>(url: string, config?: AxiosRequestConfig): Promise<ApiResponse<T>> {
    return instance.get(url, config)
  },

  post<T = unknown>(
    url: string,
    data?: unknown,
    config?: AxiosRequestConfig
  ): Promise<ApiResponse<T>> {
    return instance.post(url, data, config)
  },

  put<T = unknown>(
    url: string,
    data?: unknown,
    config?: AxiosRequestConfig
  ): Promise<ApiResponse<T>> {
    return instance.put(url, data, config)
  },

  delete<T = unknown>(url: string, config?: AxiosRequestConfig): Promise<ApiResponse<T>> {
    return instance.delete(url, config)
  },

  request<T = unknown>(config: AxiosRequestConfig): Promise<ApiResponse<T>> {
    return instance.request(config)
  },
}

export default request
