<?php
// +----------------------------------------------------------------------
// | BMS 后台管理系统 - 管理员控制器
// +----------------------------------------------------------------------

namespace app\admin\controller;

use think\Request;

/**
 * 管理员控制器
 *
 * 处理管理员的增删改查和角色分配功能
 * Story 2.3: 管理员管理
 */
class Admin extends Base
{
    /**
     * 获取管理员列表
     * GET /admin/admin/getList
     *
     * @param Request $request
     * @return void
     */
    public function getList(Request $request)
    {
        $username = $request->param('username', '');
        $realName = $request->param('real_name', '');
        $mobile = $request->param('mobile', '');
        $status = $request->param('status', '');
        $page = $request->param('page', 1);
        $limit = $request->param('limit', 20);

        // 分页参数校验，防止传入超大 limit 导致内存溢出
        $page  = max(1, (int)$page);
        $limit = min(200, max(1, (int)$limit));

        $where = array();
        $where['is_delete'] = 0;

        if (!empty($username)) {
            $where['username'] = $username;
        }

        if (!empty($realName)) {
            $where['real_name'] = $realName;
        }

        if (!empty($mobile)) {
            $where['mobile'] = $mobile;
        }

        if ($status !== '') {
            $where['status'] = $status;
        }

        $adminModel = model('Admin');
        $result = $adminModel->getList($where, $page, $limit);
        $list = $result['list'];

        // 批量获取所有管理员的角色信息（避免 N+1 查询）
        $adminIds  = array();
        foreach ($list as $admin) {
            $adminIds[] = $admin['admin_id'];
        }
        $rolesMap = $adminModel->getRolesForAdmins($adminIds);

        foreach ($list as $key => $admin) {
            // 移除密码字段
            unset($admin['password']);

            $adminId = $admin['admin_id'];
            $admin['role_ids'] = isset($rolesMap[$adminId]) ? $rolesMap[$adminId]['role_ids'] : array();
            $admin['roles']    = isset($rolesMap[$adminId]) ? $rolesMap[$adminId]['roles'] : array();

            $list[$key] = $admin;
        }

        // 返回分页数据
        $this->apiReturn(200, '获取成功', array(
            'list' => $list,
            'total' => $result['total']
        ));
    }

    /**
     * 获取管理员详情
     * GET /admin/admin/getDetail
     *
     * @param Request $request
     * @return void
     */
    public function getDetail(Request $request)
    {
        $adminId = $request->param('admin_id', 0);

        if ($adminId <= 0) {
            $this->apiReturn(400, '参数错误');
        }

        $adminModel = model('Admin');
        $admin = $adminModel->findById($adminId);

        if (empty($admin)) {
            $this->apiReturn(400, '管理员不存在');
        }

        // 移除密码字段
        unset($admin['password']);

        // 获取角色ID列表
        $roleIds = $adminModel->getRoleIds($adminId);
        $admin['role_ids'] = $roleIds;

        // 获取角色详情
        $roles = $adminModel->getRoles($adminId);
        $admin['roles'] = $roles;

        $this->apiReturn(200, '获取成功', $admin);
    }

    /**
     * 新增管理员
     * POST /admin/admin/add
     *
     * @param Request $request
     * @return void
     */
    public function add(Request $request)
    {
        $username = $request->param('username', '');
        $password = $request->param('password', '');
        $realName = $request->param('real_name', '');
        $mobile = $request->param('mobile', '');
        $status = $request->param('status', 1);
        $isSuper = $request->param('is_super', 0);
        $roleIds = $request->param('role_ids/a', array());

        // 参数校验
        if (empty($username)) {
            $this->apiReturn(400, '用户名不能为空');
        }

        if (empty($password)) {
            $this->apiReturn(400, '密码不能为空');
        }

        if (strlen($password) < 6) {
            $this->apiReturn(400, '密码长度不能少于6位');
        }

        // 检查用户名是否存在
        $adminModel = model('Admin');
        if ($adminModel->usernameExists($username)) {
            $this->apiReturn(400, '用户名已存在');
        }

        // 检查手机号是否存在
        if (!empty($mobile) && $adminModel->mobileExists($mobile)) {
            $this->apiReturn(400, '手机号已存在');
        }

        // 准备数据
        $data = array(
            'username' => $username,
            'password' => $password,
            'real_name' => $realName,
            'mobile' => $mobile,
            'status' => $status,
            'is_super' => $isSuper
        );

        // 使用模型的 add 方法
        $result = $adminModel->add($data);

        if ($result) {
            // 分配角色
            if (!empty($roleIds)) {
                $adminModel->assignRoles($result, $roleIds);
            }

            $this->apiReturn(200, '添加成功', array('admin_id' => $result));
        } else {
            $this->apiReturn(400, '添加失败');
        }
    }

