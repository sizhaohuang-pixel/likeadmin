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

namespace app\common\service;

use app\common\model\BatchTask;
use app\common\model\BatchTaskDetail;
use app\common\model\AltAccount;
use app\common\service\LineApiService;
use think\facade\Db;
use think\facade\Log;

/**
 * 批量任务服务类
 * Class BatchTaskService
 * @package app\common\service
 */
class BatchTaskService
{
    /**
     * 创建批量验活任务
     * @param int $tenantId 租户ID
     * @param int $createAdminId 创建人ID
     * @param array $searchParams 搜索参数
     * @param array $accountIds 指定账号ID数组（可选）
     * @return BatchTask|false
     * @throws \Exception
     */
    public static function createBatchVerifyTask(int $tenantId, int $createAdminId, array $searchParams = [], array $accountIds = [])
    {
        // 检查是否已有运行中的同类型任务
        if (BatchTask::hasRunningTask($tenantId, BatchTask::TYPE_BATCH_VERIFY)) {
            throw new \Exception('您已有正在执行的批量验活任务，请等待完成后再试');
        }

        // 获取要处理的账号列表
        if (empty($accountIds)) {
            $accountIds = self::getAccountIdsBySearch($tenantId, $searchParams);
        } else {
            // 验证指定的账号是否属于当前租户
            $validAccountIds = AltAccount::where('tenant_id', $tenantId)
                ->whereIn('id', $accountIds)
                ->column('id');
            
            if (count($validAccountIds) !== count($accountIds)) {
                throw new \Exception('部分账号不属于当前租户，操作被拒绝');
            }
            $accountIds = $validAccountIds;
        }

        if (empty($accountIds)) {
            throw new \Exception('未找到符合条件的账号');
        }

        // 限制批量任务数量
        if (count($accountIds) > 1000) {
            throw new \Exception('单次批量操作不能超过1000个账号');
        }

        // 开始事务
        return Db::transaction(function () use ($tenantId, $createAdminId, $searchParams, $accountIds) {
            // 创建批量任务
            $task = BatchTask::create([
                'tenant_id' => $tenantId,
                'task_name' => '批量验活任务-' . date('Y-m-d H:i:s'),
                'task_type' => BatchTask::TYPE_BATCH_VERIFY,
                'task_status' => BatchTask::STATUS_PENDING,
                'total_count' => count($accountIds),
                'task_data' => json_encode([
                    'search_params' => $searchParams,
                    'account_ids' => $accountIds
                ]),
                'create_admin_id' => $createAdminId,
                'create_time' => time(),
                'update_time' => time()
            ]);

            if (!$task) {
                throw new \Exception('创建批量任务失败');
            }

            // 创建任务详情
            if (!BatchTaskDetail::createBatchDetails($task->id, $accountIds)) {
                throw new \Exception('创建任务详情失败');
            }

            return $task;
        });
    }

