<template>
  <div class="login-page">
    <div class="login-container">
      <!-- 标题 -->
      <div class="login-header">
        <h1 class="login-title">{{ appTitle }}</h1>
      </div>

      <!-- 登录表单 -->
      <el-form ref="loginFormRef" :model="loginForm" :rules="loginRules" class="login-form">
        <!-- 用户名 -->
        <el-form-item prop="username" class="form-item">
          <el-input
            v-model="loginForm.username"
            placeholder="用户名"
            size="default"
            class="dark-input"
            autocomplete="username"
            @keyup.enter="handleLogin"
          >
            <template #prefix>
              <el-icon><User /></el-icon>
            </template>
          </el-input>
        </el-form-item>

        <!-- 密码 -->
        <el-form-item prop="password" class="form-item">
          <el-input
            v-model="loginForm.password"
            type="password"
            show-password
            placeholder="密码"
            size="default"
            class="dark-input"
            autocomplete="current-password"
            @keyup.enter="handleLogin"
          >
            <template #prefix>
              <el-icon><Lock /></el-icon>
            </template>
          </el-input>
        </el-form-item>

        <!-- 验证码 -->
        <el-form-item prop="captcha" class="form-item">
          <div class="captcha-group">
            <el-input
              v-model="loginForm.captcha"
              placeholder="验证码"
              maxlength="4"
              size="default"
              class="dark-input captcha-input-field"
              @keyup.enter="handleLogin"
            >
              <template #prefix>
                <el-icon><Key /></el-icon>
              </template>
            </el-input>
            <div class="captcha-box" @click="refreshCaptcha" title="点击刷新验证码">
              <img
                v-if="captchaData"
                :src="`data:image/png;base64,${captchaData}`"
                alt="验证码"
                class="captcha-img"
              />
              <span v-else class="captcha-placeholder">...</span>
            </div>
          </div>
        </el-form-item>

        <!-- 登录按钮 -->
        <el-form-item class="form-item">
          <el-button type="primary" class="login-btn" :loading="loading" @click="handleLogin">
            登 录
          </el-button>
        </el-form-item>
      </el-form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { User, Lock, Key } from '@element-plus/icons-vue'
import type { FormInstance, FormRules } from 'element-plus'
import { getCaptcha } from '@/api/captcha'
import { login } from '@/api/auth'
import { useAdminStore } from '@/store/modules/admin'
import { usePermissionStore } from '@/store/modules/permission'

const router = useRouter()
const adminStore = useAdminStore()
const permissionStore = usePermissionStore()
const loginFormRef = ref<FormInstance>()
const loading = ref(false)
const captchaData = ref('')

// 系统名称（从环境变量读取）
const appTitle = import.meta.env.VITE_APP_TITLE

/**
 * 登录表单数据
 */
interface LoginForm {
  username: string
  password: string
  captcha: string
}

const loginForm = reactive<LoginForm>({
  username: '',
  password: '',
  captcha: '',
})

/**
 * 表单验证规则
 */
const loginRules: FormRules<LoginForm> = {
  username: [
    { required: true, message: '请输入用户名', trigger: 'blur' },
    { min: 3, max: 20, message: '用户名长度为 3-20 个字符', trigger: 'blur' },
  ],
  password: [
    { required: true, message: '请输入密码', trigger: 'blur' },
    { min: 6, max: 20, message: '密码长度为 6-20 个字符', trigger: 'blur' },
  ],
  captcha: [
    { required: true, message: '请输入验证码', trigger: 'blur' },
    { pattern: /^[A-Z0-9]{4}$/i, message: '验证码必须为4位字符', trigger: 'blur' },
  ],
}

/**
 * 获取验证码
 */
const fetchCaptcha = async () => {
  try {
    const res = await getCaptcha()
    if (res.code === 200 && res.data?.image) {
      captchaData.value = res.data.image
    }
  } catch (error) {
    console.error('获取验证码失败:', error)
  }
}

/**
 * 刷新验证码
 */
const refreshCaptcha = () => {
  captchaData.value = ''
  fetchCaptcha()
}

/**
 * 处理登录
 * Story 2.6 AC10: 登录成功后加载权限数据
 */
