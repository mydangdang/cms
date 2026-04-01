# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

---

# 项目开发规范

> **版本**: v2.1
> **更新日期**: 2026-02-27
> **项目**: BMS 后台管理系统

---

## 核心原则

- ✅ RBAC 权限模型：基于角色的访问控制（管理员 ↔ 角色 ↔ 权限）
- ✅ 统一权限模型：菜单 + 路由 + 按钮三合一，实现动态权限驱动架构
- ✅ 功能权限精细化：支持页面级、按钮级、接口级权限控制
- ✅ 企业级扩展：支持定时任务、系统配置、动态缓存

## 全局约束

- **会话语言**：始终使用中文进行会话和代码注释。
- **权限菜单约束**：开发文档中未特别要求添加的目录、菜单、按钮、API权限，无需自行添加，必须严格按照开发文档（PRD/Architecture/Story）中的权限设计来添加权限菜单。
- **模块开发约束**：新模块开发严格按照文档来完成，尤其是前、后端文件清单指定的文件命名和存放路径；开发前阅读全局约束和相关文档，严格按照模块设计文档来执行，现有文档仅作为辅助和补充。

### 一、技术栈

#### 1.1 前端技术栈

| 技术 | 版本 | 说明 |
|------|------|------|
| **Vue** | 3.5+ | 渐进式 JavaScript 框架 |
| **Vite** | 7.x | 下一代前端构建工具 |
| **Vue Router** | 4.x | Vue.js 官方路由管理器 |
| **Pinia** | 3.x | Vue 3 官方状态管理库 |
| **TypeScript** | 5.x | 类型安全 |
| **Element Plus** | 2.x | 基于 Vue 3 的组件库 |
| **Axios** | 1.x | HTTP 请求库 |
| **Day.js** | 1.x | 轻量级日期处理库 |
| **lodash-es** | 4.x | JavaScript 工具库 |
| **SCSS** | - | CSS 预处理器 |
| **ESLint** | - | 代码质量检查工具 |
| **Prettier** | - | 代码格式化工具 |
| **Vitest** | - | 单元测试框架 |

---

#### 1.2 后端技术栈

| 技术 | 版本 | 说明 |
|------|------|------|
| **ThinkPHP** | 5.0 | PHP 快速开发框架 |
| **PHP** | 5.6 | 服务端编程语言（⚠️ **不允许使用 PHP 7+ 语法**） |
| **XAMPP** | - | 集成开发环境（包含 PHP 5.6） |
| **MySQL** | 5.7+ | 关系型数据库 |
| **Composer** | - | PHP 依赖管理工具 |
| **JWT** | - | JSON Web Token 认证 |

---

#### 1.3 开发工具

| 工具 | 说明 |
|------|------|
| **Git** | 版本控制 |
| **VS Code** | 代码编辑器 |
| **Apifox** | API 测试工具 |
| **Navicat** | 数据库管理工具 |
| **Chrome DevTools** | 浏览器开发者工具 |

---

### 二、数据库约束

#### 2.1 存储引擎和字符集约束（强制）

```sql
-- ✅ 所有表必须使用 InnoDB 存储引擎
ENGINE=InnoDB

-- ✅ 字符集统一使用 utf8
DEFAULT CHARSET=utf8

-- ✅ 排序规则统一使用 utf8_general_ci
COLLATE=utf8_general_ci
```

**约束说明**：
- **存储引擎**：所有表必须使用 `InnoDB`，支持事务和外键
- **字符集**：统一使用 `utf8`，支持中文
- **排序规则**：`utf8_general_ci` 不区分大小写排序

**建表示例**：
```sql
CREATE TABLE `bms_admins` (
  `admin_id` INT(11) NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `username` VARCHAR(50) NOT NULL COMMENT '用户名',
  PRIMARY KEY (`admin_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='管理员表';
```

---

#### 2.2 命名规范（强制）

```sql
-- 表名: bms_ 前缀 + 复数形式 + 小写
bms_admins, bms_roles, bms_permissions

-- 主键: {表单数}_id
admin_id, role_id, permission_id

-- 索引命名
PRIMARY KEY pk_admin_id (admin_id)
UNIQUE KEY uk_username (username)
KEY idx_status (status)

-- 关联表命名: {表1}_{表2} (按字母顺序)
bms_admin_roles       -- 管理员-角色
bms_role_permissions  -- 角色-权限
```

---

#### 2.3 字段类型（强制）

```sql
-- 时间字段: 使用 INT(11) 存储时间戳
created_at INT(11) NOT NULL
updated_at INT(11) NOT NULL
deleted_at INT(11) NOT NULL