    /**
     * 根据搜索条件获取账号ID列表
     * @param int $tenantId 租户ID
     * @param array $searchParams 搜索参数
     * @return array
     */
    private static function getAccountIdsBySearch(int $tenantId, array $searchParams): array
    {
        $query = AltAccount::where('tenant_id', $tenantId)
            ->whereNull('delete_time');

        // 应用搜索条件，支持前端账号管理页面的所有搜索字段
        if (!empty($searchParams['nickname'])) {
            $query->where('nickname', 'like', '%' . $searchParams['nickname'] . '%');
        }
        
        if (!empty($searchParams['area_code'])) {
            $query->where('area_code', $searchParams['area_code']);
        }
        
        if (!empty($searchParams['phone'])) {
            $query->where('phone', 'like', '%' . $searchParams['phone'] . '%');
        }
        
        if (!empty($searchParams['password'])) {
            $query->where('password', 'like', '%' . $searchParams['password'] . '%');
        }
        
        // 昵称存在性搜索条件
        if (!empty($searchParams['has_nickname'])) {
            if ($searchParams['has_nickname'] === 'yes') {
                // 存在昵称：nickname不为空
                $query->where('nickname', '<>', '')->whereNotNull('nickname');
            } elseif ($searchParams['has_nickname'] === 'no') {
                // 不存在昵称：nickname为空或NULL
                $query->where(function($q) {
                    $q->whereNull('nickname')->whereOr('nickname', '');
                });
            }
        }
        
        // 自定义ID存在性搜索条件
        if (!empty($searchParams['has_uid'])) {
            if ($searchParams['has_uid'] === 'yes') {
                // 存在自定义ID：uid不为空
                $query->where('uid', '<>', '')->whereNotNull('uid');
            } elseif ($searchParams['has_uid'] === 'no') {
                // 不存在自定义ID：uid为空或NULL
                $query->where(function($q) {
                    $q->whereNull('uid')->whereOr('uid', '');
                });
            }
        }
        
        if (!empty($searchParams['mid'])) {
            $query->where('mid', 'like', '%' . $searchParams['mid'] . '%');
        }
        
        if (!empty($searchParams['status']) && $searchParams['status'] !== '') {
            $query->where('status', $searchParams['status']);
        }
        
        if (!empty($searchParams['group_id']) && $searchParams['group_id'] !== '') {
            $query->where('group_id', $searchParams['group_id']);
        }
        
        if (!empty($searchParams['operator_id']) && $searchParams['operator_id'] !== '') {
            $query->where('operator_id', $searchParams['operator_id']);
        }
        
        if (!empty($searchParams['platform'])) {
            $query->where('platform', $searchParams['platform']);
        }
        
        // 代理状态搜索条件
        if (!empty($searchParams['proxy_status'])) {
            if ($searchParams['proxy_status'] === 'none') {
                // 未设置代理：proxy_url为空或NULL
                $query->where(function($q) {
                    $q->whereNull('proxy_url')->whereOr('proxy_url', '');
                });
            } elseif ($searchParams['proxy_status'] === 'set') {
                // 已设置代理：proxy_url不为空
                $query->where('proxy_url', '<>', '')->whereNotNull('proxy_url');
            }
        }

        return $query->column('id');
    }

    /**
     * 处理批量验活任务
     * @param BatchTask $task
     * @return bool
     */
    public static function processBatchVerifyTask(BatchTask $task): bool
    {
        try {
            $originalStatus = $task->task_status;
            if ($originalStatus === BatchTask::STATUS_PENDING) {
                if (!$task->changeStatus(BatchTask::STATUS_RUNNING)) {
                    return false;
                }
            } elseif ($originalStatus !== BatchTask::STATUS_RUNNING) {
                Log::warning("批量验活任务状态异常，无法处理: {$task->id}, 当前状态: {$originalStatus}");
                return false;
            }

            $task->refresh();

            Log::info("开始处理批量验活任务: {$task->id}");

            $batchSize = 10; // 批次大小
            $processed = (int)($task->processed_count ?? 0);
            $success = (int)($task->success_count ?? 0);
            $failed = (int)($task->failed_count ?? 0);

            while (true) {
                // 检查并重新连接数据库（防止长时间运行导致连接断开）
                self::ensureDatabaseConnection();
                
                // 获取待处理的任务详情
                $details = BatchTaskDetail::getPendingDetails($task->id, $batchSize);
                
                if ($details->isEmpty()) {
                    break; // 没有待处理的任务了
                }

                foreach ($details as $detail) {
                    try {
                        // 每处理10个账号检查一次数据库连接
                        if ($processed % 10 === 0) {
                            self::ensureDatabaseConnection();
                        }
                        
                        // 处理单个账号验活
                        $result = self::processAccountVerify($detail);
                        
                        // 使用事务确保数据一致性，但避免长事务
                        Db::transaction(function () use ($task, $detail, $result, &$success, &$failed, &$processed) {
                            if ($result['success']) {
                                $success++;
                                BatchTaskDetail::updateResult(
                                    $task->id,
                                    $detail->account_id,
                                    BatchTaskDetail::STATUS_SUCCESS,
                                    $result['message'],
                                    $result['code'],
                                    $result['token_refreshed'] ?? false
                                );
                            } else {
                                $failed++;
                                BatchTaskDetail::updateResult(
                                    $task->id,
                                    $detail->account_id,
                                    BatchTaskDetail::STATUS_FAILED,
                                    $result['message'],
                                    $result['code']
                                );
                            }
                            
                            $processed++;
                            
                            // 更新任务进度
                            $task->updateProgress($processed, $success, $failed);
                        });
                        
                        // 检查任务是否被取消
                        $task->refresh();
                        if ($task->task_status === BatchTask::STATUS_CANCELLED) {
                            Log::info("批量验活任务被取消: {$task->id}");
                            return true;
                        }
                        
                        // 短暂延迟，避免对API造成过大压力
                        usleep(500000); // 0.5秒
                        
                    } catch (\Exception $e) {
                        // 检查是否为数据库连接错误，如果是则重新连接并重试一次
                        if (self::isDatabaseConnectionError($e)) {
                            Log::warning("检测到数据库连接错误，尝试重新连接: " . $e->getMessage());
                            self::ensureDatabaseConnection();
                            
                            // 重试更新结果
                            try {
                                $failed++;
                                BatchTaskDetail::updateResult(
                                    $task->id,
                                    $detail->account_id,
                                    BatchTaskDetail::STATUS_FAILED,
                                    '处理异常（已重连）：' . $e->getMessage(),
                                    0
                                );
                                $processed++;
                                $task->updateProgress($processed, $success, $failed);
                            } catch (\Exception $retryE) {
                                Log::error("重试后仍然失败: " . $retryE->getMessage());
                            }
                        } else {
                            $failed++;
                            BatchTaskDetail::updateResult(
                                $task->id,
                                $detail->account_id,
                                BatchTaskDetail::STATUS_FAILED,
                                '处理异常：' . $e->getMessage(),
                                0
                            );
                            $processed++;
                            $task->updateProgress($processed, $success, $failed);
                        }
                        
                        Log::error("处理账号验活异常: " . $e->getMessage(), [
                            'task_id' => $task->id,
                            'account_id' => $detail->account_id
                        ]);
                    }
                }
            }

            // 任务完成
            $task->changeStatus(BatchTask::STATUS_COMPLETED);
            Log::info("批量验活任务完成: {$task->id}, 总计: {$processed}, 成功: {$success}, 失败: {$failed}");
            
            return true;
            
        } catch (\Exception $e) {
            // 任务失败
            $task->changeStatus(BatchTask::STATUS_FAILED);
            $task->error_message = $e->getMessage();
            $task->save();
            
            Log::error("批量验活任务失败: " . $e->getMessage(), ['task_id' => $task->id]);
            return false;
        }
    }

