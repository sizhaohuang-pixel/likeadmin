<?php
// +----------------------------------------------------------------------
// | likeadmin快速开发前后端分离管理后台（PHP版）
// +----------------------------------------------------------------------
// | 欢迎阅读学习系统程序代码，建议反馈是我们前进的动力
// | 开源版本可自由商用，可去除界面版权logo
// | gitee下载：https://gitee.com/likeshop_gitee/likeadmin
// | github下载：https://github.com/likeshop-github/likeadmin
// | 访问官网：https://www.likeadmin.cn
// | likeadmin团队 版权所有 拥有最终解释权
// +----------------------------------------------------------------------
// | author: likeadminTeam
// +----------------------------------------------------------------------

declare (strict_types = 1);

namespace app\common\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Log;
use think\facade\Cache;
use app\common\model\BatchTask;
use app\common\service\BatchTaskService;

/**
 * 批量任务处理器
 * Class BatchTaskProcessor
 * @package app\common\command
 */
class BatchTaskProcessor extends Command
{
    /**
     * 进程锁前缀
     */
    const LOCK_PREFIX = 'batch_task_lock:';
    
    /**
     * 进程锁超时时间（秒）
     */
    const LOCK_TIMEOUT = 3600 * 12;

    protected function configure()
    {
        $this->setName('batch-task:process')
            ->setDescription('批量任务处理器 - 处理待执行的批量任务');
    }

