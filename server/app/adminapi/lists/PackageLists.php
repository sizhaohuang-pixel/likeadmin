<?php
declare (strict_types = 1);

namespace app\adminapi\lists;

use app\adminapi\lists\BaseAdminDataLists;
use app\common\lists\ListsSearchInterface;
use app\common\lists\ListsSortInterface;
use app\common\lists\ListsExcelInterface;
use app\common\model\package\PackageAssignment;
use app\common\model\auth\Admin;
use app\common\service\PortStatisticsService;

/**
 * 套餐分配列表
 * Class PackageLists
 * @package app\adminapi\lists
 */
class PackageLists extends BaseAdminDataLists implements ListsSearchInterface, ListsSortInterface, ListsExcelInterface
{
    /**
     * @notes 设置搜索条件
     * @return array
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public function setSearch(): array
    {
        return [
            '=' => ['agent_id', 'tenant_id', 'status'],
            '%like%' => ['remark'],
        ];
    }

    /**
     * @notes 设置支持排序字段
     * @return array
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public function setSortFields(): array
    {
        return [
            'create_time' => 'create_time',
            'assign_time' => 'assign_time',
            'expire_time' => 'expire_time',
            'port_count' => 'port_count',
            'id' => 'id'
        ];
    }

    /**
     * @notes 设置默认排序
     * @return array
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public function setDefaultOrder(): array
    {
        return ['create_time' => 'desc', 'id' => 'desc'];
    }

    /**
     * @notes 设置导出字段
     * @return array
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public function setExcelFields(): array
    {
        return [
            'id' => 'ID',
            'agent_name' => '代理商',
            'tenant_name' => '租户',
            'port_count' => '端口数量',
            'assign_time' => '分配时间',
            'expire_time' => '到期时间',
            'status_text' => '状态',
            'remaining_days' => '剩余天数',
            'remark' => '备注',
        ];
    }

    /**
     * @notes 设置导出文件名
     * @return string
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public function setFileName(): string
    {
        return '套餐分配记录_' . date('YmdHis');
    }

    /**
     * @notes 自定义查询条件
     * @return array
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public function queryWhere(): array
    {
        $where = [];

        // 权限过滤：非root用户只能查看自己分配的记录
        $currentAdmin = Admin::findOrEmpty($this->adminId);
        if ($currentAdmin->root != 1) {
            $where[] = ['agent_id', '=', $this->adminId];
        }

        // 时间范围搜索
        if (!empty($this->params['start_time']) && !empty($this->params['end_time'])) {
            $startTime = strtotime($this->params['start_time']);
            $endTime = strtotime($this->params['end_time'] . ' 23:59:59');
            $where[] = ['assign_time', 'between', [$startTime, $endTime]];
        }

        // 到期状态筛选
        if (isset($this->params['expire_status'])) {
            $currentTime = time();
            switch ($this->params['expire_status']) {
                case 'valid':
                    // 有效套餐
                    $where[] = ['status', '=', 1];
                    $where[] = ['expire_time', '>', $currentTime];
                    break;
                case 'expired':
                    // 已过期套餐
                    $where[] = ['expire_time', '<=', $currentTime];
                    break;
                case 'expiring_soon':
                    // 即将过期（7天内）
                    $sevenDaysLater = $currentTime + (7 * 24 * 60 * 60);
                    $where[] = ['status', '=', 1];
                    $where[] = ['expire_time', '>', $currentTime];
                    $where[] = ['expire_time', '<=', $sevenDaysLater];
                    break;
            }
        }

        // 租户筛选
        if (!empty($this->params['tenant_id'])) {
            $where[] = ['tenant_id', '=', $this->params['tenant_id']];
        }

        // 代理商筛选（仅root用户可用）
        if (!empty($this->params['agent_id']) && $currentAdmin->root == 1) {
            $where[] = ['agent_id', '=', $this->params['agent_id']];
        }

        // 套餐状态筛选
        if (isset($this->params['status']) && $this->params['status'] !== '') {
            $where[] = ['status', '=', $this->params['status']];
        }

        // 端口数量范围筛选
        if (!empty($this->params['port_count_min'])) {
            $where[] = ['port_count', '>=', $this->params['port_count_min']];
        }
        if (!empty($this->params['port_count_max'])) {
            $where[] = ['port_count', '<=', $this->params['port_count_max']];
        }

        // 剩余天数筛选
        if (isset($this->params['remaining_days'])) {
            $currentTime = time();
            $targetTime = $currentTime + ($this->params['remaining_days'] * 24 * 60 * 60);

            switch ($this->params['remaining_days_operator']) {
                case 'lt': // 小于
                    $where[] = ['expire_time', '<', $targetTime];
                    $where[] = ['expire_time', '>', $currentTime]; // 确保未过期
                    break;
                case 'lte': // 小于等于
                    $where[] = ['expire_time', '<=', $targetTime];
                    $where[] = ['expire_time', '>', $currentTime];
                    break;
                case 'gt': // 大于
                    $where[] = ['expire_time', '>', $targetTime];
                    break;
                case 'gte': // 大于等于
                    $where[] = ['expire_time', '>=', $targetTime];
                    break;
                case 'eq': // 等于（当天）
                default:
                    $dayStart = $targetTime - (24 * 60 * 60);
                    $where[] = ['expire_time', 'between', [$dayStart, $targetTime]];
                    break;
            }
        }

        // 分配时间范围筛选
        if (!empty($this->params['assign_start_time']) && !empty($this->params['assign_end_time'])) {
            $assignStartTime = strtotime($this->params['assign_start_time']);
            $assignEndTime = strtotime($this->params['assign_end_time'] . ' 23:59:59');
            $where[] = ['assign_time', 'between', [$assignStartTime, $assignEndTime]];
        }

        // 到期时间范围筛选
        if (!empty($this->params['expire_start_time']) && !empty($this->params['expire_end_time'])) {
            $expireStartTime = strtotime($this->params['expire_start_time']);
            $expireEndTime = strtotime($this->params['expire_end_time'] . ' 23:59:59');
            $where[] = ['expire_time', 'between', [$expireStartTime, $expireEndTime]];
        }

        // 备注关键词搜索
        if (!empty($this->params['remark_keyword'])) {
            $where[] = ['remark', 'like', '%' . $this->params['remark_keyword'] . '%'];
        }

        // 套餐ID筛选（支持多个）
        if (!empty($this->params['package_ids'])) {
            $packageIds = is_array($this->params['package_ids'])
                ? $this->params['package_ids']
                : explode(',', $this->params['package_ids']);
            $where[] = ['id', 'in', $packageIds];
        }

        return $where;
    }

    /**
     * @notes 获取套餐分配列表
     * @return array
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public function lists(): array
    {
        $lists = PackageAssignment::with(['agent', 'tenant'])
            ->where($this->searchWhere)
            ->where($this->queryWhere())
            ->field([
                'id', 'agent_id', 'tenant_id', 'port_count',
                'assign_time', 'expire_time', 'status', 'remark',
                'create_time', 'update_time'
            ])
            ->limit($this->limitOffset, $this->limitLength)
            ->order($this->sortOrder)
            ->select()
            ->toArray();

        // 数据处理
        foreach ($lists as &$item) {
            // 代理商信息
            $item['agent_name'] = $item['agent']['name'] ?? '未知';
            $item['agent_account'] = $item['agent']['account'] ?? '';
            
            // 租户信息
            $item['tenant_name'] = $item['tenant']['name'] ?? '未知';
            $item['tenant_account'] = $item['tenant']['account'] ?? '';
            
            // 时间格式化 - 现在时间戳是整数，可以正常格式化
            $item['assign_time_text'] = $item['assign_time'] ? date('Y-m-d H:i:s', $item['assign_time']) : '';
            $item['expire_time_text'] = $item['expire_time'] ? date('Y-m-d H:i:s', $item['expire_time']) : '';
            $item['create_time_text'] = $item['create_time'] ? date('Y-m-d H:i:s', $item['create_time']) : '';

            // 状态文本
            $item['status_text'] = $this->getStatusText($item['status'], $item['expire_time']);

            // 剩余天数
            $item['remaining_days'] = $this->getRemainingDays($item['expire_time']);

            // 是否即将过期（7天内）
            $item['is_expiring_soon'] = $this->isExpiringSoon($item['expire_time']);

            // 端口统计信息
            $portStats = $this->getPortStatistics($item['id'], $item['tenant_id'], $item['expire_time'], $item['port_count']);
            $item['port_total'] = $item['port_count']; // 端口总数
            $item['port_used'] = $portStats['used']; // 已用端口
            $item['port_free'] = $portStats['free']; // 空闲端口
            $item['port_expired'] = $portStats['expired']; // 过期端口

            // 清理关联数据
            unset($item['agent'], $item['tenant']);
        }

        return $lists;
    }

    /**
     * @notes 获取套餐分配数量
     * @return int
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public function count(): int
    {
        return PackageAssignment::where($this->searchWhere)
            ->where($this->queryWhere())
            ->count();
    }

    /**
     * @notes 获取状态文本
     * @param int $status
     * @param int $expireTime
     * @return string
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    private function getStatusText(int $status, int $expireTime): string
    {
        if ($status == 0) {
            return '已过期';
        }
        
        if ($expireTime > 0 && $expireTime <= time()) {
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
    private function getRemainingDays(int $expireTime): int
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
     * @notes 是否即将过期
     * @param int $expireTime
     * @return bool
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    private function isExpiringSoon(int $expireTime): bool
    {
        if ($expireTime <= 0) {
            return false;
        }
        
        $currentTime = time();
        $sevenDaysLater = $currentTime + (7 * 24 * 60 * 60);
        
        return $expireTime > $currentTime && $expireTime <= $sevenDaysLater;
    }

    /**
     * @notes 获取统计信息
     * @return array
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public function getStatistics(): array
    {
        $query = PackageAssignment::where($this->searchWhere)
            ->where($this->queryWhere());

        $currentTime = time();
        $sevenDaysLater = $currentTime + (7 * 24 * 60 * 60);

        return [
            'total_count' => $query->count(),
            'total_ports' => $query->sum('port_count'),
            'valid_count' => $query->where('status', 1)->where('expire_time', '>', $currentTime)->count(),
            'valid_ports' => $query->where('status', 1)->where('expire_time', '>', $currentTime)->sum('port_count'),
            'expired_count' => $query->where('expire_time', '<=', $currentTime)->count(),
            'expired_ports' => $query->where('expire_time', '<=', $currentTime)->sum('port_count'),
            'expiring_soon_count' => $query->where('status', 1)
                ->where('expire_time', '>', $currentTime)
                ->where('expire_time', '<=', $sevenDaysLater)
                ->count(),
            'expiring_soon_ports' => $query->where('status', 1)
                ->where('expire_time', '>', $currentTime)
                ->where('expire_time', '<=', $sevenDaysLater)
                ->sum('port_count'),
        ];
    }

    /**
     * @notes 获取套餐端口统计信息（按优先级分配）
     * @param int $packageId 套餐ID
     * @param int $tenantId 租户ID
     * @param int $expireTime 套餐到期时间
     * @param int $totalPorts 套餐总端口数
     * @return array
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    private function getPortStatistics(int $packageId, int $tenantId, int $expireTime, int $totalPorts): array
    {
        $currentTime = time();
        $isExpired = $expireTime <= $currentTime;

        // 获取该租户的总体端口使用情况（使用统一的服务类）
        $tenantUsedPorts = PortStatisticsService::getTenantUsedPorts($tenantId);

        if ($isExpired) {
            // 套餐已过期
            return [
                'used' => 0,
                'free' => $totalPorts,
                'expired' => 0 // 过期套餐不显示过期端口，避免重复计算
            ];
        } else {
            // 套餐未过期，使用优先级分配算法
            $allocationDetails = \app\common\model\package\PackageAssignment::calculatePortAllocationDetails($tenantId, $tenantUsedPorts);

            // 查找当前套餐的分配详情
            $currentPackageDetail = null;
            foreach ($allocationDetails as $detail) {
                if ($detail['package_id'] == $packageId) {
                    $currentPackageDetail = $detail;
                    break;
                }
            }

            if ($currentPackageDetail) {
                return [
                    'used' => $currentPackageDetail['port_used'],
                    'free' => $currentPackageDetail['port_free'],
                    'expired' => 0
                ];
            } else {
                // 如果没有找到详情，说明该套餐完全空闲
                return [
                    'used' => 0,
                    'free' => $totalPorts,
                    'expired' => 0
                ];
            }
        }
    }
}
