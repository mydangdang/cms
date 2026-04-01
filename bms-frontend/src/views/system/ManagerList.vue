<template>
  <div class="manager-list">
    <el-card>
      <template #header>
        <div class="card-header">
          <span>管理员管理</span>
          <el-button type="primary" v-permission="'system:manager:add'" @click="handleAdd"
            >新增</el-button
          >
        </div>
      </template>

      <!-- 搜索表单 -->
      <el-form :inline="true" :model="searchForm" class="search-form">
        <el-form-item label="用户名">
          <el-input
            v-model="searchForm.username"
            placeholder="请输入用户名"
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
        <el-table-column prop="admin_id" label="ID" width="120" />
        <el-table-column prop="username" label="用户名" />
        <el-table-column prop="real_name" label="真实姓名" />
        <el-table-column prop="mobile" label="手机号" />
        <el-table-column label="角色">
          <template #default="{ row }">
            <span v-if="row.is_super === 1"
              >系统管理员<span class="super-admin-tag">(超管)</span></span
            >
            <span v-else-if="row.roles && row.roles.length > 0">
              {{ formatRoles(row.roles) }}
            </span>
            <span v-else class="text-gray">未分配</span>
          </template>
        </el-table-column>
        <el-table-column label="状态" width="130">
          <template #default="{ row }">
            <StatusTag :status="row.status" />
          </template>
        </el-table-column>
        <el-table-column label="最近登录时间" width="180">
          <template #default="{ row }">
            <span :class="{ 'text-today': isToday(row.last_login_time) }">
              {{ formatLastLoginTime(row.last_login_time) }}
            </span>
          </template>
        </el-table-column>
        <el-table-column label="操作" fixed="right" width="220">
          <template #default="{ row }">
            <el-button
              type="primary"
              plain
              size="small"
              v-permission="'system:manager:edit'"
              @click="handleEdit(row)"
              >编辑</el-button
            >
            <el-button
              type="danger"
              plain
              size="small"
              v-permission="'system:manager:delete'"
              v-if="row.is_super !== 1"
              @click="handleDelete(row)"
              >删除</el-button
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
        <el-form-item label="选择角色" prop="role_ids">
          <el-select
            v-model="formData.role_ids"
            placeholder="请选择角色"
            multiple
            style="width: 100%"
          >
            <el-option
              v-for="role in roleList"
              :key="role.role_id"
              :label="role.name"
              :value="role.role_id"
            />
          </el-select>
        </el-form-item>
        <el-form-item label="用户名" prop="username">
          <el-input
            v-model="formData.username"
            placeholder="请输入用户名"
            :disabled="formData.admin_id > 0"
          />
        </el-form-item>
        <el-form-item label="真实姓名" prop="real_name">
          <el-input v-model="formData.real_name" placeholder="请输入真实姓名" />
        </el-form-item>
        <el-form-item label="密码" prop="password">
          <el-input
            v-model="formData.password"
            type="password"
            :placeholder="formData.admin_id > 0 ? '不修改密码请保留为空' : '请输入密码'"
            show-password
          />
        </el-form-item>
        <el-form-item label="手机号" prop="mobile">
          <el-input v-model="formData.mobile" placeholder="请输入手机号" />
        </el-form-item>
        <el-form-item label="状态" prop="status">
          <el-radio-group v-model="formData.status">
            <el-radio :value="1">启用</el-radio>
            <el-radio :value="0">禁用</el-radio>
          </el-radio-group>
        </el-form-item>
      </el-form>
      <template #footer>
        <el-button @click="dialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleSubmit">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted } from 'vue'
import { ElMessageBox, ElMessage, type FormRules } from 'element-plus'
import { getManagerList, addManager, editManager, deleteManager, type Manager } from '@/api/manager'
import { getRoleList, type Role } from '@/api/role'
import { isToday, formatTimestamp, STATUS_OPTIONS } from '@/utils/commonUtils'
import StatusTag from '@/components/Common/StatusTag.vue'

// 组件名称用于 KeepAlive 缓存
defineOptions({
  name: 'system:manager',
})

/**
 * 管理员列表页面
 * Story 2.3: 管理员管理（后端 API）
 */

// 搜索表单
const searchForm = reactive({
  username: '',
  status: 1 as number, // 默认启用状态，-1 表示全部
})

// 表格数据
const tableData = ref<Manager[]>([])

// 角色列表
const roleList = ref<Role[]>([])

// 分页
const pagination = reactive({
  page: 1,
  limit: 10,
  total: 0,
})

// 加载状态
const loading = ref(false)

/**
 * 获取管理员列表
 */
