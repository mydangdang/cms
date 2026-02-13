# BMS Admin 模块开发规范

> **版本**: v1.0
> **更新日期**: 2026-02-05
> **模块**: BMS 后台管理 - Admin 模块

本文档定义 Admin 模块的 MVC 开发规范，所有后续开发需遵守。

---

## 目录

1. [MVC 架构](#mvc-架构)
2. [Controller 开发规范](#controller-开发规范)
3. [Model 开发规范](#model-开发规范)
4. [API 响应规范](#api-响应规范)
5. [常见模式](#常见模式)

---

## MVC 架构

```
Controller (控制器层)
    ↓ 接收请求、调用服务、返回响应
Service (服务层) - 可选
    ↓ 业务逻辑处理
Model (模型层)
    ↓ 数据访问、缓存操作
Cache/Database (存储层)
```

**核心原则**:
- **严禁在 Controller 中直接操作数据库或 Cache**
- **业务逻辑优先放在 Model 层**（可后续扩展 Service 层）
- **Model 层负责数据访问和缓存操作**

---

## Controller 开发规范

### 基础结构

```php
<?php
namespace app\admin\controller;

/**
 * 控制器描述
 *
 * 功能说明
 */
class Example extends Base
{
    /**
     * 模型实例
     *
     * @var \app\admin\model\Example
     */
    protected $model;

    /**
     * 初始化
     *
     * @return void
     */
    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Example');
    }

    /**
     * 方法说明
     * GET /admin/example/index
     *
     * @return void
     */
    public function index()
    {
        // 调用 Model 层方法
        $data = $this->model->getData();

        // 统一返回格式
        $this->apiReturn(200, '获取成功', $data);
    }
}
```

### 命名规范

| 类型 | 命名规则 | 示例 |
|------|----------|------|
| 类名 | 大驼峰 | `Login`, `AdminUser` |
| 方法名 | 小驼峰 | `index()`, `getAdminInfo()` |
| 属性名 | 小驼峰 | `$model`, `$needAuth` |

### 必须继承 Base

所有控制器必须继承 `app\admin\controller\Base`，以获得：
- CORS 跨域处理
- 统一 API 返回格式 `apiReturn()`
- 认证检查机制

### 跳过认证

对于不需要认证的接口（如登录、验证码）：

```php
/**
 * 是否需要登录认证
 *
 * @var bool
 */
protected $needAuth = false;
```

### 方法注释格式

```php
/**
 * 方法说明
 * HTTP_METHOD /admin/controller/action
 *
 * @param \think\Request $request 请求对象（如果需要）
 * @return void
 */
```

---

## Model 开发规范

### 基础结构

```php
<?php
namespace app\admin\model;
use think\Model;
use think\Cache;

/**
 * 模型描述
 *
 * 功能说明
 */
class Example extends Model
{
    /**
     * 属性说明
     *
     * @var string
     */
    protected $propertyName = 'value';

    /**
     * 方法说明
     *
     * @param string $param 参数说明
     * @return array 返回值说明
     */
    public function methodName($param = 'default')
    {
        // 方法实现
    }
}
```

### 属性定义规范

- 必须添加 PHPDoc 注释
- 使用 `@var` 标注类型
- 属性名使用小驼峰

```php
/**
 * 缓存键前缀
 *
 * @var string
 */
protected $cacheKey = 'BmsExampleKey';

/**
 * 过期时间（秒）
 *
 * @var int
 */
protected $expireTime = 600;
```

### 方法命名规范

| 功能类型 | 命名模式 | 示例 |
|----------|----------|------|
| 获取数据 | `get{名词}` | `getData()`, `getCaptcha()` |
| 设置数据 | `set{名词}` | `setData()`, `setCaptcha()` |
| 验证数据 | `validate{名词}` | `validateCaptcha()` |
| 生成数据 | `generate{名词}` | `generateCode()`, `generateToken()` |
| 删除数据 | `delete{名词}`, `clear{名词}` | `deleteCache()`, `clearCaptcha()` |
| 检查状态 | `is{状态}`, `has{名词}` | `isActive()`, `hasPermission()` |

### 返回值规范

**成功返回**:
```php
// 返回单一值
return $value;

// 返回数组
return array('key' => 'value');

// 返回结果数组（带状态）
return array(
    'valid' => true,
    'message' => '操作成功'
);
```

**错误返回**（在验证类方法中）:
```php
return array(
    'valid' => false,
    'message' => '错误信息'
);
```

### Cache 操作规范

```php
/**
 * 获取缓存键
 *
 * @param string $type 类型
 * @return string
 */
public function getCacheKey($type = 'default')
{
    // 基于会话ID和类型生成唯一键
    $sessionId = session_id();
    $key = substr(md5($this->cacheKey . $sessionId), 0, 8);
    $str = substr(md5($type), 0, 8);
    return md5($key . $str);
}

/**
 * 设置缓存
 *
 * @param string $key 缓存键
 * @param mixed $value 缓存值
 * @return bool
 */
public function setCache($key, $value)
{
    return Cache::set($key, $value, $this->expireTime);
}

/**
 * 获取缓存
 *
 * @param string $key 缓存键
 * @return mixed
 */
public function getCache($key)
{
    return Cache::get($key);
}

/**
 * 删除缓存
 *
 * @param string $key 缓存键
 * @return bool
 */
public function deleteCache($key)
{
    return Cache::rm($key);
}
```

---

## API 响应规范

### 统一使用 apiReturn()

所有接口必须使用 `Base::apiReturn()` 返回：

```php
$this->apiReturn($code, $msg, $data);
```

### 参数说明

| 参数 | 类型 | 必填 | 说明 |
|------|------|------|------|
| $code | int | 是 | 状态码：200成功，400/500失败 |
| $msg | string | 是 | 消息描述 |
| $data | mixed | 否 | 返回数据，默认空数组 |

### 成功响应示例

```php
// 获取数据成功
$this->apiReturn(200, '获取成功', $data);

// 操作成功
$this->apiReturn(200, '保存成功');
```

### 失败响应示例

```php
// 参数错误
$this->apiReturn(400, '参数错误');

// 验证失败
$this->apiReturn(400, '验证码错误');

// 服务器错误
$this->apiReturn(500, '系统错误');
```

### 返回格式

```json
{
    "code": 200,
    "msg": "获取成功",
    "data": {
        "key": "value"
    }
}
```

---

## 常见模式

### 1. 列表查询

```php
// Controller
public function index()
{
    $page = input('page', 1);
    $pageSize = input('page_size', 10);

    $result = $this->model->getList($page, $pageSize);
    $this->apiReturn(200, '获取成功', $result);
}

// Model
public function getList($page = 1, $pageSize = 10)
{
    $offset = ($page - 1) * $pageSize;

    return $this
        ->order('id desc')
        ->limit($offset, $pageSize)
        ->select();
}
```

### 2. 详情查询

```php
// Controller
public function read()
{
    $id = input('id');
    $data = $this->model->getById($id);

    if (!$data) {
        $this->apiReturn(404, '数据不存在');
    }

    $this->apiReturn(200, '获取成功', $data);
}

// Model
public function getById($id)
{
    return $this->find($id);
}
```

### 3. 验证码模式

```php
// Controller - 生成验证码
public function index()
{
    $code = $this->model->generateCode(4);
    $cacheKey = $this->model->getCacheKey('login');
    $this->model->setCache($cacheKey, $code);
    $image = $this->model->generateImage($code);

    $this->apiReturn(200, '获取成功', array('image' => $image));
}

// Controller - 验证验证码
public function verify(Request $request)
{
    $code = $request->param('code', '');
    $result = $this->model->validateCode($code, 'login');

    if (!$result['valid']) {
        $this->apiReturn(400, $result['message']);
    }

    // 验证成功后清除
    $this->model->clearCode('login');
    $this->apiReturn(200, '验证成功');
}
```

### 4. 无需认证的控制器

```php
/**
 * 登录控制器不需要认证
 *
 * @var bool
 */
protected $needAuth = false;
```

---

## PHP 5.6 兼容性注意

| PHP 7+ 语法 | PHP 5.6 替代写法 |
|-------------|------------------|
| `$var ?? $default` | `isset($var) ? $var : $default` |
| `$a <=> $b` | 使用三元表达式 |
| `function foo(int $a): int` | 移除类型声明 |
| `function(): void` | 移除返回类型 |

---

**文档维护**: 随项目演进持续更新
