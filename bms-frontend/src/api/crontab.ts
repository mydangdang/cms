import request from '@/utils/request'

/**
 * 定时任务数据结构
 */
export interface Crontab {
  crontab_id: number
  name: string
  cron: string
  command: string
  description: string
  sort_order: number
  status: number
  next_execute_time: number
  last_execute_time: number
  created_at: number
  updated_at: number
}

/**
 * 定时任务列表响应
 */
export interface CrontabListResponse {
  list: Crontab[]
  total: number
}

/**
 * 执行记录数据结构
 */
export interface CrontabLog {
  execute_id: number
  crontab_id: string
  status: number
  duration: number
  execute_time: number
  message: string
  execute_type: number // 1=API强制执行 2=CLI按计划执行
}

/**
 * 执行记录列表响应
 */
export interface CrontabLogListResponse {
  list: CrontabLog[]
  total: number
}

/**
 * Cron 验证响应
 */
export interface CronValidateResponse {
  valid: boolean
  next_execute_time?: number
  next_execute_time_text?: string
}

/**
 * 获取定时任务列表
 * GET /admin/crontab/getList
 */
export const getCrontabList = (params?: {
  name?: string
  status?: number
  page?: number
  limit?: number
}) => {
  return request.get<CrontabListResponse>('/admin/crontab/getList', { params })
}

/**
 * 获取任务详情
 * GET /admin/crontab/getDetail
 */
export const getCrontabDetail = (crontab_id: number) => {
  return request.get<Crontab>('/admin/crontab/getDetail', { params: { crontab_id } })
}

/**
 * 新增定时任务
 * POST /admin/crontab/add
 */
export const addCrontab = (data: Partial<Crontab>) => {
  return request.post<{ crontab_id: number }>('/admin/crontab/add', data)
}

/**
 * 编辑定时任务
 * POST /admin/crontab/edit
 */
export const editCrontab = (data: Partial<Crontab> & { crontab_id: number }) => {
  return request.post('/admin/crontab/edit', data)
}

/**
 * 删除定时任务
 * POST /admin/crontab/delete
 */
export const deleteCrontab = (crontab_id: number) => {
  return request.post('/admin/crontab/delete', { crontab_id })
}

/**
 * 立即执行任务
 * POST /admin/crontab/execute
 * Story 4.4: 立即执行任务（重构版 - 返回新格式）
 */
export const executeCrontab = (crontab_id: number) => {
  return request.post<{
    duration: number
    status: number
    message: string
  }>('/admin/crontab/execute', { crontab_id })
}

/**
 * 验证 Cron 表达式
 * GET /admin/crontab/validateCron
 */
export const validateCron = (cron: string) => {
  return request.get<CronValidateResponse>('/admin/crontab/validateCron', { params: { cron } })
}

/**
 * 获取执行记录
 * GET /admin/crontab/getLogs
 */
export const getCrontabLogs = (params: {
  crontab_id: number
  page?: number
  limit?: number
}) => {
  return request.get<CrontabLogListResponse>('/admin/crontab/getLogs', { params })
}

/**
 * 清空执行记录
 * POST /admin/crontab/clearLogs
 */
export const clearCrontabLogs = (crontab_id: number) => {
  return request.post('/admin/crontab/clearLogs', { crontab_id })
}
