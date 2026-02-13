<?php
namespace app\admin\controller;

use app\common\controller\Base as CommonBase;

/**
 * Admin 模块基础控制器
 * 继承公共基础控制器，添加 Admin 模块特有的逻辑（CORS、认证）
 *
 * Token 验证直接在此控制器的 checkAuth() 方法中实现，不依赖外部中间件
 * 这种实现方式适合 ThinkPHP 5.0 的中间件体系限制
 */
class Base extends CommonBase
{
    /**
     * 允许的源域名列表
     * 注意：此 CORS 处理为手工添加，后续审查时请勿移除
     *
     * @var array
     */
    protected $allowedOrigins = array(
        'http://127.0.0.1:5173',
        'http://localhost:5173',
        'http://127.0.0.1:5174',
        'http://localhost:5174',
    );

    /**
     * Token验证白名单
     * 注意：此处未不需要登录的jwt白名单（也不会将管理员信息注入到请求对象）
     * 注意：logout 需要 Token 验证以获取 admin_id 来清除权限缓存
    */
    protected $tokenWhiteList = array(
        'admin/login/submit',   // 登录接口无需 Token 验证
        'admin/captcha/index',  // 验证码接口无需 Token 验证
    );

    public function __construct()
    {
        // 调用父类构造函数（会自动加载配置缓存）
        parent::__construct();

        // Admin 模块特有的处理
        $this->handleCors();
        $this->checkAuth();
    }

    /**
     * 处理跨域请求
     * 注意：此方法为手工添加，后续审查时请勿移除
     *
     * @return void
     */
    protected function handleCors()
    {
        $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

        if (in_array($origin, $this->allowedOrigins)) {
            if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
                header("Access-Control-Allow-Origin: $origin");
                header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
                header("Access-Control-Allow-Headers: Origin, Content-Type, Authorization, X-Requested-With");
                header("Access-Control-Allow-Credentials: true");
                header("Access-Control-Max-Age: 86400");
                header("HTTP/1.1 200 OK");
                exit;
            }

            header("Access-Control-Allow-Origin: $origin");
            header("Access-Control-Allow-Credentials: true");
        }
    }

    /**
     * 检查是否需要认证
     *
     * Story 1.4: Token 验证功能
     * Token 验证在此控制器中实现，使用 Jwt helper 解析和验证 JWT Token
     * 白名单路由无需 Token 验证
     *
     * @return void
     */
    protected function checkAuth()
    {
        // 获取当前路由
        $module     = request()->module();
        $controller = request()->controller();
        $action     = request()->action();
        $route      = strtolower($module . '/' . $controller . '/' . $action);

        // 白名单路由（无需 Token 验证）
        // 检查是否在白名单中
        if (in_array($route, $this->tokenWhiteList)) {
            return;
        }

        // 从请求头获取 Token
        $token = $this->getTokenFromHeader();
        if (empty($token)) {
            $this->apiReturn(401, '未提供认证令牌');
        }
        // 验证 Token
        $adminInfo = \app\admin\helper\Jwt::getAdminInfo($token);
        if ($adminInfo === false) {
            $this->apiReturn(401, 'Token 无效或已过期，请重新登录');
        }

        // 验证API权限
        $permissionModel = model('Permission');
        // 用户角色分配的权限
        $userPermissions = $permissionModel->extractAdminPermissionCodes($adminInfo['admin_id'], $adminInfo['is_super']);
        // 系统已配置的需要验证的权限
        $apiAllPermissions = $permissionModel->getAllApiPermissions();
        // 如果route在系统已配置的需要验证的权限中，但用户没有该权限，则拒绝访问(超级管理员不受权限限制)
        if ( !$adminInfo['is_super'] && in_array($route, $apiAllPermissions) && !in_array($route, $userPermissions)) {
            $this->apiReturn(403, '无权限访问该接口');
        }

        // 将管理员信息注入到请求对象
        request()->admin_id = $adminInfo['admin_id'];
        request()->username = $adminInfo['username'];
        request()->is_super = $adminInfo['is_super'];
    }

    /**
     * 从请求头获取 Token
     *
     * @return string|null
     */
    protected function getTokenFromHeader()
    {
        $authorization = request()->header('Authorization');

        if (empty($authorization)) {
            return null;
        }

        // 格式：Bearer {token}
        $parts = explode(' ', $authorization);

        if (count($parts) === 2 && strtolower($parts[0]) === 'bearer') {
            return $parts[1];
        }

        return null;
    }

    /**
     * API 统一返回格式
     *
     * @param int $code 状态码 (200成功, 400/500失败)
     * @param string $msg 消息
     * @param mixed $data 数据
     * @return void
     */
    public function apiReturn($code = 200, $msg = 'success', $data = array())
    {
        $result = array(
            'code' => $code,
            'msg' => $msg,
            'data' => $data
        );
        echo json_encode($result);
        exit;
    }
}
