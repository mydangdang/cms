import request from '@/utils/request'
import type { Role } from './role'

/**
 * 管理员数据结构
 */
export interface Admin {
  admin_id: number
  username: string
  real_name?: string
  mobile?: string
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
export interface AdminListResponse {
  list: Admin[]
  total: number
}

/**
 * 获取管理员列表
 * GET /admin/admin/getList
 */
export const getAdminList = (params?: {
  username?: string
  real_name?: string
  mobile?: string
  status?: number
  page?: number
  limit?: number
}) => {
  return request.get<AdminListResponse>('/admin/admin/getList', { params })
}

/**
 * 获取管理员详情
 * GET /admin/admin/getDetail
 */
export const getAdminDetail = (admin_id: number) => {
  return request.get<Admin>('/admin/admin/getDetail', { params: { admin_id } })
}

/**
 * 新增管理员
 * POST /admin/admin/add
 */
export const addAdmin = (data: Partial<Admin> & { password: string }) => {
  return request.post<{ admin_id: number }>('/admin/admin/add', data)
}

/**
 * 编辑管理员
 * POST /admin/admin/edit
 */
export const editAdmin = (data: Partial<Admin> & { admin_id: number }) => {
  return request.post('/admin/admin/edit', data)
}

/**
 * 删除管理员
 * POST /admin/admin/delete
 */
export const deleteAdmin = (admin_id: number) => {
  return request.post('/admin/admin/delete', { admin_id })
}

/**
 * 分配角色
 * POST /admin/admin/assignRole
 */
export const assignRole = (admin_id: number, role_ids: number[]) => {
  return request.post('/admin/admin/assignRole', { admin_id, role_ids })
}

/**
 * 修改密码
 * POST /admin/admin/changePassword
 */
export const changePassword = (data: {
  old_password: string
  new_password: string
  confirm_password: string
}) => {
  return request.post('/admin/admin/changePassword', data)
}
