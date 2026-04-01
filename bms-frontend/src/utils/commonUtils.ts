/**
 * 通用工具函数集合
 * 包含：日期时间处理、状态处理等常用功能
 */

// ==================== 日期时间工具 ====================

/**
 * 判断时间戳是否为今天
 * @param timestamp 时间戳（秒）
 * @returns 是否为今天
 */
export const isToday = (timestamp: number): boolean => {
  if (!timestamp) return false
  const date = new Date(timestamp * 1000)
  const today = new Date()
  return (
    date.getFullYear() === today.getFullYear() &&
    date.getMonth() === today.getMonth() &&
    date.getDate() === today.getDate()
  )
}

/**
 * 格式化时间为 YYYY-MM-DD HH:mm:ss 格式
 * @param timestamp 时间戳（秒）
 * @returns 格式化后的时间字符串
 */
export const formatTimestamp = (timestamp: number): string => {
  if (!timestamp) return '-'
  const date = new Date(timestamp * 1000)
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  const hour = String(date.getHours()).padStart(2, '0')
  const minute = String(date.getMinutes()).padStart(2, '0')
  const second = String(date.getSeconds()).padStart(2, '0')
  return `${year}-${month}-${day} ${hour}:${minute}:${second}`
}

/**
 * 格式化时间为 YYYY-MM-DD 格式
 * @param timestamp 时间戳（秒）
 * @returns 格式化后的日期字符串
 */
export const formatDate = (timestamp: number): string => {
  if (!timestamp) return '-'
  const date = new Date(timestamp * 1000)
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

/**
 * 格式化时间为 HH:mm:ss 格式
 * @param timestamp 时间戳（秒）
 * @returns 格式化后的时间字符串
 */
export const formatTime = (timestamp: number): string => {
  if (!timestamp) return '-'
  const date = new Date(timestamp * 1000)
  const hour = String(date.getHours()).padStart(2, '0')
  const minute = String(date.getMinutes()).padStart(2, '0')
  const second = String(date.getSeconds()).padStart(2, '0')
  return `${hour}:${minute}:${second}`
}

/**
 * 获取相对时间描述
 * @param timestamp 时间戳（秒）
 * @returns 相对时间描述（如：刚刚、5分钟前、今天 10:30 等）
 */
export const getRelativeTime = (timestamp: number): string => {
  if (!timestamp) return '-'

  const now = Date.now()
  const time = timestamp * 1000
  const diff = now - time

  // 小于1分钟
  if (diff < 60000) {
    return '刚刚'
  }

  // 小于1小时
  if (diff < 3600000) {
    const minutes = Math.floor(diff / 60000)
    return `${minutes}分钟前`
  }

  // 今天
  if (isToday(timestamp)) {
    return `今天 ${formatTime(timestamp)}`
  }

  // 昨天
  const yesterday = new Date()
  yesterday.setDate(yesterday.getDate() - 1)
  const targetDate = new Date(time)
  if (
    targetDate.getFullYear() === yesterday.getFullYear() &&
    targetDate.getMonth() === yesterday.getMonth() &&
    targetDate.getDate() === yesterday.getDate()
  ) {
    return `昨天 ${formatTime(timestamp)}`
  }

  // 今年
  if (targetDate.getFullYear() === new Date().getFullYear()) {
    return `${targetDate.getMonth() + 1}月${targetDate.getDate()}日 ${formatTime(timestamp)}`
  }

  // 其他情况显示完整日期
  return formatTimestamp(timestamp)
}

// ==================== 状态工具 ====================

/**
 * 状态值枚举
 */
export enum Status {
  DISABLED = 0, // 禁用/失败
  ENABLED = 1, // 启用/成功
}

/**
 * 获取状态标签类型（Element Plus Tag）
 * @param status 状态值
 * @returns Element Plus Tag 类型
 */
export const getStatusTagType = (status: number): 'success' | 'danger' | 'info' | 'warning' => {
  switch (status) {
    case Status.ENABLED:
      return 'success'
    case Status.DISABLED:
      return 'danger'
    default:
      return 'info'
  }
}

/**
 * 获取状态文本
 * @param status 状态值
 * @returns 状态文本
 */
export const getStatusText = (status: number): string => {
  switch (status) {
    case Status.ENABLED:
      return '启用'
    case Status.DISABLED:
      return '禁用'
    default:
      return '未知'
  }
}

/**
 * 状态配置对象（用于渲染选项等）
 */
export const STATUS_OPTIONS = [
  { label: '全部', value: -1 },
  { label: '启用', value: Status.ENABLED },
  { label: '禁用', value: Status.DISABLED },
]

// ==================== 通用工具 ====================

/**
 * 深拷贝对象
 * @param obj 要拷贝的对象
 * @returns 拷贝后的新对象
 */
export const deepClone = <T>(obj: T): T => {
  if (obj === null || typeof obj !== 'object') return obj
  if (obj instanceof Date) return new Date(obj.getTime()) as unknown as T
  if (obj instanceof Array) return obj.map((item) => deepClone(item)) as unknown as T
  if (typeof obj === 'object') {
    const copy = {} as T
    for (const key in obj) {
      if (Object.prototype.hasOwnProperty.call(obj, key)) {
        ;(copy as Record<string, unknown>)[key] = deepClone(obj[key])
      }
    }
    return copy
  }
  return obj
}

/**
 * 防抖函数
 * @param fn 要防抖的函数
 * @param delay 延迟时间（毫秒）
 * @returns 防抖后的函数
 */
export const debounce = <T extends (...args: unknown[]) => unknown>(
  fn: T,
  delay: number
): ((...args: Parameters<T>) => void) => {
  let timer: ReturnType<typeof setTimeout> | null = null
  return function (this: unknown, ...args: Parameters<T>) {
    if (timer) clearTimeout(timer)
    timer = setTimeout(() => {
      fn.apply(this, args)
    }, delay)
  }
}

/**
 * 节流函数
 * @param fn 要节流的函数
 * @param delay 延迟时间（毫秒）
 * @returns 节流后的函数
 */
export const throttle = <T extends (...args: unknown[]) => unknown>(
  fn: T,
  delay: number
): ((...args: Parameters<T>) => void) => {
  let lastTime = 0
  return function (this: unknown, ...args: Parameters<T>) {
    const now = Date.now()
    if (now - lastTime >= delay) {
      lastTime = now
      fn.apply(this, args)
    }
  }
}

/**
 * 格式化文件大小
 * @param bytes 字节数
 * @returns 格式化后的文件大小
 */
export const formatFileSize = (bytes: number): string => {
  if (bytes === 0) return '0 B'
  const k = 1024
  const sizes = ['B', 'KB', 'MB', 'GB', 'TB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return `${(bytes / Math.pow(k, i)).toFixed(2)} ${sizes[i]}`
}

/**
 * 生成唯一 ID
 * @returns 唯一 ID 字符串
 */
export const generateId = (): string => {
  return `${Date.now()}-${Math.random().toString(36).substr(2, 9)}`
}
