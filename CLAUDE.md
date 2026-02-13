# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

---

# BMS 项目开发规范

> **版本**: v1.3
> **更新日期**: 2026-02-13
> **项目**: BMS 后台管理系统 (前后端分离架构)

## 常用命令

### 前端 (bms-frontend/)

```bash
npm run dev          # 启动开发服务器 (http://127.0.0.1:5173)
npm run build        # 生产构建 (vue-tsc + vite build)
npm run lint         # ESLint 检查并自动修复
npm run format       # Prettier 格式化
npm run test         # Vitest 单元测试
npm run test:ui      # Vitest UI 界面
npm run test:coverage # 测试覆盖率报告
```

### 后端 (bms-backend/)

```bash
php -l path/to/file.php   # 检查 PHP 5.6 语法兼容性
```

后端运行需要配置 Web 服务器指向 `bms-backend/public/`，开发环境推荐使用 XAMPP。

### 环境配置

- 前端 API 地址: `bms-frontend/.env.development` → `VITE_API_BASE_URL=http://api.bms.co`
- 后端数据库: `bms-backend/application/database.php`
- 后端 CORS 白名单: `bms-backend/application/admin/controller/Base.php` → `$allowedOrigins`

---

## 技术栈

| 层级 | 技术 | 版本 | 说明 |
|------|------|------|------|
| 后端 | PHP | **5.6** | ⚠️ 严禁使用 PHP 7+ 语法 |
| 后端 | ThinkPHP | 5.0 | 框架版本 |
| 前端 | Vue | 3.5+ | Composition API |
| 前端 | Vite | 7.x | 构建工具 |
| 前端 | TypeScript | 5.x | 类型系统 |
| 前端 | Element Plus | 2.x | UI 组件库 |
| 前端 | Pinia | 3.x | 状态管理 |
| 数据库 | MySQL | 5.7+ | 表前缀: `bms_` |

---

## 项目架构

### 后端分层架构

```
Controller (控制器层)
    ↓
Service (服务层)
    ↓
Model (模型层)
    ↓
Database (数据库)
```

**核心原则**:
- 严禁在 Controller 中直接操作数据库
- 所有业务逻辑放在 Model 层 (当前项目 Model 层承担了 Service 层职责)
- 使用 `$this->apiReturn($code, $msg, $data)` 统一响应格式

### API 路由模式

后端 API 统一使用 `/admin/{controller}/{action}` 格式:
- `POST /admin/login/submit` - 登录 (无需认证)
- `GET /admin/captcha/index` - 验证码 (无需认证)
- `/admin/admin/getList`, `/admin/role/getList`, `/admin/permission/getList` 等

### 权限架构 (关键设计)

项目采用**动态权限驱动**架构:
1. `bms_permissions` 表统一管理 4 类权限: 目录(type=1), 菜单(type=2), 按钮(type=3), API(type=4)
2. 前端路由、菜单、按钮均从后端权限数据动态生成
3. 跳过认证的控制器设置 `protected $needAuth = false`

### 前端目录结构

```
src/
├── api/          # API 接口定义
├── assets/       # 静态资源
├── components/   # 公共组件
├── composables/  # 组合式函数
├── router/       # 路由配置
├── stores/       # Pinia 状态管理
├── utils/        # 工具函数
├── views/        # 页面组件
└── types/        # TypeScript 类型定义
```

---

## 数据库设计规范

### 数据库表结构

| 表名 | 说明 |
|------|------|
| `bms_admins` | 管理员账户 |
| `bms_roles` | 角色定义 |
| `bms_permissions` | 统一权限 (目录/菜单/按钮/API) |
| `bms_admin_roles` | 管理员-角色关联 |
| `bms_role_permissions` | 角色-权限关联 |
| `bms_configs` | 系统配置 |
| `bms_crontab` | 定时任务 |
| `bms_crontab_logs` | 任务执行日志 |

### 核心约束

#### 1. 命名规范 (强制)

```sql
-- 表名: bms_ 前缀 + 复数形式 + 小写
bms_admins, bms_roles, bms_permissions

-- 主键: {表单数}_id
admin_id, role_id, permission_id

-- 索引命名
PRIMARY KEY pk_admin_id (admin_id)
UNIQUE KEY uk_username (username)
KEY idx_status (status)
```

#### 2. 字段类型 (强制)

```sql
-- 时间字段: 使用 INT(11) 存储时间戳
created_at INT(11) NOT NULL
updated_at INT(11) NOT NULL

-- 布尔值: 使用 TINYINT(1)
status TINYINT(1) NOT NULL DEFAULT 1  -- 0禁用 1正常
is_super TINYINT(1) NOT NULL DEFAULT 0

-- 小数: 使用 DECIMAL
amount DECIMAL(10, 2)

-- 禁止 FLOAT/DOUBLE 存储金额
```

#### 3. SQL 编写规范 (强制)

