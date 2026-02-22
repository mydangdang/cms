<?php
/**
 * 公共基础控制器
 * 所有模块的公共逻辑放在这里
 */

namespace app\common\controller;

use think\Controller;
use think\Cache;

class Base extends Controller
{
    /**
     * 是否需要加载配置缓存
     * 子类可以设置为 false 跳过配置加载
     *
     * @var bool
     */
    protected $needConfigCache = true;

    public function __construct()
    {
        parent::__construct();

        if ($this->needConfigCache) {
            $this->configCache();
        }
    }

    /**
     * 加载系统配置缓存
     * 从缓存中读取配置，如果缓存不存在则从数据库读取并缓存
     * 缓存时间：30天
     *
     * @return void
     */
    protected function configCache()
    {
        $cacheExpire = 30 * 24 * 60 * 60; // 30天

        // 尝试从缓存获取
        $config = Cache::get(\app\admin\model\Config::CACHE_KEY_ALL);

        if ($config !== false) {
            // 使用缓存数据
            \think\Config::set($config);
            return;
        }

        // 缓存不存在，从数据库读取
        $configModel = new \app\admin\model\Config();
        $list = $configModel->where('deleted_at', 0)
            ->order('config_group ASC, sort_order ASC, config_id ASC')
            ->field('config_key, config_type, config_value')
            ->select();

        $config = array();
        if ($list) {
            foreach ($list as $item) {
                if (empty($item['config_key'])) {
                    continue;
                }

                $value = $item['config_value'];

                // 根据类型转换配置值
                switch ($item['config_type']) {
                    case 'number':
                        // 数字类型
                        $value = is_numeric($value) ? floatval($value) : 0;
                        break;

                    case 'boolean':
                        // 布尔类型转换为 0/1
                        $value = in_array($value, array(1, '1', 'true', true), true) ? 1 : 0;
                        break;

                    case 'array':
                        // 数组类型，解析格式：1:A 2:B (按行分割)
                        $array = preg_split('/[\r\n]+/', trim($value));
                        $value = array();

                        foreach ($array as $line) {
                            $line = trim($line);
                            if (empty($line)) {
                                continue;
                            }

                            // 检查是否包含冒号（键值对格式）
                            if (strpos($line, ':') !== false) {
                                list($k, $v) = explode(':', $line, 2);
                                $value[trim($k)] = trim($v);
                            } else {
                                $value[] = $line;
                            }
                        }
                        break;

                    case 'text':
                    case 'textarea':
                    default:
                        // 文本类型保持原样
                        break;
                }

                $config['dbc_' . $item['config_key']] = $value;
            }
        }

        // 写入缓存（30天）
        Cache::set(\app\admin\model\Config::CACHE_KEY_ALL, $config, $cacheExpire);

        // 设置到全局配置
        \think\Config::set($config);
    }
}
