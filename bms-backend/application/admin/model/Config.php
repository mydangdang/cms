<?php
namespace app\admin\model;
use think\Model;
use think\Cache;

/**
 * 配置模型
 * Story 3.1: 系统配置管理（后端 API）
 * 优化：config_group 字段改为 tinyint，伪删除，全局缓存
 */
class Config extends Model
{
    /**
     * 全局配置缓存键
     */
    const CACHE_KEY_ALL = 'SYSTEM_DB_CONFIG';

    /**
     * 分组配置缓存键
     */
    const CACHE_KEY_GROUPED = 'config:grouped';

    /**
     * 表名
     * @var string
     */
    protected $name = 'configs';

    /**
     * 主键
     * @var string
     */
    protected $pk = 'config_id';

    /**
     * 缓存过期时间（秒）24小时
     * @var int
     */
    private $cacheExpire = 86400;

    /**
     * 配置分组类型
     * @var array
     */
    public static $groupTypes = array(
        0 => '未定义',
        1 => '站点设置',
        2 => '运营参数',
        3 => '系统参数'
    );

    /**
     * 配置类型
     * @var array
     */
    public static $configTypes = array(
        'text' => '文本',
        'number' => '数字',
        'boolean' => '布尔',
        'textarea' => '长文本',
        'array' => '数组'
    );

    /**
     * 获取配置分组列表（按分组组织的缓存数据）
     * @param array $where 查询条件
     * @return array 按分组组织的配置
     */
    public function getGroupedList($where = array())
    {
        // 先尝试从缓存获取
        $cached = Cache::get(self::CACHE_KEY_GROUPED);
        if ($cached !== false) {
            return $cached;
        }

        // 从数据库查询（只查询未删除的）
        $list = $this->where('deleted_at', 0)
            ->where($where)
            ->order('config_group ASC, sort_order ASC, config_id ASC')
            ->select();

        // 按分组组织
        $grouped = array();
        foreach ($list as $item) {
            $groupValue = isset($item['config_group']) ? intval($item['config_group']) : 0;
            if (!isset($grouped[$groupValue])) {
                $grouped[$groupValue] = array();
            }
            $grouped[$groupValue][] = $item;
        }

        // 缓存结果
        Cache::set(self::CACHE_KEY_GROUPED, $grouped, $this->cacheExpire);

        return $grouped;
    }

    /**
     * 获取配置列表（用于表格显示）
     * @param array $where 查询条件
     * @param int $page 页码
     * @param int $limit 每页数量
     * @return array
     */
    public function getList($where = array(), $page = 1, $limit = 20)
    {
        $map = array();

        // 固定条件：未删除
        $map['deleted_at'] = 0;

        // 配置分组筛选（精确匹配）
        if (isset($where['config_group']) && $where['config_group'] !== '') {
            $map['config_group'] = $where['config_group'];
        }

        // 配置名称筛选（模糊匹配）
        if (isset($where['config_name']) && !empty($where['config_name'])) {
            $map['config_name'] = array('like', '%' . $where['config_name'] . '%');
        }

        // 配置键筛选（模糊匹配）
        if (isset($where['config_key']) && !empty($where['config_key'])) {
            $map['config_key'] = array('like', '%' . $where['config_key'] . '%');
        }

        // 获取总数
        $total = $this->where($map)->count();

        // 分页查询
        $list = $this->where($map)
            ->order('config_group ASC, sort_order ASC, config_id ASC')
            ->page($page, $limit)
            ->select();

        return array(
            'list' => $list,
            'total' => $total
        );
    }

    /**
     * 获取配置详情
     * @param int $configId 配置ID
     * @return array|null
     */
    public function getDetail($configId)
    {
        return $this->where('config_id', $configId)
            ->where('deleted_at', 0)
            ->find();
    }

    /**
     * 新增配置项
     * @param array $data 配置数据
     * @return int|false 配置ID或false
     */
    public function add($data)
    {
        $data['created_at'] = time();
        $data['updated_at'] = time();
        $data['deleted_at'] = 0;

        $result = $this->insertGetId($data);
        if ($result !== false) {
            $this->clearConfigCache();
        }
        return $result;
    }

    /**
     * 编辑配置项
     * @param int $configId 配置ID
     * @param array $data 配置数据
     * @return bool
     */
    public function edit($configId, $data)
    {
        $data['updated_at'] = time();
        $result = $this->where('config_id', $configId)
            ->where('deleted_at', 0)
            ->update($data);
        if ($result !== false) {
            $this->clearConfigCache();
        }
        return $result;
    }

    /**
     * 删除配置项（伪删除）
     * @param int $configId 配置ID
     * @return bool
     */
    public function remove($configId)
    {
        $result = $this->where('config_id', $configId)
            ->where('deleted_at', 0)
            ->update(array('deleted_at' => time()));
        if ($result !== false) {
            $this->clearConfigCache();
        }
        return $result;
    }

    /**
     * 从缓存获取配置
     * @param string $cacheKey 缓存键
     * @return array|false
     */
    public function getFromCache($cacheKey = 'config:all')
    {
        return Cache::get($cacheKey);
    }

    /**
     * 设置配置到缓存
     * @param string $cacheKey 缓存键
     * @param array $data 配置数据
     * @return bool
     */
    public function setToCache($cacheKey, $data)
    {
        return Cache::set($cacheKey, $data, $this->cacheExpire);
    }

    /**
     * 清除配置缓存
     * 删除系统配置缓存和分组缓存
     * @return bool
     */
    public function clearConfigCache()
    {
        // 清除全局配置缓存
        $result1 = Cache::rm(self::CACHE_KEY_ALL);
        // 清除分组配置缓存
        $result2 = Cache::rm(self::CACHE_KEY_GROUPED);
        return $result1 && $result2;
    }

    /**
     * 获取分组名称
     * @param int $groupValue 分组值
     * @return string
     */
    public static function getGroupName($groupValue)
    {
        return isset(self::$groupTypes[$groupValue]) ? self::$groupTypes[$groupValue] : '未定义';
    }

    /**
     * 获取配置类型名称
     * @param string $configType 配置类型
     * @return string
     */
    public static function getConfigTypeName($configType)
    {
        return isset(self::$configTypes[$configType]) ? self::$configTypes[$configType] : $configType;
    }
}
