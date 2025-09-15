<?php
declare (strict_types = 1);

namespace app\common\model\package;

use app\common\model\BaseModel;
use app\common\model\auth\Admin;

/**
 * 小号分配记录模型
 * Class AltAccountAssignment
 * @package app\common\model\package
 */
class AltAccountAssignment extends BaseModel
{
    protected $name = 'alt_account_assignment';
    protected $pk = 'alt_account_id';

    // 设置时间字段类型为整数，避免自动格式化
    protected $type = [
        'assign_time' => 'integer',
        'create_time' => 'integer',
        'update_time' => 'integer',
    ];

    /**
     * @notes 关联租户信息
     * @return \think\model\relation\BelongsTo
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public function tenant()
    {
        return $this->belongsTo(Admin::class, 'tenant_id', 'id');
    }

    /**
     * @notes 关联客服信息
     * @return \think\model\relation\BelongsTo
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public function operator()
    {
        return $this->belongsTo(Admin::class, 'operator_id', 'id');
    }

    // 移除时间获取器，避免在业务逻辑中重复格式化导致错误
    // 时间格式化应该在具体的展示层处理

    /**
     * @notes 搜索器 - 租户ID
     * @param $query
     * @param $value
     * @param $data
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public function searchTenantIdAttr($query, $value, $data)
    {
        if ($value) {
            $query->where('tenant_id', $value);
        }
    }

    /**
     * @notes 搜索器 - 客服ID
     * @param $query
     * @param $value
     * @param $data
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public function searchOperatorIdAttr($query, $value, $data)
    {
        if ($value) {
            $query->where('operator_id', $value);
        }
    }

    /**
     * @notes 搜索器 - 小号ID数组
     * @param $query
     * @param $value
     * @param $data
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public function searchAltAccountIdsAttr($query, $value, $data)
    {
        if ($value && is_array($value)) {
            $query->whereIn('alt_account_id', $value);
        }
    }

    /**
     * @notes 获取租户已用端口数（已分配小号数量）
     * @param int $tenantId 租户ID
     * @return int
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public static function getTenantUsedPorts(int $tenantId): int
    {
        return self::where('tenant_id', $tenantId)->count();
    }

    /**
     * @notes 获取客服已分配的小号数量
     * @param int $operatorId 客服ID
     * @return int
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public static function getOperatorAssignedCount(int $operatorId): int
    {
        return self::where('operator_id', $operatorId)->count();
    }

    /**
     * @notes 批量分配小号给客服
     * @param array $altAccountIds 小号ID数组
     * @param int $tenantId 租户ID
     * @param int $operatorId 客服ID
     * @return bool
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public static function batchAssign(array $altAccountIds, int $tenantId, int $operatorId): bool
    {
        if (empty($altAccountIds)) {
            return false;
        }

        $time = time();
        $data = [];
        
        // 生成优先级（同一秒内的分配按顺序生成优先级）
        foreach ($altAccountIds as $index => $altAccountId) {
            $data[] = [
                'alt_account_id' => $altAccountId,
                'tenant_id' => $tenantId,
                'operator_id' => $operatorId,
                'assign_time' => $time,
                'priority' => $index,
                'create_time' => $time,
                'update_time' => $time,
            ];
        }

        return self::insertAll($data) !== false;
    }

    /**
     * @notes 批量释放小号分配
     * @param array $altAccountIds 小号ID数组
     * @return bool
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public static function batchRelease(array $altAccountIds): bool
    {
        if (empty($altAccountIds)) {
            return false;
        }

        return self::whereIn('alt_account_id', $altAccountIds)->delete() !== false;
    }

    /**
     * @notes 获取需要释放的小号（按优先级排序）
     * @param int $tenantId 租户ID
     * @param int $releaseCount 需要释放的数量
     * @return array
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public static function getAccountsToRelease(int $tenantId, int $releaseCount): array
    {
        if ($releaseCount <= 0) {
            return [];
        }

        return self::where('tenant_id', $tenantId)
                   ->order('assign_time', 'asc')
                   ->order('priority', 'asc')
                   ->order('alt_account_id', 'asc')
                   ->limit($releaseCount)
                   ->column('alt_account_id');
    }

    /**
     * @notes 智能释放超出的端口分配
     * @param int $tenantId 租户ID
     * @param int $maxPorts 最大允许端口数
     * @return int 释放的数量
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public static function smartRelease(int $tenantId, int $maxPorts): int
    {
        $usedPorts = self::getTenantUsedPorts($tenantId);
        
        if ($usedPorts <= $maxPorts) {
            return 0; // 无需释放
        }

        $releaseCount = $usedPorts - $maxPorts;
        $accountsToRelease = self::getAccountsToRelease($tenantId, $releaseCount);
        
        if (empty($accountsToRelease)) {
            return 0;
        }

        $result = self::batchRelease($accountsToRelease);
        return $result ? count($accountsToRelease) : 0;
    }

    /**
     * @notes 检查小号是否已分配
     * @param int $altAccountId 小号ID
     * @return bool
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public static function isAssigned(int $altAccountId): bool
    {
        return self::where('alt_account_id', $altAccountId)->count() > 0;
    }

    /**
     * @notes 检查小号数组中哪些已分配
     * @param array $altAccountIds 小号ID数组
     * @return array 已分配的小号ID数组
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public static function getAssignedAccounts(array $altAccountIds): array
    {
        if (empty($altAccountIds)) {
            return [];
        }

        return self::whereIn('alt_account_id', $altAccountIds)
                   ->column('alt_account_id');
    }
}
