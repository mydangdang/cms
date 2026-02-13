<template>
  <div class="permission-list">
    <el-card>
      <template #header>
        <div class="card-header">
          <span>权限管理</span>
          <div class="header-actions">
            <el-button @click="getList">刷新</el-button>
            <el-button type="primary" v-permission="'system:permission:add'" @click="handleAdd">新增</el-button>
          </div>
        </div>
      </template>

      <!-- 数据表格 -->
      <el-table
        :data="tableData"
        border
        v-loading="loading"
        class="data-table"
        row-key="permission_id"
        :tree-props="{ children: 'children', hasChildren: 'hasChildren' }"
      >
        <el-table-column prop="title" label="权限名称" />
        <el-table-column prop="code" label="权限编码" />
        <el-table-column label="类型" width="150">
          <template #default="{ row }">
            <el-tag v-if="row.type === 1" type="success">目录</el-tag>
            <el-tag v-else-if="row.type === 2" type="primary">菜单</el-tag>
            <el-tag v-else-if="row.type === 3" type="warning">按钮</el-tag>
            <el-tag v-else-if="row.type === 4" type="info">接口</el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="path" label="路由路径" show-overflow-tooltip />
        <el-table-column prop="icon" label="图标" width="120">
          <template #default="{ row }">
            <el-icon v-if="row.icon" :size="18">
              <component :is="row.icon" />
            </el-icon>
            <span v-else class="text-gray">-</span>
          </template>
        </el-table-column>
        <el-table-column prop="sort_order" label="排序" width="100" />
        <el-table-column label="隐藏" width="80" align="center">
          <template #default="{ row }">
            <el-tag :type="row.is_hidden === 1 ? 'warning' : 'info'" size="small">
              {{ row.is_hidden === 1 ? '是' : '否' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="固定" width="80" align="center">
          <template #default="{ row }">
            <el-tag :type="row.is_affix === 1 ? 'success' : 'info'" size="small">
              {{ row.is_affix === 1 ? '是' : '否' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="缓存" width="80" align="center">
          <template #default="{ row }">
            <el-tag :type="row.is_cache === 1 ? 'success' : 'info'" size="small">
              {{ row.is_cache === 1 ? '是' : '否' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column label="状态" width="150">
          <template #default="{ row }">
            <StatusTag :status="row.status" />
          </template>
        </el-table-column>
        <el-table-column label="操作" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" plain size="small" v-permission="'system:permission:edit'" @click="handleEdit(row)">编辑</el-button>
            <el-button type="success" plain size="small" v-permission="'system:permission:add'" @click="handleAddChild(row)">新增子项</el-button>
            <el-button type="danger" plain size="small" v-permission="'system:permission:delete'" @click="handleDelete(row)">删除</el-button>
          </template>
        </el-table-column>
      </el-table>
    </el-card>

    <!-- 新增/编辑弹窗 -->
    <el-dialog v-model="dialogVisible" :title="dialogTitle" width="700px" :close-on-click-modal="false" @close="handleCloseDialog">
      <el-form :model="formData" :rules="formRules" ref="formRef" label-width="100px">
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="上级权限">
              <el-tree-select
                v-model="formData.parent_id"
                :data="permissionTreeData"
                :props="{ label: 'title', value: 'permission_id', children: 'children' }"
                placeholder="请选择上级权限"
                check-strictly
                clearable
                :render-after-expand="false"
              />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="权限类型" prop="type">
              <el-select v-model="formData.type" placeholder="请选择类型">
                <el-option label="目录" :value="1" />
                <el-option label="菜单" :value="2" />
                <el-option label="按钮" :value="3" />
                <el-option label="接口" :value="4" />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="权限标题" prop="title">
              <el-input v-model="formData.title" placeholder="请输入权限标题" />
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="权限编码" prop="code">
              <el-input v-model="formData.code" placeholder="请输入权限编码，如：system:user:list" />
            </el-form-item>
          </el-col>
        </el-row>
        <el-form-item label="路由路径" v-if="[1, 2].includes(formData.type)">
          <el-input v-model="formData.path" placeholder="请输入路由路径，如：/system/user" />
        </el-form-item>
        <el-form-item label="组件路径" v-if="formData.type === 2">
          <el-input v-model="formData.component" placeholder="请输入组件路径，如：@/views/system/UserList.vue" />
        </el-form-item>
        <el-row :gutter="20">
          <el-col :span="12">
            <el-form-item label="图标" v-if="[1, 2].includes(formData.type)">
              <el-input v-model="formData.icon" placeholder="请输入图标名称">
                <template #append>
                  <el-icon><component :is="formData.icon || 'QuestionFilled'" /></el-icon>
                </template>
              </el-input>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="排序">
              <el-input-number v-model="formData.sort_order" :min="0" :max="9999" />
            </el-form-item>
          </el-col>
        </el-row>
        <el-row :gutter="20" v-if="[1, 2].includes(formData.type)">
          <el-col :span="8">
            <el-form-item label="是否隐藏">
              <el-switch v-model="formData.is_hidden" :active-value="1" :inactive-value="0" />
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="是否固定">
              <el-switch v-model="formData.is_affix" :active-value="1" :inactive-value="0" />
            </el-form-item>
          </el-col>
          <el-col :span="8">
            <el-form-item label="开启缓存">
              <el-switch v-model="formData.is_cache" :active-value="1" :inactive-value="0" />
            </el-form-item>
          </el-col>
        </el-row>
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
import { ref, reactive, onMounted, computed } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import {
  getPermissionList,
  addPermission,
  editPermission,
  deletePermission,
  type Permission
} from '@/api/permission'
import StatusTag from '@/components/Common/StatusTag.vue'

// 组件名称用于 KeepAlive 缓存
defineOptions({
  name: 'system:permission'
})

/**
 * 权限列表页面
 * Story 2.1: 权限菜单管理（后端 API）
 */

// 表格数据（树形结构不需要分页）
const tableData = ref<Permission[]>([])

// 加载状态
const loading = ref(false)

// 权限树数据（用于选择上级权限）
const permissionTreeData = computed(() => {
  return [{ permission_id: 0, title: '顶级权限', children: tableData.value }]
})

/**
 * 获取权限列表
 */
const getList = async () => {
  try {
    loading.value = true
    const res = await getPermissionList({})
    tableData.value = res.data || []
  } catch (error) {
    // 错误消息由 request 拦截器统一处理
  } finally {
    loading.value = false
  }
}

// 弹窗显示控制
const dialogVisible = ref(false)
const dialogTitle = ref('')
const formRef = ref()

// 表单数据
const formData = reactive({
  permission_id: 0,
  parent_id: 0,
  type: 1,
  title: '',
  code: '',
  path: '',
  component: '',
  icon: '',
  is_hidden: 0,
  is_affix: 0,
  is_cache: 0,
  sort_order: 0,
  status: 1
})

// 表单验证规则
const formRules = {
  type: [{ required: true, message: '请选择权限类型', trigger: 'change' }],
  title: [{ required: true, message: '请输入权限标题', trigger: 'blur' }],
  code: [{ required: true, message: '请输入权限编码', trigger: 'blur' }]
}

/**
 * 新增
 */
const handleAdd = () => {
  dialogTitle.value = '新增权限'
  Object.assign(formData, {
    permission_id: 0,
    parent_id: 0,
    type: 1,
    title: '',
    code: '',
    path: '',
    component: '',
    icon: '',
    is_hidden: 0,
    is_affix: 0,
    is_cache: 0,
    sort_order: 0,
    status: 1
  })
  dialogVisible.value = true
}

/**
 * 新增子项
 */
const handleAddChild = (row: Permission) => {
  dialogTitle.value = '新增子权限'
  Object.assign(formData, {
    permission_id: 0,
    parent_id: row.permission_id,
    type: row.type === 1 ? 2 : 3,
    title: '',
    code: '',
    path: '',
    component: '',
    icon: '',
    is_hidden: 0,
    is_affix: 0,
    is_cache: 0,
    sort_order: 0,
    status: 1
  })
  dialogVisible.value = true
}

/**
 * 编辑
 */
const handleEdit = (row: Permission) => {
  dialogTitle.value = '编辑权限'
  Object.assign(formData, {
    permission_id: row.permission_id,
    parent_id: row.parent_id,
    type: row.type,
    title: row.title,
    code: row.code,
    path: row.path || '',
    component: row.component || '',
    icon: row.icon || '',
    is_hidden: row.is_hidden,
    is_affix: row.is_affix,
    is_cache: row.is_cache,
    sort_order: row.sort_order,
    status: row.status
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
    const isEdit = formData.permission_id > 0

    if (isEdit) {
      const res = await editPermission({
        permission_id: formData.permission_id,
        parent_id: formData.parent_id,
        type: formData.type,
        title: formData.title,
        code: formData.code,
        path: formData.path,
        component: formData.component,
        icon: formData.icon,
        is_hidden: formData.is_hidden,
        is_affix: formData.is_affix,
        is_cache: formData.is_cache,
        sort_order: formData.sort_order,
        status: formData.status
      })
      ElMessage.success(res.msg || '编辑成功')
    } else {
      const res = await addPermission({
        parent_id: formData.parent_id,
        type: formData.type,
        title: formData.title,
        code: formData.code,
        path: formData.path,
        component: formData.component,
        icon: formData.icon,
        is_hidden: formData.is_hidden,
        is_affix: formData.is_affix,
        is_cache: formData.is_cache,
        sort_order: formData.sort_order,
        status: formData.status
      })
      ElMessage.success(res.msg || '新增成功')
    }

    dialogVisible.value = false
    getList()
  } catch (error: any) {
    if (error.msg) {
      ElMessage.error(error.msg || '操作失败')
    }
  }
}

/**
 * 关闭弹窗
 */
const handleCloseDialog = () => {
  formRef.value?.resetFields()
}

/**
 * 删除
 */
const handleDelete = async (row: Permission) => {
  // 检查是否有子权限
  if (row.children && row.children.length > 0) {
    ElMessage.warning('该权限下有子权限，不能删除')
    return
  }

  try {
    await ElMessageBox.confirm('确定要删除该权限吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning',
      closeOnClickModal: false
    })

    const res = await deletePermission(row.permission_id)
    ElMessage.success(res.msg || '删除成功')
    getList()
  } catch (error) {
    // 用户取消操作或错误消息由 request 拦截器统一处理
  }
}

onMounted(() => {
  getList()
})
</script>

<style scoped lang="scss">
.permission-list {
  .card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;

    .header-actions {
      display: flex;
      gap: 10px;
    }
  }

  // 表格宽度 100%，列左对齐
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
      text-align: left;
    }

    :deep(.el-table__body td) {
      padding: 12px 0;
      text-align: left;
    }

    .text-gray {
      color: #999;
    }
  }
}
</style>
