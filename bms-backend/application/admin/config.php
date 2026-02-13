<?php
/**
 * JWT 配置
 */

return array(
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
);
