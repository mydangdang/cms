<?php
// +----------------------------------------------------------------------
// | BMS 后台管理系统 - 图形验证码控制器
// +----------------------------------------------------------------------

namespace app\admin\controller;

/**
 * 图形验证码控制器
 *
 * 功能：生成图形验证码
 * 验证码验证逻辑在登录控制器中实现
 */
class Captcha extends Base
{
    /**
     * 模型实例
     * @var \app\admin\model\Captcha
     */
    protected $model;

    /**
     * 初始化
     */
    public function __construct()
    {
        parent::__construct();
        $this->model = model('Captcha');
    }

    /**
     * 生成验证码
     * GET /admin/captcha/index
     *
     * @return void
     */
    public function index()
    {
        // 生成 4 位数字验证码
        $code = $this->model->generateCode(4);

        // 生成缓存键
        $cacheKey = $this->model->getCaptchaCacheKey('login');

        // 存储到 Cache（10分钟过期）
        $this->model->setCaptcha($cacheKey, $code);

        // 生成图片
        $imageData = $this->model->generateCaptchaImage($code);

        // 返回图片数据
        $data = array(
            'image' => $imageData
        );
        $this->apiReturn(200, '获取成功', $data);
    }

}
