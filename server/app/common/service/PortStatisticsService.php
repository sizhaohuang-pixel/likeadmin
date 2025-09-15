<?php
declare (strict_types = 1);

namespace app\common\service;

use app\common\model\AltAccount;
use app\common\model\package\PackageAssignment;

/**
 * 端口统计服务类
 * 统一管理所有端口相关的统计逻辑，确保数据一致性
 * Class PortStatisticsService
 * @package app\common\service
 */
class PortStatisticsService
{
    /**
     * @notes 获取租户端口统计信息
     * @param int $tenantId 租户ID
     * @return array
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public static function getTenantPortStats(int $tenantId): array
    {
        $currentTime = time();

        // 1. 计算端口总数（当前有效端口数量）
        $totalPorts = PackageAssignment::where('tenant_id', $tenantId)
            ->where('status', 1)
            ->where('expire_time', '>', $currentTime)
            ->sum('port_count') ?: 0;

        // 2. 计算已用端口数（使用新机制：基于package_id统计）
        $usedPorts = AltAccount::where('tenant_id', $tenantId)
            ->where('package_id', '>', 0)  // 使用package_id统计，确保精确性
            ->count();

        // 3. 计算空闲端口数（总端口 - 已用端口）
        $availablePorts = max(0, $totalPorts - $usedPorts);

        // 4. 计算过期端口数（已过期但状态仍为1的套餐端口数）
        $expiredPorts = PackageAssignment::where('tenant_id', $tenantId)
            ->where('status', 1)
            ->where('expire_time', '<=', $currentTime)
            ->sum('port_count') ?: 0;

        return [
            'total_ports' => (int)$totalPorts,
            'used_ports' => (int)$usedPorts,
            'available_ports' => (int)$availablePorts,
            'expired_ports' => (int)$expiredPorts,
        ];
    }

    /**
     * @notes 获取租户已用端口数
     * @param int $tenantId 租户ID
     * @return int
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public static function getTenantUsedPorts(int $tenantId): int
    {
        return AltAccount::where('tenant_id', $tenantId)
            ->where('package_id', '>', 0)  // 使用package_id统计，确保精确性
            ->count();
    }

    /**
     * @notes 获取租户有效端口总数
     * @param int $tenantId 租户ID
     * @return int
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public static function getTenantTotalPorts(int $tenantId): int
    {
        $result = PackageAssignment::where('tenant_id', $tenantId)
            ->where('status', 1)
            ->where('expire_time', '>', time())
            ->sum('port_count');
        return (int)($result ?: 0);
    }

    /**
     * @notes 获取租户可用端口数
     * @param int $tenantId 租户ID
     * @return int
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public static function getTenantAvailablePorts(int $tenantId): int
    {
        $totalPorts = self::getTenantTotalPorts($tenantId);
        $usedPorts = self::getTenantUsedPorts($tenantId);
        return max(0, $totalPorts - $usedPorts);
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
        $totalPorts = self::getTenantTotalPorts($tenantId);
        $usedPorts = self::getTenantUsedPorts($tenantId);
        $availablePorts = max(0, $totalPorts - $usedPorts);

        return [
            'available' => $availablePorts >= $needCount,
            'total_ports' => (int)$totalPorts,
            'used_ports' => (int)$usedPorts,
            'available_ports' => (int)$availablePorts,
            'need_ports' => $needCount,
        ];
    }

    /**
     * @notes 获取套餐的端口使用详情
     * @param int $packageId 套餐ID
     * @return array
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public static function getPackagePortDetails(int $packageId): array
    {
        // 获取套餐信息
        $package = PackageAssignment::find($packageId);
        if (!$package) {
            return [
                'port_total' => 0,
                'port_used' => 0,
                'port_free' => 0,
                'port_expired' => 0
            ];
        }

        $currentTime = time();
        $isExpired = $package->expire_time <= $currentTime;

        // 统计该套餐的端口使用情况
        $usedPorts = AltAccount::where('package_id', $packageId)->count();

        if ($isExpired) {
            return [
                'port_total' => $package->port_count,
                'port_used' => 0,
                'port_free' => $package->port_count,
                'port_expired' => $usedPorts
            ];
        } else {
            return [
                'port_total' => $package->port_count,
                'port_used' => $usedPorts,
                'port_free' => max(0, $package->port_count - $usedPorts),
                'port_expired' => 0
            ];
        }
    }

    /**
     * @notes 获取租户各套餐的端口分配详情
     * @param int $tenantId 租户ID
     * @return array
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public static function getTenantPackagePortDetails(int $tenantId): array
    {
        $usedPorts = self::getTenantUsedPorts($tenantId);
        return PackageAssignment::calculatePortAllocationDetails($tenantId, $usedPorts);
    }

    /**
     * @notes 验证端口统计数据的一致性
     * @param int $tenantId 租户ID
     * @return array
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public static function validatePortConsistency(int $tenantId): array
    {
        // 基于package_id的统计
        $packageBasedUsed = AltAccount::where('tenant_id', $tenantId)
            ->where('package_id', '>', 0)
            ->count();

        // 基于operator_id的统计（旧方式）
        $operatorBasedUsed = AltAccount::where('tenant_id', $tenantId)
            ->where('operator_id', '>', 0)
            ->count();

        // 各套餐的实际使用统计
        $packageDetails = self::getTenantPackagePortDetails($tenantId);
        $calculatedUsed = array_sum(array_column($packageDetails, 'port_used'));

        return [
            'package_based_used' => $packageBasedUsed,
            'operator_based_used' => $operatorBasedUsed,
            'calculated_used' => $calculatedUsed,
            'is_consistent' => ($packageBasedUsed === $calculatedUsed),
            'difference' => abs($packageBasedUsed - $operatorBasedUsed),
            'package_details' => $packageDetails
        ];
    }
}