-- 布尔值: 使用 TINYINT(1)
status TINYINT(1) NOT NULL DEFAULT 1  -- 0禁用 1正常
is_super TINYINT(1) NOT NULL DEFAULT 0

-- 小数: 使用 DECIMAL
amount DECIMAL(10, 2)

-- 禁止 FLOAT/DOUBLE 存储金额
```

---

### 三、SQL 编写规范（强制）

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

-- ✅ 项目状态值约定
status: 0=禁用/失败, 1=启用/成功
is_super: 0=否, 1=是
deleted_at: 0=否, >0是
```

---

### 四、常用命令

#### 4.1 前端 (bms-frontend/)

```bash
npm run dev          # 启动开发服务器 (http://127.0.0.1:5173)
npm run build        # 生产构建 (vue-tsc + vite build)
npm run lint         # ESLint 检查并自动修复
npm run format       # Prettier 格式化
npm run test         # Vitest 单元测试
npm run test:ui      # Vitest UI 界面
npm run test:coverage # 测试覆盖率报告
```

---

#### 4.2 后端 (bms-backend/)

```bash
php -l path/to/file.php   # 检查 PHP 5.6 语法兼容性
```

后端运行需要配置 Web 服务器指向 `bms-backend/public/`，开发环境推荐使用 XAMPP。

---

### 五、项目架构

#### 5.1 后端分层架构

```
Controller (控制器层)
    ↓
Model (模型层) - 业务逻辑 + 数据访问
    ↓
Database (数据库)
```

> **说明**: 当前项目 Model 层承担了 Service 层职责，包含业务逻辑和数据访问。

**核心原则**:
- 严禁在 Controller 中直接操作数据库
- 所有业务逻辑放在 Model 层
- 使用 `$this->apiReturn($code, $msg, $data)` 统一响应格式

---

#### 5.2 API 路由模式

后端 API 统一使用 `/admin/{controller}/{action}` 格式:
- `POST /admin/login/submit` - 登录 (无需认证)
- `GET /admin/captcha/index` - 验证码 (无需认证)
- `/admin/manager/getList`, `/admin/role/getList`, `/admin/permission/getList` 等

**URL 访问规则（ThinkPHP 5.0 约束）**:
- 格式：`http://api.cms101.co/{模块}/{控制器}/{方法}?参数=值`
- 示例：`http://api.cms101.co/admin/login/submit` → admin模块/Login控制器/submit方法
- 不做伪静态处理，直接使用 ThinkPHP 默认 URL 模式
- ThinkPHP 5.0 默认支持此规则，无需额外配置路由

---

#### 5.3 统一响应格式

```json
{
  "code": 200,
  "msg": "操作成功",
  "data": {}
}
```

##### 响应状态码规范

| code | 说明 |
|------|------|
| 200 | 成功 |
| 400 | 请求错误 |
| 401 | 未认证 |
| 403 | 无权限 |
| 500 | 服务器错误 |

---

#### 5.4 权限架构 (关键设计)

项目采用**动态权限驱动**架构:
1. `bms_permissions` 表统一管理 4 类权限: 目录(type=1), 菜单(type=2), 按钮(type=3), API(type=4)
2. 前端路由、菜单、按钮均从后端权限数据动态生成
3. 跳过认证的控制器设置 `protected $needAuth = false`

---

#### 5.5 前端目录结构

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

#### 5.6 后端目录结构

```
bms-backend/
├── application/
│   ├── common.php   # 公共函数库 (时间解析、高精度计算、IP获取等)
│   └── admin/
│       ├── controller/       # 控制器 (必须继承 Base)
│       │   ├── Base.php     # 基础控制器 (CORS, JWT, 权限)
│       │   ├── Login.php    # 登录控制器
│       │   └── ...
│       ├── model/           # 模型层 (数据访问 + 缓存)
│       │   ├── Admin.php
│       │   └── ...
│       ├── helper/          # 辅助类
│       │   └── Jwt.php      # JWT 工具类
│       └── config.php       # 模块配置
└── public/                 # 入口目录
```

---

#### 5.7 环境配置

- 前端 API 地址: `bms-frontend/.env.development` → `VITE_API_BASE_URL=http://api.cms101.co`
- 后端数据库: `bms-backend/application/database.php`
- 后端 CORS 白名单: `bms-backend/application/admin/config.php` → `cors.allowed_origins`

