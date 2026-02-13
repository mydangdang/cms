<template>
  <div class="role-list">
    <el-card>
      <template #header>
        <div class="card-header">
          <span>角色管理</span>
          <el-button type="primary" v-permission="'system:role:add'" @click="handleAdd">新增</el-button>
        </div>
      </template>

      <!-- 搜索表单 -->
      <el-form :inline="true" :model="searchForm" class="search-form">
        <el-form-item label="角色名称">
          <el-input v-model="searchForm.name" placeholder="请输入角色名称" clearable style="width: 200px" />
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
        <el-table-column prop="role_id" label="ID" />
        <el-table-column prop="name" label="角色名称" />
        <el-table-column prop="description" label="描述" show-overflow-tooltip />
        <el-table-column label="状态">
          <template #default="{ row }">
            <StatusTag :status="row.status" />
          </template>
        </el-table-column>
        <el-table-column prop="sort_order" label="排序" />
        <el-table-column label="操作" fixed="right">
          <template #default="{ row }">
            <el-button type="primary" plain size="small" v-permission="'system:role:edit'" @click="handleEdit(row)">编辑</el-button>
            <el-button type="warning" plain size="small" v-permission="'system:role:assign'" @click="handlePermission(row)">权限</el-button>
            <el-button type="danger" plain size="small" v-permission="'system:role:delete'" @click="handleDelete(row)">删除</el-button>
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
        <el-form-item label="角色名称" prop="name">
          <el-input v-model="formData.name" placeholder="请输入角色名称" />
        </el-form-item>
        <el-form-item label="描述" prop="description">
          <el-input v-model="formData.description" type="textarea" :rows="3" placeholder="请输入描述" />
        </el-form-item>
        <el-form-item label="排序" prop="sort_order">
          <el-input-number v-model="formData.sort_order" :min="0" :max="9999" />
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

    <!-- 权限分配弹窗 -->
    <el-dialog v-model="permissionDialogVisible" :title="permissionDialogTitle" width="600px" :close-on-click-modal="false" @close="handleClosePermissionDialog">
      <el-tree
        ref="permissionTreeRef"
        :data="permissionTreeData"
        :props="{ label: 'title', children: 'children' }"
        node-key="permission_id"
        show-checkbox
        :key="permissionTreeKey"
      >
        <template #default="{ data }">
          <span class="permission-node">
            <span>{{ data.title }}</span>
            <span v-if="data.code" class="permission-code">({{ data.code }})</span>
            <span class="permission-type" :class="'type-' + data.type">
              {{ getTypeName(data.type) }}
            </span>
          </span>
        </template>
      </el-tree>
      <template #footer>
        <el-button @click="permissionDialogVisible = false">取消</el-button>
        <el-button type="primary" @click="handleAssignPermission">确定</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, reactive, onMounted, nextTick } from 'vue'
import { ElMessageBox, ElMessage } from 'element-plus'
import { getRoleList, addRole, editRole, deleteRole, assignPermission, type Role } from '@/api/role'
import { getPermissionList, type Permission } from '@/api/permission'
import { STATUS_OPTIONS } from '@/utils/commonUtils'
import StatusTag from '@/components/Common/StatusTag.vue'

/**
 * 角色列表页面
 * Story 2.2: 角色管理（后端 API）
 */

// 搜索表单
const searchForm = reactive({
  name: '',
  status: 1 as number // 默认启用状态，-1 表示全部
})

// 表格数据
const tableData = ref<Role[]>([])

// 分页
const pagination = reactive({
  page: 1,
  limit: 10,
  total: 0
})

// 加载状态
const loading = ref(false)

/**
 * 获取角色列表
 */
const getList = async () => {
  try {
    loading.value = true
    const params: any = {
      page: pagination.page,
      limit: pagination.limit
    }
    if (searchForm.name) {
      params.name = searchForm.name
    }
    if (searchForm.status !== -1) {
      params.status = searchForm.status
    }

    const res = await getRoleList(params)
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
  role_id: 0,
  name: '',
  description: '',
  sort_order: 0,
  status: 1
})

// 表单验证规则
const formRules = {
  name: [{ required: true, message: '请输入角色名称', trigger: 'blur' }]
}

/**
 * 新增
 */
const handleAdd = () => {
  dialogTitle.value = '新增角色'
  Object.assign(formData, {
    role_id: 0,
    name: '',
    description: '',
    sort_order: 0,
    status: 1
  })
  dialogVisible.value = true
}

/**
 * 编辑
 */
