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

namespace app\common\model;

use think\model\concern\SoftDelete;
use app\common\model\auth\Admin;

/**
 * 批量任务模型
 * Class BatchTask
 * @package app\common\model
 */
class BatchTask extends BaseModel
{
    use SoftDelete;

    protected $name = 'batch_task';
    protected $deleteTime = 'delete_time';

    /**
     * 任务状态常量
     */
    const STATUS_PENDING = 'pending';
    const STATUS_RUNNING = 'running';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * 任务类型常量
     */
    const TYPE_BATCH_VERIFY = 'batch_verify';

    /**
     * 状态描述
     */
    const STATUS_DESC = [
        self::STATUS_PENDING => '等待中',
        self::STATUS_RUNNING => '执行中',
        self::STATUS_COMPLETED => '已完成',
        self::STATUS_FAILED => '已失败',
        self::STATUS_CANCELLED => '已取消'
    ];

    /**
     * 任务类型描述
     */
    const TYPE_DESC = [
        self::TYPE_BATCH_VERIFY => '批量验活'
    ];

    /**
     * 允许的状态转换
     */
    const ALLOWED_TRANSITIONS = [
        self::STATUS_PENDING => [self::STATUS_RUNNING, self::STATUS_CANCELLED],
        self::STATUS_RUNNING => [self::STATUS_COMPLETED, self::STATUS_FAILED, self::STATUS_CANCELLED],
    ];

    /**
     * 关联创建人
     */
    public function createAdmin()
    {
        return $this->belongsTo(Admin::class, 'create_admin_id');
    }

    /**
     * 关联租户
     */
    public function tenant()
    {
        return $this->belongsTo(Admin::class, 'tenant_id');
    }

    /**
     * 关联任务详情
     */
    public function details()
    {
        return $this->hasMany(BatchTaskDetail::class, 'task_id');
    }

    /**
     * 获取任务状态描述
     */
    public function getTaskStatusDescAttr($value, $data)
    {
        return self::STATUS_DESC[$data['task_status']] ?? '未知状态';
    }

    /**
     * 获取任务类型描述
     */
    public function getTaskTypeDescAttr($value, $data)
    {
        return self::TYPE_DESC[$data['task_type']] ?? '未知类型';
    }

    /**
     * 获取进度百分比
     */
    public function getProgressPercentAttr($value, $data)
    {
        if ($data['total_count'] == 0) {
            return 0;
        }
        return round(($data['processed_count'] / $data['total_count']) * 100, 2);
    }

    /**
     * 获取成功率
     */
    public function getSuccessRateAttr($value, $data)
    {
        if ($data['processed_count'] == 0) {
            return 0;
        }
        return round(($data['success_count'] / $data['processed_count']) * 100, 2);
    }

    /**
     * 获取格式化的开始时间
     */
    public function getStartTimeTextAttr($value, $data)
    {
        return $data['start_time'] ? date('Y-m-d H:i:s', $data['start_time']) : '';
    }

    /**
     * 获取格式化的结束时间
     */
    public function getEndTimeTextAttr($value, $data)
    {
        return $data['end_time'] ? date('Y-m-d H:i:s', $data['end_time']) : '';
    }

    /**
     * 获取执行耗时
     */
    public function getDurationAttr($value, $data)
    {
        if (!$data['start_time'] || !$data['end_time']) {
            return 0;
        }
        return $data['end_time'] - $data['start_time'];
    }

    /**
     * 获取格式化的执行耗时
     */
    public function getDurationTextAttr($value, $data)
    {
        $duration = $this->getDurationAttr($value, $data);
        if ($duration == 0) {
            return '-';
        }

        $hours = floor($duration / 3600);
        $minutes = floor(($duration % 3600) / 60);
        $seconds = $duration % 60;

        if ($hours > 0) {
            return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
        }
        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    /**
     * 获取任务数据JSON解析
     */
    public function getTaskDataArrayAttr($value, $data)
    {
        return $data['task_data'] ? json_decode($data['task_data'], true) : [];
    }

    /**
     * 更改任务状态
     * @param string $newStatus 新状态
     * @return bool
     * @throws \Exception
     */
    public function changeStatus(string $newStatus): bool
    {
        $currentStatus = $this->task_status;
        
        // 检查状态转换是否合法
        $allowedStatuses = self::ALLOWED_TRANSITIONS[$currentStatus] ?? [];
        if (!in_array($newStatus, $allowedStatuses)) {
            throw new \Exception("不允许从状态 {$currentStatus} 转换到 {$newStatus}");
        }

        $this->task_status = $newStatus;
        
        // 设置时间戳
        if ($newStatus === self::STATUS_RUNNING && !$this->start_time) {
            $this->start_time = time();
        }
        if (in_array($newStatus, [self::STATUS_COMPLETED, self::STATUS_FAILED, self::STATUS_CANCELLED]) && !$this->end_time) {
            $this->end_time = time();
        }

        return $this->save();
    }

    /**
     * 检查是否可以取消
     * @return bool
     */
    public function canCancel(): bool
    {
        return in_array($this->task_status, [self::STATUS_PENDING, self::STATUS_RUNNING]);
    }

    /**
     * 更新任务进度
     * @param int $processed 已处理数量
     * @param int $success 成功数量
     * @param int $failed 失败数量
     * @return bool
     */
    public function updateProgress(int $processed, int $success, int $failed): bool
    {
        $this->processed_count = $processed;
        $this->success_count = $success;
        $this->failed_count = $failed;
        
        return $this->save();
    }

    /**
     * 批量获取租户的运行中任务
     * @param int $tenantId
     * @param string $taskType
     * @return \think\Collection
     */
    public static function getRunningTasks(int $tenantId, string $taskType = '')
    {
        $query = static::where('tenant_id', $tenantId)
            ->whereIn('task_status', [self::STATUS_PENDING, self::STATUS_RUNNING]);
            
        if ($taskType) {
            $query->where('task_type', $taskType);
        }
        
        return $query->select();
    }

    /**
     * 检查是否存在运行中的任务
     * @param int $tenantId
     * @param string $taskType
     * @return bool
     */
    public static function hasRunningTask(int $tenantId, string $taskType): bool
    {
        return static::where('tenant_id', $tenantId)
            ->where('task_type', $taskType)
            ->whereIn('task_status', [self::STATUS_PENDING, self::STATUS_RUNNING])
            ->count() > 0;
    }
}