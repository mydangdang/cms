<template>
  <div class="config-list">
    <el-card>
      <template #header>
        <div class="card-header">
          <span>系统配置</span>
          <el-button type="primary" v-permission="'system:config:add'" @click="handleAdd">新增配置</el-button>
        </div>
      </template>

      <!-- 搜索表单 -->
      <el-form :inline="true" :model="searchForm" class="search-form">
        <el-form-item label="配置分组">
          <el-select v-model="searchForm.config_group" placeholder="请选择分组" style="width: 150px" clearable>
            <el-option label="全部" :value="-1" />
            <el-option v-for="(name, value) in ConfigGroupNames" :key="value" :label="name" :value="Number(value)" />
          </el-select>
        </el-form-item>
        <el-form-item label="配置名称">
          <el-input v-model="searchForm.config_name" placeholder="请输入配置名称" clearable style="width: 180px" />
        </el-form-item>
        <el-form-item label="配置键">
          <el-input v-model="searchForm.config_key" placeholder="请输入配置键" clearable style="width: 180px" />
        </el-form-item>
        <el-form-item>
          <el-button type="primary" @click="handleSearch">查询</el-button>
          <el-button @click="handleReset">重置</el-button>
        </el-form-item>
      </el-form>

      <!-- 数据表格 -->
      <el-table :data="tableData" border v-loading="loading" class="data-table">
        <el-table-column prop="config_id" label="ID" width="120" />
        <el-table-column prop="config_name" label="配置名称" />
        <el-table-column prop="config_key" label="配置键" />
        <el-table-column prop="group_name" label="配置分组">
          <template #default="{ row }">
            <el-tag :type="getGroupTagType(row.config_group)" size="small">
              {{ row.group_name || '-' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="config_value" label="配置值" show-overflow-tooltip />
        <el-table-column prop="type_name" label="配置类型" width="120" />
        <el-table-column prop="description" label="配置描述" show-overflow-tooltip />
        <el-table-column prop="sort_order" label="排序" width="120" />
        <el-table-column label="更新时间" width="180">
          <template #default="{ row }">
            <span :class="{ 'text-today': isToday(row.updated_at) }">
              {{ formatTimestamp(row.updated_at) }}
            </span>
          </template>
        </el-table-column>
        <el-table-column label="操作" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" plain size="small" v-permission="'system:config:edit'" @click="handleEdit(row)">编辑</el-button>
            <el-button type="danger" plain size="small" v-permission="'system:config:delete'" @click="handleDelete(row)">删除</el-button>
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
    <el-dialog v-model="dialogVisible" :title="dialogTitle" width="600px" :close-on-click-modal="false" @close="handleCloseDialog">
      <el-form :model="formData" :rules="formRules" ref="formRef" label-width="100px">
        <el-form-item label="配置分组" prop="config_group">
          <el-select v-model="formData.config_group" placeholder="请选择分组" style="width: 100%">
            <el-option v-for="(name, value) in ConfigGroupNames" :key="value" :label="name" :value="Number(value)" />
          </el-select>
        </el-form-item>
        <el-form-item label="配置名称" prop="config_name">
          <el-input v-model="formData.config_name" placeholder="请输入配置名称" />
        </el-form-item>
        <el-form-item label="配置键" prop="config_key">
          <el-input v-model="formData.config_key" placeholder="请输入配置键（英文，如：site_name）" />
        </el-form-item>
        <el-form-item label="配置类型" prop="config_type">
          <el-select v-model="formData.config_type" placeholder="请选择配置类型" style="width: 100%">
            <el-option v-for="(name, value) in ConfigTypeNames" :key="value" :label="name" :value="value" />
          </el-select>
        </el-form-item>
        <el-form-item label="配置值" prop="config_value">
          <!-- 根据类型显示不同控件 -->
          <el-input
            v-if="formData.config_type === 'text'"
            v-model="formData.config_value"
            placeholder="请输入配置值"
          />
          <el-input-number
            v-else-if="formData.config_type === 'number'"
            v-model="formData.config_value"
            :min="0"
            controls-position="right"
            style="width: 100%"
          />
          <el-switch
            v-else-if="formData.config_type === 'boolean'"
            v-model="formData.config_value"
            :active-value="1"
            :inactive-value="0"
            active-text="启用"
            inactive-text="禁用"
          />
          <el-input
            v-else-if="formData.config_type === 'array'"
            v-model="formData.config_value"
            type="textarea"
            :rows="4"
            placeholder="参考格式：&#10;1:A&#10;2:B"
          />
          <el-input
            v-else
            v-model="formData.config_value"
            type="textarea"
            :rows="3"
            placeholder="请输入配置值"
          />
        </el-form-item>
        <el-form-item label="配置描述" prop="description">
          <el-input v-model="formData.description" type="textarea" :rows="2" placeholder="请输入配置描述" />
        </el-form-item>
        <el-form-item label="排序" prop="sort_order">
          <el-input-number v-model="formData.sort_order" :min="0" controls-position="right" style="width: 100%" />
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
import { ref, reactive, onMounted } from 'vue'
import { ElMessageBox, ElMessage, type FormInstance, type FormRules } from 'element-plus'
import {
  getConfigList,
  addConfig,
  editConfig as apiEditConfig,
  deleteConfig as apiDeleteConfig,
  type ConfigItem,
  type ConfigForm,
  ConfigGroupNames,
  ConfigTypeNames
} from '@/api/config'
import { isToday, formatTimestamp } from '@/utils/commonUtils'

// 组件名称用于 KeepAlive 缓存
defineOptions({
  name: 'system:config'
})

/**
 * 系统配置列表页面
 * Story 3.1: 系统配置管理
 * 优化：表格展示、新增、编辑、删除
 */

// 搜索表单
const searchForm = reactive({
  config_group: -1 as number,
  config_name: '',
  config_key: ''
})

// 表格数据
const tableData = ref<ConfigItem[]>([])

// 分页
const pagination = reactive({
  page: 1,
  limit: 20,
  total: 0
})

// 加载状态
const loading = ref(false)

// 弹窗状态
const dialogVisible = ref(false)
const dialogTitle = ref('新增配置')
const submitLoading = ref(false)
const formRef = ref<FormInstance>()

// 表单数据
const formData = reactive<ConfigForm>({
  config_id: undefined,
  config_group: 0,
  config_name: '',
  config_key: '',
  config_value: '',
  config_type: 'text',
  description: '',
  sort_order: 0
})

// 表单验证规则
const formRules: FormRules = {
  config_group: [{ required: true, message: '请选择配置分组', trigger: 'change' }],
  config_name: [
    { required: true, message: '请输入配置名称', trigger: 'blur' },
    { max: 50, message: '配置名称长度不能超过50个字符', trigger: 'blur' }
  ],
  config_key: [
    { required: true, message: '请输入配置键', trigger: 'blur' },
    {
      pattern: /^[a-zA-Z0-9_]+$/,
      message: '配置键只能包含字母、数字和下划线',
      trigger: 'blur'
    },
    { max: 50, message: '配置键长度不能超过50个字符', trigger: 'blur' }
  ],
  config_type: [{ required: true, message: '请选择配置类型', trigger: 'change' }],
  config_value: [{ required: true, message: '请输入配置值', trigger: 'blur' }],
  sort_order: [
    {
      type: 'number',
      min: 0,
      message: '排序值不能小于0',
      trigger: 'blur'
    }
  ]
}

/**
 * 获取配置列表
 */
const loadConfigList = async () => {
  try {
    loading.value = true
    const params: any = {
      page: pagination.page,
      limit: pagination.limit
    }

    // 只有当 config_group 不为 -1（全部）时才添加查询参数
    if (searchForm.config_group !== -1) {
      params.config_group = searchForm.config_group
    }

    // 配置名称模糊查询
    if (searchForm.config_name) {
      params.config_name = searchForm.config_name
    }

    // 配置键模糊查询
    if (searchForm.config_key) {
      params.config_key = searchForm.config_key
    }

    const res = await getConfigList(params)
    tableData.value = res.data.list || []
    pagination.total = res.data.total || 0
  } catch (error) {
    // 错误消息由 request 拦截器统一处理
  } finally {
    loading.value = false
  }
}

/**
 * 搜索
 */
const handleSearch = () => {
  pagination.page = 1
  loadConfigList()
}

/**
 * 重置搜索
 */
const handleReset = () => {
  searchForm.config_group = -1
  searchForm.config_name = ''
  searchForm.config_key = ''
  pagination.page = 1
  loadConfigList()
}

/**
 * 新增
 */
const handleAdd = () => {
  dialogTitle.value = '新增配置'
  Object.assign(formData, {
    config_id: undefined,
    config_group: 0,
    config_name: '',
    config_key: '',
    config_value: '',
    config_type: 'text',
    description: '',
    sort_order: 0
  })
  dialogVisible.value = true
}

/**
 * 编辑
 */
const handleEdit = (row: ConfigItem) => {
  dialogTitle.value = '编辑配置'
  Object.assign(formData, {
    config_id: row.config_id,
    config_group: row.config_group,
    config_name: row.config_name,
    config_key: row.config_key,
    config_value: row.config_value,
    config_type: row.config_type,
    description: row.description || '',
    sort_order: row.sort_order
  })
  dialogVisible.value = true
}

/**
 * 删除
 */
const handleDelete = (row: ConfigItem) => {
  ElMessageBox.confirm(`确认删除配置"${row.config_name}"吗？`, '提示', {
    confirmButtonText: '确定',
    cancelButtonText: '取消',
    type: 'warning',
    closeOnClickModal: false
  }).then(async () => {
    try {
      await apiDeleteConfig(row.config_id)
      ElMessage.success('删除成功')
      loadConfigList()
    } catch (error) {
      // 错误消息由 request 拦截器统一处理
    }
  }).catch(() => {
    // 取消删除
  })
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

      // 处理配置值（布尔类型转为数字）
      const submitData = { ...formData }
      if (submitData.config_type === 'boolean') {
        submitData.config_value = String(submitData.config_value === '1' || submitData.config_value === 1 ? 1 : 0)
      } else if (submitData.config_type === 'number') {
        submitData.config_value = String(submitData.config_value || 0)
      } else {
        submitData.config_value = String(submitData.config_value || '')
      }

      if (submitData.config_id) {
        await apiEditConfig(submitData)
        ElMessage.success('更新成功')
      } else {
        await addConfig(submitData)
        ElMessage.success('添加成功')
      }

      dialogVisible.value = false
      loadConfigList()
    } catch (error) {
      // 错误消息由 request 拦截器统一处理
    } finally {
      submitLoading.value = false
    }
  })
}

