<template>
  <div class="crontab-list">
    <el-card>
      <template #header>
        <div class="card-header">
          <span>定时任务管理</span>
          <el-button type="primary" v-permission="'system:crontab:add'" @click="handleAdd"
            >新增</el-button
          >
        </div>
      </template>

      <!-- 搜索表单 -->
      <el-form :inline="true" :model="searchForm" class="search-form">
        <el-form-item label="任务名称">
          <el-input
            v-model="searchForm.name"
            placeholder="请输入任务名称"
            clearable
            style="width: 200px"
          />
        </el-form-item>
        <el-form-item label="状态">
          <el-select v-model="searchForm.status" placeholder="请选择状态" style="width: 120px">
            <el-option
              v-for="item in STATUS_OPTIONS"
              :key="item.value"
              :label="item.label"
              :value="item.value"
            />
          </el-select>
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">查询</el-button>
          <el-button @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>

      <!-- 数据表格 -->
      <el-table :data="tableData" border v-loading="loading" class="data-table">
        <el-table-column prop="crontab_id" label="ID" width="120" />
        <el-table-column prop="name" label="任务名称" />
        <el-table-column prop="cron" label="Cron表达式">
          <template #default="{ row }">
            <code class="cron-code">{{ row.cron }}</code>
          </template>
        </el-table-column>
        <el-table-column prop="command" label="执行方法名" show-overflow-tooltip />
        <el-table-column label="最后执行" width="180">
          <template #default="{ row }">
            <span :class="{ 'text-today': isToday(row.last_execute_time) }">
              {{ formatLastExecuteTime(row.last_execute_time) }}
            </span>
          </template>
        </el-table-column>
        <el-table-column label="下次执行" width="180">
          <template #default="{ row }">
            <span :class="{ 'text-today': isToday(row.next_execute_time) }">
              {{ formatNextExecuteTime(row.next_execute_time) }}
            </span>
          </template>
        </el-table-column>
        <el-table-column label="状态" width="130">
          <template #default="{ row }">
            <StatusTag :status="row.status" />
          </template>
        </el-table-column>
        <el-table-column label="排序" width="140">
          <template #default="{ row }">
            <SortableInput
              v-model="row.sort_order"
              :sort-api="sortCrontab"
              :row-data="row"
              id-field="crontab_id"
              permission="system:crontab:resort"
              @success="handleSortSuccess"
            />
          </template>
        </el-table-column>
        <el-table-column label="操作" fixed="right" width="280">
          <template #default="{ row }">
            <el-button
              type="primary"
              plain
              size="small"
              v-permission="'system:crontab:edit'"
              @click="handleEdit(row)"
              >编辑</el-button
            >
            <el-button
              type="danger"
              plain
              size="small"
              v-permission="'system:crontab:delete'"
              @click="handleDelete(row)"
              >删除</el-button
            >
            <el-button
              type="success"
              plain
              size="small"
              v-permission="'system:crontab:getlogs'"
              @click="handleLogs(row)"
              >记录</el-button
            >
            <el-button
              type="info"
              plain
              size="small"
              v-permission="'system:crontab:execute'"
              @click="handleExecute(row)"
              >执行</el-button
            >
          </template>
        </el-table-column>
      </el-table>

      <!-- 分页 -->
      <el-pagination
        v-model:current-page="pagination.page"
        v-model:page-size="pagination.limit"
        :total="pagination.total"
        :page-sizes="[10, 20, 50, 100]"
        layout="total, sizes, prev, pager, next, jumper"
        @size-change="handleSearch"
        @current-change="handleSearch"
      />
    </el-card>

    <!-- 新增/编辑弹窗 -->
    <el-dialog
      v-model="dialogVisible"
      :title="dialogTitle"
      width="700px"
      :close-on-click-modal="false"
      @close="handleCloseDialog"
    >
      <el-form :model="formData" :rules="formRules" ref="formRef" label-width="120px">
        <el-form-item label="任务名称" prop="name">
          <el-input v-model="formData.name" placeholder="请输入任务名称" />
        </el-form-item>

        <el-form-item label="Cron 表达式" prop="cron">
          <CronSelector v-model="formData.cron" />
        </el-form-item>

        <el-form-item label="执行方法名" prop="command">
          <el-input
            v-model="formData.command"
            placeholder="请输入执行方法名，如: sendDailyReport（6-20个字母）"
          />
        </el-form-item>

        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="状态" prop="status">
              <el-select v-model="formData.status" placeholder="请选择状态" style="width: 100%">
                <el-option :value="1" label="启用" />
                <el-option :value="0" label="禁用" />
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="排序" prop="sort_order">
              <el-input v-model="formData.sort_order" placeholder="请输入排序号" />
            </el-form-item>
          </el-col>
        </el-row>

        <el-form-item label="任务描述" prop="description">
          <el-input
            v-model="formData.description"
            type="textarea"
            :rows="2"
            placeholder="请输入任务描述"
          />
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSubmit">确定</el-button>
      </template>
    </el-dialog>

    <!-- 执行记录弹窗 -->
    <el-dialog
      v-model="logsDialogVisible"
      title="执行记录"
      width="1000px"
      :close-on-click-modal="false"
    >
      <template #header>
        <div style="display: flex; justify-content: space-between; align-items: center">
          <span>执行记录</span>
          <div style="display: flex; gap: 10px">
            <el-button type="danger" size="small" @click="handleClearLogs" v-if="canClearLogs">
              清空
            </el-button>
            <el-button type="primary" size="small" @click="getLogs">
              <el-icon><Refresh /></el-icon> 刷新
            </el-button>
          </div>
        </div>
      </template>

      <el-table
        :data="logsData"
        border
        v-loading="logsLoading"
        max-height="500"
        empty-text="暂无执行记录"
      >
        <el-table-column label="执行类型" width="130">
          <template #default="{ row }">
            <el-tag v-if="row.execute_type === 1" type="primary">API执行</el-tag>
            <el-tag v-else-if="row.execute_type === 2" type="info">CLI执行</el-tag>
            <el-tag v-else type="info">未知</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="状态" width="130">
          <template #default="{ row }">
            <el-tag v-if="row.status === 1" type="success">成功</el-tag>
            <el-tag v-else-if="row.status === 0" type="danger">失败</el-tag>
            <el-tag v-else-if="row.status === 2" type="warning">超时</el-tag>
          </template>
        </el-table-column>
        <el-table-column label="耗时" width="100">
          <template #default="{ row }"> {{ row.duration }}s </template>
        </el-table-column>
        <el-table-column label="执行时间" width="180">
          <template #default="{ row }">
            <span :class="{ 'text-today': isToday(row.execute_time) }">
              {{ formatExecuteTime(row.execute_time) }}
            </span>
          </template>
        </el-table-column>
        <el-table-column prop="message" label="描述" show-overflow-tooltip />
      </el-table>
      <el-pagination
        v-model:current-page="logsPagination.page"
        v-model:page-size="logsPagination.limit"
        :total="logsPagination.total"
        :page-sizes="[10, 20, 50]"
        layout="total, sizes, prev, pager, next"
        @size-change="getLogs"
        @current-change="getLogs"
      />
    </el-dialog>

    <!-- 日志详情弹窗 -->
    <el-dialog
      v-model="logDetailDialogVisible"
      :title="logDetailTitle"
      width="700px"
      :close-on-click-modal="false"
    >
      <pre class="log-detail-content">{{ logDetailContent }}</pre>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessageBox, ElMessage, ElNotification } from 'element-plus'
