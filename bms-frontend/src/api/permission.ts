import request from '@/utils/request'

/**
 * 权限类型枚举
 */
export enum PermissionType {
  Directory = 1, // 目录
  Menu = 2, // 菜单/页面
  Button = 3, // 按钮
  Api = 4, // 接口
}

/**
 * 权限项数据结构
 */
export interface Permission {
  permission_id: number
  parent_id: number
  title: string
  code: string
  type: PermissionType
  path?: string
  component?: string
  icon?: string
  is_hidden: number
  is_affix: number
  is_cache: number // 是否开启缓存：0=否，1=是
  sort_order: number
  status: number
  children?: Permission[]
}

/**
 * 权限列表响应
 */
export interface PermissionListResponse {
  list: Permission[]
  total: number
}

/**
 * 获取权限列表（树形）
 * GET /admin/permission/getList
 */
export const getPermissionList = (params?: { type?: number; status?: number }) => {
  return request.get<Permission[]>('/admin/permission/getList', { params })
}

/**
 * 新增权限
 * POST /admin/permission/add
 */
export const addPermission = (data: Partial<Permission>) => {
  return request.post<{ permission_id: number }>('/admin/permission/add', data)
}

/**
 * 编辑权限
 * POST /admin/permission/edit
 */
export const editPermission = (data: Partial<Permission> & { permission_id: number }) => {
  return request.post('/admin/permission/edit', data)
}

/**
 * 删除权限
 * POST /admin/permission/delete
 */
export const deletePermission = (permission_id: number) => {
  return request.post('/admin/permission/delete', { permission_id })
}

/**
 * 更新权限排序
 * POST /admin/permission/resort
 * @param permission_id 权限ID
 * @param sort_order 排序值
 */
export const sortPermission = (permission_id: number, sort_order: number) => {
  return request.post('/admin/permission/resort', { permission_id, sort_order })
}

/**
 * 获取当前用户权限列表
 * GET /admin/permission/getUserPermissions
 * Story 2.4: 这是 BMS 系统最核心的接口
 */
export const getUserPermissions = () => {
  return request.get<Permission[]>('/admin/permission/getUserPermissions')
}
