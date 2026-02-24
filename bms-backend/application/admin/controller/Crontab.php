<?php
namespace app\admin\controller;

use think\Request;

/**
 * 定时任务管理控制器
 * Story 4.1: 定时任务管理（后端 API）
 */
class Crontab extends Base
{
    /**
     * 获取任务列表
     * GET /admin/crontab/getList
     */
    public function getList(Request $request)
    {
        $page = $request->param('page', 1);
        $limit = $request->param('limit', 10);

        // 分页参数校验，防止传入超大 limit 导致内存溢出
        $page  = max(1, (int)$page);
        $limit = min(200, max(1, (int)$limit));
        $name = $request->param('name', '');
        $status = $request->param('status', -1);

        $where = array();

        // 任务名称模糊查询
        if (!empty($name)) {
            $where['name'] = $name;
        }

        // 状态筛选（-1 表示全部）
        if ($status !== '' && $status != -1) {
            $where['status'] = intval($status);
        }

        $crontabModel = model('Crontab');
        $result = $crontabModel->getList($where, $page, $limit);

        $this->apiReturn(200, '获取成功', $result);
    }

    /**
     * 获取任务详情
     * GET /admin/crontab/getDetail
     */
    public function getDetail(Request $request)
    {
        $crontabId = $request->param('crontab_id', 0);

        if ($crontabId <= 0) {
            $this->apiReturn(400, '参数错误');
        }

        $crontabModel = model('Crontab');
        $crontab = $crontabModel->getDetail($crontabId);

        if (empty($crontab)) {
            $this->apiReturn(400, '任务不存在');
        }

        $this->apiReturn(200, '获取成功', $crontab);
    }

    /**
     * 新增任务
     * POST /admin/crontab/add
     */
    public function add(Request $request)
    {
        $data = array(
            'name' => $request->param('name', ''),
            'cron' => $request->param('cron', ''),
            'command' => $request->param('command', ''),
            'description' => $request->param('description', ''),
            'sort_order' => $request->param('sort_order', 0),
            'status' => $request->param('status', 1)
        );

        // 参数校验
        if (empty($data['name'])) {
            $this->apiReturn(400, '任务名称不能为空');
        }

        if (empty($data['cron'])) {
            $this->apiReturn(400, 'Cron表达式不能为空');
        }

        if (empty($data['command'])) {
            $this->apiReturn(400, '执行方法名不能为空');
        }

        // 验证执行方法名：6-20个字母
        $command = $data['command'];
        if (strlen($command) < 6 || strlen($command) > 20) {
            $this->apiReturn(400, '执行方法名长度必须为6-20个字母');
        }
        if (!preg_match('/^[a-zA-Z]+$/', $command)) {
            $this->apiReturn(400, '执行方法名只能包含大小写字母');
        }

        // 验证 Cron 表达式
        $crontabModel = model('Crontab');
        $validateResult = $crontabModel->validateCron($data['cron']);
        if (!$validateResult['valid']) {
            $this->apiReturn(400, 'Cron表达式格式错误：' . $validateResult['error']);
        }

        $result = $crontabModel->add($data);

        if ($result !== false) {
            $this->apiReturn(200, '新增成功', array('crontab_id' => $result));
        } else {
            $this->apiReturn(400, '新增失败');
        }
    }

    /**
     * 编辑任务
     * POST /admin/crontab/edit
     */
    public function edit(Request $request)
    {
        $crontabId = $request->param('crontab_id', 0);

        if ($crontabId <= 0) {
            $this->apiReturn(400, '参数错误');
        }

        // 检查任务是否存在
        $crontabModel = model('Crontab');
        $crontab = $crontabModel->getDetail($crontabId);

        if (empty($crontab)) {
            $this->apiReturn(400, '任务不存在');
        }

        $data = array();

        // 只接收传过来的字段
        if ($request->has('name')) {
            $data['name'] = $request->param('name');
        }
        if ($request->has('cron')) {
            $data['cron'] = $request->param('cron');
        }
        if ($request->has('command')) {
            $data['command'] = $request->param('command');
        }
        if ($request->has('description')) {
            $data['description'] = $request->param('description');
        }
        if ($request->has('sort_order')) {
            $data['sort_order'] = $request->param('sort_order');
        }
        if ($request->has('status')) {
            $data['status'] = $request->param('status');
        }

        // 参数校验
        if (isset($data['name']) && empty($data['name'])) {
            $this->apiReturn(400, '任务名称不能为空');
        }

        if (isset($data['cron'])) {
            if (empty($data['cron'])) {
                $this->apiReturn(400, 'Cron表达式不能为空');
            }
            // 验证 Cron 表达式
            $validateResult = $crontabModel->validateCron($data['cron']);
            if (!$validateResult['valid']) {
                $this->apiReturn(400, 'Cron表达式格式错误：' . $validateResult['error']);
            }
        }

        if (isset($data['command']) && empty($data['command'])) {
            $this->apiReturn(400, '执行方法名不能为空');
        }

        if (isset($data['command'])) {
            $command = $data['command'];
            if (strlen($command) < 6 || strlen($command) > 20) {
                $this->apiReturn(400, '执行方法名长度必须为6-20个字母');
            }
            if (!preg_match('/^[a-zA-Z]+$/', $command)) {
                $this->apiReturn(400, '执行方法名只能包含大小写字母');
            }
        }

        $result = $crontabModel->edit($crontabId, $data);

        if ($result !== false) {
            $this->apiReturn(200, '编辑成功');
        } else {
            $this->apiReturn(400, '编辑失败');
        }
    }