---

### 六、前端开发规范

#### 6.1 TypeScript 类型定义

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

---

#### 6.2 组件命名

| 类型 | 命名规则 | 示例 |
|------|----------|------|
| 组件文件 | 大驼峰 | `UserList.vue`, `SidebarItem.vue` |
| 文件夹 | 小写 | `api/`, `utils/`, `views/` |
| JS/TS 文件 | 小驼峰 | `usePermission.ts`, `request.ts` |
| 常量 | 大写+下划线 | `API_BASE_URL` |

---

#### 6.3 Composition API

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

---

#### 6.4 API 请求封装

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

---

#### 6.5 权限控制

使用权限指令和组合式函数:

```vue
<!-- 指令权限 -->
<el-button v-permission="'system:manager:add'">新增</el-button>

<!-- 函数权限 -->
<script setup lang="ts">
import { usePermission } from '@/composables/usePermission'

const { hasPermission } = usePermission()

const canDelete = hasPermission('system:manager:delete')
</script>
```

---

#### 6.6 表单组件约束

- ✅ **表单验证**：必填、格式、长度等验证
- ✅ **表单类型**：文本框、下拉框、日期选择器、开关等
- ✅ **提交验证**：提交前完整验证
- ✅ **重置功能**：一键重置表单
  
**数字输入框**: 所有数字类字段项一律使用 `el-input` 组件，禁止使用 `el-input-number`

```vue
<!-- ✅ 正确 -->
<el-input v-model="formData.sort_order" placeholder="请输入排序号" />

<!-- ❌ 错误 -->
<el-input-number v-model="formData.sort_order" :min="0" :max="9999" />
```

---

#### 6.7 数据表格列宽约束

##### 6.7.1 数据表格展示

- ✅ **数据展示**：分页表格，支持排序
- ✅ **搜索筛选**：顶部搜索表单，支持多条件筛选
- ✅ **操作列**：编辑、删除等操作按钮（权限控制）
- ✅ **批量操作**：复选框选择，批量删除等
  
---

##### 6.7.1 数据表格列约束

| 列类型 | 尺寸要求 |
|--------|----------|
| id列 | 120px |
| 排序列 | 140px |
| 时间列 | 180px |
| 操作列 | 220px |
| 置顶/推荐 | 100px |
| 状态/类型 | 130px |
| 其他列 | 不设置列宽，自适应 |
| 对齐方式 | 使用组件默认 left，无需额外设置 |

---

#### 6.8 表单弹窗约束

- ✅ **弹窗表单**：新增、编辑使用弹窗表单
- ✅ **确认删除**：删除操作使用确认弹窗

| 属性 | 默认值 |
|------|--------|
| width | 700px |
| label-width | 120px |
  
---

#### 6.9 翻页器约束

- 封装成公共组件，统一调用
  
---


#### 6.10 列表通用参数

| 参数 | 说明 |
|------|------|
| page | 页码 |
| limit | 每页记录数 |
| keyword | 搜索关键词表单名 |
| status | 状态表单名 |

---

#### 6.11 日期选择器封装 (DatePicker)

```
type="daterange"
range-separator="-"
start-placeholder="开始时间"
end-placeholder="结束时间"
```

**约束：**
- 默认值：最近七天
- 传到API格式：`2026-01-01_2026-01-30`
- shortcuts 配置：今天、昨天、最近一周、最近一月、最近三个月
- 选择器使用中文

---

#### 6.12 日期时间选择器封装 (DateTimePicker)

```
type="datetime"
```

**约束：**
- 默认当天日期，时分秒为 00:00:00
- 中文显示
- 选择后格式示例：`2026-01-01 10:00:00`

---

#### 6.13 上传组件封装

**单图上传：**
- 通过传参定义字段名、上传描述
- 成功后返回图片相对路径：`/uploads/20260101/2026010112345.jpg`

**多图上传：**
- 参数限制最大图片数量
- 一次只能上传一张图片，上传成功后在界面预览，同时后面出现新的上传按钮
- 前端返回格式：相对路径用英文逗号分隔

---

#### 6.14 公共 CSS 样式约束

**全局样式文件**: `src/style.css`

所有页面模块共用的 CSS 样式必须定义在全局样式文件中，禁止在各模块中重复定义：

