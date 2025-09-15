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

use app\common\model\AltAccount;

/**
 * 批量任务详情模型
 * Class BatchTaskDetail
 * @package app\common\model
 */
class BatchTaskDetail extends BaseModel
{
    protected $name = 'batch_task_detail';

    /**
     * 处理状态常量
     */
    const STATUS_PENDING = 'pending';
    const STATUS_SUCCESS = 'success';
    const STATUS_FAILED = 'failed';

    /**
     * 状态描述
     */
    const STATUS_DESC = [
        self::STATUS_PENDING => '待处理',
        self::STATUS_SUCCESS => '成功',
        self::STATUS_FAILED => '失败'
    ];

    /**
     * 关联批量任务
     */
    public function batchTask()
    {
        return $this->belongsTo(BatchTask::class, 'task_id');
    }

    /**
     * 关联账号
     */
    public function account()
    {
        return $this->belongsTo(AltAccount::class, 'account_id');
    }

    /**
     * 获取状态描述
     */
    public function getStatusDescAttr($value, $data)
    {
        return self::STATUS_DESC[$data['status']] ?? '未知状态';
    }

    /**
     * 获取格式化的处理时间
     */
    public function getProcessTimeTextAttr($value, $data)
    {
        return $data['process_time'] ? date('Y-m-d H:i:s', $data['process_time']) : '';
    }

    /**
     * 获取结果状态图标
     */
    public function getStatusIconAttr($value, $data)
    {
        switch ($data['status']) {
            case self::STATUS_SUCCESS:
                return '✅';
            case self::STATUS_FAILED:
                return '❌';
            default:
                return '⏳';
        }
    }

    /**
     * 获取Token刷新状态文本
     */
    public function getTokenRefreshedTextAttr($value, $data)
    {
        return $data['token_refreshed'] ? '是' : '否';
    }

    /**
     * 批量创建任务详情
     * @param int $taskId
     * @param array $accountIds
     * @return bool
     */
    public static function createBatchDetails(int $taskId, array $accountIds): bool
    {
        $details = [];
        $now = time();
        
        // 获取账号信息
        $accounts = AltAccount::whereIn('id', $accountIds)->column('uid,mid', 'id');
        
        foreach ($accountIds as $accountId) {
            $account = $accounts[$accountId] ?? [];
            $details[] = [
                'task_id' => $taskId,
                'account_id' => $accountId,
                'account_uid' => $account['uid'] ?? $account['mid'] ?? '',
                'status' => self::STATUS_PENDING,
                'create_time' => $now,
                'update_time' => $now
            ];
        }
        
        // insertAll返回插入的行数，转换为bool类型
        $result = static::insertAll($details);
        return $result > 0;
    }

    /**
     * 更新处理结果
     * @param int $taskId
     * @param int $accountId
     * @param string $status
     * @param string $message
     * @param int $resultCode
     * @param bool $tokenRefreshed
     * @return bool
     */
    public static function updateResult(int $taskId, int $accountId, string $status, string $message = '', int $resultCode = null, bool $tokenRefreshed = false): bool
    {
        $result = static::where('task_id', $taskId)
            ->where('account_id', $accountId)
            ->update([
                'status' => $status,
                'result_message' => $message,
                'result_code' => $resultCode,
                'token_refreshed' => $tokenRefreshed ? 1 : 0,
                'process_time' => time(),
                'update_time' => time()
            ]);
        
        return $result > 0;
    }

    /**
     * 获取任务统计信息
     * @param int $taskId
     * @return array
     */
    public static function getTaskStats(int $taskId): array
    {
        $stats = static::where('task_id', $taskId)
            ->field([
                'COUNT(*) as total',
                'COUNT(CASE WHEN status = "success" THEN 1 END) as success',
                'COUNT(CASE WHEN status = "failed" THEN 1 END) as failed',
                'COUNT(CASE WHEN status = "pending" THEN 1 END) as pending',
                'COUNT(CASE WHEN status != "pending" THEN 1 END) as processed'
            ])
            ->find();
            
        return $stats ? $stats->toArray() : [
            'total' => 0,
            'success' => 0, 
            'failed' => 0,
            'pending' => 0,
            'processed' => 0
        ];
    }

    /**
     * 获取待处理的任务详情
     * @param int $taskId
     * @param int $limit
     * @return \think\Collection
     */
    public static function getPendingDetails(int $taskId, int $limit = 10)
    {
        return static::where('task_id', $taskId)
            ->where('status', self::STATUS_PENDING)
            ->with(['account'])
            ->order('id', 'asc')
            ->limit($limit)
            ->select();
    }

    /**
     * 获取任务处理结果概览
     * @param int $taskId
     * @return array
     */
    public static function getResultSummary(int $taskId): array
    {
        $results = static::where('task_id', $taskId)
            ->whereIn('status', [self::STATUS_SUCCESS, self::STATUS_FAILED])
            ->field(['status', 'result_code', 'COUNT(*) as count'])
            ->group('status, result_code')
            ->select()
            ->toArray();
            
        $summary = [
            'success_types' => [],
            'failed_types' => []
        ];
        
        foreach ($results as $result) {
            if ($result['status'] === self::STATUS_SUCCESS) {
                $summary['success_types'][$result['result_code']] = $result['count'];
            } else {
                $summary['failed_types'][$result['result_code']] = $result['count'];
            }
        }
        
        return $summary;
    }
}