    /**
     * 删除任务
     * POST /admin/crontab/delete
     */
    public function delete(Request $request)
    {
        $crontabId = $request->param('crontab_id', 0);

        if ($crontabId <= 0) {
            $this->apiReturn(400, '参数错误');
        }

        $crontabModel = model('Crontab');
        $crontab = $crontabModel->getDetail($crontabId);

        if (empty($crontab)) {
            $this->apiReturn(400, '任务不存在');
        }

        $result = $crontabModel->remove($crontabId);

        if ($result !== false) {
            $this->apiReturn(200, '删除成功');
        } else {
            $this->apiReturn(400, '删除失败');
        }
    }

    /**
     * 验证 Cron 表达式
     * GET /admin/crontab/validateCron
     */
    public function validateCron(Request $request)
    {
        $cron = $request->param('cron', '');

        if (empty($cron)) {
            $this->apiReturn(400, 'Cron表达式不能为空');
        }

        $crontabModel = model('Crontab');
        $result = $crontabModel->validateCron($cron);

        if ($result['valid']) {
            // 计算下次执行时间
            $nextTime = $crontabModel->calculateNextExecuteTime($cron);
            $data = array(
                'valid' => true,
                'next_execute_time' => $nextTime,
                'next_execute_time_text' => $nextTime > 0 ? date('Y-m-d H:i:s', $nextTime) : '无法计算'
            );
            $this->apiReturn(200, 'Cron表达式有效', $data);
        } else {
            $this->apiReturn(400, $result['error']);
        }
    }

    /**
     * 立即执行任务
     * POST /admin/crontab/execute
     * Story 4.4: 立即执行任务（重构版 - 使用 CrontabExecute 模型）
     */
    public function execute(Request $request)
    {
        $crontabId = $request->param('crontab_id', 0);

        if ($crontabId <= 0) {
            $this->apiReturn(400, '参数错误');
        }

        $crontabModel = model('Crontab');
        $crontab = $crontabModel->getDetail($crontabId);

        if (empty($crontab)) {
            $this->apiReturn(400, '任务不存在');
        }

        if ($crontab['status'] != 1) {
            $this->apiReturn(400, '任务未启用');
        }

        // 通过 CrontabExecute 模型执行任务（API 强制执行）
        $executeModel = model('CrontabExecute');
        $result = $executeModel->runTask($crontabId, true);

        // 返回执行结果
        if ($result['state'] === 1) {
            $this->apiReturn(200, $result['msg'], $result['data']);
        } else {
            $this->apiReturn(400, $result['msg'], $result['data']);
        }
    }

    /**
     * 获取执行记录
     * GET /admin/crontab/getLogs
     * Story 4.6: 执行记录追踪
     */
    public function getLogs(Request $request)
    {
        $crontabId = $request->param('crontab_id', 0);
        $page = $request->param('page', 1);
        $limit = $request->param('limit', 20);

        // 分页参数校验
        $page  = max(1, (int)$page);
        $limit = min(200, max(1, (int)$limit));

        if ($crontabId <= 0) {
            $this->apiReturn(400, '参数错误');
        }

        $crontabModel = model('Crontab');
        $result = $crontabModel->getLogs($crontabId, $page, $limit);

        $this->apiReturn(200, '获取成功', $result);
    }

    /**
     * 清空执行记录
     * POST /admin/crontab/clearLogs
     */
    public function clearLogs(Request $request)
    {
        $crontabId = $request->param('crontab_id', 0);

        if ($crontabId <= 0) {
            $this->apiReturn(400, '参数错误');
        }

        // 检查任务是否存在
        $crontabModel = model('Crontab');
        $crontab = $crontabModel->getDetail($crontabId);

        if (empty($crontab)) {
            $this->apiReturn(400, '任务不存在');
        }

        // 删除该任务的所有执行记录
        $result = $crontabModel->clearLogs($crontabId);

        if ($result !== false) {
            $this->apiReturn(200, '清空成功');
        } else {
            $this->apiReturn(400, '清空失败');
        }
    }
}