```css
/* ==================== 公共卡片头部样式 ==================== */
.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

/* ==================== 公共搜索表单样式 ==================== */
.search-form {
  margin-bottom: 20px;
}

/* ==================== 公共表格样式 ==================== */
.data-table {
  width: 100%;
  table-layout: auto;
}

.data-table .el-table__header-wrapper,
.data-table .el-table__body-wrapper {
  width: 100% !important;
}

.data-table .el-table__header th,
.data-table .el-table__body td {
  padding: 12px 0;
}

/* ==================== 公共翻页器样式 ==================== */
.el-pagination {
  margin-top: 20px;
  display: flex;
  justify-content: flex-end;
}

/* ==================== 公共文本样式 ==================== */
.text-today {
  color: var(--el-color-danger) !important;
  font-weight: 500;
}

.text-secondary {
  color: var(--el-text-color-secondary);
}

.text-success {
  color: var(--el-color-success);
}

.text-danger {
  color: var(--el-color-danger);
}

.text-warning {
  color: var(--el-color-warning);
}

.text-gray {
  color: #999;
}
```

**模块 CSS 要求**:
- 仅允许定义模块特有的样式
- 禁止重复定义已存在于全局的样式类

---

#### 6.15 公共工具函数约束

**工具函数文件**: `src/utils/commonUtils.ts`

所有页面模块共用的工具函数必须在此文件中定义，禁止重复实现：

| 函数 | 说明 |
|------|------|
| `isToday(timestamp)` | 判断时间戳是否为今天 |
| `formatTimestamp(timestamp)` | 格式化时间戳为 `YYYY-MM-DD HH:mm:ss` |
| `formatDate(timestamp)` | 格式化时间戳为 `YYYY-MM-DD` |
| `formatTime(timestamp)` | 格式化时间戳为 `HH:mm:ss` |
| `getRelativeTime(timestamp)` | 获取相对时间描述 |
| `STATUS_OPTIONS` | 状态选项数组 `[{label: '全部', value: -1}, {label: '启用', value: 1}, {label: '禁用', value: 0}]` |

**模块中使用方式**:
```typescript
import { isToday, formatTimestamp, STATUS_OPTIONS } from '@/utils/commonUtils'

// 时间列使用
<span :class="{ 'text-today': isToday(row.created_at) }">
  {{ formatTimestamp(row.created_at) </span>

// 状态下拉使用
<el-select v-model="searchForm.status">
  <el-option v-for="item in STATUS_OPTIONS" ... />
</el-select>
```

---

#### 6.16 列表页面公共模式

**标准列表页面结构**:

```typescript
// 搜索表单
const searchForm = reactive({
  name: '',
  status: 1 as number, // 默认启用，-1 表示全部
})

// 表格数据
const tableData = ref<Type[]>([])

// 分页
const pagination = reactive({
  page: 1,
  limit: 10,
  total: 0,
})

// 加载状态
const loading = ref(false)

// 弹窗控制
const dialogVisible = ref(false)
const dialogTitle = ref('')
const formRef = ref()

// 表单数据
const formData = reactive({ ... })

// 获取列表
const getList = async () => {
  loading.value = true
  try {
    const res = await getXxxList({ page, limit, ...searchForm })
    tableData.value = res.data?.list || []
    pagination.total = res.data?.total || 0
  } finally {
    loading.value = false
  }
}

// 搜索/重置
const handleSearch = () => { pagination.page = 1; getList() }
const handleReset = () => { /* 重置表单 */; pagination.page = 1; getList() }
```

**公共方法模式**:

| 方法 | 模式 |
|------|------|
| handleAdd | 重置 formData，设置 dialogTitle，打开弹窗 |
| handleEdit | 赋值 formData，设置 dialogTitle，打开弹窗 |
| handleSubmit | 表单验证 → 调用 API → 成功提示 → 关闭弹窗 → 刷新列表 |
| handleDelete | 确认弹窗 → 调用 API → 成功提示 → 刷新列表 |
| handleCloseDialog | formRef.value?.resetFields() |

---

#### 6.17 通用组件约束

| 组件 | 文件位置 | 使用场景 |
|------|----------|----------|
| StatusTag | `src/components/Common/StatusTag.vue` | 状态列显示 |
| CronSelector | `src/components/System/CronSelector.vue` | Cron 表达式选择器 |
| SortableInput | `src/components/Common/SortableInput.vue` | 表格排序列编辑 |

---

#### 6.18 列表排序列约束

**排序列使用 SortableInput 组件**：
- 宽度固定为 100%
- 输入框失去焦点后自动调用 API 保存
- 成功后刷新列表接口（带筛选参数）
- 根据权限控制显示/隐藏输入框

