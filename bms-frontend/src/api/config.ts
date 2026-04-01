/**
 * 系统配置 API
 * Story 3.1: 系统配置管理（后端 API）
 * 优化：config_group 改为 tinyint，支持分页列表、新增、编辑、删除
 */
import request from '@/utils/request'

/**
 * 配置分组类型
 */
export enum ConfigGroup {
  Undefined = 0,
  Site = 1, // 站点设置
  Operation = 2, // 运营参数
  System = 3, // 系统参数
}

/**
 * 配置分组名称映射
 */
export const ConfigGroupNames: Record<ConfigGroup, string> = {
  [ConfigGroup.Undefined]: '未定义',
  [ConfigGroup.Site]: '站点设置',
  [ConfigGroup.Operation]: '运营参数',
  [ConfigGroup.System]: '系统参数',
}

/**
 * 配置类型
 */
export type ConfigType = 'text' | 'number' | 'boolean' | 'textarea' | 'array'

/**
 * 配置类型名称映射
 */
export const ConfigTypeNames: Record<ConfigType, string> = {
  text: '文本',
  number: '数字',
  boolean: '布尔',
  textarea: '长文本',
  array: '数组',
}

/**
 * 配置项
 */
export interface ConfigItem {
  config_id: number
  config_group: ConfigGroup
  group_name?: string
  config_name: string
  config_key: string
  config_value: string
  config_type: ConfigType
  type_name?: string
  description?: string
  sort_order: number
  created_at: number
  updated_at: number
  deleted_at: number
}

/**
 * 配置分组（用于编辑页面的缓存数据）
 */
export interface ConfigGroupMap {
  [key: string]: ConfigItem[]
}

/**
 * 配置列表响应（用于表格显示）
 */
export interface ConfigListResponse {
  list: ConfigItem[]
  total: number
}

/**
 * 新增/编辑配置参数
 */
export interface ConfigForm {
  config_id?: number
  config_group: ConfigGroup
  config_name: string
  config_key: string
  config_value: string
  config_type: ConfigType
  description?: string
  sort_order: number
}

/**
 * 获取配置分组列表（缓存数据，用于配置编辑页面）
 */
export const getConfigGroupedList = () => {
  return request.get<ConfigGroupMap>('/admin/config/getGroupedList')
}

/**
 * 获取配置列表（用于表格显示）
 * @param params 查询参数
 */
export const getConfigList = (params?: {
  page?: number
  limit?: number
  config_group?: ConfigGroup
}) => {
  return request.get<ConfigListResponse>('/admin/config/getList', { params })
}

/**
 * 新增配置项
 * @param data 配置数据
 */
export const addConfig = (data: ConfigForm) => {
  return request.post<{ config_id: number }>('/admin/config/add', data)
}

/**
 * 编辑配置项
 * @param data 配置数据
 */
export const editConfig = (data: ConfigForm) => {
  return request.post<null>('/admin/config/edit', data)
}

/**
 * 删除配置项
 * @param config_id 配置ID
 */
export const deleteConfig = (config_id: number) => {
  return request.post<null>('/admin/config/delete', { config_id })
}

/**
 * 更新配置排序
 * POST /admin/config/resort
 * @param config_id 配置ID
 * @param sort_order 排序值
 */
export const sortConfig = (config_id: number, sort_order: number) => {
  return request.post<null>('/admin/config/resort', { config_id, sort_order })
}