```sql
-- ❌ 禁止 SELECT *
SELECT * FROM bms_admins;

-- ✅ 明确指定字段
SELECT admin_id, username, status FROM bms_admins;

-- ❌ 禁止 WHERE 中使用函数
WHERE DATE(created_at) = '2026-02-05'

-- ✅ 使用范围查询
WHERE created_at >= UNIX_TIMESTAMP('2026-02-05')
  AND created_at < UNIX_TIMESTAMP('2026-02-06')

-- ❌ 禁止外键约束
FOREIGN KEY (role_id) REFERENCES bms_roles(role_id)
```

#### 4. BMS 项目约定

```sql
-- 关联表命名: {表1}_{表2} (按字母顺序)
bms_admin_roles       -- 管理员-角色
bms_role_permissions  -- 角色-权限

-- 状态值约定
status: 0=禁用/失败, 1=启用/成功
is_super: 0=否, 1=是
is_deleted: 0=否, 1=是
```

### 模型层操作规范

```php
// ✅ 正确 - 通过 Model 层操作
$admin = model('Admin')->findByUsername($username);

// ❌ 错误 - Controller 中直接使用 M() 或 Db::name()
$admin = M('admins')->where('username', $username)->find();
$admin = Db::name('admins')->where('username', $username)->find();
```

---

## 后端开发规范

### PHP 5.6 兼容性 (CRITICAL)

**⚠️ 严禁使用 PHP 7+ 语法！**

| PHP 7+ 语法 | 说明 | PHP 5.6 替代写法 |
|-------------|------|------------------|
| `$var ?? $default` | 空合并运算符 | `isset($var) ? $var : $default` |
| `$var ??= $value` | 空合并赋值 | `if (!isset($var)) $var = $value` |
| `$a <=> $b` | 太空船运算符 | 使用三元表达式实现 |
| `function foo(int $a): int` | 类型声明 | 移除类型声明 |
| `function(): void` | 返回类型 | 移除返回类型 |
| 匿名类 | 匿名类语法 | 使用命名类 |
| Group use | Group use 语句 | 单独 use 语句 |

**正确示例**:

```php
// ✅ 正确 - PHP 5.6 兼容
$value = isset($data['key']) ? $data['key'] : 'default';
$jwtConfig = Config::get('jwt');
$key = isset($jwtConfig['secret']) ? $jwtConfig['secret'] : 'default-key';

// ❌ 错误 - PHP 7+ 语法
$value = $data['key'] ?? 'default';
```

### 命名规范

| 类型 | 命名规则 | 示例 |
|------|----------|------|
| 类名 | 大驼峰 | `Admin`, `LoginService` |
| 方法名 | 小驼峰 | `getList()`, `verifyPassword()` |
| 变量名 | 小写+下划线 | `$admin_id`, `$user_name` |
| 常量 | 大写+下划线 | `DEFAULT_PAGE_SIZE` |
| 数据库表名 | 小写+下划线 | `bms_admins`, `bms_roles` |

### 文件组织

```
bms-backend/application/admin/
├── controller/       # 控制器 (必须继承 Base)
│   ├── Base.php     # 基础控制器 (CORS, JWT, 权限)
│   ├── Login.php    # 登录控制器
│   └── ...
├── model/           # 模型层 (数据访问 + 缓存)
│   ├── Admin.php
│   └── ...
├── helper/          # 辅助类
│   └── Jwt.php      # JWT 工具类
└── config.php       # 模块配置
```

### 命名空间

```php
// Controller 层
namespace app\admin\controller;

// Service 层
namespace app\admin\service;

// Model 层
namespace app\admin\model;
```

### 全局函数引用

在命名空间内使用全局函数时需添加 `\` 前缀:

```php
// ✅ 正确
\M()  // ThinkPHP M 函数
\Firebase\JWT\JWT()  // JWT 库
session()  // ThinkPHP session 函数
config()  // ThinkPHP config 函数

// ❌ 错误 - 在命名空间内直接使用
M()
```

### 响应格式

统一响应格式:

```php
// 成功响应
return json(array(
    'code' => 200,
    'msg' => '操作成功',
    'data' => $data
));

// 错误响应
return json(array(
    'code' => 400,
    'msg' => '错误信息',
    'data' => null
));
```

### 数据库操作

通过 Model 层操作数据库:

```php
// ✅ 正确 - 通过 Model
$admin = $this->adminModel->findByUsername($username);

// ❌ 错误 - Controller 中直接使用 M()
$admin = M('admins')->where('username', $username)->find();
```

### 配置读取

```php
// 从配置文件读取
$jwtConfig = Config::get('jwt');
$key = isset($jwtConfig['secret']) ? $jwtConfig['secret'] : 'default-key';

// ❌ 不要使用 PHP 7+ 语法
$key = Config::get('jwt.secret') ?? 'default-key';
```

---

## 前端开发规范

### TypeScript 类型定义

所有 API 接口必须定义类型:

```typescript
// api/auth.ts
export interface LoginParams {
  username: string
  password: string
  captcha: string
}

export interface LoginResponse {
  token: string
  adminInfo: AdminInfo
}

