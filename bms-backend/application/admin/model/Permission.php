<?php
namespace app\admin\model;
use think\Model;
use think\Cache;

/**
 * 权限菜单模型
 *
 * 功能：权限菜单数据操作
 * 统一权限模型：目录(1)、菜单(2)、按钮(3)、接口(4)
 * Story 2.4: 获取用户权限 API（添加 Redis 缓存支持）
 */
class Permission extends Model
{
    /**
     * 表名
     *
     * @var string
     */
    protected $name = 'permissions';

    /**
     * 主键
     *
     * @var string
     */
    protected $pk = 'permission_id';

    /**
     * 缓存过期时间（秒）24小时
     *
     * @var int
     */
    private $cacheExpire = 86400;

    /**
     * 获取权限列表（树形结构）
     *
     * @param array $where 查询条件
     * @return array
     */
    public function getTreeList($where = array())
    {
        $map = array();

        if(isset($where['is_delete']) && $where['is_delete'] == 1){
            $map['deleted_at'] = array('>', 0);
        }else{
            $map['deleted_at'] = 0; // 排除已删除的权限
        }

        // 类型筛选
        if (isset($where['type']) && $where['type'] !== '') {
            $map['type'] = $where['type'];
        }

        // 状态筛选
        if (isset($where['status']) && $where['status'] !== '') {
            $map['status'] = $where['status'];
        }

        // 名称筛选（模糊匹配）
        if (isset($where['name']) && !empty($where['name'])) {
            $map['name'] = array('like', '%' . $where['name'] . '%');
        }

        $list = $this->where($map)->order('type ASC, sort_order ASC, permission_id ASC')
            ->select();

        if (empty($list)) {
            return array();
        }

        return $this->buildTree($list);
    }

    /**
     * 构建树形结构
     *
     * @param array $list 权限列表
     * @param int $parentId 父级ID
     * @return array
     */
    public function buildTree($list, $parentId = 0)
    {
        $tree = array();

        foreach ($list as $item) {
            if ($item['parent_id'] == $parentId) {
                $children = $this->buildTree($list, $item['permission_id']);
                if (!empty($children)) {
                    $item['children'] = $children;
                }
                $tree[] = $item;
            }
        }

        return $tree;
    }

    /**
     * 根据ID查找权限
     *
     * @param int $permissionId 权限ID
     * @return array|null
     */
    public function findById($permissionId)
    {
        return $this->where('permission_id', $permissionId)->where('deleted_at', 0)->find();
    }

    /**
     * 根据权限编码查找
     *
     * @param string $code 权限编码
     * @return array|null
     */
    public function findByCode($code)
    {
        return $this->where('code', $code)->where('deleted_at', 0)->find();
    }

    /**
     * 检查权限编码是否存在
     *
     * @param string $code 权限编码
     * @param int $excludeId 排除的权限ID
     * @return bool
     */
    public function codeExists($code, $excludeId = 0)
    {
        $where = array('code' => $code);
        if ($excludeId > 0) {
            $where['permission_id'] = array('neq', $excludeId);
        }
        $result = $this->where($where)->find();
        return !empty($result);
    }

    /**
     * 检查是否有子权限
     *
     * @param int $permissionId 权限ID
     * @return bool
     */
    public function hasChildren($permissionId)
    {
        $count = $this->where('parent_id', $permissionId)->count();
        return $count > 0;
    }

    /**
     * 获取所有子权限ID
     *
     * @param int $permissionId 权限ID
     * @return array
     */
    public function getChildrenIds($permissionId)
    {
        $children = $this->where('parent_id', $permissionId)->field('permission_id')->select();
        $ids = array();

        foreach ($children as $child) {
            $ids[] = $child['permission_id'];
            // 递归获取子权限的子权限
            $ids = array_merge($ids, $this->getChildrenIds($child['permission_id']));
        }

        return $ids;
    }

    /**
     * 根据类型获取权限列表
     *
     * @param array $types 类型数组
     * @return array
     */
    public function getListByTypes($types)
    {
        return $this->where('type', 'in', $types)
            ->where('status', 1)
            ->where('deleted_at', 0)
            ->order('sort_order ASC, permission_id ASC')
            ->select();
    }

    /**
     * 获取菜单权限（type=1,2）
     *
     * @return array
     */
    public function getMenuList()
    {
        return $this->where('type', 'in', array(1, 2))
            ->where('status', 1)
            ->where('deleted_at', 0)
            ->order('sort_order ASC, permission_id ASC')
            ->select();
    }

    /**
     * 添加权限
     *
     * @param array $data 权限数据
     * @return int|bool 新增的权限ID 或 false
     */
    public function add($data)
    {
        $time = time();
        $data['created_at'] = $time;
        $data['updated_at'] = $time;
        $data['deleted_at'] = 0;

        return $this->insertGetId($data);
    }

    /**
     * 编辑权限
     *
     * @param int $permissionId 权限ID
     * @param array $data 权限数据
     * @return bool 是否成功
     */
    public function edit($permissionId, $data)
    {
        $data['updated_at'] = time();

        return $this->where('permission_id', $permissionId)->update($data);
    }

    /**
     * 删除权限（软删除）
     *
     * @param int $permissionId 权限ID
     * @return bool 是否成功
     */
    public function remove($permissionId)
    {
        return $this->where('permission_id', $permissionId)->update(array(
            'deleted_at' => time(),
            'updated_at' => time()
        )) !== false;
    }

