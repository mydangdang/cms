<?php
namespace app\admin\controller;

use think\Request;

/**
 * 配置管理控制器
 * Story 3.1: 系统配置管理（后端 API）
 * 优化：新增、删除、列表展示
 */
class Config extends Base
{
    /**
     * 获取配置分组列表（缓存数据，用于配置编辑页面）
     * GET /admin/config/getGroupedList
     */
    public function getGroupedList(Request $request)
    {
        $configModel = model('Config');
        $list = $configModel->getGroupedList();

        $this->apiReturn(200, '获取成功', $list);
    }

    /**
     * 获取配置列表（用于表格显示）
     * GET /admin/config/getList
     */
    public function getList(Request $request)
    {
        $page = $request->param('page', 1);
        $limit = $request->param('limit', 20);
        $configGroup = $request->param('config_group', '');
        $configName = $request->param('config_name', '');
        $configKey = $request->param('config_key', '');

        $where = array();

        // 只有当 config_group 不为空且不为 -1（全部）时才添加查询条件
        if ($configGroup !== '' && $configGroup != -1) {
            $where['config_group'] = intval($configGroup);
        }

        // 配置名称模糊查询（传递原始字符串，由 Model 构建条件）
        if (!empty($configName)) {
            $where['config_name'] = $configName;
        }

        // 配置键模糊查询（传递原始字符串，由 Model 构建条件）
        if (!empty($configKey)) {
            $where['config_key'] = $configKey;
        }

        $configModel = model('Config');
        $result = $configModel->getList($where, $page, $limit);

        // 添加分组名称和类型名称
        foreach ($result['list'] as &$item) {
            $item['group_name'] = \app\admin\model\Config::getGroupName($item['config_group']);
            $item['type_name'] = \app\admin\model\Config::getConfigTypeName($item['config_type']);
        }

        $this->apiReturn(200, '获取成功', $result);
    }

    /**
     * 获取配置详情
     * GET /admin/config/getDetail
     */
    public function getDetail(Request $request)
    {
        $configId = $request->param('config_id', 0);

        if ($configId <= 0) {
            $this->apiReturn(400, '参数错误');
        }

        $configModel = model('Config');
        $config = $configModel->getDetail($configId);

        if (empty($config)) {
            $this->apiReturn(400, '配置项不存在');
        }

        $this->apiReturn(200, '获取成功', $config);
    }

    /**
     * 新增配置项
     * POST /admin/config/add
     */
    public function add(Request $request)
    {
        $data = array(
            'config_group' => $request->param('config_group', 0),
            'config_name' => $request->param('config_name', ''),
            'config_key' => $request->param('config_key', ''),
            'config_value' => $request->param('config_value', ''),
            'config_type' => $request->param('config_type', 'text'),
            'description' => $request->param('description', ''),
            'sort_order' => $request->param('sort_order', 0)
        );

        // 参数校验
        if (empty($data['config_name'])) {
            $this->apiReturn(400, '配置名称不能为空');
        }
        if (empty($data['config_key'])) {
            $this->apiReturn(400, '配置键不能为空');
        }

        // 验证配置键格式（只允许字母、数字、下划线）
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $data['config_key'])) {
            $this->apiReturn(400, '配置键只能包含字母、数字和下划线');
        }

        // 验证配置类型
        if (!in_array($data['config_type'], array('text', 'number', 'boolean', 'textarea', 'array'))) {
            $this->apiReturn(400, '配置类型无效');
        }

        // 验证配置值
        if (!$this->validateConfigValue($data['config_value'], $data['config_type'])) {
            $this->apiReturn(400, '配置值格式不正确');
        }

        $configModel = model('Config');
        $configId = $configModel->add($data);

        if ($configId !== false) {
            $this->apiReturn(200, '添加成功', array('config_id' => $configId));
        } else {
            $this->apiReturn(400, '添加失败');
        }
    }

    /**
     * 编辑配置项
     * POST /admin/config/edit
     */
    public function edit(Request $request)
    {
        $configId = $request->param('config_id', 0);

        if ($configId <= 0) {
            $this->apiReturn(400, '参数错误');
        }

        $configModel = model('Config');
        $config = $configModel->getDetail($configId);

        if (empty($config)) {
            $this->apiReturn(400, '配置项不存在');
        }

        $data = array(
            'config_group' => $request->param('config_group', $config['config_group']),
            'config_name' => $request->param('config_name', $config['config_name']),
            'config_key' => $request->param('config_key', $config['config_key']),
            'config_value' => $request->param('config_value', $config['config_value']),
            'config_type' => $request->param('config_type', $config['config_type']),
            'description' => $request->param('description', $config['description']),
            'sort_order' => $request->param('sort_order', $config['sort_order'])
        );

        // 参数校验
        if (empty($data['config_name'])) {
            $this->apiReturn(400, '配置名称不能为空');
        }
        if (empty($data['config_key'])) {
            $this->apiReturn(400, '配置键不能为空');
        }

        // 验证配置键格式
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $data['config_key'])) {
            $this->apiReturn(400, '配置键只能包含字母、数字和下划线');
        }

        // 验证配置类型
        if (!in_array($data['config_type'], array('text', 'number', 'boolean', 'textarea', 'array'))) {
            $this->apiReturn(400, '配置类型无效');
        }

        // 验证配置值
        if (!$this->validateConfigValue($data['config_value'], $data['config_type'])) {
            $this->apiReturn(400, '配置值格式不正确');
        }

        $result = $configModel->edit($configId, $data);

        if ($result !== false) {
            $this->apiReturn(200, '更新成功');
        } else {
            $this->apiReturn(400, '更新失败');
        }
    }

    /**
     * 删除配置项
     * POST /admin/config/delete
     */
    public function delete(Request $request)
    {
        $configId = $request->param('config_id', 0);

        if ($configId <= 0) {
            $this->apiReturn(400, '参数错误');
        }

        $configModel = model('Config');
        $result = $configModel->remove($configId);

        if ($result !== false) {
            $this->apiReturn(200, '删除成功');
        } else {
            $this->apiReturn(400, '删除失败');
        }
    }

    /**
     * 清除配置缓存
     * POST /admin/config/clearCache
     */
    public function clearCache(Request $request)
    {
        $configModel = model('Config');
        $result = $configModel->clearConfigCache();

        if ($result) {
            $this->apiReturn(200, '缓存清除成功');
        } else {
            $this->apiReturn(400, '缓存清除失败');
        }
    }

    /**
     * 验证配置值是否符合类型
     * @param string $value 配置值
     * @param string $type 配置类型
     * @return bool
     */
    private function validateConfigValue($value, $type)
    {
        switch ($type) {
            case 'number':
                // 验证是否为数字
                return is_numeric($value);
            case 'boolean':
                // 验证是否为布尔值（0, 1, true, false）
                return in_array($value, array(0, 1, '0', '1', true, false, 'true', 'false'), true);
            case 'text':
            case 'textarea':
            case 'array':
            default:
                // 文本和数组类型不做特殊验证
                return true;
        }
    }
}
