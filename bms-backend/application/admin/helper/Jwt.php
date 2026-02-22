<?php
/**
 * JWT 助手类
 *
 * 功能：生成和验证 JWT Token
 * 兼容 PHP 5.6
 */

namespace app\admin\helper;

use \Firebase\JWT\JWT as FirebaseJWT;

// 防止类重复声明
if (class_exists('app\admin\helper\Jwt', false)) {
    return;
}

class Jwt
{
    /**
     * 获取 JWT 配置
     * 优先从 config.php 读取，使用默认值作为回退
     *
     * @return array
     */
    protected static function getConfig()
    {
        $config = \think\Config::get('jwt');
        return array(
            'algorithm' => isset($config['algorithm']) ? $config['algorithm'] : 'HS256',
            'expire' => isset($config['expire']) ? (int)$config['expire'] : 7200,
            'issuer' => isset($config['issuer']) ? $config['issuer'] : 'bms-system',
            'audience' => isset($config['audience']) ? $config['audience'] : 'bms-admin'
        );
    }

    /**
     * 生成 JWT Token
     *
     * @param int $adminId 管理员ID
     * @param string $username 用户名
     * @param int $isSuper 是否超级管理员
     * @return string Token
     */
    public static function encode($adminId, $username, $isSuper = 0)
    {
        $config = self::getConfig();
        $now = time();
        $expire = $now + $config['expire'];

        $payload = array(
            'iss' => $config['issuer'],
            'aud' => $config['audience'],
            'iat' => $now,
            'exp' => $expire,
            'data' => array(
                'admin_id' => (int)$adminId,
                'username' => $username,
                'is_super' => (int)$isSuper
            )
        );

        return FirebaseJWT::encode($payload, self::getSecretKey(), $config['algorithm']);
    }

    /**
     * 验证并解码 JWT Token
     *
     * @param string $token JWT Token
     * @return array|false 解码后的数据或 false
     */
    public static function decode($token)
    {
        $config = self::getConfig();
        try {
            $decoded = FirebaseJWT::decode($token, self::getSecretKey(), array($config['algorithm']));
            return (array)$decoded;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 从 Token 中获取管理员信息
     *
     * @param string $token JWT Token
     * @return array|false 管理员信息或 false
     */
    public static function getAdminInfo($token)
    {
        $decoded = self::decode($token);

        if ($decoded === false || !isset($decoded['data'])) {
            return false;
        }

        // 确保 data 属性是数组（FirebaseJWT 返回的 data 可能是 stdClass）
        $data = $decoded['data'];
        return is_object($data) ? (array)$data : $data;
    }

    /**
     * 验证 Token 是否有效
     *
     * @param string $token JWT Token
     * @return bool 是否有效
     */
    public static function verify($token)
    {
        $decoded = self::decode($token);
        return $decoded !== false;
    }

    /**
     * 获取密钥
     *
     * @return string
     */
    protected static function getSecretKey()
    {
        // 从配置文件读取密钥，如果没有则使用默认值
        $config = \think\Config::get('jwt');
        $key = isset($config['secret_key']) ? $config['secret_key'] : 'bms-default-secret-key-change-in-production';
        return $key;
    }

    /**
     * 获取 Token 过期时间（秒）
     *
     * @return int
     */
    public static function getExpireTime()
    {
        $config = self::getConfig();
        return $config['expire'];
    }
}