    /**
     * 处理单个账号验活
     * @param BatchTaskDetail $detail
     * @return array
     */
    private static function processAccountVerify(BatchTaskDetail $detail): array
    {
        $account = $detail->account;
        if (!$account) {
            return [
                'success' => false,
                'message' => '账号不存在',
                'code' => 0
            ];
        }

        // 检查必需字段
        if (empty($account->mid) || empty($account->accesstoken) || empty($account->proxy_url)) {
            return [
                'success' => false,
                'message' => '账号信息不完整（缺少MID、访问令牌或代理地址）',
                'code' => 0
            ];
        }

        // 调用验活API
        $result = LineApiService::verifyAccount($account->mid, $account->accesstoken, $account->proxy_url);
        
        // 记录验活结果，但排除可能的大型数据
        $logResult = $result;
        if (isset($logResult['data']) && is_array($logResult['data'])) {
            foreach ($logResult['data'] as $key => $value) {
                if (is_string($value) && strlen($value) > 1000) {
                    $logResult['data'][$key] = "数据过长已过滤(长度:" . strlen($value) . "字符)";
                }
            }
        }
        Log::warning('验活API: ' . json_encode($logResult));
        // 如果状态为3（下线），尝试刷新Token
        $tokenRefreshed = false;
        if (in_array($result['code'], [3,5]) && !empty($account->refreshtoken)) {
            $refreshResult = LineApiService::refreshToken(
                $account->mid,
                $account->accesstoken,
                $account->refreshtoken,
                $account->proxy_url
            );
            
            if ($refreshResult['success']) {
                // 更新数据库中的token
                $account->accesstoken = $refreshResult['data']['accessToken'];
                $account->refreshtoken = $refreshResult['data']['refreshToken'];
                $account->save();
                
                // 使用新token重新验活
                $result = LineApiService::verifyAccount(
                    $account->mid,
                    $account->accesstoken,
                    $account->proxy_url
                );
                $tokenRefreshed = true;
            }
        }

        // 更新账号状态
        $newStatus = self::mapVerifyCodeToStatus($result['code']);
        if ($newStatus !== null) {
            $account->status = $newStatus;
            $account->save();
        }

        return [
            'success' => $result['success'],
            'message' => $result['message'],
            'code' => $result['code'],
            'token_refreshed' => $tokenRefreshed
        ];
    }

