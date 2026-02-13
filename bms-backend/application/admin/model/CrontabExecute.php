<?php
namespace app\admin\model;

use think\Model;

/**
 * 定时任务执行模型
 * 负责执行具体的定时任务方法
 * Story 4.4: 立即执行任务（重构版）
 */
class CrontabExecute extends Model
{
    /**
     * 构造函数 - 初始化日志记录模型
     */
    public function __construct()
    {
        parent::__construct();
    }

    /* 任务方法列表 */

    private function clearConfigCache($crontabId)
    {
        $result = ['state' => 0, 'msg' => '清除缓存失败'];
        // 调用系统配置模型的清除缓存方法
        $res = model('admin/Config')->clearConfigCache();
        if ($res) {
            $result['state'] = 1;
            $result['msg']   = '清除缓存成功';
        }else{
            $result['msg'] = '清除缓存失败';
        }

        return $result;
    }

    /* 任务方法列表 */

    /**
     * 执行任务并记录日志
     * @param int $crontabId 任务ID
     * @param bool $force 是否强制执行（跳过时间检查）true=API强制执行, false=CLI按计划执行
     * @return array 返回格式 {state: 1/0, msg: '...', data: {duration, status, message}}
     */
    public function runTask($crontabId, $force = false)
    {
        // 获取任务信息
        $task = \think\Db::name('crontab')->where('crontab_id', $crontabId)->find();

        if (empty($task)) {
            return array(
                'state' => 0,
                'msg'   => '任务不存在',
                'data'  => array('duration' => 0)
            );
        }

        // 从任务详情中获取任务名称和方法名
        $taskName = isset($task['name']) ? $task['name'] : '';
        $methodName = isset($task['command']) ? $task['command'] : '';

        if (empty($methodName)) {
            return array(
                'state' => 0,
                'msg'   => '执行方法不能为空',
                'data'  => array('duration' => 0)
            );
        }

        // 非强制执行时，检查当前时间是否匹配 Cron 表达式
        if (!$force) {
            $cronExpression = isset($task['cron']) ? $task['cron'] : '';
            if (!empty($cronExpression)) {
                if (!$this->matchCronNow($cronExpression)) {
                    // 当前时间不在执行时间段，跳过执行
                    return array(
                        'state' => 0,
                        'msg'   => '当前不在执行时间段',
                        'data'  => array('duration' => 0)
                    );
                }
            }
        }

        // 开始时间
        $startTime = microtime(true);
        $result = array(
            'state' => 0,
            'msg'   => '任务('.$taskName.')执行失败',
            'data'  => array(
                'duration' => 0
            )
        );

        // 执行类型：1=API强制执行, 2=CLI按计划执行
        $executeType = $force ? 1 : 2;

        try {
            // 检查方法是否存在
            if (!method_exists($this, $methodName)) {
                $result['msg'] = '执行方法不存在: ' . $methodName;

                // 记录执行日志
                $this->logExecution($crontabId, 0, 0, $result['msg'], $executeType);
                return $result;
            }

            // 执行任务方法
            $executeResult = $this->$methodName($crontabId);

            // 处理执行结果
            $duration = round(microtime(true) - $startTime, 2);


            $result['state'] = (isset($executeResult['state']) && $executeResult['state'] == 1) ? 1 : 0;
            $result['msg']   = isset($executeResult['msg']) ? $executeResult['msg'] : '任务已执行完成';
            $result['data']['duration'] = $duration;

            // 记录执行日志
            $this->logExecution($crontabId, $duration, $result['state'], $result['msg'], $executeType);

        } catch (\Exception $e) {
            $result['msg'] = $e->getMessage();

            // 记录执行日志
            $this->logExecution($crontabId, 0, 0, $result['msg'], $executeType);
            return $result;
        }

        return $result;
    }

    /**
     * 检查当前时间是否匹配 Cron 表达式
     * @param string $cronExpression Cron 表达式 (分 时 日 月 周)
     * @return bool
     */
    private function matchCronNow($cronExpression)
    {
        $parts = explode(' ', trim($cronExpression));
        if (count($parts) != 5) {
            return false;
        }

        list($minute, $hour, $day, $month, $weekday) = $parts;
        $now = time();

        return $this->matchCron($now, $minute, $hour, $day, $month, $weekday);
    }

    /**
     * 检查时间是否匹配 Cron 表达式
     * @param int $timestamp 时间戳
     * @param string $minute 分钟
     * @param string $hour 小时
     * @param string $day 日
     * @param string $month 月
     * @param string $weekday 星期
     * @return bool
     */
    private function matchCron($timestamp, $minute, $hour, $day, $month, $weekday)
    {
        $date = getdate($timestamp);

        // 检查分钟
        if (!$this->matchField($date['minutes'], $minute, 0, 59)) {
            return false;
        }

        // 检查小时
        if (!$this->matchField($date['hours'], $hour, 0, 23)) {
            return false;
        }

        // 检查日
        if (!$this->matchField($date['mday'], $day, 1, 31)) {
            return false;
        }

        // 检查月
        if (!$this->matchField($date['mon'], $month, 1, 12)) {
            return false;
        }

        // 检查星期（0=周日, 1=周一, ..., 6=周六）
        // Cron 中 0=周日, 7=也代表周日
        $weekdayNum = $date['wday'];
        if ($weekdayNum == 0) {
            $weekdayNum = 7; // 转换为 Cron 格式（周日=7）
        }
        if (!$this->matchField($weekdayNum, $weekday, 1, 7)) {
            return false;
        }

        return true;
    }

    /**
     * 匹配单个字段
     * @param int $value 实际值
     * @param string $cron Cron 值
     * @param int $min 最小值
     * @param int $max 最大值
     * @return bool
     */
    private function matchField($value, $cron, $min, $max)
    {
        // * 表示任意值
        if ($cron === '*') {
            return true;
        }

        // */n 表示每 n 单位
        if (strpos($cron, '*/') === 0) {
            $step = intval(substr($cron, 2));
            return ($value - $min) % $step === 0;
        }

        // n-m 表示范围
        if (strpos($cron, '-') !== false) {
            $parts = explode('-', $cron);
            $start = intval($parts[0]);
            $end = intval($parts[1]);
            return $value >= $start && $value <= $end;
        }

        // n,m,n 表示多个值
        if (strpos($cron, ',') !== false) {
            $values = explode(',', $cron);
            return in_array($value, $values);
        }

        // 单个值
        return intval($cron) == $value;
    }

    /**
     * 记录执行日志
     * @param int $crontab_id 任务ID
     * @param float $duration 执行耗时
     * @param int $status 执行状态 0=失败 1=成功 2=超时
     * @param string $message 执行消息
     * @param int $execute_type 执行类型 1=API强制执行 2=CLI按计划执行
     * @return bool
     */
    private function logExecution($crontab_id, $duration, $status, $message, $execute_type = 2)
    {
        $logData = [
            'crontab_id'   => $crontab_id,
            'execute_time' => time(),
            'duration'     => $duration,
            'status'       => $status,
            'message'      => $message,
            'execute_type' => $execute_type
        ];
        return \think\Db::name('crontab_logs')->insert($logData);
    }
}