const handleLogin = async () => {
  if (!loginFormRef.value) return

  try {
    loading.value = true

    // 表单验证
    await loginFormRef.value.validate()

    // 调用登录 API
    const res = await login(loginForm)

    if (res.code === 200 && res.data) {
      // 存储 Token 和管理员信息
      adminStore.setToken(res.data.token)
      adminStore.setAdminInfo(res.data.adminInfo)

      // 加载权限数据 - Story 2.6 AC10
      try {
        await permissionStore.loadPermissions()
        ElMessage.success('登录成功')

        // 跳转到首页
        router.push('/')
      } catch (permError) {
        // 权限加载失败，清除 token 并提示用户
        console.error('权限加载失败:', permError)
        localStorage.removeItem('token')
        adminStore.resetState()
        ElMessage.error('权限加载失败，请重新登录')
      }
    }
  } catch (error) {
    console.error('登录失败:', error)
    // 刷新验证码
    refreshCaptcha()
  } finally {
    loading.value = false
  }
}

// 组件挂载时获取验证码
onMounted(() => {
  fetchCaptcha()
})
</script>

<style scoped>
/* 页面容器 - 深色背景，全屏无滚动 */
.login-page {
  font-family:
    -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
  background: #2d3a4b;
  height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

/* 登录容器 */
.login-container {
  width: 100%;
  max-width: 400px;
  padding: 0 20px;
}

/* 标题 */
.login-header {
  text-align: center;
  margin-bottom: 40px;
}

.login-title {
  font-size: 26px;
  font-weight: 500;
  color: #ffffff;
  letter-spacing: 3px;
  margin: 0;
}

/* 表单样式 - 无底色，无间距 */

.form-item {
  margin-bottom: 16px;
}

.form-item :deep(.el-form-item__content) {
  line-height: normal;
}

/* 深色输入框样式 */
.dark-input :deep(.el-input__wrapper) {
  background: rgba(0, 0, 0, 0.1);
  border: 1px solid hsla(0, 0%, 100%, 0.1);
  border-radius: 5px;
  box-shadow: none;
  padding: 0;
  height: 36px;
}

.dark-input :deep(.el-input__wrapper:hover) {
  border-color: hsla(0, 0%, 100%, 0.2);
}

.dark-input :deep(.el-input__wrapper.is-focus) {
  border-color: #1890ff;
  box-shadow: none;
}

.dark-input :deep(.el-input__inner) {
  color: #ffffff;
  height: 36px;
  line-height: 36px;
}

.dark-input :deep(.el-input__inner::placeholder) {
  color: rgba(255, 255, 255, 0.6);
}

.dark-input :deep(.el-input__prefix) {
  color: #889aa4;
  padding-left: 12px;
}

.dark-input :deep(.el-input__prefix-inner) {
  display: flex;
  align-items: center;
}

/* 验证码组 */
.captcha-group {
  display: flex;
  gap: 12px;
  align-items: center;
  width: 100%;
}

.captcha-input-field {
  flex: 1;
}

.captcha-box {
  width: 100px;
  height: 36px;
  background: rgba(0, 0, 0, 0.1);
  border: 1px solid hsla(0, 0%, 100%, 0.1);
  border-radius: 5px;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  flex-shrink: 0;
  overflow: hidden;
}

.captcha-box:hover {
  border-color: rgba(255, 255, 255, 0.2);
}

.captcha-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.captcha-placeholder {
  font-size: 16px;
  font-weight: 600;
  color: #1890ff;
  letter-spacing: 3px;
  user-select: none;
}

/* 登录按钮 */
.login-btn {
  width: 100%;
  height: 36px;
  background: #1890ff;
  border: 1px solid #1890ff;
  border-radius: 4px;
  color: #ffffff;
  font-size: 14px;
  font-weight: 400;
  margin-top: 8px;
}

.login-btn:hover {
  background: #40a9ff;
  border-color: #40a9ff;
}

.login-btn:active {
  background: #096dd9;
  border-color: #096dd9;
}

/* 响应式 */
@media (max-width: 480px) {
  .login-container {
    padding: 0 16px;
  }

  .login-title {
    font-size: 22px;
  }
}
</style>
