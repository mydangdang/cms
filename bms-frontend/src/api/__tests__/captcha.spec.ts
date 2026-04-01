import { describe, it, expect, vi, beforeEach } from 'vitest'
import { getCaptcha } from '../captcha'
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
        msg: '获取成功',
        data: { image: 'base64-image-data' },
      }
      vi.mocked(request.get).mockResolvedValue(mockResponse)

      const result = await getCaptcha()

      expect(request.get).toHaveBeenCalledWith('/admin/captcha/index')
      expect(result).toEqual(mockResponse)
    })

    it('应该正确处理错误响应', async () => {
      const mockError = new Error('网络错误')
      vi.mocked(request.get).mockRejectedValue(mockError)

      await expect(getCaptcha()).rejects.toThrow('网络错误')
    })
  })
})
