import { describe, it, expect, vi, beforeEach } from 'vitest'
import { getCaptcha, verifyCaptcha } from '../captcha'
import request from '@/utils/request'

// Mock request module
vi.mock('@/utils/request')

describe('captcha API', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  describe('getCaptcha', () => {
    it('应该调用获取验证码接口', async () => {
      const mockResponse = {
        code: 200,
        message: '获取成功',
        data: { image: 'base64-image-data' },
      }
      vi.mocked(request.get).mockResolvedValue(mockResponse)

      const result = await getCaptcha()

      expect(request.get).toHaveBeenCalledWith('/admin/captcha')
      expect(result).toEqual(mockResponse)
    })

    it('应该正确处理错误响应', async () => {
      const mockError = new Error('网络错误')
      vi.mocked(request.get).mockRejectedValue(mockError)

      await expect(getCaptcha()).rejects.toThrow('网络错误')
    })
  })

  describe('verifyCaptcha', () => {
    it('应该调用验证码校验接口', async () => {
      const mockData = { code: 'AB12' }
      const mockResponse = {
        code: 200,
        message: '验证成功',
      }
      vi.mocked(request.post).mockResolvedValue(mockResponse)

      const result = await verifyCaptcha(mockData)

      expect(request.post).toHaveBeenCalledWith('/admin/captcha/verify', mockData)
      expect(result).toEqual(mockResponse)
    })

    it('应该传递正确的验证码参数', async () => {
      const mockData = { code: 'TEST' }
      const mockResponse = {
        code: 400,
        message: '验证码错误',
      }
      vi.mocked(request.post).mockResolvedValue(mockResponse)

      const result = await verifyCaptcha(mockData)

      expect(result.code).toBe(400)
      expect(result.message).toBe('验证码错误')
    })
  })
})