import { Refresh } from '@element-plus/icons-vue'
import {
  getCrontabList,
  addCrontab,
  editCrontab,
  deleteCrontab,
  executeCrontab,
  getCrontabLogs,
  clearCrontabLogs,
  sortCrontab,
  type Crontab,
} from '@/api/crontab'
import CronSelector from '@/components/System/CronSelector.vue'
import SortableInput from '@/components/Common/SortableInput.vue'
import { usePermission } from '@/composables/usePermission'
import { isToday, formatTimestamp, STATUS_OPTIONS } from '@/utils/commonUtils'
import StatusTag from '@/components/Common/StatusTag.vue'

// 组件名称用于 KeepAlive 缓存
defineOptions({
  name: 'system:crontab',
})

/**
 * 定时任务列表页面
 * Story 4.3: 定时任务页面（前端）
 */

// 搜索表单
const searchForm = reactive({
  name: '',
  status: -1 as number, // 默认全部，-1 表示全部
})

// 表格数据
const tableData = ref<Crontab[]>([])

// 分页
const pagination = reactive({
  page: 1,
  limit: 10,
  total: 0,
})

// 加载状态
const loading = ref(false)

/**
 * 获取定时任务列表
 */
const getList = async () => {
  try {
    loading.value = true
    const params: any = {
      page: pagination.page,
      limit: pagination.limit,
    }
    if (searchForm.name) {
      params.name = searchForm.name
    }
    if (searchForm.status !== -1) {
      params.status = searchForm.status
    }

    const res = await getCrontabList(params)
    tableData.value = res.data?.list || []
    pagination.total = res.data?.total || 0
  } catch (error) {
    // 错误消息由 request 拦截器统一处理
  } finally {
    loading.value = false
  }
}

