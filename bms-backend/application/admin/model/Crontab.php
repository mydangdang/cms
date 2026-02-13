<?php
namespace app\admin\model;

use think\Model;
use think\Cache;

/**
 * 定时任务模型
 * Story 4.1: 定时任务管理（后端 API）
 */
class Crontab extends Model
{
    /**
     * 表名
     * @var string
     */
    protected $name = 'crontab';

    /**
     * 主键
     * @var string
     */
    protected $pk = 'crontab_id';

    /**
     * 任务状态
     * @var array
     */
    public static $statusTypes = array(
        0 => '禁用',
        1 => '启用'
    );

    /**
     * 执行状态
     * @var array
     */
    public static $executeStatus = array(
        0 => '失败',
        1 => '成功',
        2 => '超时'
    );

    /**
     * 获取任务列表（分页）
     * @param array $where 查询条件
     * @param int $page 页码
     * @param int $limit 每页数量
     * @return array
     */
    public function getList($where = array(), $page = 1, $limit = 10)
    {
        $map = array();
        $map['deleted_at'] = 0;

        // 任务名称筛选
        if (isset($where['name']) && !empty($where['name'])) {
            $map['name'] = array('like', '%' . $where['name'] . '%');
        }

        // 状态筛选
        if (isset($where['status']) && $where['status'] !== -1 && $where['status'] !== '') {
            $map['status'] = $where['status'];
        }

        // 获取总数
        $total = $this->where($map)->count();

        // 获取列表
        $list = $this->where($map)->order('sort_order ASC, crontab_id DESC')->page($page, $limit)->select();

        // 获取所有任务的最后执行时间
        $crontabIds = array();
        foreach ($list as $item) {
            $crontabIds[] = $item['crontab_id'];
        }

        if (!empty($crontabIds)) {
            $lastExecuteTimes = \think\Db::name('crontab_logs')
                ->field('crontab_id, MAX(execute_time) as last_execute_time')
                ->whereIn('crontab_id', $crontabIds)
                ->group('crontab_id')
                ->select();

            $timeMap = array();
            foreach ($lastExecuteTimes as $row) {
                $timeMap[$row['crontab_id']] = $row['last_execute_time'];
            }

            // 添加状态名称和扩展字段
            foreach ($list as &$item) {
                $item['status_name'] = isset(self::$statusTypes[$item['status']])
                    ? self::$statusTypes[$item['status']]
                    : '未知';
                $item['last_execute_time'] = isset($timeMap[$item['crontab_id']])
                    ? $timeMap[$item['crontab_id']]
                    : 0;
                // 计算下次执行时间
                $item['next_execute_time'] = $this->calculateNextExecuteTime($item['cron']);
            }
        }

        return array(
            'list' => $list,
            'total' => $total
        );
    }

    /**
     * 获取任务详情
     * @param int $crontabId 任务ID
     * @return array|null
     */
    public function getDetail($crontabId)
    {
        $crontab = $this->where(array(
            'crontab_id' => $crontabId,
            'deleted_at' => 0
        ))->find();

        if ($crontab) {
            // 从日志表获取最后执行时间
            $lastLog = \think\Db::name('crontab_logs')
                ->where('crontab_id', $crontabId)
                ->order('execute_time DESC')
                ->find();
            $crontab['last_execute_time'] = $lastLog ? $lastLog['execute_time'] : 0;

            $crontab['status_name'] = isset(self::$statusTypes[$crontab['status']])
                ? self::$statusTypes[$crontab['status']]
                : '未知';
        }

        return $crontab;
    }

    /**
     * 新增任务
     * @param array $data 任务数据
     * @return int|false 任务ID或false
     */
    public function add($data)
    {
        $data['created_at'] = time();
        $data['updated_at'] = time();
        $data['deleted_at'] = 0;

        return $this->insertGetId($data);
    }

    /**
     * 编辑任务
     * @param int $crontabId 任务ID
     * @param array $data 任务数据
     * @return bool
     */
    public function edit($crontabId, $data)
    {
        $data['updated_at'] = time();

        $result = $this->where(array(
            'crontab_id' => $crontabId,
            'deleted_at' => 0
        ))->update($data);

        return $result !== false;
    }

    /**
     * 删除任务（软删除）
     * @param int $crontabId 任务ID
     * @return bool
     */
    public function remove($crontabId)
    {
        $data = array(
            'deleted_at' => time(),
            'updated_at' => time()
        );

        $result = $this->where('crontab_id', $crontabId)->update($data);

        return $result !== false;
    }

