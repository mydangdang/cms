<?php
/**
 * Admin 模块配置
 */

return array(
    // JWT 配置
    'jwt' => array(
        // JWT 密钥 - 生产环境请修改为随机字符串
        'secret_key' => 'bms-jwt-secret-key-2026-change-in-production-environment',
        // Token 有效期（秒）- 2 小时
        'expire' => 7200,
        // 签发者
        'issuer' => 'bms-system',
        // 受众
        'audience' => 'bms-admin',
        // 加密算法
        'algorithm' => 'HS256',
    ),

    // CORS 跨域配置
    'cors' => array(
        // 允许的源域名列表
        'allowed_origins' => array(
            'http://127.0.0.1:5173',
            'http://localhost:5173',
        ),
        // 允许的请求方法
        'allowed_methods' => 'GET, POST, PUT, DELETE, OPTIONS',
        // 允许的请求头
        'allowed_headers' => 'Origin, Content-Type, Authorization, X-Requested-With',
        // 预检请求缓存时间（秒）
        'max_age' => 86400,
    ),
);