/**
 * 查询列表
 */
const handleSearch = () => {
  pagination.page = 1
  getList()
}

/**
 * 重置搜索
 */
const handleReset = () => {
  searchForm.name = ''
  searchForm.status = 1
  pagination.page = 1
  getList()
}

// 弹窗显示控制
const dialogVisible = ref(false)
const dialogTitle = ref('')
const formRef = ref()

// 表单数据
const formData = reactive({
  crontab_id: 0,
  name: '',
  cron: '',
  command: '',
  description: '',
  sort_order: 0,
  status: 1,
})

// 表单验证规则
const formRules = {
  name: [{ required: true, message: '请输入任务名称', trigger: 'blur' }],
  cron: [{ required: true, message: '请输入Cron表达式', trigger: 'blur' }],
  command: [
    { required: true, message: '请输入执行方法名', trigger: 'blur' },
    { min: 6, max: 20, message: '执行方法名长度为6-20个字母', trigger: 'blur' },
    { pattern: /^[a-zA-Z]+$/, message: '执行方法名只能包含大小写字母', trigger: 'blur' },
  ],
}

/**
 * 获取命令输入框提示
 */
/**
 * 新增
 */
const handleAdd = () => {
  dialogTitle.value = '新增定时任务'
  Object.assign(formData, {
    crontab_id: 0,
    name: '',
    cron: '',
    command: '',
    description: '',
    sort_order: 0,
    status: 1,
  })
  dialogVisible.value = true
}

/**
 * 编辑
 */
const handleEdit = (row: Crontab) => {
  dialogTitle.value = '编辑定时任务'
  Object.assign(formData, {
    crontab_id: row.crontab_id,
    name: row.name,
    cron: row.cron,
    command: row.command,
    description: row.description || '',
    sort_order: row.sort_order || 0,
    status: row.status,
  })
  dialogVisible.value = true
}

/**
 * 提交表单
 */
const handleSubmit = async () => {
  if (!formRef.value) return

  try {
    await formRef.value.validate()
    const isEdit = formData.crontab_id > 0

    if (isEdit) {
      const res = await editCrontab({
        crontab_id: formData.crontab_id,
        name: formData.name,
        cron: formData.cron,
        command: formData.command,
        description: formData.description,
        sort_order: formData.sort_order,
        status: formData.status,
      })
      ElMessage.success(res.msg || '编辑成功')
    } else {
      const res = await addCrontab({
        name: formData.name,
        cron: formData.cron,
        command: formData.command,
        description: formData.description,
        sort_order: formData.sort_order,
        status: formData.status,
      })
      ElMessage.success(res.msg || '新增成功')
    }

    dialogVisible.value = false
    getList()
  } catch (error) {
    // 错误消息由 request 拦截器统一处理
  }
}

/**
 * 关闭弹窗
 */
const handleCloseDialog = () => {
  formRef.value?.resetFields()
}

/**
 * 立即执行任务
 * Story 4.4: 立即执行任务
 */
const handleExecute = async (row: Crontab) => {
  try {
    await ElMessageBox.confirm(`确认立即执行任务"${row.name}"吗？`, '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning',
      closeOnClickModal: false,
    })

    const loadingInstance = ElMessage.info({
      message: '正在执行任务...',
      duration: 0,
    })

    try {
      const res = await executeCrontab(row.crontab_id)
      loadingInstance.close()

      if (res.code === 200) {
        // 执行成功 - 新返回格式: {msg, data: {duration, status, message}}
        ElNotification({
          title: res.msg || '执行成功',
          message: `任务"${row.name}"执行完成，耗时 ${res.data?.duration || 0} 秒}`,
          type: 'success',
          duration: 5000,
        })

        // 刷新列表以更新执行次数和最后执行时间
        getList()
      }
    } catch (execError) {
      loadingInstance.close()
      // 错误消息由 request 拦截器统一处理
    }
  } catch (error) {
    // 用户取消操作
  }
}