/**
 * 关闭弹窗
 */
const handleCloseDialog = () => {
  formRef.value?.resetFields()
}

/**
 * 获取分组标签类型
 */
const getGroupTagType = (group: number) => {
  const typeMap: Record<number, any> = {
    0: 'info',
    1: 'success',
    2: 'warning',
    3: 'danger'
  }
  return typeMap[group] || 'info'
}

onMounted(() => {
  loadConfigList()
})
</script>

<style scoped lang="scss">
.config-list {
  .card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .search-form {
    margin-bottom: 20px;
  }

  .text-today {
    color: var(--el-color-danger);
    font-weight: 500;
  }

  // 表格宽度 100%，列均匀分布
  .data-table {
    width: 100%;
    table-layout: auto;

    :deep(.el-table__header-wrapper),
    :deep(.el-table__body-wrapper) {
      width: 100% !important;
    }

    :deep(.el-table__header),
    :deep(.el-table__body) {
      width: 100% !important;
      table-layout: auto;
    }

    :deep(.el-table__header th) {
      padding: 12px 0;
      text-align: center;
    }

    :deep(.el-table__body td) {
      padding: 12px 0;
      text-align: center;
    }
  }

  .el-pagination {
    margin-top: 20px;
    display: flex;
    justify-content: flex-end;
  }
}
</style>