    /**
     * 获取所有启用状态的权限
     * Story 2.4: 获取用户权限 API（超级管理员使用）
     *
     * @return array
     */
    public function getAllPermissions()
    {
        return $this->where('status', 1)
            ->where('is_hidden', 0)
            ->where('deleted_at', 0)
            ->order('sort_order ASC, permission_id ASC')
            ->select();
    }

    /**
     * 根据管理员ID获取权限树（带缓存）
     * Story 2.4: 获取用户权限 API
     *
     * @param int $adminId 管理员ID
     * @param int $isSuper 是否超级管理员
     * @return array
     */
    public function getUserPermissions($adminId, $isSuper = 0)
    {
        // 先尝试从缓存获取
        $cacheKey = 'permission:' . $adminId;

        $cached = Cache::get($cacheKey);
        if ($cached !== false) {
            return $cached;
        }

        // 超级管理员返回所有权限
        if ($isSuper == 1) {
            $permissions = $this->getAllPermissions();
        } else {
            // 普通管理员：通过角色获取权限
            $permissions = $this->getPermissionsByAdminId($adminId);
        }

        // 构建树形结构
        $tree = $this->buildTree($permissions);

        // 缓存权限数据
        Cache::set($cacheKey, $tree, $this->cacheExpire);

        return $tree;
    }

    /**
     * 根据管理员ID获取权限（通过角色）
     * Story 2.4: 获取用户权限 API
     *
     * @param int $adminId 管理员ID
     * @return array
     */
    public function getPermissionsByAdminId($adminId)
    {
        // 查询管理员的角色ID
        $roleIds = \think\Db::name('admin_roles')
            ->where('admin_id', $adminId)
            ->column('role_id');

        if (empty($roleIds)) {
            return array();
        }

        // 查询这些角色的权限ID
        $permissionIds = \think\Db::name('role_permissions')
            ->where('role_id', 'in', $roleIds)
            ->column('permission_id');

        if (empty($permissionIds)) {
            return array();
        }

        // 去重
        $permissionIds = array_unique($permissionIds);
        $permissionIds = array_values($permissionIds);

        // 查询权限详情
        return $this->where('permission_id', 'in', $permissionIds)
            ->where('status', 1)
            ->where('is_hidden', 0)
            ->where('deleted_at', 0)
            ->order('sort_order ASC, permission_id ASC')
            ->select();
    }

    /**
     * 清除管理员权限缓存
     * Story 2.4: 获取用户权限 API
     *
     * @param int $adminId 管理员ID
     * @return bool
     */
    public function clearPermissionCache($adminId)
    {
        // 缓存 key 格式
        $cacheKey = 'permission:' . $adminId;
        $result1 = Cache::rm($cacheKey);
        return $result1;
    }

    /**
     * 所有的API接口权限 (扁平化列表)
     * 缓存 key ALL_API_PERMISSIONS
     * @return array
    */

    public function getAllApiPermissions()
    { 
        $cacheKey    = 'ALL_API_PERMISSIONS';
        $cacheExpire = 30 * 24 * 60 * 60; // 30天

        // 尝试从缓存获取
        $permissions = Cache::get($cacheKey);
        if ($permissions && is_array($permissions) && count($permissions) > 0) {
            return $permissions;
        }

        // 查询所有API接口权限
        $permissionsList = $this->where('type', 4) // 4 表示 API 接口权限
            ->where('status', 1)
            ->where('is_hidden', 0)
            ->where('deleted_at', 0)
            ->field('code')
            ->order('sort_order ASC, permission_id ASC')
            ->select();
        if (!$permissionsList) {
            return array();
        }
        $permissions = array();
        foreach ($permissionsList as $item) {
            if($item['code']){
                $permissions[] = $item['code'];
            }
        }

        // 缓存结果
        Cache::set($cacheKey, $permissions, $cacheExpire);

        return $permissions;
    }

    /**
     * 提取指定用户的权限码(扁平化列表)
     * 注意: 带缓存，支持递归提取所有层级的权限
     * @param int $adminId 管理员ID
     * @param int $isSuper 是否超级管理员
     * @return array
     */
    public function extractAdminPermissionCodes($adminId, $isSuper = 0)
    {
        // 获取用户权限列表
        $permissions = $this->getUserPermissions($adminId, $isSuper);
        if (!$permissions) {
            return array();
        }

        // 递归提取所有权限码
        $codes = array();
        $this->extractCodesRecursive($permissions, $codes);

        return array_values(array_unique($codes));
    }

    /**
     * 递归提取权限树中的所有权限码
     * @param array $permissions 权限树或权限列表
     * @param array &$codes 引用传递，用于收集权限码
     * @return void
     */
    private function extractCodesRecursive($permissions, &$codes)
    {
        foreach ($permissions as $permission) {
            // 添加当前权限的权限码
            if (!empty($permission['code'])) {
                $codes[] = $permission['code'];
            }
            // 递归处理子权限
            if (isset($permission['children']) && is_array($permission['children'])) {
                $this->extractCodesRecursive($permission['children'], $codes);
            }
        }
    }

    /**
     * 清理所有API接口权限缓存
     * 缓存 key ALL_API_PERMISSIONS
     * 编辑/删除按钮权限时自动清除
     * @return bool
    */
    public function clearAllApiPermissionsCache()
    {
        $cacheKey = 'ALL_API_PERMISSIONS';
        return Cache::rm($cacheKey);
    }

}
