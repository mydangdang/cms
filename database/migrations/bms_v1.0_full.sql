-- ========================================
-- BMS 后台管理系统 - 数据库迁移脚本
-- 版本: v1.0
-- 创建时间: 2026-02-28
-- 说明: 完整初始化数据库结构及初始数据
-- ========================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- 1. 管理员表 (bms_admins)
-- ----------------------------
DROP TABLE IF EXISTS `bms_admins`;
CREATE TABLE `bms_admins` (
  `admin_id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `username` VARCHAR(50) NOT NULL COMMENT '用户名',
  `password` VARCHAR(255) NOT NULL COMMENT '密码(bcrypt hash)',
  `real_name` VARCHAR(50) DEFAULT NULL COMMENT '真实姓名',
  `mobile` VARCHAR(20) DEFAULT NULL COMMENT '手机号',
  `status` TINYINT(1) NOT NULL DEFAULT '1' COMMENT '状态：0禁用 1正常',
  `is_super` TINYINT(1) NOT NULL DEFAULT '0' COMMENT '是否超级管理员',
  `last_login_ip` VARCHAR(50) DEFAULT NULL COMMENT '最后登录IP',
  `last_login_time` INT(11) DEFAULT NULL COMMENT '最后登录时间',
  `password_update_time` INT(11) DEFAULT NULL COMMENT '密码修改时间',
  `created_at` INT(11) NOT NULL COMMENT '创建时间',
  `updated_at` INT(11) NOT NULL COMMENT '更新时间',
  `deleted_at` INT(11) UNSIGNED DEFAULT '0' COMMENT '删除时间',
  PRIMARY KEY (`admin_id`),
  UNIQUE KEY `uk_username` (`username`),
  KEY `idx_status` (`status`),
  KEY `idx_deleted_at` (`deleted_at`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='管理员表';

-- ----------------------------
-- 2. 角色表 (bms_roles)
-- ----------------------------
DROP TABLE IF EXISTS `bms_roles`;
CREATE TABLE `bms_roles` (
  `role_id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '角色ID',
  `name` VARCHAR(50) NOT NULL COMMENT '角色名称',
  `description` VARCHAR(200) DEFAULT NULL COMMENT '角色描述',
  `status` TINYINT(4) DEFAULT '1' COMMENT '状态（0=禁用,1=启用）',
  `sort_order` INT(11) DEFAULT '0' COMMENT '排序',
  `created_at` INT(11) NOT NULL COMMENT '创建时间（时间戳）',
  `updated_at` INT(11) NOT NULL COMMENT '更新时间（时间戳）',
  `deleted_at` INT(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '删除时间（时间戳）',
  PRIMARY KEY (`role_id`),
  KEY `idx_status` (`status`),
  KEY `idx_deleted_at` (`deleted_at`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='角色表';

-- ----------------------------
-- 3. 权限菜单表 (bms_permissions)
-- ----------------------------
DROP TABLE IF EXISTS `bms_permissions`;
CREATE TABLE `bms_permissions` (
  `permission_id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '权限ID',
  `parent_id` INT(11) DEFAULT '0' COMMENT '父级权限ID（0为顶级）',
  `title` VARCHAR(50) NOT NULL COMMENT '权限标题（中文显示）',
  `code` VARCHAR(100) DEFAULT NULL COMMENT '权限编码（如：system:manager:add）',
  `type` TINYINT(4) NOT NULL COMMENT '1=目录,2=菜单,3=按钮,4=接口',
  `path` VARCHAR(200) DEFAULT NULL COMMENT '前端路由路径',
  `component` VARCHAR(200) DEFAULT NULL COMMENT '前端组件路径',
  `icon` VARCHAR(50) DEFAULT NULL COMMENT '图标（Element Plus 图标名）',
  `is_hidden` TINYINT(4) DEFAULT '0' COMMENT '是否隐藏（0=显示,1=隐藏）',
  `is_affix` TINYINT(4) DEFAULT '0' COMMENT '是否固定标签（0=否,1=是）',
  `is_cache` TINYINT(4) DEFAULT '0' COMMENT '是否开启 KeepAlive 缓存（0=否,1=是）',
  `sort_order` INT(11) DEFAULT '0' COMMENT '排序（数字越小越靠前）',
  `status` TINYINT(4) DEFAULT '1' COMMENT '状态（0=禁用,1=启用）',
  `created_at` INT(11) NOT NULL COMMENT '创建时间（时间戳）',
  `updated_at` INT(11) NOT NULL COMMENT '更新时间（时间戳）',
  `deleted_at` INT(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '删除时间（时间戳）',
  PRIMARY KEY (`permission_id`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_type` (`type`),
  KEY `idx_status` (`status`),
  KEY `idx_code` (`code`),
  KEY `idx_deleted_at` (`deleted_at`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='权限菜单表';

-- ----------------------------
-- 4. 管理员角色关联表 (bms_admin_roles)
-- ----------------------------
DROP TABLE IF EXISTS `bms_admin_roles`;
CREATE TABLE `bms_admin_roles` (
  `admin_id` INT(11) NOT NULL COMMENT '管理员ID',
  `role_id` INT(11) NOT NULL COMMENT '角色ID',
  `created_at` INT(11) NOT NULL COMMENT '创建时间',
  PRIMARY KEY (`admin_id`, `role_id`),
  KEY `idx_admin_id` (`admin_id`),
  KEY `idx_role_id` (`role_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='管理员角色关联表';

-- ----------------------------
-- 5. 角色权限关联表 (bms_role_permissions)
-- ----------------------------
DROP TABLE IF EXISTS `bms_role_permissions`;
CREATE TABLE `bms_role_permissions` (
  `id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `role_id` INT(11) NOT NULL COMMENT '角色ID',
  `permission_id` INT(11) NOT NULL COMMENT '权限ID',
  `created_at` INT(11) NOT NULL COMMENT '创建时间（时间戳）',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_role_permission` (`role_id`, `permission_id`),
  KEY `idx_role_id` (`role_id`),
  KEY `idx_permission_id` (`permission_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='角色权限关联表';

-- ----------------------------
-- 6. 系统配置表 (bms_configs)
-- ----------------------------
DROP TABLE IF EXISTS `bms_configs`;
CREATE TABLE `bms_configs` (
  `config_id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '配置ID',
  `config_group` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '0:未定义 1:站点设置；2:运营参数 3:系统参数',
  `config_name` VARCHAR(50) NOT NULL COMMENT '配置名称',
  `config_key` VARCHAR(50) NOT NULL COMMENT '配置键',
  `config_value` TEXT COMMENT '配置值',
  `config_type` VARCHAR(20) DEFAULT 'text' COMMENT '配置类型: text/number/boolean/textarea',
  `description` VARCHAR(200) DEFAULT NULL COMMENT '配置描述',
  `sort_order` INT(11) DEFAULT '0' COMMENT '排序',
  `created_at` INT(11) NOT NULL COMMENT '创建时间（时间戳）',
  `updated_at` INT(11) NOT NULL COMMENT '更新时间（时间戳）',
  `deleted_at` INT(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '删除时间（伪删除）',
  PRIMARY KEY (`config_id`),
  UNIQUE KEY `uk_group_key` (`config_group`, `config_key`),
  KEY `idx_config_group` (`config_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='系统配置表';

-- ----------------------------
-- 7. 定时任务表 (bms_crontab)
-- ----------------------------
DROP TABLE IF EXISTS `bms_crontab`;
CREATE TABLE `bms_crontab` (
  `crontab_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '任务ID',
  `name` VARCHAR(100) NOT NULL COMMENT '任务名称',
  `cron` VARCHAR(100) NOT NULL COMMENT 'Cron表达式',
  `command` VARCHAR(100) NOT NULL COMMENT '执行命令',
  `timeout` INT(11) UNSIGNED NOT NULL DEFAULT '300' COMMENT '超时时间(秒)',
  `description` VARCHAR(500) DEFAULT NULL COMMENT '任务描述',
  `sort_order` INT(11) NOT NULL DEFAULT '0' COMMENT '排序',
  `status` TINYINT(1) NOT NULL DEFAULT '1' COMMENT '状态: 0=禁用 1=启用',
  `created_at` INT(11) NOT NULL COMMENT '创建时间（时间戳）',
  `updated_at` INT(11) NOT NULL COMMENT '更新时间（时间戳）',
  `deleted_at` INT(11) NOT NULL DEFAULT '0' COMMENT '删除时间（0=未删除）',
  PRIMARY KEY (`crontab_id`),
  KEY `idx_status` (`status`),
  KEY `idx_deleted_at` (`deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='定时任务表';

-- ----------------------------
-- 8. 定时任务执行记录表 (bms_crontab_logs)
-- ----------------------------
DROP TABLE IF EXISTS `bms_crontab_logs`;
CREATE TABLE `bms_crontab_logs` (
  `execute_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '执行ID',
  `crontab_id` INT(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '任务ID',
  `execute_type` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '执行类型：1-API；2-CLI',
  `execute_time` INT(11) UNSIGNED NOT NULL DEFAULT '0' COMMENT '执行时间（时间戳）',
  `duration` DECIMAL(10,2) UNSIGNED NOT NULL DEFAULT '0.00' COMMENT '执行耗时（秒）',
  `status` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0' COMMENT '状态: 0=失败 1=成功 2=超时',
  `message` VARCHAR(255) DEFAULT NULL COMMENT '输出内容',
  PRIMARY KEY (`execute_id`),
  KEY `idx_crontab_id` (`crontab_id`),
  KEY `idx_execute_time` (`execute_time`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='定时任务执行记录表';

-- ========================================
-- 初始数据
-- ========================================

-- 默认角色
INSERT INTO `bms_roles` (`name`, `description`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES
('系统管理员', '拥有系统所有权限', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 默认超级管理员 (密码: 123456)
INSERT INTO `bms_admins` (`username`, `password`, `real_name`, `status`, `is_super`, `created_at`, `updated_at`) VALUES
('admin', '$2y$12$NnpfY/Hm4x2ofA6ZLgBUzuymlvPQz9U2CwKuXjcRmUVd7/FbJevQm', '超级管理员', 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 管理员角色关联
INSERT INTO `bms_admin_roles` (`admin_id`, `role_id`, `created_at`) VALUES
(1, 1, UNIX_TIMESTAMP());

-- ========================================
-- 系统初始化菜单
-- ========================================

-- 1. 工作台
INSERT INTO `bms_permissions` (`parent_id`, `title`, `code`, `type`, `path`, `icon`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES
(0, '系统首页', 'workspace', 1, '', NULL, 100, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
SET @workspace_id = LAST_INSERT_ID();

INSERT INTO `bms_permissions` (`parent_id`, `title`, `code`, `type`, `path`, `component`, `icon`, `is_affix`, `is_cache`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES
(@workspace_id, '工作台', 'workspace:dashboard', 2, '/', 'views/Dashboard.vue', 'Platform', 0, 1, 10, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 2. 系统管理
INSERT INTO `bms_permissions` (`parent_id`, `title`, `code`, `type`, `path`, `icon`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES
(0, '系统管理', 'system', 1, '', NULL, 200, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
SET @system_dir_id = LAST_INSERT_ID();

-- 2.1 管理员管理
INSERT INTO `bms_permissions` (`parent_id`, `title`, `code`, `type`, `path`, `component`, `icon`, `is_cache`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES
(@system_dir_id, '管理员管理', 'system:manager', 2, '/system/manager', 'views/system/ManagerList.vue', 'User', 1, 10, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
SET @manager_menu_id = LAST_INSERT_ID();

-- 管理员按钮权限
INSERT INTO `bms_permissions` (`parent_id`, `title`, `code`, `type`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES
(@manager_menu_id, '新增管理员', 'system:manager:add', 3, 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@manager_menu_id, '编辑管理员', 'system:manager:edit', 3, 2, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@manager_menu_id, '删除管理员', 'system:manager:delete', 3, 3, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 管理员 API 权限
INSERT INTO `bms_permissions` (`parent_id`, `title`, `code`, `type`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES
(@manager_menu_id, '管理员列表API', 'admin/manager/getlist', 4, 4, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@manager_menu_id, '新增管理员API', 'admin/manager/add', 4, 5, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@manager_menu_id, '编辑管理员API', 'admin/manager/edit', 4, 6, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@manager_menu_id, '删除管理员API', 'admin/manager/delete', 4, 7, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 2.2 角色管理
INSERT INTO `bms_permissions` (`parent_id`, `title`, `code`, `type`, `path`, `component`, `icon`, `is_cache`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES
(@system_dir_id, '角色管理', 'system:role', 2, '/system/role', 'views/system/RoleList.vue', 'MapLocation', 1, 20, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
SET @role_menu_id = LAST_INSERT_ID();

-- 角色按钮权限
INSERT INTO `bms_permissions` (`parent_id`, `title`, `code`, `type`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES
(@role_menu_id, '新增角色', 'system:role:add', 3, 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@role_menu_id, '编辑角色', 'system:role:edit', 3, 2, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@role_menu_id, '删除角色', 'system:role:delete', 3, 3, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@role_menu_id, '分配权限', 'system:role:assignpermission', 3, 4, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@role_menu_id, '排序角色', 'system:role:resort', 3, 5, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 角色 API 权限
INSERT INTO `bms_permissions` (`parent_id`, `title`, `code`, `type`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES
(@role_menu_id, '新增角色API', 'admin/role/add', 4, 5, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@role_menu_id, '编辑角色API', 'admin/role/edit', 4, 6, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@role_menu_id, '删除角色API', 'admin/role/delete', 4, 7, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@role_menu_id, '分配权限API', 'admin/role/assignpermission', 4, 8, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@role_menu_id, '排序角色API', 'admin/role/resort', 4, 9, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 2.3 权限菜单管理
INSERT INTO `bms_permissions` (`parent_id`, `title`, `code`, `type`, `path`, `component`, `icon`, `is_cache`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES
(@system_dir_id, '权限菜单管理', 'system:permission', 2, '/system/permission', 'views/system/PermissionList.vue', 'Lock', 1, 30, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
SET @permission_menu_id = LAST_INSERT_ID();

-- 权限菜单按钮权限
INSERT INTO `bms_permissions` (`parent_id`, `title`, `code`, `type`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES
(@permission_menu_id, '新增菜单', 'system:permission:add', 3, 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@permission_menu_id, '编辑菜单', 'system:permission:edit', 3, 2, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@permission_menu_id, '删除菜单', 'system:permission:delete', 3, 3, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@permission_menu_id, '排序菜单', 'system:permission:resort', 3, 4, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 权限菜单 API 权限
INSERT INTO `bms_permissions` (`parent_id`, `title`, `code`, `type`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES
(@permission_menu_id, '新增菜单API', 'admin/permission/add', 4, 4, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@permission_menu_id, '编辑菜单API', 'admin/permission/edit', 4, 5, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@permission_menu_id, '删除菜单API', 'admin/permission/delete', 4, 6, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@permission_menu_id, '排序菜单API', 'admin/permission/resort', 4, 7, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 2.4 系统配置
INSERT INTO `bms_permissions` (`parent_id`, `title`, `code`, `type`, `path`, `component`, `icon`, `is_cache`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES
(@system_dir_id, '系统配置', 'system:config', 2, '/system/config', 'views/system/Config.vue', 'Discount', 1, 40, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
SET @config_menu_id = LAST_INSERT_ID();

-- 系统配置按钮权限
INSERT INTO `bms_permissions` (`parent_id`, `title`, `code`, `type`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES
(@config_menu_id, '新增配置', 'system:config:add', 3, 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@config_menu_id, '编辑配置', 'system:config:edit', 3, 2, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@config_menu_id, '删除配置', 'system:config:delete', 3, 3, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@config_menu_id, '排序配置', 'system:config:resort', 3, 4, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 系统配置 API 权限
INSERT INTO `bms_permissions` (`parent_id`, `title`, `code`, `type`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES
(@config_menu_id, '新增配置API', 'admin/config/add', 4, 4, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@config_menu_id, '编辑配置API', 'admin/config/edit', 4, 5, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@config_menu_id, '删除配置API', 'admin/config/delete', 4, 6, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@config_menu_id, '排序配置API', 'admin/config/resort', 4, 7, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 2.5 定时任务
INSERT INTO `bms_permissions` (`parent_id`, `title`, `code`, `type`, `path`, `component`, `icon`, `is_cache`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES
(@system_dir_id, '定时任务', 'system:crontab', 2, '/system/crontab', 'views/system/CrontabList.vue', 'Clock', 1, 50, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());
SET @crontab_menu_id = LAST_INSERT_ID();

-- 定时任务按钮权限
INSERT INTO `bms_permissions` (`parent_id`, `title`, `code`, `type`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES
(@crontab_menu_id, '新增任务', 'system:crontab:add', 3, 1, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@crontab_menu_id, '编辑任务', 'system:crontab:edit', 3, 2, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@crontab_menu_id, '删除任务', 'system:crontab:delete', 3, 3, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@crontab_menu_id, '立即执行', 'system:crontab:execute', 3, 4, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@crontab_menu_id, '查看记录', 'system:crontab:getlogs', 3, 5, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@crontab_menu_id, '清空记录', 'system:crontab:clearlogs', 3, 6, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@crontab_menu_id, '排序任务', 'system:crontab:resort', 3, 7, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- 定时任务 API 权限
INSERT INTO `bms_permissions` (`parent_id`, `title`, `code`, `type`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES
(@crontab_menu_id, '新增任务API', 'admin/crontab/add', 4, 7, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@crontab_menu_id, '编辑任务API', 'admin/crontab/edit', 4, 8, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@crontab_menu_id, '删除任务API', 'admin/crontab/delete', 4, 9, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@crontab_menu_id, '立即执行API', 'admin/crontab/execute', 4, 10, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@crontab_menu_id, '记录列表API', 'admin/crontab/getlogs', 4, 11, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@crontab_menu_id, '清空记录API', 'admin/crontab/clearlogs', 4, 12, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP()),
(@crontab_menu_id, '排序任务API', 'admin/crontab/resort', 4, 13, 1, UNIX_TIMESTAMP(), UNIX_TIMESTAMP());

-- ========================================
-- 为系统管理员角色分配所有权限
-- ========================================
INSERT INTO `bms_role_permissions` (`role_id`, `permission_id`, `created_at`)
SELECT 1, `permission_id`, UNIX_TIMESTAMP() FROM `bms_permissions` WHERE `deleted_at` = 0;

SET FOREIGN_KEY_CHECKS = 1;