const getList = async () => {
  try {
    loading.value = true
    const params: any = {
      page: pagination.page,
      limit: pagination.limit,
    }
    if (searchForm.username) {
      params.username = searchForm.username
    }
    if (searchForm.status !== -1) {
      params.status = searchForm.status
    }

    const res = await getManagerList(params)
    tableData.value = res.data?.list || []
    pagination.total = res.data?.total || 0
  } catch (error) {
    // 错误消息由 request 拦截器统一处理
  } finally {
    loading.value = false
  }
}

/**
 * 获取角色列表
 */
const getRoles = async () => {
  try {
    const res = await getRoleList({ status: 1, limit: 100 })
    if (res.code === 200) {
      roleList.value = res.data?.list || []
    }
  } catch (error) {
    console.error('获取角色列表失败:', error)
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
  searchForm.username = ''
  searchForm.status = 1 // 重置为默认启用状态
  pagination.page = 1
  getList()
}

// 弹窗显示控制
const dialogVisible = ref(false)
const dialogTitle = ref('')
const formRef = ref()

// 表单数据
const formData = reactive({
  admin_id: 0,
  username: '',
  password: '',
  real_name: '',
  mobile: '',
  status: 1,
  role_ids: [] as number[],
})

// 表单验证规则
const formRules: FormRules = {
  role_ids: [{ required: true, message: '请选择角色', trigger: 'change', type: 'array' as const }],
  username: [{ required: true, message: '请输入用户名', trigger: 'blur' }],
  real_name: [{ required: true, message: '请输入真实姓名', trigger: 'blur' }],
  password: [
    {
      validator: (_rule: any, value: string, callback: any) => {
        // 新增时必须填写密码
        if (formData.admin_id === 0 && !value) {
          callback(new Error('请输入密码'))
        }
        // 编辑时如果填写了密码，则验证格式
        if (formData.admin_id > 0 && value && value.length < 6) {
          callback(new Error('密码长度不能少于6位'))
        }
        callback()
      },
      trigger: 'blur',
    },
  ],
  mobile: [{ pattern: /^1[3-9]\d{9}$/, message: '请输入正确的手机号', trigger: 'blur' }],
}

/**
 * 新增
 */
const handleAdd = () => {
  dialogTitle.value = '新增管理员'
  Object.assign(formData, {
    admin_id: 0,
    username: '',
    password: '',
    real_name: '',
    mobile: '',
    status: 1,
    role_ids: [],
  })
  dialogVisible.value = true
}

/**
 * 编辑
 */
const handleEdit = (row: Manager) => {
  dialogTitle.value = '编辑管理员'
  Object.assign(formData, {
    admin_id: row.admin_id,
    username: row.username,
    password: '',
    real_name: row.real_name || '',
    mobile: row.mobile || '',
    status: row.status,
    role_ids: row.role_ids || [],
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
    const isEdit = formData.admin_id > 0

    if (isEdit) {
      // 编辑时，如果填写了密码则更新密码
      const editData: any = {
        admin_id: formData.admin_id,
        username: formData.username,
        real_name: formData.real_name,
        mobile: formData.mobile,
        status: formData.status,
        role_ids: formData.role_ids,
      }
      // 只有密码不为空时才传密码字段
      if (formData.password) {
        editData.password = formData.password
      }
      const res = await editManager(editData)
      ElMessage.success(res.msg || '编辑成功')
    } else {
      const res = await addManager({
        username: formData.username,
        password: formData.password,
        real_name: formData.real_name,
        mobile: formData.mobile,
        status: formData.status,
        role_ids: formData.role_ids,
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
 * 格式化角色名称
 */
const formatRoles = (roles: Role[]) => {
  return roles.map((r) => r.name).join(', ')
}

/**
 * 格式化最近登录时间
 */
const formatLastLoginTime = (timestamp: number) => {
  if (!timestamp) return '从未登录'
  return formatTimestamp(timestamp)
}

/**
 * 删除
 */
const handleDelete = async (row: Manager) => {
  try {
    await ElMessageBox.confirm('确定要删除该管理员吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning',
      closeOnClickModal: false,
    })

    const res = await deleteManager(row.admin_id)
    ElMessage.success(res.msg || '删除成功')
    getList()
  } catch (error) {
    // 用户取消操作或错误消息由 request 拦截器统一处理
  }
}

onMounted(() => {
  getRoles()
  handleSearch()
})
</script>

<style scoped lang="scss">
.manager-list {
  .super-admin-tag {
    color: #f56c6c;
    margin-left: 10px;
  }
}
</style>
