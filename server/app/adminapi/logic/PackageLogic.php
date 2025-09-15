<?php
declare (strict_types = 1);

namespace app\adminapi\logic;

use app\common\logic\BaseLogic;
use app\common\model\package\PackageAssignment;
use app\common\model\package\AltAccountAssignment;
use app\common\model\auth\Admin;
use app\common\service\auth\AgentAdminService;
use think\facade\Db;

/**
 * 套餐分配逻辑
 * Class PackageLogic
 * @package app\adminapi\logic
 */
class PackageLogic extends BaseLogic
{
    /**
     * @notes 分配套餐
     * @param array $params
     * @param int $currentAdminId 当前操作的管理员ID
     * @return bool
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public static function assign(array $params, int $currentAdminId = 0): bool
    {
        Db::startTrans();
        try {
            // 验证代理商身份和权限
            $validateResult = self::validateAgentPermission($currentAdminId, $params['tenant_id']);
            if ($validateResult !== true) {
                throw new \Exception($validateResult);
            }

            // 计算到期时间
            $expireTime = self::calculateExpireTime($params['expire_days']);

            // 创建套餐分配记录
            PackageAssignment::create([
                'agent_id' => $currentAdminId,
                'tenant_id' => $params['tenant_id'],
                'port_count' => $params['port_count'],
                'assign_time' => time(),
                'expire_time' => $expireTime,
                'status' => 1,
                'remark' => $params['remark'] ?? '',
                'create_time' => time(),
                'update_time' => time(),
            ]);

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 获取租户套餐信息（虚拟端口池版本）
     * @param int $tenantId 租户ID
     * @param int $currentAdminId 当前操作的管理员ID
     * @return array
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public static function getTenantPackages(int $tenantId, int $currentAdminId = 0): array
    {
        try {
            // 验证权限
            $validateResult = self::validateTenantAccess($currentAdminId, $tenantId);
            if ($validateResult !== true) {
                throw new \Exception($validateResult);
            }

            // 计算虚拟端口池状态
            $portPoolStatus = self::calculatePortPoolStatus($tenantId);

            // 获取套餐列表
            $packages = PackageAssignment::with(['agent'])
                ->where('tenant_id', $tenantId)
                ->order('create_time', 'desc')
                ->select()
                ->toArray();

            // 获取已分配的小号列表
            $assignedAccounts = AltAccountAssignment::with(['operator'])
                ->where('tenant_id', $tenantId)
                ->order('assign_time', 'asc')
                ->select()
                ->toArray();

            return [
                'port_pool_status' => $portPoolStatus,
                'packages' => $packages,
                'assigned_accounts' => $assignedAccounts,
            ];
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return [];
        }
    }

    /**
     * @notes 分配小号给客服
     * @param array $params
     * @param int $currentAdminId 当前操作的管理员ID
     * @return bool
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public static function assignAltAccount(array $params, int $currentAdminId = 0): bool
    {
        Db::startTrans();
        try {
            $altAccountIds = $params['alt_account_ids'];
            $operatorId = $params['operator_id'];

            // 验证权限（当前用户必须是租户）
            $validateResult = self::validateTenantPermission($currentAdminId);
            if ($validateResult !== true) {
                throw new \Exception($validateResult);
            }

            // 验证客服权限：客服必须是当前租户的下级
            $validateOperatorResult = self::validateOperatorPermission($currentAdminId, $operatorId);
            if ($validateOperatorResult !== true) {
                throw new \Exception($validateOperatorResult);
            }

            // 检查端口可用性
            $availabilityResult = self::checkPortAvailability($currentAdminId, count($altAccountIds));
            if (!$availabilityResult['available']) {
                throw new \Exception('端口不足，当前可用端口：' . $availabilityResult['available_ports'] . '个，需要：' . count($altAccountIds) . '个');
            }

            // 检查客服的账号分配上限（使用悲观锁确保并发安全）
            $canAssign = \app\common\model\auth\Admin::canAssignAccounts($operatorId, count($altAccountIds), true);
            if (!$canAssign['can_assign']) {
                throw new \Exception($canAssign['message']);
            }

            // 检查小号是否已分配
            $assignedAccounts = AltAccountAssignment::getAssignedAccounts($altAccountIds);
            if (!empty($assignedAccounts)) {
                throw new \Exception('以下小号已被分配：' . implode(',', $assignedAccounts));
            }

            // 批量分配小号
            $result = AltAccountAssignment::batchAssign($altAccountIds, $currentAdminId, $operatorId);
            if (!$result) {
                throw new \Exception('分配失败');
            }

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 检查端口可用性
     * @param int $tenantId 租户ID
     * @param int $needCount 需要的端口数
     * @return array
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public static function checkPortAvailability(int $tenantId, int $needCount): array
    {
        try {
            $portPoolStatus = self::calculatePortPoolStatus($tenantId);
            
            $available = $portPoolStatus['available_ports'] >= $needCount;
            
            return [
                'available' => $available,
                'total_ports' => $portPoolStatus['total_ports'],
                'used_ports' => $portPoolStatus['used_ports'],
                'available_ports' => $portPoolStatus['available_ports'],
                'need_ports' => $needCount,
                'can_assign' => $available,
            ];
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return [
                'available' => false,
                'total_ports' => 0,
                'used_ports' => 0,
                'available_ports' => 0,
                'need_ports' => $needCount,
                'can_assign' => false,
            ];
        }
    }

    /**
     * @notes 获取分配历史
     * @param array $params
     * @param int $currentAdminId 当前操作的管理员ID
     * @return array
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public static function getAssignHistory(array $params, int $currentAdminId = 0): array
    {
        try {
            $query = PackageAssignment::with(['tenant', 'agent']);

            // 根据用户身份过滤数据
            $currentAdmin = Admin::findOrEmpty($currentAdminId);
            if ($currentAdmin->root != 1) {
                // 非root用户只能查看自己分配的记录
                $query->where('agent_id', $currentAdminId);
            }

            // 条件筛选
            if (!empty($params['tenant_id'])) {
                $query->where('tenant_id', $params['tenant_id']);
            }

            // 修复status参数检查：支持status为0的情况
            if (isset($params['status']) && $params['status'] !== '' && $params['status'] !== null) {
                $query->where('status', $params['status']);
            }

            if (!empty($params['time_range']) && is_array($params['time_range'])) {
                $startTime = strtotime($params['time_range'][0]);
                $endTime = strtotime($params['time_range'][1] . ' 23:59:59');
                $query->whereBetween('assign_time', [$startTime, $endTime]);
            }

            $list = $query->order('create_time', 'desc')
                         ->paginate([
                             'list_rows' => $params['limit'] ?? 20,
                             'page' => $params['page'] ?? 1,
                         ]);

            $result = $list->toArray();

            // 格式化时间字段
            if (!empty($result['data'])) {
                foreach ($result['data'] as &$item) {
                    // 强制转换时间戳为整数，然后格式化
                    $assignTime = is_numeric($item['assign_time']) ? (int)$item['assign_time'] : strtotime($item['assign_time']);
                    $expireTime = is_numeric($item['expire_time']) ? (int)$item['expire_time'] : strtotime($item['expire_time']);
                    $createTime = is_numeric($item['create_time']) ? (int)$item['create_time'] : strtotime($item['create_time']);
                    $updateTime = is_numeric($item['update_time']) ? (int)$item['update_time'] : strtotime($item['update_time']);

                    // 时间格式化
                    $item['assign_time_text'] = $assignTime ? date('Y-m-d H:i:s', $assignTime) : '';
                    $item['expire_time_text'] = $expireTime ? date('Y-m-d H:i:s', $expireTime) : '';
                    $item['create_time_text'] = $createTime ? date('Y-m-d H:i:s', $createTime) : '';
                    $item['update_time_text'] = $updateTime ? date('Y-m-d H:i:s', $updateTime) : '';

                    // 状态文本
                    $item['status_text'] = self::getStatusText((int)$item['status'], $expireTime);

                    // 剩余天数
                    $item['remaining_days'] = self::getRemainingDays($expireTime);

                    // 是否即将过期（7天内）
                    $item['is_expiring_soon'] = self::isExpiringSoon($expireTime);

                    // 代理商和租户信息处理
                    if (isset($item['agent'])) {
                        $item['agent_name'] = $item['agent']['name'] ?? '';
                        $item['agent_account'] = $item['agent']['account'] ?? '';
                    }

                    if (isset($item['tenant'])) {
                        $item['tenant_name'] = $item['tenant']['name'] ?? '';
                        $item['tenant_account'] = $item['tenant']['account'] ?? '';
                    }
                }
            }

            return $result;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return [];
        }
    }

    /**
     * @notes 计算虚拟端口池状态（核心算法）- 使用统一的服务类
     * @param int $tenantId 租户ID
     * @return array
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public static function calculatePortPoolStatus(int $tenantId): array
    {
        // 使用统一的端口统计服务
        $portStats = \app\common\service\PortStatisticsService::getTenantPortStats($tenantId);

        // 计算即将过期的端口数（7天内）
        $expiringSoon = PackageAssignment::getTenantExpiringSoonPorts($tenantId);

        return [
            'total_ports' => $portStats['total_ports'],
            'used_ports' => $portStats['used_ports'],
            'available_ports' => $portStats['available_ports'],
            'expiring_soon' => $expiringSoon,
        ];
    }

    /**
     * @notes 处理套餐过期（定时任务调用）
     * @return array
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public static function handleExpiredPackages(): array
    {
        Db::startTrans();
        try {
            // 更新过期套餐状态
            $expiredCount = PackageAssignment::updateExpiredPackages();

            // 获取所有受影响的租户
            $affectedTenants = PackageAssignment::where('status', 0)
                ->where('expire_time', '<=', time())
                ->where('expire_time', '>', time() - 3600) // 最近1小时过期的
                ->group('tenant_id')
                ->column('tenant_id');

            $releasedCount = 0;
            $releasedDetails = [];

            // 处理每个受影响租户的端口释放
            foreach ($affectedTenants as $tenantId) {
                $portStatus = self::calculatePortPoolStatus($tenantId);

                // 如果已用端口超过总端口，需要智能释放
                if ($portStatus['used_ports'] > $portStatus['total_ports']) {
                    $maxPorts = $portStatus['total_ports'];
                    $released = AltAccountAssignment::smartRelease($tenantId, $maxPorts);

                    if ($released > 0) {
                        $releasedCount += $released;
                        $releasedDetails[] = [
                            'tenant_id' => $tenantId,
                            'released_count' => $released,
                            'remaining_ports' => $maxPorts,
                        ];
                    }
                }
            }

            Db::commit();

            return [
                'expired_packages' => $expiredCount,
                'released_accounts' => $releasedCount,
                'affected_tenants' => count($affectedTenants),
                'release_details' => $releasedDetails,
            ];
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return [
                'expired_packages' => 0,
                'released_accounts' => 0,
                'affected_tenants' => 0,
                'release_details' => [],
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * @notes 验证代理商权限
     * @param int $agentId 代理商ID
     * @param int $tenantId 租户ID
     * @return string|true
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    private static function validateAgentPermission(int $agentId, int $tenantId)
    {
        // 检查代理商是否存在
        $agent = Admin::findOrEmpty($agentId);
        if ($agent->isEmpty()) {
            return '代理商不存在';
        }

        // 检查代理商状态
        if ($agent->disable == 1) {
            return '代理商已被禁用';
        }

        // 检查租户是否存在
        $tenant = Admin::findOrEmpty($tenantId);
        if ($tenant->isEmpty()) {
            return '租户不存在';
        }

        // 检查租户状态
        if ($tenant->disable == 1) {
            return '租户已被禁用';
        }

        // 检查层级关系：租户必须是代理商的下级（root用户除外）
        if ($agent->root != 1 && $tenant->parent_id != $agentId) {
            return '您只能为自己的下级租户分配套餐';
        }

        return true;
    }

    /**
     * @notes 验证租户访问权限
     * @param int $currentAdminId 当前管理员ID
     * @param int $tenantId 租户ID
     * @return string|true
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    private static function validateTenantAccess(int $currentAdminId, int $tenantId)
    {
        $currentAdmin = Admin::findOrEmpty($currentAdminId);
        if ($currentAdmin->isEmpty()) {
            return '用户不存在';
        }

        // root用户可以查看所有租户
        if ($currentAdmin->root == 1) {
            return true;
        }

        // 代理商只能查看自己的下级租户
        // 租户只能查看自己的信息
        if ($currentAdminId == $tenantId) {
            return true; // 查看自己的信息
        }

        $tenant = Admin::findOrEmpty($tenantId);
        if ($tenant->isEmpty()) {
            return '租户不存在';
        }

        if ($tenant->parent_id == $currentAdminId) {
            return true; // 查看下级租户
        }

        return '您没有权限查看该租户信息';
    }

    /**
     * @notes 验证租户权限
     * @param int $tenantId 租户ID
     * @return string|true
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    private static function validateTenantPermission(int $tenantId)
    {
        $tenant = Admin::findOrEmpty($tenantId);
        if ($tenant->isEmpty()) {
            return '租户不存在';
        }

        // 检查租户状态
        if ($tenant->disable == 1) {
            return '租户已被禁用';
        }

        return true;
    }

    /**
     * @notes 验证客服权限（客服必须是租户的下级）
     * @param int $tenantId 租户ID
     * @param int $operatorId 客服ID
     * @return string|true
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    private static function validateOperatorPermission(int $tenantId, int $operatorId)
    {
        // 检查租户是否存在
        $tenant = Admin::findOrEmpty($tenantId);
        if ($tenant->isEmpty()) {
            return '租户不存在';
        }

        // 检查客服是否存在
        $operator = Admin::findOrEmpty($operatorId);
        if ($operator->isEmpty()) {
            return '客服不存在';
        }

        // 检查客服状态
        if ($operator->disable == 1) {
            return '客服已被禁用';
        }

        // 检查层级关系：客服必须是租户的下级
        if ($operator->parent_id != $tenantId) {
            return '您只能为自己的下级客服分配小号';
        }

        return true;
    }

    /**
     * @notes 套餐续费
     * @param array $params
     * @param int $currentAdminId 当前操作的管理员ID
     * @return bool
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public static function renew(array $params, int $currentAdminId = 0): bool
    {
        Db::startTrans();
        try {
            $packageId = $params['package_id'];
            $extendDays = $params['extend_days'];

            // 查找套餐记录
            $package = PackageAssignment::findOrEmpty($packageId);
            if ($package->isEmpty()) {
                throw new \Exception('套餐记录不存在');
            }

            // 权限验证：只有代理商可以为自己分配的套餐续费
            if ($package->agent_id != $currentAdminId) {
                throw new \Exception('您没有权限为该套餐续费');
            }

            // 计算新的到期时间
            $currentExpireTime = $package->expire_time;
            $baseTime = max($currentExpireTime, time()); // 如果已过期，从当前时间开始计算
            $newExpireTime = $baseTime + ($extendDays * 24 * 60 * 60);

            // 更新套餐信息
            PackageAssignment::where('id', $packageId)->update([
                'expire_time' => $newExpireTime,
                'status' => 1, // 确保状态为有效
                'update_time' => time(),
                'remark' => ($package->remark ? $package->remark . ' | ' : '') . "续费{$extendDays}天"
            ]);

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 批量续费套餐
     * @param array $params
     * @param int $currentAdminId 当前操作的管理员ID
     * @return bool
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public static function batchRenew(array $params, int $currentAdminId = 0): bool
    {
        Db::startTrans();
        try {
            $packageIds = $params['package_ids'];
            $extendDays = $params['extend_days'];
            $renewedCount = 0;

            foreach ($packageIds as $packageId) {
                $renewParams = [
                    'package_id' => $packageId,
                    'extend_days' => $extendDays
                ];

                if (self::renew($renewParams, $currentAdminId)) {
                    $renewedCount++;
                }
            }

            if ($renewedCount == 0) {
                throw new \Exception('没有套餐续费成功');
            }

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 计算到期时间
     * @param int $expireDays 有效天数
     * @return int
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    private static function calculateExpireTime(int $expireDays): int
    {
        return time() + ($expireDays * 24 * 60 * 60);
    }

    /**
     * @notes 获取状态文本
     * @param int $status
     * @param int $expireTime
     * @return string
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    private static function getStatusText(int $status, int $expireTime): string
    {
        if ($status == 0) {
            return '已过期';
        }

        if ($expireTime > 0 && $expireTime < time()) {
            return '已过期';
        }

        return '有效';
    }

    /**
     * @notes 获取剩余天数
     * @param int $expireTime
     * @return int
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    private static function getRemainingDays(int $expireTime): int
    {
        if ($expireTime <= 0) {
            return 0;
        }

        $remainingSeconds = $expireTime - time();
        if ($remainingSeconds <= 0) {
            return 0;
        }

        return (int)ceil($remainingSeconds / 86400);
    }

    /**
     * @notes 判断是否即将过期（7天内）
     * @param int $expireTime
     * @return bool
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    private static function isExpiringSoon(int $expireTime): bool
    {
        if ($expireTime <= 0) {
            return false;
        }

        $currentTime = time();
        $sevenDaysLater = $currentTime + (7 * 24 * 60 * 60);

        return $expireTime > $currentTime && $expireTime <= $sevenDaysLater;
    }
}
