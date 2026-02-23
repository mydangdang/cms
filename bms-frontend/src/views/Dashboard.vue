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
      </div>
    </el-card>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useAdminStore } from '@/store/modules/admin'

// 组件名称用于 KeepAlive 缓存
defineOptions({
  name: 'Dashboard'
})

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
</style>