export interface AdminInfo {
  admin_id: number
  username: string
  nickname: string
  is_super: number
}
```

### 组件命名

| 类型 | 命名规则 | 示例 |
|------|----------|------|
| 组件文件 | 大驼峰 | `UserList.vue`, `SidebarItem.vue` |
| 文件夹 | 小写 | `api/`, `utils/`, `views/` |
| JS/TS 文件 | 小驼峰 | `usePermission.ts`, `request.ts` |
| 常量 | 大写+下划线 | `API_BASE_URL` |

### Composition API

优先使用 `<script setup>` 语法:

```vue
<script setup lang="ts">
import { ref, computed } from 'vue'

const loading = ref(false)
const formData = ref({
  username: '',
  password: '',
  captcha: ''
})

const handleSubmit = async () => {
  // ...
}
</script>
```

### API 请求封装

使用统一的 request 工具:

```typescript
// utils/request.ts
const instance = axios.create({
  baseURL: import.meta.env.VITE_API_BASE_URL || '/api',
  timeout: 10000,
  withCredentials: true, // 跨域携带 Cookie
})

// api/auth.ts
export const login = (params: LoginParams) => {
  return request.post<LoginResponse>('/admin/login/submit', params)
}
```

### 权限控制

使用权限指令和组合式函数:

```vue
<!-- 指令权限 -->
<el-button v-permission="'system:admin:add'">新增</el-button>

<!-- 函数权限 -->
<script setup lang="ts">
import { usePermission } from '@/composables/usePermission'

const { hasPermission } = usePermission()

const canDelete = hasPermission('system:admin:delete')
</script>
```

---
## 开发流程

### 新增功能流程

1. **数据库设计**
   - 创建/修改表结构
   - 编写迁移脚本到 `database/migrations/`

2. **后端开发**
   - 创建 Model (`application/admin/model/`)
   - 创建 Controller (`application/admin/controller/`)
   - 添加权限数据到 `bms_permissions`

3. **前端开发**
   - 创建 API 接口 (`src/api/`)
   - 创建页面组件 (`src/views/`)
   - 添加路由 (动态路由自动加载)

4. **测试**
   - 后端 API 测试
   - 前端组件测试

### 权限配置

1. 在 `bms_permissions` 表添加权限记录
2. 为角色分配权限 (`bms_role_permissions`)
3. 前端使用 `v-permission` 指令控制按钮显示

```vue
<el-button v-permission="'system:admin:add'">新增</el-button>
```

---

## 安全规范

### 密码处理

- 使用 `password_hash()` 和 `password_verify()` 处理密码
- 永远不在日志或响应中输出明文密码

### SQL 注入防护

- 使用参数绑定查询
- 避免 SQL 字符串拼接

### XSS 防护

- 输出时进行 HTML 转义
- 前端使用 Vue 的默认转义

### 防止时序攻击

```php
// 验证密码时使用恒定时间比较
// 用户不存在时也执行密码验证以保持一致的响应时间
$fakeHash = '$2y$10$xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';
if (!$admin) {
    password_verify($password, $fakeHash);
    return false;
}
```

### 错误消息

- 不暴露系统内部信息
- 统一错误消息防止信息泄露

```php
// ✅ 正确 - 统一错误消息
$errorMessage = '验证码错误或已过期';

// ❌ 错误 - 暴露具体错误
if (empty($sessionCode)) {
    return 'Session 中没有验证码';
}
```

---

## 代码风格

### 注释规范

```php
/**
 * 类/方法/属性注释
 *
 * @param string $param 参数说明
 * @return array 返回值说明
 * @throws \Exception 异常说明
 */
```

### Git 提交格式

```
<type>(<scope>): <subject>

<body>

<footer>
```

类型:
- `feat`: 新功能
- `fix`: 修复
- `refactor`: 重构
- `docs`: 文档
- `style`: 格式
- `test`: 测试
- `chore`: 构建

---

## 常见问题

### Q: 为什么必须使用 PHP 5.6 语法?

A: 生产环境使用 XAMPP (PHP 5.6)，虽然本地开发可以使用更高版本的 PHP，但代码必须在 PHP 5.6 环境下运行。

### Q: 如何检查 PHP 5.6 兼容性?

A: 使用以下命令检查语法:
```bash
php -l path/to/file.php
```

### Q: 前端 API 域名配置在哪里?

A: 配置在 `bms-frontend/.env.development`:
```env
VITE_API_BASE_URL=http://api.cms101.co
```

### Q: CORS 跨域如何配置?

A: 后端配置在 `bms-backend/application/admin/controller/Base.php`，添加允许的源到 `$allowedOrigins` 数组。

### Q: Session 和 Cookie 跨域问题?

A: 确保:
1. 后端 `session_set_cookie_params(0, '/', '', false, true)`
2. 前端 axios 配置 `withCredentials: true`
3. 后端 CORS `Access-Control-Allow-Credentials: true`

---

**文档维护**: 本文档应随项目演进持续更新
