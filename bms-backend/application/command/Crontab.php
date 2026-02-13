<?php
// application/command/Crontab.php
namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;
use think\Cache;

class Crontab extends Command
{
    protected function configure()
    {
        $this->setName('crontab')
             ->setDescription('定时任务管理器')
             ->addArgument('action');
    }

    protected function execute(Input $input, Output $output)
    {
        $action = $input->getArgument('action');
        
        if (empty($action)) {
            $output->writeln('请指定要执行的任务: run');
            return;
        }

        $output->writeln('====================================');
        $output->writeln('计划任务开始: ' . date('Y-m-d H:i:s'));
        $output->writeln('任务类型: ' . $action);
        $output->writeln('====================================');

        switch ($action) {
            case 'run':
                $this->crontabInit($output);
                break;
            default:
                $output->writeln('未知任务类型:'.$action);
                $output->writeln('====================================');
        }
    }

    /**
     * 计划任务调度器 crontab中设置没5分钟执行一次
     * 根据任务表中查找需要执行的任务，并执行
     */
    protected function crontabInit($output)
    {
        try {
            $where = [
                'status'     => 1,
                'deleted_at' => 0,
            ];
            $tasks = Db::name('crontab')
                ->where($where)
                ->select();
            if($tasks){
                foreach ($tasks as $task) {
                    $this->executeTask($task, $output);
                }
            }
        } catch (\Exception $e) {
            $output->writeln('计划任务调度器初始化失败: ' . $e->getMessage());
        }
    }

    protected function executeTask($task, $output)
    {
        try {
            // 定时任务执行模型
            $crontabExecute = model('admin/CrontabExecute');
            $result = $crontabExecute->runTask($task['crontab_id']);
            if($result['state'] === 1){
                $output->writeln($result['msg']);
            }else{
                // 对于"当前不在执行时间段"的消息，不输出
                if ($result['msg'] !== '当前不在执行时间段') {
                    $output->writeln($result['msg']);
                }
            }
        } catch (\Exception $e) {
            $output->writeln('执行失败: ' . $e->getMessage());
        }
    }

}