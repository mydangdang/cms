<?php
namespace app\admin\model;
use think\Model;

/**
 * 角色模型
 *
 * Story 2.2: 角色管理（后端 API）
 */
class Role extends Model
{
    /**
     * 表名
     *
     * @var string
     */
    protected $name = 'roles';

    /**
     * 主键
     *
     * @var string
     */
    protected $pk = 'role_id';

    /**
     * 获取角色列表
     *
     * @param array $where 查询条件
     * @param int $page 页码
     * @param int $limit 每页数量
     * @return array
     */
    public function getList($where = array(), $page = 1, $limit = 0)
    {
        $map = array();

        if(isset($where['is_delete']) && $where['is_delete'] == 1){
            $map['deleted_at'] = array('>', 0);
        }else{
            $map['deleted_at'] = 0; // 排除已删除的角色
        }

        // 角色名称筛选（模糊匹配）
        if (isset($where['name']) && !empty($where['name'])) {
            $map['name'] = array('like', '%' . $where['name'] . '%');
        }

        // 状态筛选（精确匹配）
        if (isset($where['status']) && $where['status'] !== '') {
            $map['status'] = $where['status'];
        }

        // 获取总数
        $total = $this->where($map)->count();

        // 如果不分页，返回全部
        if ($limit <= 0) {
            $list = $this->where($map)->order('sort_order ASC, role_id ASC')->select();
            return array('list' => $list, 'total' => $total);
        }

        // 分页查询
        $list = $this->where($map)->order('sort_order ASC, role_id ASC')
            ->page($page, $limit)
            ->select();

        return array('list' => $list, 'total' => $total);
    }

    /**
     * 根据ID查找角色
     *
     * @param int $roleId 角色ID
     * @return array|null
     */
    public function findById($roleId)
    {
        return $this->where('role_id', $roleId)->where('deleted_at', 0)->find();
    }

    /**
     * 检查角色名称是否存在
     *
     * @param string $name 角色名称
     * @param int $excludeId 排除的ID
     * @return bool
     */
    public function nameExists($name, $excludeId = 0)
    {
        $where = array(
            'name' => $name,
            'deleted_at' => 0
        );
        if ($excludeId > 0) {
            $where['role_id'] = array('neq', $excludeId);
        }
        $result = $this->where($where)->find();
        return !empty($result);
    }

    /**
     * 检查是否有管理员使用该角色
     *
     * @param int $roleId 角色ID
     * @return bool
     */
    public function hasAdmins($roleId)
    {
        $count = \think\Db::name('admin_roles')
            ->where('role_id', $roleId)
            ->count();
        return $count > 0;
    }

    /**
     * 新增角色
     *
     * @param array $data 角色数据
     * @return int|bool
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
     * 编辑角色
     *
     * @param int $roleId 角色ID
     * @param array $data 角色数据
     * @return bool
     */
    public function edit($roleId, $data)
    {
        $data['updated_at'] = time();
        return $this->where('role_id', $roleId)->update($data);
    }

    /**
     * 删除角色（软删除）
     *
     * @param int $roleId 角色ID
     * @return bool
     */
    public function remove($roleId)
    {
        // 获取角色信息
        $role = $this->where('role_id', $roleId)->find();
        if (empty($role)) {
            return false;
        }

        // 删除角色与权限的关联
        \think\Db::name('role_permissions')->where('role_id', $roleId)->delete();

        // 软删除角色，name 后面加上 _已删除
        $newName = isset($role['name']) ? $role['name'] . '_已删除' : '';
        return $this->where('role_id', $roleId)->update(array(
            'name' => $newName,
            'deleted_at' => time(),
            'updated_at' => time()
        )) !== false;
    }

    /**
     * 为角色分配权限
     *
     * @param int $roleId 角色ID
     * @param array $permissionIds 权限ID数组
     * @return bool
     */
    public function assignPermissions($roleId, $permissionIds)
    {
        // 使用事务确保数据一致性
        return \think\Db::transaction(function() use ($roleId, $permissionIds) {
            // 先删除该角色的所有权限
            \think\Db::name('role_permissions')->where('role_id', $roleId)->delete();

            // 批量插入新权限
            if (empty($permissionIds)) {
                return true;
            }

            // 去重并重新索引
            $permissionIds = array_unique($permissionIds);
            $permissionIds = array_values($permissionIds);

            // 过滤非整数值
            $permissionIds = array_filter($permissionIds, function($val) {
                return is_numeric($val) && $val > 0;
            });
            $permissionIds = array_map('intval', $permissionIds);

            // 如果过滤后为空，直接返回
            if (empty($permissionIds)) {
                return true;
            }

            // 验证所有权限ID是否存在
            $validPermissionIds = \think\Db::name('permissions')
                ->where('permission_id', 'in', $permissionIds)
                ->column('permission_id');

            // 只插入有效的权限ID
            $time = time();
            $data = array();
            foreach ($validPermissionIds as $permissionId) {
                $data[] = array(
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                    'created_at' => $time
                );
            }

            if (empty($data)) {
                return true;
            }

            return \think\Db::name('role_permissions')->insertAll($data);
        });
    }

    /**
     * 获取角色的权限ID列表
     *
     * @param int $roleId 角色ID
     * @return array
     */
    public function getPermissionIds($roleId)
    {
        return \think\Db::name('role_permissions')
            ->where('role_id', $roleId)
            ->column('permission_id');
    }
}
