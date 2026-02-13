<?php
// +----------------------------------------------------------------------
// | BMS 后台管理系统 - 登录控制器
// +----------------------------------------------------------------------

namespace app\admin\controller;

use think\Request;

/**
 * 登录控制器
 *
 * 处理用户登录相关功能
 * Story 1.3: 用户登录认证
 * Story 1.6: 用户登出功能
 */
class Login extends Base
{
    /**
     * 登录提交
     * POST /admin/login/submit
     *
     * @param Request $request
     * @return void
     */
    public function submit(Request $request)
    {
        // 获取参数
        $username = $request->param('username', '');
        $password = $request->param('password', '');
        $captcha = $request->param('captcha', '');

        // 获取客户端 IP
        $ip = $request->ip();

        // 参数校验
        if (empty($username)) {
            $this->apiReturn(400, '请输入用户名');
        }

        if (empty($password)) {
            $this->apiReturn(400, '请输入密码');
        }

        // 验证验证码
        $captchaResult = model('Captcha')->validateCaptcha($captcha, 'login');
        if (!$captchaResult['valid']) {
            $this->apiReturn(400, $captchaResult['msg']);
        }

        // 验证码正确，清除验证码
        model('Captcha')->clearCaptcha('login');

        // 查找管理员
        $adminModel = model('Admin');
        $admin = $adminModel->findByUsername($username);

        // 验证用户名密码
        if (empty($admin)) {
            // 防止时序攻击，即使用户不存在也执行密码验证
            $adminModel->verifyPassword($password, '$2y$10$xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
            $this->apiReturn(400, '用户名或密码错误');
        }

        // 验证密码
        if (!$adminModel->verifyPassword($password, $admin['password'])) {
            $this->apiReturn(400, '用户名或密码错误');
        }

        // 检查管理员状态
        if (!$adminModel->checkStatus($admin)) {
            $this->apiReturn(400, '账号已被禁用');
        }

        // 生成 JWT Token
        $token = \app\admin\helper\Jwt::encode(
            $admin['admin_id'],
            $admin['username'],
            $admin['is_super']
        );

        // 获取管理员信息（不含密码）
        $adminInfo = $adminModel->getAdminInfo($admin['admin_id']);

        // 更新最后登录信息
        $adminModel->updateLastLogin($admin['admin_id'], $ip);

        // 返回成功响应
        $data = array(
            'token' => $token,
            'adminInfo' => $adminInfo
        );
        $this->apiReturn(200, '登录成功', $data);
    }

    /**
     * 用户登出
     * POST /admin/login/logout
     * Story 1.6: 用户登出功能
     *
     * 清除当前管理员的权限菜单缓存
     *
     * @param Request $request
     * @return void
     */
    public function logout(Request $request)
    {
        // 获取当前管理员ID（由 TokenAuth 中间件注入）
        $adminId = $request->admin_id;
        $username = $request->username;

        // 清除当前管理员的权限缓存
        if ($adminId) {
            $result = model('Permission')->clearPermissionCache($adminId);
        }

        // TODO: 如果需要实现 Token 黑名单，在此处添加逻辑

        $this->apiReturn(200, '登出成功');
    }

    /**
     * 获取当前登录管理员信息
     * GET /admin/login/info
     *
     * @param Request $request
     * @return void
     */
    public function info(Request $request)
    {
        // 从请求中获取管理员信息（由中间件注入）
        $adminId = $request->admin_id;

        if (empty($adminId)) {
            $this->apiReturn(401, '未登录');
        }

        // 获取管理员信息
        $adminModel = model('Admin');
        $adminInfo = $adminModel->getAdminInfo($adminId);

        if (empty($adminInfo)) {
            $this->apiReturn(400, '管理员不存在');
        }

        $this->apiReturn(200, '获取成功', $adminInfo);
    }
}