    /**
     * 映射验活状态码到账号状态
     * @param int $code
     * @return int|null
     */
    private static function mapVerifyCodeToStatus(int $code): ?int
    {
        $mapping = [
            1 => 1, // 正常
            2 => 2, // 代理不可用
            3 => 3, // 下线
            4 => 4, // 封禁
        ];
        
        return $mapping[$code] ?? null;
    }

    /**
     * 取消任务
     * @param BatchTask $task
     * @return bool
     * @throws \Exception
     */
    public static function cancelTask(BatchTask $task): bool
    {
        if (!$task->canCancel()) {
            throw new \Exception('当前任务状态不允许取消');
        }

        return $task->changeStatus(BatchTask::STATUS_CANCELLED);
    }

    /**
     * 获取任务详细统计信息
     * @param BatchTask $task
     * @return array
     */
    public static function getTaskDetailStats(BatchTask $task): array
    {
        $stats = BatchTaskDetail::getTaskStats($task->id);
        $summary = BatchTaskDetail::getResultSummary($task->id);
        
        return array_merge($stats, $summary);
    }

    /**
     * 清理过期任务
     * @param int $days 保留天数
     * @return int 清理数量
     */
    public static function cleanExpiredTasks(int $days = 30): int
    {
        $expireTime = time() - ($days * 24 * 3600);
        
        return BatchTask::where('create_time', '<', $expireTime)
            ->whereIn('task_status', [BatchTask::STATUS_COMPLETED, BatchTask::STATUS_FAILED, BatchTask::STATUS_CANCELLED])
            ->delete();
    }

    /**
     * 获取租户任务统计
     * @param int $tenantId
     * @return array
     */
    public static function getTenantTaskStats(int $tenantId): array
    {
        $stats = BatchTask::where('tenant_id', $tenantId)
            ->field([
                'task_status',
                'COUNT(*) as count',
                'SUM(total_count) as total_accounts',
                'SUM(success_count) as success_accounts',
                'SUM(failed_count) as failed_accounts'
            ])
            ->group('task_status')
            ->select()
            ->toArray();
            
        $result = [
            'total_tasks' => 0,
            'running_tasks' => 0,
            'completed_tasks' => 0,
            'failed_tasks' => 0,
            'total_accounts' => 0,
            'success_accounts' => 0,
            'failed_accounts' => 0
        ];
        
        foreach ($stats as $stat) {
            $result['total_tasks'] += $stat['count'];
            $result['total_accounts'] += $stat['total_accounts'];
            $result['success_accounts'] += $stat['success_accounts'];
            $result['failed_accounts'] += $stat['failed_accounts'];
            
            switch ($stat['task_status']) {
                case BatchTask::STATUS_RUNNING:
                case BatchTask::STATUS_PENDING:
                    $result['running_tasks'] += $stat['count'];
                    break;
                case BatchTask::STATUS_COMPLETED:
                    $result['completed_tasks'] += $stat['count'];
                    break;
                case BatchTask::STATUS_FAILED:
                    $result['failed_tasks'] += $stat['count'];
                    break;
            }
        }
        
        return $result;
    }

    /**
     * 确保数据库连接正常
     * @return void
     */
    private static function ensureDatabaseConnection(): void
    {
        try {
            // 执行一个简单的查询来检查连接状态
            Db::query('SELECT 1');
        } catch (\Exception $e) {
            // 如果连接失败，强制重新连接
            Log::warning("数据库连接检查失败，尝试重新连接: " . $e->getMessage());
            try {
                // 关闭现有连接
                Db::disconnect();
                // 执行一个查询来触发重新连接
                Db::query('SELECT 1');
                Log::info("数据库重新连接成功");
            } catch (\Exception $reconnectE) {
                Log::error("数据库重新连接失败: " . $reconnectE->getMessage());
                throw $reconnectE;
            }
        }
    }

    /**
     * 检查异常是否为数据库连接错误
     * @param \Exception $e
     * @return bool
     */
    private static function isDatabaseConnectionError(\Exception $e): bool
    {
        $message = $e->getMessage();
        $connectionErrors = [
            'MySQL server has gone away',
            'Lost connection to MySQL server',
            'connection timed out',
            'Connection reset by peer',
            'Broken pipe',
            'No such file or directory'
        ];

        foreach ($connectionErrors as $error) {
            if (stripos($message, $error) !== false) {
                return true;
            }
        }

        return false;
    }
}