```vue
<el-table-column label="排序" width="100">
  <template #default="{ row }">
    <SortableInput
      v-model="row.sort_order"
      :sort-api="sortXxx"
      :row-data="row"
      id-field="xxx_id"
      permission="system:xxx:sort"
      @success="handleSortSuccess"
    />
  </template>
</el-table-column>
```

**排序权限命名规范**：
- 按钮权限: `{module}:{feature}:resort`
- API 权限: `admin/{controller}/resort`

**排序更新成功回调**：
```typescript
const handleSortSuccess = () => {
  // 刷新列表（带筛选参数）
  getList()
}
```

---

### 七、后端开发规范

#### 7.1 PHP 5.6 兼容性

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

---

#### 7.2 命名空间

```php
// Controller 层
namespace app\admin\controller;

// Service 层
namespace app\admin\service;

// Model 层
namespace app\admin\model;
```

---

#### 7.3 命名规范

- 控制器：`Admin.php`、`Permission.php`（大驼峰）
- 模型：`Admin.php`、`Permission.php`（大驼峰）
- 方法：`getList()`、`getUserPermissions()`（小驼峰）
- 变量：`$admin_id`、`$permission_code`（小写+下划线）

---

#### 7.4 全局函数引用

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

---

#### 7.5 响应格式

统一使用 `apiReturn()` 方法返回：

```php
// 成功响应
$this->apiReturn(200, 'success', $data);

// 失败响应
$this->apiReturn(400, '错误信息');

// 分页列表响应
$this->apiReturn(200, 'success', array(
    'list' => $list,
    'total' => $total
));
```

---

#### 7.6 数据库操作

通过 Model 层操作数据库:

```php
// ✅ 正确 - 通过 Model
$admin = $this->adminModel->findByUsername($username);

// ❌ 错误 - Controller 中直接使用 M()
$admin = M('admins')->where('username', $username)->find();
```

#### 7.7 配置读取

```php
// 从配置文件读取
$jwtConfig = Config::get('jwt');
$key = isset($jwtConfig['secret']) ? $jwtConfig['secret'] : 'default-key';

// ❌ 不要使用 PHP 7+ 语法
$key = Config::get('jwt.secret') ?? 'default-key';
```

---

#### 7.8 公共函数库

项目提供公共函数库 `application/common.php`，所有模块可直接调用：

```php
// 时间范围解析
$timeRange = '2026-01-01_2026-01-31';
$time = parse_time_range($timeRange);
// $time['startTime'] = 起始时间戳, $time['endTime'] = 结束时间戳

// 高精度计算
$sum = number_bcadd('10.00', '20.00', 2);  // 加法
$diff = number_bcsub('20.00', '10.00', 2); // 减法
$result = number_bccomp('10.00', '20.00');  // 比较大小

// 获取客户端IP
$ip = get_ip(1);  // 返回字符串IP
$ip = get_ip(0);  // 返回整型IP

// 手机号验证
$isMobile = is_mobile('13800138000');

// 格式化
$money = format_money(100);     // "100.00"
$num = format_number(123.456, 2); // "123.46"

// 加密解密
$encrypted = think_encrypt('data', 3600); // 加密，1小时过期
$decrypted = think_decrypt($encrypted);   // 解密
```

---

#### 7.9 后端 Controller 层规范

##### 7.9.1 基础规范

| 规范项 | 要求 | 示例 |
|--------|------|------|
| 路由路径 | 使用小写字母，多个单词用下划线分隔 | `/admin/website/get_list` |
| 请求方法 | 列表用 GET，新增/编辑/删除用 POST | GET 列表, POST 增删改 |
| 接口命名 | 与控制器名保持一致 | Website.php → `/admin/website/*` |
| 认证方式 | 无需认证的控制器设置 `$needAuth = false` | Login 控制器 |
| 无需认证的方法 | 设置 `$noAuthMethods = array('方法名')` | `$noAuthMethods = array('index')` |

##### 7.9.2 控制器结构示例

