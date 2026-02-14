<?php
// +----------------------------------------------------------------------
// | BMS 后台管理系统 - 权限菜单控制器
// +----------------------------------------------------------------------

namespace app\admin\controller;

use think\Request;

/**
 * 权限菜单控制器
 *
 * 处理权限菜单的增删改查功能
 * 统一权限模型：目录(1)、菜单(2)、按钮(3)、接口(4)
 * Story 2.1: 权限菜单管理
 */
class Permission extends Base
{
    /**
     * 获取权限列表（树形结构）
     * GET /admin/permission/getList
     *
     * @param Request $request
     * @return void
     */
    public function getList(Request $request)
    {
        $type = $request->param('type', '');
        $status = $request->param('status', '');

        $where = array();
        $where['is_delete'] = 0;

        if ($type !== '') {
            $where['type'] = $type;
        }

        if ($status !== '') {
            $where['status'] = $status;
        }

        $permissionModel = model('Permission');
        $list = $permissionModel->getTreeList($where);

        $this->apiReturn(200, '获取成功', $list);
    }

    /**
     * 获取权限详情
     * GET /admin/permission/getDetail
     *
     * @param Request $request
     * @return void
     */
    public function getDetail(Request $request)
    {
        $permissionId = $request->param('permission_id', 0);

        if ($permissionId <= 0) {
            $this->apiReturn(400, '参数错误');
        }

        $permissionModel = model('Permission');
        $permission = $permissionModel->findById($permissionId);

        if (empty($permission)) {
            $this->apiReturn(400, '权限不存在');
        }

        $this->apiReturn(200, '获取成功', $permission);
    }

    /**
     * 新增权限
     * POST /admin/permission/add
     *
     * @param Request $request
     * @return void
     */
    public function add(Request $request)
    {
        $parentId = $request->param('parent_id', 0);
        $title = $request->param('title', '');
        $code = $request->param('code', '');
        $type = $request->param('type', 2);
        $path = $request->param('path', '');
        $component = $request->param('component', '');
        $icon = $request->param('icon', '');
        $isHidden = $request->param('is_hidden', 0);
        $isAffix = $request->param('is_affix', 0);
        $isCache = $request->param('is_cache', 0);
        $sortOrder = $request->param('sort_order', 0);
        $status = $request->param('status', 1);

        // 参数校验
        if (empty($title)) {
            $this->apiReturn(400, '权限标题不能为空');
        }

        if (empty($code)) {
            $this->apiReturn(400, '权限编码不能为空');
        }

        // 检查权限编码是否存在
        $permissionModel = model('Permission');
        if ($permissionModel->codeExists($code)) {
            $this->apiReturn(400, '权限编码已存在');
        }

        // 验证类型
        if (!in_array($type, array(1, 2, 3, 4))) {
            $this->apiReturn(400, '权限类型无效');
        }

        // 验证父级权限
        if ($parentId > 0) {
            $parent = $permissionModel->findById($parentId);
            if (empty($parent)) {
                $this->apiReturn(400, '父级权限不存在');
            }
        }

        $data = array(
            'parent_id' => $parentId,
            'title' => $title,
            'code' => $code,
            'type' => $type,
            'path' => $path,
            'component' => $component,
            'icon' => $icon,
            'is_hidden' => $isHidden,
            'is_affix' => $isAffix,
            'is_cache' => $isCache,
            'sort_order' => $sortOrder,
            'status' => $status
        );

        $result = $permissionModel->add($data);

        if ($result) {
            // 清除全局 API 权限缓存
            $permissionModel->clearAllApiPermissionsCache();
            // 清除所有管理员的权限缓存
            $permissionModel->clearAllAdminPermissionCache();
            $this->apiReturn(200, '添加成功', array('permission_id' => $result));
        } else {
            $this->apiReturn(400, '添加失败');
        }
    }