const handleEdit = (row: Role) => {
  dialogTitle.value = '编辑角色'
  Object.assign(formData, {
    role_id: row.role_id,
    name: row.name,
    description: row.description,
    sort_order: row.sort_order || 0,
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
    const isEdit = formData.role_id > 0

    if (isEdit) {
      const res = await editRole({
        role_id: formData.role_id,
        name: formData.name,
        description: formData.description,
        sort_order: formData.sort_order,
        status: formData.status
      })
      ElMessage.success(res.msg || '编辑成功')
    } else {
      const res = await addRole({
        name: formData.name,
        description: formData.description,
        sort_order: formData.sort_order,
        status: formData.status
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

// 权限分配相关
const permissionDialogVisible = ref(false)
const permissionDialogTitle = ref('权限分配')
const permissionTreeRef = ref()
const permissionTreeData = ref<Permission[]>([])
const permissionTreeKey = ref(0) // 用于强制重新渲染树
const currentRoleId = ref(0)
const currentRoleName = ref('')
const checkedPermissionIds = ref<number[]>([]) // 当前选中的权限ID列表

/**
 * 权限设置
 */
const handlePermission = async (row: Role) => {
  currentRoleId.value = row.role_id
  currentRoleName.value = row.name
  permissionDialogTitle.value = `${row.name}_权限分配`

  // 清空之前的选中状态和树数据
  checkedPermissionIds.value = []
  permissionTreeData.value = []

  // 强制树重新渲染（通过改变 key）
  permissionTreeKey.value = Date.now()

  permissionDialogVisible.value = true

  try {
    const res = await getPermissionList({ type: undefined })
    if (res.code === 200) {
      // 按类型排序权限树
      permissionTreeData.value = sortPermissionsByType(res.data || [])

      // 保存当前角色的权限ID
      if (row.permission_ids && row.permission_ids.length > 0) {
        checkedPermissionIds.value = row.permission_ids
      }

      // 使用 nextTick 等待 DOM 更新后设置选中状态
      await nextTick()

      // 先清空所有选中
      permissionTreeRef.value?.setCheckedKeys([])

      // 等待下一个 tick 后设置选中状态
      await nextTick()

      // 设置正确的选中状态
      // 只设置叶子节点的ID，避免父节点自动展开导致全选
      if (checkedPermissionIds.value.length > 0) {
        const leafIds = filterLeafPermissionIds(
          checkedPermissionIds.value,
          permissionTreeData.value
        )
        permissionTreeRef.value?.setCheckedKeys(leafIds)
      }
    }
  } catch (error) {
    console.error('获取权限列表失败:', error)
  }
}

/**
 * 递归排序权限（按 type 升序）
 */
const sortPermissionsByType = (permissions: Permission[]): Permission[] => {
  if (!permissions || permissions.length === 0) {
    return []
  }

  // 先对子权限进行排序
  const sorted = permissions.map((p) => ({
    ...p,
    children: sortPermissionsByType(p.children || [])
  }))

  // 然后按 type 排序当前层级
  return sorted.sort((a, b) => {
    if (a.type !== b.type) {
      return a.type - b.type
    }
    // 类型相同时按 sort_order 排序
    return (a.sort_order || 0) - (b.sort_order || 0)
  })
}

/**
 * 从权限ID列表中过滤出叶子节点ID
 * 因为 setCheckedKeys 会自动展开父节点，所以只需要设置叶子节点
 */
const filterLeafPermissionIds = (permissionIds: number[], treeData: Permission[]): number[] => {
  const allLeafIds = new Set<number>()

  // 递归收集所有叶子节点的 ID
  const collectLeafIds = (permissions: Permission[]) => {
    permissions.forEach((p) => {
      if (!p.children || p.children.length === 0) {
        // 没有子节点，是叶子节点
        allLeafIds.add(p.permission_id)
      } else {
        // 有子节点，递归处理
        collectLeafIds(p.children)
      }
    })
  }

  collectLeafIds(treeData)

  // 只返回既是叶子节点又在 permissionIds 中的 ID
  return permissionIds.filter((id) => allLeafIds.has(id))
}

/**
 * 分配权限
 */
const handleAssignPermission = async () => {
  const checkedKeys = permissionTreeRef.value?.getCheckedKeys() || []
  const halfCheckedKeys = permissionTreeRef.value?.getHalfCheckedKeys() || []
  const allCheckedKeys = [...checkedKeys, ...halfCheckedKeys] as number[]

  // 验证是否选择了权限
  if (allCheckedKeys.length === 0) {
    ElMessage.warning('请至少选择一个权限')
    return
  }

  // 将权限ID数组转换为用 '-' 分隔的字符串
  const permissionIdsStr = allCheckedKeys.join('-')

  try {
    const res = await assignPermission(currentRoleId.value, permissionIdsStr)
    ElMessage.success(res.msg || '权限分配成功')
    permissionDialogVisible.value = false
    getList()
  } catch (error) {
    // 错误消息由 request 拦截器统一处理
  }
}

/**
 * 关闭权限弹窗
 */
const handleClosePermissionDialog = () => {
  permissionTreeRef.value?.setCheckedKeys([])
  checkedPermissionIds.value = []
  currentRoleName.value = ''
}

/**
 * 获取权限类型名称
 */
const getTypeName = (type: number) => {
  const typeMap: Record<number, string> = {
    1: '菜单',
    2: '菜单',
    3: '按钮',
    4: '接口'
  }
  return typeMap[type] || '未知'
}

/**
 * 删除
 */
const handleDelete = async (row: Role) => {
  try {
    await ElMessageBox.confirm('确定要删除该角色吗？', '提示', {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning',
      closeOnClickModal: false
    })

    const res = await deleteRole(row.role_id)
    ElMessage.success(res.msg || '删除成功')
    getList()
  } catch (error) {
    // 用户取消操作或错误消息由 request 拦截器统一处理
  }
}

onMounted(() => {
  handleSearch()
})
</script>

<style scoped lang="scss">
.role-list {
  .card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .search-form {
    margin-bottom: 20px;
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

// 权限树节点样式
.permission-node {
  .permission-code {
    margin-left: 10px;
    margin-right: 10px;
    color: #909399;
  }

  .permission-type {
    font-size: 12px;

    &.type-1,
    &.type-2 {
      color: #2690ff; // 菜单 - 蓝色
    }

    &.type-3 {
      color: #b59200; // 按钮 - 橙色
    }

    &.type-4 {
      color: #f56c6c; // 接口 - 红色
    }
  }
}
</style>