// 执行记录相关
const logsDialogVisible = ref(false)
const logsData = ref<any[]>([])
const logsLoading = ref(false)
const logsPagination = reactive({ page: 1, limit: 20, total: 0 })
const currentCrontabId = ref(0)

// 权限检查
const { hasPermission } = usePermission()
const canClearLogs = hasPermission('system:crontab:clearlogs')

// 日志详情弹窗相关
const logDetailDialogVisible = ref(false)
const logDetailTitle = ref('')
const logDetailContent = ref('')

/**
 * 查看执行记录
 */
const handleLogs = (row: Crontab) => {
  currentCrontabId.value = row.crontab_id
  logsDialogVisible.value = true
  getLogs()
}

/**
 * 获取执行记录
 */
const getLogs = async () => {
  try {
    logsLoading.value = true
    const res = await getCrontabLogs({
      crontab_id: currentCrontabId.value,
      page: logsPagination.page,
      limit: logsPagination.limit,
    })
    logsData.value = res.data?.list || []
    logsPagination.total = res.data?.total || 0
  } catch (error) {
    // 错误消息由 request 拦截器统一处理
  } finally {
    logsLoading.value = false
  }
}

/**
 * 清空执行记录
 */
const handleClearLogs = async () => {
  try {
    await ElMessageBox.confirm('确认清空该任务的所有执行记录吗？此操作不可恢复！', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning',
      closeOnClickModal: false,
    })

    try {
      await clearCrontabLogs(currentCrontabId.value)
      ElMessage.success('清空成功')
      // 刷新执行记录
      getLogs()
    } catch (error) {
      // 错误消息由 request 拦截器统一处理
    }
  } catch (error) {
    // 用户取消操作
  }
}

/**
 * 排序更新成功
 */
const handleSortSuccess = () => {
  ElMessage.success('排序更新成功')
  // 排序更新成功后刷新列表（带筛选参数）
  getList()
}

/**
 * 删除
 */
const handleDelete = async (row: Crontab) => {
  try {
    await ElMessageBox.confirm('确定要删除该定时任务吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning',
      closeOnClickModal: false,
    })

    const res = await deleteCrontab(row.crontab_id)
    ElMessage.success(res.msg || '删除成功')
    getList()
  } catch (error) {
    // 用户取消操作或错误消息由 request 拦截器统一处理
  }
}

/**
 * 格式化最后执行时间
 */
const formatLastExecuteTime = (timestamp: number) => {
  if (!timestamp) return '从未执行'
  return formatTimestamp(timestamp)
}

/**
 * 格式化下次执行时间
 */
const formatNextExecuteTime = (timestamp: number) => {
  if (!timestamp) return '-'
  return formatTimestamp(timestamp)
}

/**
 * 格式化执行时间
 */
const formatExecuteTime = (timestamp: number) => {
  if (!timestamp) return '-'
  return formatTimestamp(timestamp)
}

onMounted(() => {
  handleSearch()
})
</script>

<style scoped lang="scss">
.crontab-list {
  .cron-code {
    font-family: 'Courier New', monospace;
    font-size: 12px;
    padding: 2px 6px;
    background-color: var(--el-fill-color);
    border-radius: 3px;
  }

  // 执行记录表格样式
  :deep(.el-table) {
    .el-tag {
      display: inline-flex;
      align-items: center;
      gap: 4px;

      .el-icon {
        font-size: 14px;
      }
    }
  }

  // 日志详情内容
  .log-detail-content {
    background-color: #f5f5f5;
    padding: 16px;
    border-radius: 4px;
    font-family: 'Courier New', monospace;
    font-size: 13px;
    line-height: 1.6;
    white-space: pre-wrap;
    word-break: break-all;
    max-height: 500px;
    overflow-y: auto;
  }
}
</style>