    /**
     * 编辑管理员
     * POST /admin/admin/edit
     *
     * @param Request $request
     * @return void
     */
    public function edit(Request $request)
    {
        $adminId = $request->param('admin_id', 0);
        $realName = $request->param('real_name', '');
        $mobile = $request->param('mobile', '');
        $password = $request->param('password', '');
        $status = $request->param('status', 1);
        $roleIds = $request->param('role_ids/a', array());

        // 参数校验
        if ($adminId <= 0) {
            $this->apiReturn(400, '参数错误');
        }

        $adminModel = model('Admin');
        $admin = $adminModel->findById($adminId);

        if (empty($admin)) {
            $this->apiReturn(400, '管理员不存在');
        }

        // 检查手机号是否存在
        if (!empty($mobile) && $adminModel->mobileExists($mobile, $adminId)) {
            $this->apiReturn(400, '手机号已存在');
        }

        // 准备数据（is_super 字段系统初始化后不能修改）
        $data = array(
            'real_name' => $realName,
            'mobile' => $mobile,
            'status' => $status
        );

        // 如果提供了新密码，验证长度
        if (!empty($password) && strlen($password) < 6) {
            $this->apiReturn(400, '密码长度不能少于6位');
        }

        // 只有提供了新密码才更新密码和密码修改时间
        if (!empty($password)) {
            $data['password'] = $password;
            $data['password_update_time'] = time();
        }

        // 使用模型的 edit 方法
        $result = $adminModel->edit($adminId, $data);

        if ($result) {
            // 分配角色
            $adminModel->assignRoles($adminId, $roleIds);

            $this->apiReturn(200, '编辑成功');
        } else {
            $this->apiReturn(400, '编辑失败');
        }
    }

    /**
     * 删除管理员
     * POST /admin/admin/delete
     *
     * @param Request $request
     * @return void
     */
    public function delete(Request $request)
    {
        $adminId = $request->param('admin_id', 0);

        if ($adminId <= 0) {
            $this->apiReturn(400, '参数错误');
        }

        $adminModel = model('Admin');
        $admin = $adminModel->findById($adminId);

        if (empty($admin)) {
            $this->apiReturn(400, '管理员不存在');
        }

        // 不能删除超级管理员
        if ($admin['is_super'] == 1) {
            $this->apiReturn(400, '不能删除超级管理员');
        }

        // 不能删除自己
        $currentAdminId = isset(request()->admin_id) ? request()->admin_id : 0;
        if ($adminId == $currentAdminId) {
            $this->apiReturn(400, '不能删除自己');
        }

        // 使用模型的 remove 方法
        $result = $adminModel->remove($adminId);

        if ($result) {
            $this->apiReturn(200, '删除成功');
        } else {
            $this->apiReturn(400, '删除失败');
        }
    }

    /**
     * 分配角色
     * POST /admin/admin/assignRole
     * Story 2.3: 管理员管理
     * Story 2.4: 添加缓存清除逻辑
     *
     * @param Request $request
     * @return void
     */
    public function assignRole(Request $request)
    {
        $adminId = $request->param('admin_id', 0);
        $roleIds = $request->param('role_ids/a', array());

        if ($adminId <= 0) {
            $this->apiReturn(400, '参数错误');
        }

        // 验证角色ID数组格式
        if (!is_array($roleIds)) {
            $this->apiReturn(400, '角色ID格式错误');
        }

        $adminModel = model('Admin');
        $admin = $adminModel->findById($adminId);

        if (empty($admin)) {
            $this->apiReturn(400, '管理员不存在');
        }

        // 分配角色
        $result = $adminModel->assignRoles($adminId, $roleIds);

        if ($result) {
            // Story 2.4: 清除该管理员的权限缓存
            $permissionModel = model('Permission');
            $permissionModel->clearPermissionCache($adminId);

            $this->apiReturn(200, '分配成功');
        } else {
            $this->apiReturn(400, '分配失败');
        }
    }

    /**
     * 修改密码
     * POST /admin/admin/changePassword
     *
     * @param Request $request
     * @return void
     */
    public function changePassword(Request $request)
    {
        $oldPassword = $request->param('old_password', '');
        $newPassword = $request->param('new_password', '');
        $confirmPassword = $request->param('confirm_password', '');

        // 参数校验
        if (empty($oldPassword)) {
            $this->apiReturn(400, '原密码不能为空');
        }

        if (empty($newPassword)) {
            $this->apiReturn(400, '新密码不能为空');
        }

        if (strlen($newPassword) < 6) {
            $this->apiReturn(400, '新密码长度不能少于6位');
        }

        if ($newPassword !== $confirmPassword) {
            $this->apiReturn(400, '两次输入的密码不一致');
        }

        $adminId = isset(request()->admin_id) ? request()->admin_id : 0;
        if ($adminId <= 0) {
            $this->apiReturn(401, '未登录');
        }

        $adminModel = model('Admin');
        $admin = $adminModel->findById($adminId);

        if (empty($admin)) {
            $this->apiReturn(400, '管理员不存在');
        }

        // 验证原密码
        if (!$adminModel->verifyPassword($oldPassword, $admin['password'])) {
            $this->apiReturn(400, '原密码错误');
        }

        // 更新密码
        $data = array(
            'password' => $adminModel->hashPassword($newPassword),
            'password_update_time' => time(),
            'updated_at' => time()
        );

        $result = $adminModel->changePassword($adminId, $data);

        if ($result !== false) {
            $this->apiReturn(200, '修改成功');
        } else {
            $this->apiReturn(400, '修改失败');
        }
    }
}
