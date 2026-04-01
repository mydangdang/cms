import request from '@/utils/request'
import type { Permission } from './permission'

/**
 * 角色数据结构
 */
export interface Role {
  role_id: number
  name: string
  description: string
  sort_order: number
  status: number
  created_at: number
  updated_at: number
  permission_ids?: number[]
  permissions?: Permission[]
}

/**
 * 角色列表响应
 */
export interface RoleListResponse {
  list: Role[]
  total: number
}

/**
 * 获取角色列表
 * GET /admin/role/getList
 */
export const getRoleList = (params?: {
  name?: string
  status?: number
  page?: number
  limit?: number
}) => {
  return request.get<RoleListResponse>('/admin/role/getList', { params })
}

/**
 * 新增角色
 * POST /admin/role/add
 */
export const addRole = (data: Partial<Role>) => {
  return request.post<{ role_id: number }>('/admin/role/add', data)
}

/**
 * 编辑角色
 * POST /admin/role/edit
 */
export const editRole = (data: Partial<Role> & { role_id: number }) => {
  return request.post('/admin/role/edit', data)
}

/**
 * 删除角色
 * POST /admin/role/delete
 */
export const deleteRole = (role_id: number) => {
  return request.post('/admin/role/delete', { role_id })
}

/**
 * 分配权限
 * POST /admin/role/assignPermission
 * @param role_id 角色ID
 * @param permission_ids 权限ID字符串（用 '-' 分隔，例如：'33-35-37-1-31'）
 */
export const assignPermission = (role_id: number, permission_ids: string) => {
  return request.post('/admin/role/assignPermission', { role_id, permission_ids })
}

/**
 * 更新角色排序
 * POST /admin/role/resort
 * @param role_id 角色ID
 * @param sort_order 排序值
 */
export const sortRole = (role_id: number, sort_order: number) => {
  return request.post('/admin/role/resort', { role_id, sort_order })
}