```php
<?php
namespace app\admin\controller;

/**
 * 合作站点控制器
 *
 * 继承 Base 控制器，自动获得 CORS、Token 认证、权限验证
 */
class Website extends Base
{
    /**
     * 不需要认证的方法
     *
     * @var array
     */
    protected $noAuthMethods = array();

    /**
     * 获取列表
     * GET /admin/website/getList
     *
     * @return void
     */
    public function getList()
    {
        // 1. 获取筛选条件
        $where = $this->request->param();

        // 2. 调用 Model 层获取数据
        $websiteModel = model('Website');
        $list = $websiteModel->listWebsite($where);

        // 3. 返回响应
        $this->apiReturn(200, 'success', $list);
    }

    /**
     * 新增
     * POST /admin/website/add
     *
     * @return void
     */
    public function add()
    {
        // 1. 获取请求数据
        $data = $this->request->post();

        // 2. 表单验证
        if (empty($data['website_name'])) {
            $this->apiReturn(400, '站点名称不能为空');
        }

        // 3. 调用 Model 层
        $websiteModel = model('Website');
        $result = $websiteModel->addWebsite($data);

        if ($result) {
            $this->apiReturn(200, '新增成功', $result);
        } else {
            $this->apiReturn(400, '新增失败');
        }
    }

    /**
     * 编辑
     * POST /admin/website/edit
     *
     * @return void
     */
    public function edit()
    {
        $data = $this->request->post();

        if (empty($data['website_id'])) {
            $this->apiReturn(400, '参数错误');
        }

        $websiteModel = model('Website');
        $result = $websiteModel->editWebsite($data);

        if ($result) {
            $this->apiReturn(200, '编辑成功');
        } else {
            $this->apiReturn(400, '编辑失败');
        }
    }

    /**
     * 排序
     * POST /admin/website/resort
     *
     * @return void
     */
    public function resort()
    {
        $websiteId = $this->request->param('website_id', 0);
        $sortOrder = $this->request->param('sort_order', 0);

        // 参数校验
        if ($websiteId <= 0) {
            $this->apiReturn(400, '参数错误');
        }

        // 验证排序值
        if (!is_numeric($sortOrder) || $sortOrder < 0) {
            $this->apiReturn(400, '排序值必须是非负整数');
        }

        $websiteModel = model('Website');
        $result = $websiteModel->editWebsite(array('website_id' => $websiteId, 'sort_order' => $sortOrder));

        if ($result !== false) {
            $this->apiReturn(200, '排序更新成功');
        } else {
            $this->apiReturn(400, '排序更新失败');
        }
    }

    /**
     * 删除
     * POST /admin/website/delete
     *
     * @return void
     */
    public function delete()
    {
        $website_id = $this->request->post('website_id');

        if (empty($website_id)) {
            $this->apiReturn(400, '参数错误');
        }

        $websiteModel = model('Website');
        $result = $websiteModel->deleteWebsite($website_id);

        if ($result) {
            $this->apiReturn(200, '删除成功');
        } else {
            $this->apiReturn(400, '删除失败');
        }
    }
}
```

---

#### 7.10 后端 Model 层规范

##### 7.10.1 模型层方法命名规范

为避免与 ThinkPHP 内置方法冲突（如 `delete()`），Model 层方法命名规则如下：

**格式**: `操作` + `模块名`（首字母大写）

**示例**（website 模块）:

```php
// 列表
public function listWebsite($where) { }

// 添加
public function addWebsite($data) { }

// 编辑
public function editWebsite($gameId, $data) { }

// 删除（软删除）
public function deleteWebsite($gameId) { }
```

---

##### 7.10.2 Model 结构示例
```php
<?php
namespace app\admin\model;

/**
 * 合作站点模型
 */
class Website extends Base
{
    /**
     * 获取列表
     *
     * @param array $where 筛选条件
     * @return array
     */
    public function listWebsite($where = array())
    {
        $map = array();

        // 删除状态筛选
        if (isset($where['is_delete']) && $where['is_delete'] == 1) {
            $map['deleted_at'] = array('>', 0);
        } else {
            $map['deleted_at'] = 0;
        }

        // 状态筛选 (-1 表示全部)
        if (isset($where['status']) && $where['status'] !== '-1') {
            $map['status'] = $where['status'];
        }

        // 名称筛选（模糊匹配）
        if (isset($where['keyword']) && !empty($where['keyword'])) {
            $map['website_name'] = array('like', '%' . $where['keyword'] . '%');
        }

        // 分页参数
        $page = isset($where['page']) ? intval($where['page']) : 1;
        $limit = isset($where['limit']) ? intval($where['limit']) : 15;

        $list = $this->where($map)
            ->order('sort_order desc, website_id desc')
            ->page($page, $limit)
            ->select();

        $total = $this->where($map)->count();

        return array(
            'list' => $list,
            'total' => $total
        );
    }

    /**
     * 新增
     *
     * @param array $data 数据
     * @return mixed
     */
    public function addWebsite($data)
    {
        $data['created_at'] = time();
        $data['updated_at'] = time();

        return $this->save($data);
    }

    /**
     * 编辑
     *
     * @param array $data 数据
     * @return mixed
     */
    public function editWebsite($data)
    {
        $website_id = $data['website_id'];
        unset($data['website_id']);

        $data['updated_at'] = time();

        return $this->save($data, array('website_id' => $website_id));
    }

    /**
     * 删除（软删除）
     *
     * @param int $website_id ID
     * @return mixed
     */
    public function deleteWebsite($website_id)
    {
        $data = array(
            'deleted_at' => time()
        );

        return $this->save($data, array('website_id' => $website_id));
    }
}
```

