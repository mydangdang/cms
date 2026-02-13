import request, { type ApiResponse } from '@/utils/request'

/**
 * 验证码响应数据接口
 */
export interface CaptchaData {
  image: string // base64 编码的验证码图片
}

/**
 * 获取图形验证码
 * GET /admin/captcha/index
 *
 * @returns Promise<ApiResponse<CaptchaData>>
 */
export const getCaptcha = (): Promise<ApiResponse<CaptchaData>> => {
  return request.get('/admin/captcha/index')
}

/**
 * 注意：验证码验证逻辑在登录提交时由后端处理
 * 前端不需要单独的 verifyCaptcha 方法
 */
