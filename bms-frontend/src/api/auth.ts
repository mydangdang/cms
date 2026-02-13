import request from '@/utils/request'

/**
 * 登录请求参数
 */
export interface LoginParams {
  username: string
  password: string
  captcha: string
}

/**
 * 登录响应数据
 */
export interface LoginResponse {
  token: string
  adminInfo: AdminInfo
}

/**
 * 管理员信息
 */
export interface AdminInfo {
  admin_id: number
  username: string
  real_name?: string
  nickname?: string
  avatar?: string
  mobile?: string
  email?: string
  status: number
  is_super: number
  last_login_ip?: string
  last_login_time?: number
  created_at: number
  updated_at: number
}

/**
 * 登录 API
 * POST /admin/login/submit
 *
 * @param params 登录参数
 * @returns Promise<ApiResponse<LoginResponse>>
 */
export const login = (params: LoginParams) => {
  return request.post<LoginResponse>('/admin/login/submit', params)
}

/**
 * 登出 API
 * Story 1.6: 用户登出功能
 * POST /admin/login/logout
 */
export const logout = () => {
  return request.post('/admin/login/logout')
}

/**
 * 获取当前管理员信息 API
 * Story 1.4: Token 验证中间件后可用
 * GET /admin/login/info
 */
export const getAdminInfo = () => {
  return request.get<AdminInfo>('/admin/login/info')
}
