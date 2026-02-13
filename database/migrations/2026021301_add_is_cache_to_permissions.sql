-- ========================================
-- 数据库迁移: 添加 is_cache 字段到权限表
-- 版本: 2026021301
-- 日期: 2026-02-13
-- 描述: 在 bms_permissions 表中添加 is_cache 字段，用于控制菜单页面是否开启 KeepAlive 缓存
-- ========================================

-- 添加 is_cache 字段到 bms_permissions 表
ALTER TABLE `bms_permissions`
ADD COLUMN `is_cache` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '是否开启缓存：0=否，1=是' AFTER `is_affix`;

-- 更新所有菜单类数据(type=2)的 is_cache 为 1（开启缓存）
UPDATE `bms_permissions` SET `is_cache` = 1 WHERE `type` = 2;

-- 回滚SQL（如需回滚请执行以下语句）
-- ALTER TABLE `bms_permissions` DROP COLUMN `is_cache`;
