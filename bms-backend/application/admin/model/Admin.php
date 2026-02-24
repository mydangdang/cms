<?php
namespace app\admin\model;
use think\Model;

/**
 * 管理员模型
 *
 * 功能：管理员数据操作
 */
class Admin extends Model
{
    /**
     * 表名
     *
     * @var string
     */
    protected $name = 'admins';

    /**
     * 主键
     *
     * @var string
     */
    protected $pk = 'admin_id';

    /**
     * 根据用户名查找管理员
     *
     * @param string $username 用户名
     * @return array|null
     */
    public function findByUsername($username)
    {
        return $this->where('username', $username)->find();
    }

    /**
     * 根据ID查找管理员
     *
     * @param int $adminId 管理员ID
     * @return array|null
     */
    public function findById($adminId)
    {
        return $this->where('admin_id', $adminId)->find();
    }

    /**
     * 验证密码
     *
     * @param string $password 明文密码
     * @param string $hash 哈希密码
     * @return bool
     */
    public function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }

    /**
     * 生成密码哈希
     *
     * @param string $password 明文密码
     * @return string
     */
    public function hashPassword($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * 检查管理员状态
     *
     * @param array $admin 管理员数据
     * @return bool
     */
    public function checkStatus($admin)
    {
        if (empty($admin)) {
            return false;
        }

        $status = isset($admin['status']) ? $admin['status'] : 1;
        return $status == 1;
    }

    /**
     * 获取管理员信息（不包含密码）
     *
     * @param int $adminId 管理员ID
     * @return array|null
     */
    public function getAdminInfo($adminId)
    {
        $admin = $this->where('admin_id', $adminId)->find();

        if (empty($admin)) {
            return null;
        }

        // 移除密码字段
        unset($admin['password']);

        return $admin;
    }

    /**
     * 更新最后登录信息
     *
     * @param int $adminId 管理员ID
     * @param string $ip IP地址
     * @return bool
     */
    public function updateLastLogin($adminId, $ip)
    {
        $data = array(
            'last_login_ip' => $ip,
            'last_login_time' => time(),
            'updated_at' => time()
        );

        return $this->where('admin_id', $adminId)->update($data) !== false;
    }

    /**
     * 获取管理员列表
     *
     * @param array $where 查询条件
     * @param int $page 页码
     * @param int $limit 每页数量
     * @return array
     */
    public function getList($where = array(), $page = 1, $limit = 20)
    {
        $map = array();

        if(isset($where['is_delete']) && $where['is_delete'] == 1){
            $map['deleted_at'] = array('>', 0);
        }else{
            $map['deleted_at'] = 0; // 排除已删除的管理员
        }

        // 用户名筛选（精确匹配）
        if (isset($where['username']) && !empty($where['username'])) {
            $map['username'] = array('like', '%' . $where['username'] . '%');
        }

        // 真实姓名筛选（精确匹配）
        if (isset($where['real_name']) && !empty($where['real_name'])) {
            $map['real_name'] = array('like', '%' . $where['real_name'] . '%');
        }

        // 手机号筛选（精确匹配）
        if (isset($where['mobile']) && !empty($where['mobile'])) {
            $map['mobile'] = array('like', '%' . $where['mobile'] . '%');
        }

        // 状态筛选（精确匹配）
        if (isset($where['status']) && $where['status'] !== '') {
            $map['status'] = $where['status'];
        }

        // 获取总数
        $total = $this->where($map)->count();

        // 获取列表
        $list = $this->where($map)->order('admin_id DESC')->page($page, $limit)->select();

        return array(
            'list' => $list,
            'total' => $total
        );
    }

    /**
     * 检查用户名是否存在
     *
     * @param string $username 用户名
     * @param int $excludeId 排除的管理员ID
     * @return bool
     */
    public function usernameExists($username, $excludeId = 0)
    {
        $where = array('username' => $username);
        if ($excludeId > 0) {
            $where['admin_id'] = array('neq', $excludeId);
        }
        $result = $this->where($where)->find();
        return !empty($result);
    }

    /**
     * 检查手机号是否存在
     *
     * @param string $mobile 手机号
     * @param int $excludeId 排除的管理员ID
     * @return bool
     */
    public function mobileExists($mobile, $excludeId = 0)
    {
        $where = array('mobile' => $mobile);
        if ($excludeId > 0) {
            $where['admin_id'] = array('neq', $excludeId);
        }
        $result = $this->where($where)->find();
        return !empty($result);
    }

    /**
     * 批量获取多个管理员的角色信息（避免 N+1 查询）
     *
     * @param array $adminIds 管理员ID数组
     * @return array 以 admin_id 为键，包含 role_ids 和 roles 的关联数组
     */
    public function getRolesForAdmins($adminIds)
    {
        if (empty($adminIds)) {
            return array();
        }

        // 一次查询所有管理员的角色关联
        $adminRoles = \think\Db::name('admin_roles')
            ->where('admin_id', 'in', $adminIds)
            ->field('admin_id, role_id')
            ->select();

        if (empty($adminRoles)) {
            // 没有角色数据时，为每个管理员返回空数组
            $result = array();
            foreach ($adminIds as $adminId) {
                $result[$adminId] = array('role_ids' => array(), 'roles' => array());
            }
            return $result;
        }

        // 建立 admin_id => role_ids 的映射，并收集所有 role_id
        $adminRoleMap = array();
        $allRoleIds   = array();
        foreach ($adminRoles as $ar) {
            $adminRoleMap[$ar['admin_id']][] = $ar['role_id'];
            $allRoleIds[] = $ar['role_id'];
        }
        $allRoleIds = array_values(array_unique($allRoleIds));

        // 一次查询所有涉及的角色详情
        $roles    = model('Role')->where('role_id', 'in', $allRoleIds)->select();
        $roleMap  = array();
        foreach ($roles as $role) {
            $roleMap[$role['role_id']] = $role;
        }

        // 组装每个管理员的角色数据
        $result = array();
        foreach ($adminIds as $adminId) {
            $roleIds     = isset($adminRoleMap[$adminId]) ? $adminRoleMap[$adminId] : array();
            $roleDetails = array();
            foreach ($roleIds as $roleId) {
                if (isset($roleMap[$roleId])) {
                    $roleDetails[] = $roleMap[$roleId];
                }
            }
            $result[$adminId] = array(
                'role_ids' => $roleIds,
                'roles'    => $roleDetails
            );
        }

        return $result;
    }

    /**
     * 获取管理员的角色ID列表
     *
     * @param int $adminId 管理员ID
     * @return array
     */
    public function getRoleIds($adminId)
    {
        $list = \think\Db::name('admin_roles')
            ->where('admin_id', $adminId)
            ->field('role_id')
            ->select();

        $ids = array();
        foreach ($list as $item) {
            $ids[] = $item['role_id'];
        }

        return $ids;
    }

    /**
     * 获取管理员的角色详情列表
     *
     * @param int $adminId 管理员ID
     * @return array
     */
    public function getRoles($adminId)
    {
        $roleIds = $this->getRoleIds($adminId);

        if (empty($roleIds)) {
            return array();
        }

        return model('Role')->where('role_id', 'in', $roleIds)->select();
    }

    /**
     * 分配角色
     *
     * @param int $adminId 管理员ID
     * @param array $roleIds 角色ID数组
     * @return bool
     */
    public function assignRoles($adminId, $roleIds)
    {
        // 使用事务确保数据一致性
        return \think\Db::transaction(function() use ($adminId, $roleIds) {
            // 先删除该管理员的所有角色
            \think\Db::name('admin_roles')->where('admin_id', $adminId)->delete();

            // 如果没有分配角色，直接返回成功
            if (empty($roleIds)) {
                return true;
            }

            // 去重处理
            $roleIds = array_unique($roleIds);
            $roleIds = array_values($roleIds);

            // 验证角色ID是否存在
            $validRoleIds = \think\Db::name('roles')
                ->where('role_id', 'in', $roleIds)
                ->column('role_id');

            // 如果没有有效角色，返回成功
            if (empty($validRoleIds)) {
                return true;
            }

            // 批量插入新角色
            $data = array();
            $now = time();

            foreach ($validRoleIds as $roleId) {
                $data[] = array(
                    'admin_id' => $adminId,
                    'role_id' => $roleId,
                    'created_at' => $now
                );
            }

            return \think\Db::name('admin_roles')->insertAll($data) !== false;
        });
    }

    /**
     * 新增管理员
     *
     * @param array $data 管理员数据
     * @return int|bool 管理员ID
     */
    public function add($data)
    {
        // 密码哈希处理（如果需要）
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        $time = time();
        $data['created_at'] = $time;
        $data['updated_at'] = $time;

        return $this->insertGetId($data);
    }

    /**
     * 编辑管理员
     *
     * @param int $adminId 管理员ID
     * @param array $data 管理员数据
     * @return bool
     */
    public function edit($adminId, $data)
    {
        // 如果提供了新密码，需要哈希处理
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            $data['password_update_time'] = time();
        } else {
            // 不更新密码字段
            unset($data['password']);
        }

        $data['updated_at'] = time();
        return $this->where('admin_id', $adminId)->update($data) !== false;
    }

    /**
     * 修改管理员密码
     *
     * @param int $adminId 管理员ID
     * @param string $password 新密码哈希
     * @param int $passwordUpdateTime 密码修改时间
     * @return bool
     */
    public function changePassword($adminId, $data)
    {
        return $this->where('admin_id', $adminId)->update($data) !== false;
    }

    /**
     * 删除管理员（软删除）
     *
     * @param int $adminId 管理员ID
     * @return bool
     */
    public function remove($adminId)
    {
        // 删除管理员与角色的关联
        \think\Db::name('admin_roles')->where('admin_id', $adminId)->delete();

        // 软删除管理员
        return $this->where('admin_id', $adminId)->update(array(
            'deleted_at' => time(),
            'updated_at' => time()
        )) !== false;
    }
}