---

##### 7.10.3 列表筛选查询约定

**Controller 层**: 收集前端筛选条件

```php
$where = array();
$where['is_delete'] = 1;    // 是否包含已删除
$where['status'] = -1;      // -1 表示全部状态
$where['keyword'] = '关键词';   // 模糊搜索
```

**Model 层**: 整合成 SQL 查询条件

```php
$map = array();

// 删除状态筛选
if (isset($where['is_delete']) && $where['is_delete'] == 1) {
    $map['deleted_at'] = array('>', 0);
} else {
    $map['deleted_at'] = 0; // 默认排除已删除
}

// 状态筛选 (-1 表示全部)
if (isset($where['status']) && $where['status'] !== '-1') {
    $map['status'] = $where['status'];
}

// 名称筛选（模糊匹配）
if (isset($where['keyword']) && !empty($where['keyword'])) {
    $map['name'] = array('like', '%' . $where['keyword'] . '%');
}

$list = $this->where($map)->order('sort_order desc')->select();
```

--

#### 7.11 模型层操作规范

```php
// ✅ 正确 - 通过 Model 层操作
$admin = model('Admin')->findByUsername($username);

// ❌ 错误 - Controller 中直接操作数据库
$admin = M('admins')->where('username', $username)->find();
$admin = Db::name('admins')->where('username', $username)->find();
Db::name('role_permissions')->where('permission_id', $permissionId)->delete();
Db::name('admin_roles')->where('role_id', $roleId)->column('admin_id');
$adminModel->where('admin_id', $adminId)->update($data);
```

---

#### 7.12 约束清单

| 约束项 | 说明 |
|--------|------|
| 控制器继承 | 必须继承 `app\admin\controller\Base` |
| 数据库操作 | 严禁在 Controller 中使用 `M()`、`Db::name()`，必须通过 Model 层 |
| PHP 语法 | 严禁使用 PHP 7+ 语法（`??`、`?:`、类型声明等） |
| 软删除 | 删除使用 `deleted_at` 时间戳标记，禁止物理删除 |
| 时间戳字段 | `created_at`、`updated_at` 使用 INT(11) 存储时间戳 |
| 状态字段 | 使用 TINYINT(1)，0=禁用/失败，1=启用/成功 |
| 列表排序 | 默认按 `sort_order desc, id desc` 倒序排列 |
| 模糊搜索 | 使用 `array('like', '%keyword%')` |
| 冗余字段 | 关联表需同步冗余字段（如 `game_name`、`server_name`） |
| API 权限码 | 需在 `bms_permissions` 表配置 type=4 接口权限 |
| 时间范围查询 | 格式：`startDate_endDate`，如 `2026-01-01_2026-01-31` |

---

#### 7.13 开发环境

- **本地开发**：本地 PHP 版本可以较高，但代码必须兼容 PHP 5.6
- **运行环境**：XAMPP 集成环境，PHP 版本为 5.6
- **系统测试**：所有测试必须在 XAMPP 环境下进行

---

### 八、权限命名规范

权限 code 命名必须严格遵循以下格式（以数据库初始化脚本为准）：

#### 8.1 按钮权限 (type=3)

**格式**: `{module}:{feature}:{action}`

**命名规则**:
- 所有权限 code 统一使用**小写字母**

**示例**:

```
system:manager:add              # 新增管理员
system:manager:edit             # 编辑管理员
system:manager:delete           # 删除管理员
system:role:assignpermission  # 分配权限
system:crontab:getlogs        # 获取日志
system:crontab:clearlogs      # 清空日志
```

#### 8.2 API 权限 (type=4)

**格式**: `admin/{controller}/{action}`

**命名规则**:
- 与按钮权限保持一致，使用小写

**示例**:

