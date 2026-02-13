<template>
  <div class="dashboard-container">
    <el-card class="welcome-card">
      <template #header>
        <h2 class="welcome-title">欢迎使用 BMS 后台管理系统</h2>
      </template>

      <div class="welcome-content">
        <el-descriptions :column="2" border>
          <el-descriptions-item label="管理员ID">
            {{ adminInfo?.admin_id || '-' }}
          </el-descriptions-item>
          <el-descriptions-item label="用户名">
            {{ adminInfo?.username || '-' }}
          </el-descriptions-item>
          <el-descriptions-item label="昵称">
            {{ adminInfo?.nickname || '-' }}
          </el-descriptions-item>
          <el-descriptions-item label="角色类型">
            <el-tag :type="adminInfo?.is_super === 1 ? 'danger' : 'primary'">
              {{ adminInfo?.is_super === 1 ? '超级管理员' : '普通管理员' }}
            </el-tag>
          </el-descriptions-item>
        </el-descriptions>

        <div class="system-info">
          <h3>系统信息</h3>
          <p>Epic 1: 用户认证与系统访问 - 已完成</p>
          <p>当前功能状态：</p>
          <ul>
            <li>✅ 图形验证码</li>
            <li>✅ 用户登录认证</li>
            <li>✅ Token 验证中间件</li>
            <li>✅ 登录日志记录</li>
            <li>✅ 用户登出功能</li>
          </ul>
        </div>
      </div>
    </el-card>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useAdminStore } from '@/store/modules/admin'

const adminStore = useAdminStore()

const adminInfo = computed(() => adminStore.adminInfo)
</script>

<style scoped>
.dashboard-container {
  padding: 20px;
}

.welcome-card {
  max-width: 800px;
  margin: 0 auto;
}

.welcome-title {
  margin: 0;
  text-align: center;
  font-size: 24px;
  font-weight: 500;
  color: #303133;
}

.welcome-content {
  padding: 20px 0;
}

.system-info {
  margin-top: 30px;
  padding: 20px;
  background-color: #f5f7fa;
  border-radius: 4px;
}

.system-info h3 {
  margin-top: 0;
  margin-bottom: 15px;
  font-size: 18px;
  color: #303133;
}

.system-info p {
  margin: 10px 0;
  color: #606266;
}

.system-info ul {
  margin: 10px 0;
  padding-left: 20px;
  color: #606266;
}

.system-info li {
  margin: 5px 0;
}
</style>
