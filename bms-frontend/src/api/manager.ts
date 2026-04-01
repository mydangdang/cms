import request from '@/utils/request'
import type { Role } from './role'

/**
 * 管理员数据结构
 */
export interface Manager {
  admin_id: number
  username: string
  real_name?: string
  mobile?: string
  password?: string
  status: number
  is_super: number
  last_login_ip?: string
  last_login_time?: number
  password_update_time?: number
  created_at: number
  updated_at: number
  role_ids?: number[]
  roles?: Role[]
}

/**
 * 管理员列表响应
 */
export interface ManagerListResponse {
  list: Manager[]
  total: number
}

/**
 * 获取管理员列表
 * GET /admin/manager/getList
 */
export const getManagerList = (params?: {
  username?: string
  real_name?: string
  mobile?: string
  status?: number
  page?: number
  limit?: number
}) => {
  return request.get<ManagerListResponse>('/admin/manager/getList', { params })
}

/**
 * 新增管理员
 * POST /admin/manager/add
 */
export const addManager = (data: Partial<Manager> & { password: string }) => {
  return request.post<{ admin_id: number }>('/admin/manager/add', data)
}

/**
 * 编辑管理员
 * POST /admin/manager/edit
 */
export const editManager = (data: Partial<Manager> & { admin_id: number }) => {
  return request.post('/admin/manager/edit', data)
}

/**
 * 删除管理员
 * POST /admin/manager/delete
 */
export const deleteManager = (admin_id: number) => {
  return request.post('/admin/manager/delete', { admin_id })
}

/**
 * 修改密码
 * POST /admin/manager/changePassword
 */
export const changePassword = (data: {
  old_password: string
  new_password: string
  confirm_password: string
}) => {
  return request.post('/admin/manager/changePassword', data)
}
