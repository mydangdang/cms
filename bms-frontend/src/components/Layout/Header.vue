<template>
  <div class="header">
    <div class="header-left">
      <!-- 系统标题已移除 -->
    </div>

    <div class="header-right">
      <el-dropdown @command="handleCommand">
        <span class="user-info">
          <el-icon><User /></el-icon>
          {{ adminStore.adminInfo?.username || '管理员' }}
        </span>
        <template #dropdown>
          <el-dropdown-menu>
            <el-dropdown-item command="settings">
              <el-icon><Setting /></el-icon>
              个人设置
            </el-dropdown-item>
            <el-dropdown-item divided command="logout">
              <el-icon><SwitchButton /></el-icon>
              退出登录
            </el-dropdown-item>
          </el-dropdown-menu>
        </template>
      </el-dropdown>
    </div>

    <!-- 个人设置弹窗 -->
    <el-dialog v-model="dialogVisible" title="个人设置" width="500px" :close-on-click-modal="false">
      <el-form :model="formData" :rules="rules" ref="formRef" label-width="80px">
        <el-form-item label="角色" prop="role">
          <el-input v-model="roleText" disabled />
        </el-form-item>
        <el-form-item label="用户名" prop="username">
          <el-input v-model="formData.username" disabled />
        </el-form-item>
        <el-form-item label="真实姓名" prop="real_name">
          <el-input v-model="formData.real_name" placeholder="请输入真实姓名" clearable />
        </el-form-item>
        <el-form-item label="手机号" prop="mobile">
          <el-input v-model="formData.mobile" placeholder="请输入手机号" clearable />
        </el-form-item>
        <el-form-item label="密码" prop="password">
          <el-input
            v-model="formData.password"
            type="password"
            placeholder="留空则不修改密码"
            clearable
            show-password
          />
        </el-form-item>
        <el-form-item v-if="formData.password" label="确认密码" prop="confirmPassword">
          <el-input
            v-model="formData.confirmPassword"
            type="password"
            placeholder="请再次输入新密码"
            clearable
            show-password
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSubmit" :loading="submitLoading">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, computed } from 'vue'
import { ElMessageBox, ElMessage, type FormInstance, type FormRules } from 'element-plus'
import { User, SwitchButton, Setting } from '@element-plus/icons-vue'
import { useRouter } from 'vue-router'
import { useAdminStore } from '@/store/modules/admin'
import { editAdmin, type Admin } from '@/api/admin'

const router = useRouter()
const adminStore = useAdminStore()

// 弹窗状态
const dialogVisible = ref(false)
const submitLoading = ref(false)
const formRef = ref<FormInstance>()

// 表单数据
const formData = reactive({
  admin_id: 0,
  username: '',
  real_name: '',
  mobile: '',
  password: '',
  confirmPassword: ''
})

// 角色文本（显示用）
const roleText = computed(() => {
  const adminInfo = adminStore.adminInfo
  if (!adminInfo) return '-'
  if ((adminInfo as any).is_super === 1) return '超级管理员'
  const roles = (adminInfo as any).roles
  if (roles && roles.length > 0) {
    return roles.map((r: any) => r.name).join('、')
  }
  return '未分配角色'
})

// 表单验证规则
const rules = reactive<FormRules>({
  real_name: [
    { max: 50, message: '真实姓名长度不能超过50个字符', trigger: 'blur' }
  ],
  mobile: [
    { pattern: /^1[3-9]\d{9}$/, message: '请输入正确的手机号', trigger: 'blur' }
  ],
  password: [
    { min: 6, max: 20, message: '密码长度为6-20个字符', trigger: 'blur' }
  ],
  confirmPassword: [
    {
      validator: (rule, value, callback) => {
        if (formData.password && value !== formData.password) {
          callback(new Error('两次输入的密码不一致'))
        } else {
          callback()
        }
      },
      trigger: 'blur'
    }
  ]
})

const handleCommand = async (command: string) => {
  if (command === 'logout') {
    // 确认对话框
    ElMessageBox.confirm('确认退出登录吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning'
    }).then(async () => {
      try {
        await adminStore.logout(router)
        ElMessage.success('登出成功')
      } catch (error) {
        ElMessage.error('登出失败')
      }
    }).catch(() => {
      // 取消登出
    })
  } else if (command === 'settings') {
    // 打开个人设置弹窗
    openSettingsDialog()
  }
}

/**
 * 打开个人设置弹窗
 */
const openSettingsDialog = () => {
  const adminInfo = adminStore.adminInfo
  if (adminInfo) {
    formData.admin_id = adminInfo.admin_id
    formData.username = adminInfo.username
    formData.real_name = adminInfo.real_name || ''
    formData.mobile = adminInfo.mobile || ''
    formData.password = ''
    formData.confirmPassword = ''
  }
  dialogVisible.value = true
}

/**
 * 提交表单
 */
const handleSubmit = async () => {
  if (!formRef.value) return

  await formRef.value.validate(async (valid) => {
    if (!valid) return

    try {
      submitLoading.value = true

      const submitData: Partial<Admin> & { admin_id: number } = {
        admin_id: formData.admin_id,
        real_name: formData.real_name,
        mobile: formData.mobile
      }

      // 如果填写了密码，则更新密码
      if (formData.password) {
        submitData.password = formData.password
      }

      const res = await editAdmin(submitData)
      ElMessage.success(res.msg || '保存成功')

      // 如果修改了密码，强制退出到登录页
      if (formData.password) {
        await adminStore.logout(router)
        ElMessage.warning('密码已修改，请重新登录')
      } else {
        // 仅更新本地用户信息
        if (adminStore.adminInfo) {
          adminStore.setAdminInfo({
            ...adminStore.adminInfo,
            real_name: formData.real_name,
            mobile: formData.mobile
          })
        }
        dialogVisible.value = false
      }
    } catch (error) {
      // 错误消息由 request 拦截器统一处理
    } finally {
      submitLoading.value = false
    }
  })
}
</script>

<style scoped>
.header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  height: 50px;
  padding: 0 20px;
  background-color: #2d3e53;
}

.header-left {
  display: flex;
  align-items: center;
}

.header-right {
  display: flex;
  align-items: center;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #fff;
  cursor: pointer;
  padding: 8px 12px;
  border-radius: 4px;
  transition: background-color 0.3s;
}

.user-info:hover {
  background-color: #425268;
}
</style>
