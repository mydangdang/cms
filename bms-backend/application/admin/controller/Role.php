<?php
// +----------------------------------------------------------------------
// | BMS 后台管理系统 - 角色控制器
// +----------------------------------------------------------------------

namespace app\admin\controller;

use think\Request;

/**
 * 角色管理控制器
 *
 * 处理角色的增删改查和权限分配功能
 * Story 2.2: 角色管理（后端 API）
 */
class Role extends Base
{
    /**
     * 获取角色列表
     * GET /admin/role/getList
     *
     * @param Request $request
     * @return void
     */
    public function getList(Request $request)
    {
        $name = $request->param('name', '');
        $status = $request->param('status', '');
        $page = $request->param('page', 1);
        $limit = $request->param('limit', 10);

        // 分页参数校验，防止传入超大 limit 导致内存溢出
        $page  = max(1, (int)$page);
        $limit = min(200, max(1, (int)$limit));

        $where = array();
        $where['is_delete'] = 0;

        if (!empty($name)) {
            $where['name'] = $name;
        }

        if ($status !== '') {
            $where['status'] = $status;
        }

        $roleModel = model('Role');
        $result = $roleModel->getList($where, $page, $limit);
        $list = $result['list'];

        // 为每个角色附加权限ID列表
        foreach ($list as $key => $role) {
            $permissionIds = $roleModel->getPermissionIds($role['role_id']);
            $list[$key]['permission_ids'] = $permissionIds;
        }

        $this->apiReturn(200, '获取成功', array(
            'list' => $list,
            'total' => $result['total']
        ));
    }

    /**
     * 新增角色
     * POST /admin/role/add
     *
     * @param Request $request
     * @return void
     */
    public function add(Request $request)
    {
        $name = $request->param('name', '');
        $description = $request->param('description', '');
        $sortOrder = $request->param('sort_order', 0);
        $status = $request->param('status', 1);

        // 参数校验
        if (empty($name)) {
            $this->apiReturn(400, '角色名称不能为空');
        }

        // 验证描述长度
        if (strlen($description) > 200) {
            $this->apiReturn(400, '角色描述不能超过200个字符');
        }

        // 验证排序值
        if (!is_numeric($sortOrder) || $sortOrder < 0) {
            $this->apiReturn(400, '排序值必须是非负整数');
        }

        $roleModel = model('Role');
        if ($roleModel->nameExists($name)) {
            $this->apiReturn(400, '角色名称已存在');
        }

        $data = array(
            'name' => $name,
            'description' => $description,
            'sort_order' => $sortOrder,
            'status' => $status
        );

        $result = $roleModel->add($data);

        if ($result) {
            $this->apiReturn(200, '添加成功', array('role_id' => $result));
        } else {
            $this->apiReturn(400, '添加失败');
        }
    }

    /**
     * 编辑角色
     * POST /admin/role/edit
     *
     * @param Request $request
     * @return void
     */
    public function edit(Request $request)
    {
        $roleId = $request->param('role_id', 0);
        $name = $request->param('name', '');
        $description = $request->param('description', '');
        $sortOrder = $request->param('sort_order', 0);
        $status = $request->param('status', 1);

        // 参数校验
        if ($roleId <= 0) {
            $this->apiReturn(400, '参数错误');
        }

        $roleModel = model('Role');
        $role = $roleModel->findById($roleId);

        if (empty($role)) {
            $this->apiReturn(400, '角色不存在');
        }

        if (empty($name)) {
            $this->apiReturn(400, '角色名称不能为空');
        }

        // 验证描述长度
        if (strlen($description) > 200) {
            $this->apiReturn(400, '角色描述不能超过200个字符');
        }

        // 验证排序值
        if (!is_numeric($sortOrder) || $sortOrder < 0) {
            $this->apiReturn(400, '排序值必须是非负整数');
        }

        if ($roleModel->nameExists($name, $roleId)) {
            $this->apiReturn(400, '角色名称已存在');
        }

        $data = array(
            'name' => $name,
            'description' => $description,
            'sort_order' => $sortOrder,
            'status' => $status
        );

        $result = $roleModel->edit($roleId, $data);

        if ($result) {
            $this->apiReturn(200, '编辑成功');
        } else {
            $this->apiReturn(400, '编辑失败');
        }
    }

    /**
     * 删除角色
     * POST /admin/role/delete
     *
     * @param Request $request
     * @return void
     */
    public function delete(Request $request)
    {
        $roleId = $request->param('role_id', 0);

        if ($roleId <= 0) {
            $this->apiReturn(400, '参数错误');
        }

        $roleModel = model('Role');
        $role = $roleModel->findById($roleId);

        if (empty($role)) {
            $this->apiReturn(400, '角色不存在');
        }

        // 检查是否有管理员使用该角色
        if ($roleModel->hasAdmins($roleId)) {
            $this->apiReturn(400, '该角色下有管理员，无法删除');
        }

        $result = $roleModel->remove($roleId);

        if ($result) {
            $this->apiReturn(200, '删除成功');
        } else {
            $this->apiReturn(400, '删除失败');
        }
    }

    /**
     * 为角色分配权限
     * POST /admin/role/assignPermission
     * Story 2.2: 角色管理
     * Story 2.4: 添加缓存清除逻辑
     *
     * @param Request $request
     * @return void
     */
    public function assignPermission(Request $request)
    {
        $roleId = $request->param('role_id', 0);
        $permissionIdsStr = $request->param('permission_ids', '');

        if ($roleId <= 0) {
            $this->apiReturn(400, '参数错误');
        }

        // 验证权限ID字符串不能为空
        if (empty($permissionIdsStr)) {
            $this->apiReturn(400, '请选择权限');
        }

        $roleModel = model('Role');
        $role = $roleModel->findById($roleId);

        if (empty($role)) {
            $this->apiReturn(400, '角色不存在');
        }

        // 将字符串按 '-' 分隔成数组
        $permissionIds = explode('-', $permissionIdsStr);

        // 过滤空值并转换为整数
        $permissionIds = array_filter($permissionIds, function($val) {
            return trim($val) !== '';
        });
        $permissionIds = array_map(function($val) {
            return intval(trim($val));
        }, $permissionIds);

        // 重新索引数组
        $permissionIds = array_values($permissionIds);

        $result = $roleModel->assignPermissions($roleId, $permissionIds);

        if ($result !== false) {
            // Story 2.4: 清除所有拥有该角色的管理员的权限缓存
            $permissionModel = model('Permission');

            // 查询拥有该角色的管理员
            $adminIds = $roleModel->getAdminIdsByRole($roleId);

            // 清除这些管理员的权限缓存
            foreach ($adminIds as $adminId) {
                $permissionModel->clearPermissionCache($adminId);
            }

            $this->apiReturn(200, '分配成功');
        } else {
            $this->apiReturn(400, '分配失败');
        }
    }
}
