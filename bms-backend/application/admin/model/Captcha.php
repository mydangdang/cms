<?php
namespace app\admin\model;
use think\Model;
use think\Cache;

/**
 * 验证码模型
 *
 * 功能：生成图形验证码、验证码存储与校验
 */
class Captcha extends Model
{
    /**
     * 验证码缓存键前缀
     *
     * @var string
     */
    protected $captchaKey = 'BmsVerfyKeys';

    /**
     * 验证码过期时间（秒）
     *
     * @var int
     */
    protected $expireTime = 600;

    /**
     * 生成随机验证码
     *
     * @param int $length 验证码长度
     * @return string
     */
    public function generateCode($length = 4)
    {
        $chars = '0123456789';
        $code = '';
        $charsLen = strlen($chars);

        for ($i = 0; $i < $length; $i++) {
            $code .= $chars[mt_rand(0, $charsLen - 1)];
        }

        return $code;
    }

    /**
     * 获取验证码缓存键
     *
     * @param string $type 验证码类型（login/register等）
     * @return string
     */
    public function getCaptchaCacheKey($type = 'login')
    {
        // 基于会话ID和类型生成唯一缓存键
        $sessionId = session_id();
        $key = substr(md5($this->captchaKey . $sessionId), 5, 8);
        $str = substr(md5($type), 8, 10);
        return md5($key . $str);
    }

    /**
     * 存储验证码到缓存
     *
     * @param string $cacheKey 缓存键
     * @param string $code 验证码
     * @return bool
     */
    public function setCaptcha($cacheKey, $code)
    {
        return Cache::set($cacheKey, $code, $this->expireTime);
    }

    /**
     * 获取缓存中的验证码
     *
     * @param string $type 验证码类型
     * @return string|null
     */
    public function getCaptcha($type = 'login')
    {
        $cacheKey = $this->getCaptchaCacheKey($type);
        return Cache::get($cacheKey);
    }

    /**
     * 验证验证码
     *
     * @param string $code 用户输入的验证码
     * @param string $type 验证码类型
     * @return array ['valid' => bool, 'msg' => string]
     */
    public function validateCaptcha($code, $type = 'login')
    {
        $cacheKey = $this->getCaptchaCacheKey($type);
        $cachedCode = Cache::get($cacheKey);

        if (empty($code)) {
            return array('valid' => false, 'msg' => '请输入验证码');
        }

        // 验证码长度校验（4位数字）
        $codeLength = strlen($code);
        if ($codeLength !== 4) {
            return array('valid' => false, 'msg' => '验证码格式错误');
        }

        if (empty($cachedCode)) {
            return array('valid' => false, 'msg' => '验证码已过期');
        }

        if ($code !== $cachedCode) {
            return array('valid' => false, 'msg' => '验证码错误');
        }

        return array('valid' => true, 'msg' => '验证成功');
    }

    /**
     * 验证后清除验证码
     *
     * @param string $type 验证码类型
     * @return bool
     */
    public function clearCaptcha($type = 'login')
    {
        $cacheKey = $this->getCaptchaCacheKey($type);
        return Cache::rm($cacheKey);
    }

    /**
     * 生成验证码图片
     *
     * @param string $code 验证码
     * @return string base64 编码的图片数据
     */
    public function generateCaptchaImage($code)
    {
        $width = 120;
        $height = 40;

        // 创建画布
        $image = imagecreatetruecolor($width, $height);

        // 设置背景色
        $bgColor = imagecolorallocate($image, 240, 240, 240);
        imagefill($image, 0, 0, $bgColor);

        // 添加干扰线
        for ($i = 0; $i < 3; $i++) {
            $lineColor = imagecolorallocate($image, mt_rand(150, 220), mt_rand(150, 220), mt_rand(150, 220));
            imageline($image, mt_rand(0, $width), mt_rand(0, $height), mt_rand(0, $width), mt_rand(0, $height), $lineColor);
        }

        // 添加验证码文字
        $textColor = imagecolorallocate($image, 50, 50, 50);
        $fontSize = 5;
        $x = 15;
        $codeLen = strlen($code);

        for ($i = 0; $i < $codeLen; $i++) {
            // 随机 Y 轴偏移，增加识别难度
            $y = mt_rand(10, 15);
            imagestring($image, $fontSize, $x, $y, $code[$i], $textColor);
            $x += 25;
        }

        // 添加噪点
        for ($i = 0; $i < 50; $i++) {
            $pixelColor = imagecolorallocate($image, mt_rand(100, 200), mt_rand(100, 200), mt_rand(100, 200));
            imagesetpixel($image, mt_rand(0, $width), mt_rand(0, $height), $pixelColor);
        }

        // 输出为 base64
        ob_start();
        imagepng($image);
        $imageData = ob_get_clean();
        imagedestroy($image);

        return base64_encode($imageData);
    }

}