    /**
     * 计算 Cron 表达式的下次执行时间
     * @param string $cron Cron 表达式
     * @return int 时间戳
     */
    public function calculateNextExecuteTime($cron)
    {
        $parts = explode(' ', trim($cron));
        if (count($parts) != 5) {
            return 0;
        }

        list($minute, $hour, $day, $month, $weekday) = $parts;

        $now = time();

        // 尝试未来30天内找到匹配的时间
        for ($dayOffset = 0; $dayOffset < 30; $dayOffset++) {
            $targetTimestamp = strtotime('+' . $dayOffset . ' days', $now);
            $targetDateInfo = getdate($targetTimestamp);

            // 从0点开始检查当天的时间点
            for ($h = 0; $h < 24; $h++) {
                for ($m = 0; $m < 60; $m++) {
                    $candidateTime = mktime($h, $m, 0, $targetDateInfo['mon'], $targetDateInfo['mday'], $targetDateInfo['year']);

                    // 如果已经是今天，需要从当前时间的下一分钟开始检查
                    if ($dayOffset == 0 && $candidateTime <= $now) {
                        continue;
                    }

                    if ($this->matchCron($candidateTime, $minute, $hour, $day, $month, $weekday)) {
                        return $candidateTime;
                    }
                }
            }
        }

        return 0;
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
     * 验证 Cron 表达式格式
     * @param string $cron Cron 表达式
     * @return array [valid: bool, error: string]
     */
    public function validateCron($cron)
    {
        $cron = trim($cron);
        $parts = explode(' ', $cron);

        if (count($parts) != 5) {
            return array('valid' => false, 'error' => 'Cron表达式格式错误，应为5个字段：分 时 日 月 周');
        }

        // 验证每个字段
        $fields = array(
            'minute' => array($parts[0], 0, 59),
            'hour' => array($parts[1], 0, 23),
            'day' => array($parts[2], 1, 31),
            'month' => array($parts[3], 1, 12),
            'weekday' => array($parts[4], 0, 7)
        );

        foreach ($fields as $fieldName => $fieldData) {
            list($value, $min, $max) = $fieldData;
            $result = $this->validateCronField($value, $min, $max);
            if (!$result['valid']) {
                return array(
                    'valid' => false,
                    'error' => $result['error'] . ' (' . $fieldName . ')'
                );
            }
        }

        return array('valid' => true, 'error' => '');
    }

    /**
     * 验证 Cron 字段
     * @param string $value 字段值
     * @param int $min 最小值
     * @param int $max 最大值
     * @return array
     */
    private function validateCronField($value, $min, $max)
    {
        // * 表示任意值
        if ($value === '*') {
            return array('valid' => true, 'error' => '');
        }

        // */n 表示每 n 单位
        if (strpos($value, '*/') === 0) {
            $step = substr($value, 2);
            if (!is_numeric($step) || $step <= 0) {
                return array('valid' => false, 'error' => '步长必须为正整数');
            }
            return array('valid' => true, 'error' => '');
        }

        // n-m 表示范围
        if (strpos($value, '-') !== false) {
            $parts = explode('-', $value);
            if (count($parts) != 2) {
                return array('valid' => false, 'error' => '范围格式错误');
            }
            $start = $parts[0];
            $end = $parts[1];
            if (!is_numeric($start) || !is_numeric($end)) {
                return array('valid' => false, 'error' => '范围边界必须为数字');
            }
            if ($start < $min || $end > $max || $start > $end) {
                return array('valid' => false, 'error' => '范围超出有效值');
            }
            return array('valid' => true, 'error' => '');
        }

        // n,m,n 表示多个值
        if (strpos($value, ',') !== false) {
            $values = explode(',', $value);
            foreach ($values as $v) {
                if (!is_numeric($v)) {
                    return array('valid' => false, 'error' => '列表值必须为数字');
                }
                $num = intval($v);
                if ($num < $min || $num > $max) {
                    return array('valid' => false, 'error' => '值超出有效范围');
                }
            }
            return array('valid' => true, 'error' => '');
        }

        // 单个值
        if (!is_numeric($value)) {
            return array('valid' => false, 'error' => '字段值必须为数字');
        }
        $num = intval($value);
        if ($num < $min || $num > $max) {
            return array('valid' => false, 'error' => '值超出有效范围');
        }

        return array('valid' => true, 'error' => '');
    }

    /**
     * 获取执行日志列表
     * Story 4.6: 执行记录追踪
     * @param int $crontabId 任务ID
     * @param int $page 页码
     * @param int $limit 每页数量
     * @return array
     */
    public function getLogs($crontabId, $page = 1, $limit = 20)
    {
        $total = \think\Db::name('crontab_logs')
            ->where('crontab_id', $crontabId)
            ->count();

        $list = \think\Db::name('crontab_logs')
            ->where('crontab_id', $crontabId)
            ->order('execute_time DESC')
            ->limit($limit)
            ->page($page)
            ->select();

        return array(
            'list' => $list,
            'total' => $total
        );
    }
}
