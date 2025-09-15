<?php
declare (strict_types = 1);

namespace app\adminapi\controller;

use app\adminapi\controller\BaseAdminController;
use app\common\service\PortStatisticsService;

/**
 * 端口统计验证控制器
 * 用于验证数据迁移后的一致性
 * Class PortValidationController
 * @package app\adminapi\controller
 */
class PortValidationController extends BaseAdminController
{
    /**
     * @notes 验证租户端口统计的一致性
     * @return \think\response\Json
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public function validateTenantPorts()
    {
        $tenantId = $this->request->get('tenant_id', 0);
        
        if (empty($tenantId)) {
            return $this->fail('请提供租户ID');
        }
        
        $validation = PortStatisticsService::validatePortConsistency($tenantId);
        
        return $this->data($validation);
    }

    /**
     * @notes 验证所有租户的端口统计一致性
     * @return \think\response\Json
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public function validateAllTenants()
    {
        // 获取所有有套餐的租户
        $tenantIds = \app\common\model\package\PackageAssignment::distinct()
            ->column('tenant_id');
        
        $results = [];
        $inconsistentCount = 0;
        
        foreach ($tenantIds as $tenantId) {
            $validation = PortStatisticsService::validatePortConsistency($tenantId);
            
            if (!$validation['is_consistent']) {
                $inconsistentCount++;
            }
            
            $results[] = [
                'tenant_id' => $tenantId,
                'is_consistent' => $validation['is_consistent'],
                'package_based_used' => $validation['package_based_used'],
                'operator_based_used' => $validation['operator_based_used'],
                'difference' => $validation['difference']
            ];
        }
        
        return $this->data([
            'total_tenants' => count($tenantIds),
            'inconsistent_count' => $inconsistentCount,
            'consistency_rate' => count($tenantIds) > 0 ? round((count($tenantIds) - $inconsistentCount) / count($tenantIds) * 100, 2) : 100,
            'details' => $results
        ]);
    }

    /**
     * @notes 获取租户端口统计详情
     * @return \think\response\Json
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public function getTenantPortDetails()
    {
        $tenantId = $this->request->get('tenant_id', 0);
        
        if (empty($tenantId)) {
            return $this->fail('请提供租户ID');
        }
        
        $portStats = PortStatisticsService::getTenantPortStats($tenantId);
        $packageDetails = PortStatisticsService::getTenantPackagePortDetails($tenantId);
        
        return $this->data([
            'tenant_id' => $tenantId,
            'port_stats' => $portStats,
            'package_details' => $packageDetails
        ]);
    }
}