    /**
     * 编辑权限
     * POST /admin/permission/edit
     *
     * @param Request $request
     * @return void
     */
    public function edit(Request $request)
    {
        $permissionId = $request->param('permission_id', 0);
        $parentId = $request->param('parent_id', 0);
        $title = $request->param('title', '');
        $code = $request->param('code', '');
        $type = $request->param('type', 2);
        $path = $request->param('path', '');
        $component = $request->param('component', '');
        $icon = $request->param('icon', '');
        $isHidden = $request->param('is_hidden', 0);
        $isAffix = $request->param('is_affix', 0);
        $isCache = $request->param('is_cache', 0);
        $sortOrder = $request->param('sort_order', 0);
        $status = $request->param('status', 1);

        // 参数校验
        if ($permissionId <= 0) {
            $this->apiReturn(400, '参数错误');
        }

        $permissionModel = model('Permission');
        $permission = $permissionModel->findById($permissionId);

        if (empty($permission)) {
            $this->apiReturn(400, '权限不存在');
        }

        if (empty($title)) {
            $this->apiReturn(400, '权限标题不能为空');
        }

        if (empty($code)) {
            $this->apiReturn(400, '权限编码不能为空');
        }

        // 检查权限编码是否存在
        if ($permissionModel->codeExists($code, $permissionId)) {
            $this->apiReturn(400, '权限编码已存在');
        }

        // 验证类型
        if (!in_array($type, array(1, 2, 3, 4))) {
            $this->apiReturn(400, '权限类型无效');
        }

        // 验证父级权限
        if ($parentId > 0) {
            // 不能将父级设置为自己
            if ($parentId == $permissionId) {
                $this->apiReturn(400, '不能将自己设置为父级');
            }

            // 不能将父级设置为自己的子权限
            $childrenIds = $permissionModel->getChildrenIds($permissionId);
            if (in_array($parentId, $childrenIds)) {
                $this->apiReturn(400, '不能将父级设置为自己的子权限');
            }

            $parent = $permissionModel->findById($parentId);
            if (empty($parent)) {
                $this->apiReturn(400, '父级权限不存在');
            }
        }

        $data = array(
            'parent_id' => $parentId,
            'title' => $title,
            'code' => $code,
            'type' => $type,
            'path' => $path,
            'component' => $component,
            'icon' => $icon,
            'is_hidden' => $isHidden,
            'is_affix' => $isAffix,
            'is_cache' => $isCache,
            'sort_order' => $sortOrder,
            'status' => $status
        );

        $result = $permissionModel->edit($permissionId, $data);

        if ($result) {
            // 清除全局 API 权限缓存
            $permissionModel->clearAllApiPermissionsCache();
            // 清除所有管理员的权限缓存
            $permissionModel->clearAllAdminPermissionCache();
            $this->apiReturn(200, '编辑成功');
        } else {
            $this->apiReturn(400, '编辑失败');
        }
    }

    /**
     * 删除权限
     * POST /admin/permission/delete
     *
     * @param Request $request
     * @return void
     */
    public function delete(Request $request)
    {
        $permissionId = $request->param('permission_id', 0);

        if ($permissionId <= 0) {
            $this->apiReturn(400, '参数错误');
        }

        $permissionModel = model('Permission');
        $permission = $permissionModel->findById($permissionId);

        if (empty($permission)) {
            $this->apiReturn(400, '权限不存在');
        }

        // 检查是否有子权限
        if ($permissionModel->hasChildren($permissionId)) {
            $this->apiReturn(400, '该权限下有子权限，无法删除');
        }

        // 删除角色与权限的关联
        \think\Db::name('role_permissions')->where('permission_id', $permissionId)->delete();

        // 删除权限
        $result = $permissionModel->remove($permissionId);

        if ($result) {
            // 清除全局 API 权限缓存
            $permissionModel->clearAllApiPermissionsCache();
            // 清除所有管理员的权限缓存
            $permissionModel->clearAllAdminPermissionCache();
            $this->apiReturn(200, '删除成功');
        } else {
            $this->apiReturn(400, '删除失败');
        }
    }

    /**
     * 获取当前用户的权限列表
     * GET /admin/permission/getUserPermissions
     * Story 2.4: 获取用户权限 API（带 Redis 缓存）
     *
     * @param Request $request
     * @return void
     */
    public function getUserPermissions(Request $request)
    {
        // 从请求中获取管理员信息（由中间件注入）
        $adminId = isset($request->admin_id) ? $request->admin_id : 0;
        $isSuper = isset($request->is_super) ? $request->is_super : 0;

        if ($adminId <= 0) {
            $this->apiReturn(401, '未登录');
        }
        $permissionModel = model('Permission');
        // 使用带缓存的模型方法
        $permissions = $permissionModel->getUserPermissions($adminId, $isSuper);
        $this->apiReturn(200, '获取成功', $permissions);
    }
}
