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

namespace app\adminapi\logic;

use app\common\logic\BaseLogic;
use app\common\model\BatchTask;
use app\common\model\BatchTaskDetail;
use app\common\service\BatchTaskService;
use think\facade\Log;

/**
 * 任务管理业务逻辑
 * Class TaskManagementLogic
 * @package app\adminapi\logic
 */
class TaskManagementLogic extends BaseLogic
{
    /**
     * @notes 创建批量验活任务
     * @param array $params
     * @return array|false
     * @author Claude
     * @date 2025/09/08
     */
    public static function createBatchVerifyTask(array $params)
    {
        try {
            $tenantId = $params['tenant_id'];
            $createAdminId = $params['create_admin_id'];
            $searchParams = $params['search_params'] ?? [];
            $accountIds = $params['account_ids'] ?? [];
            
            // 创建任务
            $task = BatchTaskService::createBatchVerifyTask(
                $tenantId,
                $createAdminId,
                $searchParams,
                $accountIds
            );
            
            if (!$task) {
                self::setError('创建批量验活任务失败');
                return false;
            }
            
            // 记录日志
            Log::info('创建批量验活任务', [
                'task_id' => $task->id,
                'tenant_id' => $tenantId,
                'create_admin_id' => $createAdminId,
                'total_count' => $task->total_count
            ]);
            
            return [
                'task_id' => $task->id,
                'task_name' => $task->task_name,
                'total_count' => $task->total_count,
                'task_status' => $task->task_status,
                'task_status_desc' => $task->task_status_desc
            ];
            
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            Log::error('创建批量验活任务异常', [
                'params' => $params,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * @notes 获取批量验活任务详情
     * @param array $params
     * @return array|false
     * @author Claude
     * @date 2025/09/08
     */
    public static function getBatchVerifyDetail(array $params)
    {
        try {
            $taskId = $params['id'];
            $tenantId = $params['tenant_id'];
            
            // 获取任务信息并验证权限
            $task = BatchTask::where('id', $taskId)
                ->where('tenant_id', $tenantId)
                ->with(['createAdmin', 'tenant'])
                ->append([
                    'task_status_desc',
                    'task_type_desc', 
                    'progress_percent',
                    'success_rate',
                    'start_time_text',
                    'end_time_text',
                    'duration_text',
                    'task_data_array'
                ])
                ->find();
                
            if (!$task) {
                self::setError('任务不存在或无权限访问');
                return false;
            }
            
            // 获取详细统计信息
            $detailStats = BatchTaskService::getTaskDetailStats($task);
            
            $result = $task->toArray();
            $result['detail_stats'] = $detailStats;
            
            return $result;
            
        } catch (\Exception $e) {
            self::setError('获取任务详情失败：' . $e->getMessage());
            Log::error('获取批量验活任务详情异常', [
                'params' => $params,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * @notes 取消批量验活任务
     * @param array $params
     * @return bool
     * @author Claude
     * @date 2025/09/08
     */
    public static function cancelBatchVerifyTask(array $params): bool
    {
        try {
            $taskId = $params['id'];
            $tenantId = $params['tenant_id'];
            
            // 获取任务并验证权限
            $task = BatchTask::where('id', $taskId)
                ->where('tenant_id', $tenantId)
                ->find();
                
            if (!$task) {
                self::setError('任务不存在或无权限访问');
                return false;
            }
            
            // 取消任务
            if (!BatchTaskService::cancelTask($task)) {
                self::setError('任务取消失败');
                return false;
            }
            
            // 记录日志
            Log::info('取消批量验活任务', [
                'task_id' => $taskId,
                'tenant_id' => $tenantId
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            self::setError('取消任务失败：' . $e->getMessage());
            Log::error('取消批量验活任务异常', [
                'params' => $params,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * @notes 检查运行中的任务
     * @param int $tenantId
     * @param string $taskType
     * @return array
     * @author Claude
     * @date 2025/09/08
     */
    public static function checkRunningTask(int $tenantId, string $taskType = ''): array
    {
        try {
            $taskType = $taskType ?: BatchTask::TYPE_BATCH_VERIFY;
            $hasRunningTask = BatchTask::hasRunningTask($tenantId, $taskType);
            
            $result = [
                'has_running_task' => $hasRunningTask,
                'task_type' => $taskType
            ];
            
            if ($hasRunningTask) {
                // 获取运行中的任务信息
                $runningTask = BatchTask::where('tenant_id', $tenantId)
                    ->where('task_type', $taskType)
                    ->whereIn('task_status', [BatchTask::STATUS_PENDING, BatchTask::STATUS_RUNNING])
                    ->append(['task_status_desc', 'progress_percent'])
                    ->find();
                    
                if ($runningTask) {
                    $result['running_task'] = [
                        'id' => $runningTask->id,
                        'task_name' => $runningTask->task_name,
                        'task_status' => $runningTask->task_status,
                        'task_status_desc' => $runningTask->task_status_desc,
                        'progress_percent' => $runningTask->progress_percent,
                        'processed_count' => $runningTask->processed_count,
                        'total_count' => $runningTask->total_count
                    ];
                }
            }
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('检查运行中任务异常', [
                'tenant_id' => $tenantId,
                'task_type' => $taskType,
                'error' => $e->getMessage()
            ]);
            
            return [
                'has_running_task' => false,
                'task_type' => $taskType
            ];
        }
    }

    /**
     * @notes 获取任务进度
     * @param array $params
     * @return array|false
     * @author Claude
     * @date 2025/09/08
     */
    public static function getTaskProgress(array $params)
    {
        try {
            $taskId = $params['id'];
            $tenantId = $params['tenant_id'];
            
            $task = BatchTask::where('id', $taskId)
                ->where('tenant_id', $tenantId)
                ->append([
                    'task_status_desc',
                    'progress_percent',
                    'success_rate',
                    'duration_text'
                ])
                ->find();
                
            if (!$task) {
                self::setError('任务不存在或无权限访问');
                return false;
            }
            
            return [
                'task_id' => $task->id,
                'task_status' => $task->task_status,
                'task_status_desc' => $task->task_status_desc,
                'total_count' => $task->total_count,
                'processed_count' => $task->processed_count,
                'success_count' => $task->success_count,
                'failed_count' => $task->failed_count,
                'progress_percent' => $task->progress_percent,
                'success_rate' => $task->success_rate,
                'duration_text' => $task->duration_text,
                'start_time' => $task->start_time,
                'end_time' => $task->end_time
            ];
            
        } catch (\Exception $e) {
            self::setError('获取任务进度失败：' . $e->getMessage());
            return false;
        }
    }

    /**
     * @notes 获取租户任务统计
     * @param int $tenantId
     * @return array
     * @author Claude
     * @date 2025/09/08
     */
    public static function getTenantTaskStats(int $tenantId): array
    {
        try {
            return BatchTaskService::getTenantTaskStats($tenantId);
        } catch (\Exception $e) {
            Log::error('获取租户任务统计异常', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'total_tasks' => 0,
                'running_tasks' => 0,
                'completed_tasks' => 0,
                'failed_tasks' => 0,
                'total_accounts' => 0,
                'success_accounts' => 0,
                'failed_accounts' => 0
            ];
        }
    }

    /**
     * @notes 获取任务执行详情列表
     * @param array $params
     * @return array
     * @author Claude
     * @date 2025/09/08
     */
    public static function getTaskDetailList(array $params): array
    {
        try {
            $taskId = $params['id'];
            $tenantId = $params['tenant_id'];
            $page = $params['page'] ?? 1;
            $limit = $params['limit'] ?? 20;
            $status = $params['status'] ?? '';
            
            // 验证任务权限
            $task = BatchTask::where('id', $taskId)
                ->where('tenant_id', $tenantId)
                ->find();
                
            if (!$task) {
                return [
                    'list' => [],
                    'count' => 0,
                    'page' => $page,
                    'limit' => $limit
                ];
            }
            
            // 构建查询
            $query = BatchTaskDetail::where('task_id', $taskId)
                ->with(['account']);
                
            if ($status) {
                $query->where('status', $status);
            }
            
            // 分页查询
            $result = $query->append([
                    'status_desc',
                    'status_icon',
                    'process_time_text',
                    'token_refreshed_text'
                ])
                ->order('id', 'asc')
                ->paginate([
                    'list_rows' => $limit,
                    'page' => $page
                ]);
                
            return [
                'list' => $result->items(),
                'count' => $result->total(),
                'page' => $page,
                'limit' => $limit,
                'total_page' => $result->lastPage()
            ];
            
        } catch (\Exception $e) {
            Log::error('获取任务详情列表异常', [
                'params' => $params,
                'error' => $e->getMessage()
            ]);
            
            return [
                'list' => [],
                'count' => 0,
                'page' => $params['page'] ?? 1,
                'limit' => $params['limit'] ?? 20
            ];
        }
    }
}