```
admin/manager/add               # 新增管理员 API
admin/manager/edit              # 编辑管理员 API
admin/manager/delete            # 删除管理员 API
admin/role/assignpermission   # 分配权限 API
admin/crontab/getlogs         # 获取日志 API
```

#### 8.3 前端使用示例

```vue
<!-- ✅ 正确 - 按钮权限 code 使用全小写格式 -->
<el-button v-permission="'system:manager:add'">新增</el-button>
<el-button v-permission="'system:role:assignpermission'">权限</el-button>

<!-- ❌ 错误 - 使用驼峰命名 -->
<el-button v-permission="'system:role:assignPermission'">权限</el-button>
```

---

### 九、最佳实践

#### 9.1 前端最佳实践

1. **Composition API 优先**：使用 `<script setup>` 语法
2. **Composable 复用**：抽取可复用的逻辑到 composables
3. **组件化开发**：合理拆分组件，提高复用性
4. **权限指令优先**：使用 `v-permission` 指令控制权限
5. **状态管理**：使用 Pinia 管理全局状态

#### 9.2 后端最佳实践

1. **分层架构**：Controller → Service → Model
2. **数据库约束**：严禁在 Controller 中直接操作数据库
3. **参数验证**：使用验证器验证请求参数
4. **异常处理**：try-catch 捕获异常，记录日志
5. **统一响应**：使用统一的响应方法（success、error）
   
---

### 十、常见问题

#### Q: 为什么必须使用 PHP 5.6 语法?

A: 生产环境使用 XAMPP (PHP 5.6)，虽然本地开发可以使用更高版本的 PHP，但代码必须在 PHP 5.6 环境下运行。

#### Q: 如何检查 PHP 5.6 兼容性?

A: 使用以下命令检查语法:
```bash
php -l path/to/file.php
```

#### Q: 前端 API 域名配置在哪里?

A: 配置在 `bms-frontend/.env.development`:
```env
VITE_API_BASE_URL=http://api.cms101.co
```

#### Q: CORS 跨域如何配置?

A: 后端配置在 `bms-backend/application/admin/config.php`，修改 `cors.allowed_origins` 数组添加允许的源域名:
```php
'cors' => array(
    'allowed_origins' => array(
        'http://127.0.0.1:5173',
        'http://localhost:5173',
        // 添加其他允许的域名...
    ),
    // ...
),
```

#### Q: Session 和 Cookie 跨域问题?

A: 确保:
1. 后端 `session_set_cookie_params(0, '/', '', false, true)`
2. 前端 axios 配置 `withCredentials: true`
3. 后端 CORS `Access-Control-Allow-Credentials: true`

---

### 十一、已知延期问题 (Deferred Issues)

> **说明**: 以下安全问题已知但暂不处理，待项目有更严谨的安全需求时再优化。后续代码审查时请忽略这些问题。

| # | 问题 | 位置 | 说明 | 潜在风险 |
|---|------|------|------|----------|
| 1 | **JWT 密钥硬编码** | `admin/config.php` | 密钥写在代码中，非环境变量 | 代码泄露后 Token 可被伪造 |
| 2 | **数据库凭证硬编码** | `database.php` | 用户名/密码写在代码中 | 代码泄露后数据库可被访问 |
| 3 | **Token 存 localStorage** | 前端 `store/admin.ts` | Token 存储在浏览器 localStorage | XSS 攻击可窃取 Token |
| 4 | **CORS 应用层处理** | `Base.php` | CORS 在 PHP 层手工处理 | 预检缓存可能导致绕过 |
| 5 | **API 权限缓存 30 天** | `Permission.php` | `getAllApiPermissions()` 缓存 30 天 | 权限变更后最长 30 天生效 |
| 6 | **生产环境 debug 模式** | `database.php` | `app_debug => true` | 暴露 SQL 错误等敏感信息 |

#### 10.1 后续优化方案（供参考）

当项目需要更高安全级别时，可按以下方案优化：

1. **JWT 密钥**: 使用 `.env` 文件或密钥管理服务（如 Vault）
2. **数据库凭证**: 使用环境变量或配置中心
3. **Token 存储**: 改用 HttpOnly Cookie 或内存存储 + Refresh Token
4. **CORS 处理**: 移至 Web 服务器层（Nginx/Apache）处理
5. **API 权限缓存**: 缩短至 1 小时，或权限变更时主动清除缓存
6. **Debug 模式**: 生产环境设置 `app_debug => false`

---

**文档维护**: 本文档应随项目演进持续更新