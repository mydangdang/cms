<?php
// +----------------------------------------------------------------------
// | BMS 后台管理系统 - 健康检查控制器
// +----------------------------------------------------------------------

namespace app\index\controller;

use app\common\controller\Base;

/**
 * 健康检查控制器
 * 继承公共基础控制器，自动加载配置缓存
 */
class Index extends Base
{
    /**
     * 健康检查接口
     * GET /
     *
     * @return void
     */
    public function index()
    {
        $data = array(
            'app_name' => 'BMS 后台管理系统',
            'version' => '1.0.0',
            'timestamp' => time(),
            'site_name' => config('dbc_site_name')
        );

        // 统一返回格式: code, msg, data
        $result = array(
            'code' => 200,
            'msg' => 'ok',
            'data' => $data
        );
        echo json_encode($result);
        exit;
    }

    /**
     * 清除配置缓存（测试接口）
     * GET /index/clearConfig
     *
     * @return void
     */
    public function clearConfig()
    {
        $cacheKey = 'SYSTEM_DB_CONFIG';
        $result = \think\Cache::rm($cacheKey);

        $data = array(
            'cache_key' => $cacheKey,
            'cleared' => $result,
            'message' => $result ? '缓存清除成功，下次请求将重建' : '缓存不存在'
        );

        $result = array(
            'code' => 200,
            'msg' => 'ok',
            'data' => $data
        );
        echo json_encode($result);
        exit;
    }
}
