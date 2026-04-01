<?php
// +----------------------------------------------------------------------
// | BMS 后台管理系统 - 管理员控制器
// +----------------------------------------------------------------------

namespace app\admin\controller;

use think\Request;

/**
 * 管理员控制器
 *
 * 处理管理员的增删改查功能
 * Story 2.3: 管理员管理
 */
class Manager extends Base
{
    /**
     * 获取管理员列表
     * GET /admin/manager/getList
     *
     * @param Request $request
     * @return void
     */
    public function getList(Request $request)
    {
        $this->ensureCanViewManagerList();

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

        $managerModel = model('Manager');
        $result = $managerModel->listManager($where, $page, $limit);
        $list = $result['list'];

        // 批量获取所有管理员的角色信息（避免 N+1 查询）
        $managerIds  = array();
        foreach ($list as $manager) {
            $managerIds[] = $manager['admin_id'];
        }
        $rolesMap = $managerModel->getRolesForManagers($managerIds);

        foreach ($list as $key => $manager) {
            // 移除密码字段
            unset($manager['password']);

            $managerId = $manager['admin_id'];
            $manager['role_ids'] = isset($rolesMap[$managerId]) ? $rolesMap[$managerId]['role_ids'] : array();
            $manager['roles']    = isset($rolesMap[$managerId]) ? $rolesMap[$managerId]['roles'] : array();

            $list[$key] = $manager;
        }

        // 返回分页数据
        $this->apiReturn(200, '获取成功', array(
            'list' => $list,
            'total' => $result['total']
        ));
    }

    /**
     * 新增管理员
     * POST /admin/manager/add
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
        $managerModel = model('Manager');
        if ($managerModel->usernameExists($username)) {
            $this->apiReturn(400, '用户名已存在');
        }

        // 检查手机号是否存在
        if (!empty($mobile) && $managerModel->mobileExists($mobile)) {
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

        // 使用模型的 addManager 方法
        $result = $managerModel->addManager($data);

        if ($result) {
            // 分配角色
            if (!empty($roleIds)) {
                $managerModel->assignRoles($result, $roleIds);
            }

            $this->apiReturn(200, '添加成功', array('admin_id' => $result));
        } else {
            $this->apiReturn(400, '添加失败');
        }
    }

    /**
     * 编辑管理员
     * POST /admin/manager/edit
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

        $managerModel = model('Manager');
        $manager = $managerModel->findById($adminId);

        if (empty($manager)) {
            $this->apiReturn(400, '管理员不存在');
        }

        // 检查手机号是否存在
        if (!empty($mobile) && $managerModel->mobileExists($mobile, $adminId)) {
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

        // 使用模型的 editManager 方法
        $result = $managerModel->editManager($adminId, $data);

        if ($result) {
            // 分配角色
            $managerModel->assignRoles($adminId, $roleIds);

            $this->apiReturn(200, '编辑成功');
        } else {
            $this->apiReturn(400, '编辑失败');
        }
    }

    /**
     * 删除管理员
     * POST /admin/manager/delete
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

        $managerModel = model('Manager');
        $manager = $managerModel->findById($adminId);

        if (empty($manager)) {
            $this->apiReturn(400, '管理员不存在');
        }

        // 不能删除超级管理员
        if ($manager['is_super'] == 1) {
            $this->apiReturn(400, '不能删除超级管理员');
        }

        // 不能删除自己
        $currentAdminId = isset(request()->admin_id) ? request()->admin_id : 0;
        if ($adminId == $currentAdminId) {
            $this->apiReturn(400, '不能删除自己');
        }

        // 使用模型的 deleteManager 方法
        $result = $managerModel->deleteManager($adminId);

        if ($result) {
            $this->apiReturn(200, '删除成功');
        } else {
            $this->apiReturn(400, '删除失败');
        }
    }

    /**
     * 修改密码
     * POST /admin/manager/changePassword
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

        $managerModel = model('Manager');
        $manager = $managerModel->findById($adminId);

        if (empty($manager)) {
            $this->apiReturn(400, '管理员不存在');
        }

        // 验证原密码
        if (!$managerModel->verifyPassword($oldPassword, $manager['password'])) {
            $this->apiReturn(400, '原密码错误');
        }

        // 更新密码
        $data = array(
            'password' => $managerModel->hashPassword($newPassword),
            'password_update_time' => time(),
            'updated_at' => time()
        );

        $result = $managerModel->changePassword($adminId, $data);

        if ($result !== false) {
            $this->apiReturn(200, '修改成功');
        } else {
            $this->apiReturn(400, '修改失败');
        }
    }

    /**
     * 兼容旧权限数据：如果数据库尚未补齐 admin/manager/getlist，
     * 则至少要求具备 system:manager 菜单权限才允许访问列表。
     *
     * @return void
     */
    protected function ensureCanViewManagerList()
    {
        $adminId = isset(request()->admin_id) ? intval(request()->admin_id) : 0;
        $isSuper = isset(request()->is_super) ? intval(request()->is_super) : 0;

        if ($isSuper == 1) {
            return;
        }

        $permissionCodes = model('Permission')->extractAdminPermissionCodes($adminId, $isSuper);
        $hasApiPermission = in_array('admin/manager/getlist', $permissionCodes);
        $hasMenuPermission = in_array('system:manager', $permissionCodes);

        if (!$hasApiPermission && !$hasMenuPermission) {
            $this->apiReturn(403, '无权限访问该接口');
        }
    }
}