    protected function execute(Input $input, Output $output)
    {
        $this->output->writeln('批量任务处理器启动...');

        try {
            // 获取待处理的任务
            $tasks = $this->getPendingTasks();
            
            if ($tasks->isEmpty()) {
                $this->output->writeln('没有待处理的任务');
                return;
            }

            $this->output->writeln('找到 ' . $tasks->count() . ' 个待处理任务');

            foreach ($tasks as $task) {
                $this->processTask($task);
            }

            // 清理超时任务
            $this->cleanupTimeoutTasks();

        } catch (\Exception $e) {
            $this->output->error('批量任务处理器执行异常: ' . $e->getMessage());
            Log::error('批量任务处理器异常: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
        }

        $this->output->writeln('批量任务处理器结束');
    }

    /**
     * 获取待处理的任务
     * @return \think\Collection
     */
    private function getPendingTasks()
    {
        return BatchTask::whereIn('task_status', [BatchTask::STATUS_PENDING, BatchTask::STATUS_RUNNING])
            ->order('create_time', 'asc')
            ->limit(5) // 限制单次处理任务数量
            ->select();
    }

    /**
     * 处理单个任务
     * @param BatchTask $task
     */
    private function processTask(BatchTask $task)
    {
        $lockKey = self::LOCK_PREFIX . $task->id;
        
        // 尝试获取任务锁
        if (!$this->acquireLock($lockKey)) {
            $this->output->writeln("任务 {$task->id} 正在被其他进程处理，跳过");
            return;
        }

        try {
            $this->output->writeln("开始处理任务 {$task->id}: {$task->task_name}");
            
            // 再次检查任务状态（防止并发问题）
            $task->refresh();
            if (!in_array($task->task_status, [BatchTask::STATUS_PENDING, BatchTask::STATUS_RUNNING])) {
                $this->output->writeln("任务 {$task->id} 状态已变更为 {$task->task_status}，跳过处理");
                return;
            }

            // 根据任务类型处理
            $success = false;
            switch ($task->task_type) {
                case BatchTask::TYPE_BATCH_VERIFY:
                    $success = $this->processBatchVerifyTask($task);
                    break;
                default:
                    $this->output->error("未知的任务类型: {$task->task_type}");
                    $task->changeStatus(BatchTask::STATUS_FAILED);
                    $task->error_message = '未知的任务类型';
                    $task->save();
                    break;
            }

            if ($success) {
                $this->output->writeln("任务 {$task->id} 处理完成");
            } else {
                $this->output->error("任务 {$task->id} 处理失败");
            }

        } catch (\Exception $e) {
            $this->output->error("处理任务 {$task->id} 时发生异常: " . $e->getMessage());
            Log::error("处理批量任务异常", [
                'task_id' => $task->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        } finally {
            // 释放任务锁
            $this->releaseLock($lockKey);
        }
    }

    /**
     * 处理批量验活任务
     * @param BatchTask $task
     * @return bool
     */
    private function processBatchVerifyTask(BatchTask $task): bool
    {
        $this->output->writeln("执行批量验活任务 {$task->id}，共 {$task->total_count} 个账号");
        
        return BatchTaskService::processBatchVerifyTask($task);
    }

    /**
     * 获取进程锁
     * @param string $key
     * @return bool
     */
    private function acquireLock(string $key): bool
    {
        // 尝试使用Redis缓存获取锁
        try {
            $cacheConfig = Cache::getConfig();
            if (isset($cacheConfig['stores']['redis']) && $cacheConfig['default'] === 'redis') {
                // Redis锁
                $redis = Cache::store('redis');
                if ($redis) {
                    return $redis->remember($key . '_lock', time(), self::LOCK_TIMEOUT) === time();
                }
            }
        } catch (\Exception $e) {
            Log::warning('Redis锁获取失败，使用文件锁: ' . $e->getMessage());
        }

        // 降级使用文件锁
        return $this->acquireFileLock($key);
    }

    /**
     * 释放进程锁
     * @param string $key
     */
    private function releaseLock(string $key)
    {
        try {
            $cacheConfig = Cache::getConfig();
            if (isset($cacheConfig['stores']['redis']) && $cacheConfig['default'] === 'redis') {
                $redis = Cache::store('redis');
                if ($redis) {
                    $redis->delete($key . '_lock');
                    return;
                }
            }
        } catch (\Exception $e) {
            Log::warning('Redis锁释放失败，使用文件锁释放: ' . $e->getMessage());
        }

        // 释放文件锁
        $this->releaseFileLock($key);
    }

    /**
     * 获取文件锁
     * @param string $key
     * @return bool
     */
    private function acquireFileLock(string $key): bool
    {
        $lockFile = runtime_path() . 'lock/' . str_replace(':', '_', $key) . '.lock';
        $lockDir = dirname($lockFile);
        
        if (!is_dir($lockDir)) {
            mkdir($lockDir, 0755, true);
        }

        $handle = fopen($lockFile, 'c');
        if ($handle === false) {
            return false;
        }

        $locked = flock($handle, LOCK_EX | LOCK_NB);
        if ($locked) {
            // 存储文件句柄以便后续释放
            $this->lockHandles[$key] = $handle;
            
            // 写入锁信息
            fwrite($handle, json_encode([
                'pid' => getmypid(),
                'time' => time(),
                'key' => $key
            ]));
            fflush($handle);
        } else {
            fclose($handle);
        }

        return $locked;
    }

    /**
     * 文件锁句柄存储
     * @var array
     */
    private $lockHandles = [];

    /**
     * 释放文件锁
     * @param string $key
     */
    private function releaseFileLock(string $key)
    {
        if (isset($this->lockHandles[$key])) {
            $handle = $this->lockHandles[$key];
            flock($handle, LOCK_UN);
            fclose($handle);
            unset($this->lockHandles[$key]);

            // 删除锁文件
            $lockFile = runtime_path() . 'lock/' . str_replace(':', '_', $key) . '.lock';
            if (file_exists($lockFile)) {
                @unlink($lockFile);
            }
        }
    }

    /**
     * 清理超时任务
     */
    private function cleanupTimeoutTasks()
    {
        $timeoutTime = time() - self::LOCK_TIMEOUT; // 1小时超时
        
        $timeoutTasks = BatchTask::where('task_status', BatchTask::STATUS_RUNNING)
            ->where('start_time', '<', $timeoutTime)
            ->whereNotNull('start_time')
            ->select();

        foreach ($timeoutTasks as $task) {
            $this->output->writeln("发现超时任务 {$task->id}，标记为失败");
            
            try {
                $task->changeStatus(BatchTask::STATUS_FAILED);
                $task->error_message = '任务执行超时';
                $task->save();
                
                Log::warning("批量任务超时", [
                    'task_id' => $task->id,
                    'start_time' => $task->start_time,
                    'timeout_threshold' => $timeoutTime
                ]);
                
            } catch (\Exception $e) {
                $this->output->error("处理超时任务 {$task->id} 时发生错误: " . $e->getMessage());
                Log::error("处理超时任务异常", [
                    'task_id' => $task->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        if ($timeoutTasks->count() > 0) {
            $this->output->writeln("清理了 " . $timeoutTasks->count() . " 个超时任务");
        }
    }

    /**
     * 析构函数 - 确保释放所有文件锁
     */
    public function __destruct()
    {
        foreach ($this->lockHandles as $key => $handle) {
            $this->releaseFileLock($key);
        }
    }
}

