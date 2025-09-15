<?php
declare (strict_types = 1);

namespace app\common\model\package;

use app\common\model\BaseModel;
use app\common\model\auth\Admin;
use think\model\concern\SoftDelete;

/**
 * 套餐分配记录模型
 * Class PackageAssignment
 * @package app\common\model\package
 */
class PackageAssignment extends BaseModel
{
    use SoftDelete;

    protected $name = 'package_assignment';
    protected $deleteTime = 'delete_time';

    // 设置时间字段类型为整数，避免自动格式化
    protected $type = [
        'assign_time' => 'integer',
        'expire_time' => 'integer',
        'create_time' => 'integer',
        'update_time' => 'integer',
    ];

    /**
     * @notes 关联代理商信息
     * @return \think\model\relation\BelongsTo
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public function agent()
    {
        return $this->belongsTo(Admin::class, 'agent_id', 'id');
    }

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

    // 移除时间获取器，避免在Lists中重复格式化导致错误
    // 时间格式化应该在具体的业务逻辑中处理，而不是在模型层

    /**
     * @notes 状态获取器 - 状态文本显示
     * @param $value
     * @return string
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public function getStatusTextAttr($value, $data)
    {
        $status = $data['status'] ?? 0;
        $expireTime = $data['expire_time'] ?? 0;
        
        if ($status == 0) {
            return '已过期';
        }
        
        if ($expireTime > 0 && $expireTime < time()) {
            return '已过期';
        }
        
        return '有效';
    }

    /**
     * @notes 剩余天数获取器
     * @param $value
     * @param $data
     * @return int
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public function getRemainingDaysAttr($value, $data)
    {
        $expireTime = $data['expire_time'] ?? 0;
        if ($expireTime <= 0) {
            return 0;
        }
        
        $remainingSeconds = $expireTime - time();
        if ($remainingSeconds <= 0) {
            return 0;
        }
        
        return ceil($remainingSeconds / 86400); // 86400 = 24 * 60 * 60
    }

    /**
     * @notes 搜索器 - 代理商ID
     * @param $query
     * @param $value
     * @param $data
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public function searchAgentIdAttr($query, $value, $data)
    {
        if ($value) {
            $query->where('agent_id', $value);
        }
    }

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
     * @notes 搜索器 - 状态
     * @param $query
     * @param $value
     * @param $data
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public function searchStatusAttr($query, $value, $data)
    {
        if ($value !== '' && $value !== null) {
            $query->where('status', $value);
        }
    }

    /**
     * @notes 搜索器 - 有效套餐（未过期且状态为1）
     * @param $query
     * @param $value
     * @param $data
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public function searchValidAttr($query, $value, $data)
    {
        if ($value) {
            $query->where('status', 1)
                  ->where('expire_time', '>', time());
        }
    }

    /**
     * @notes 搜索器 - 时间范围
     * @param $query
     * @param $value
     * @param $data
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public function searchTimeRangeAttr($query, $value, $data)
    {
        if ($value && is_array($value) && count($value) == 2) {
            $startTime = strtotime($value[0]);
            $endTime = strtotime($value[1] . ' 23:59:59');
            $query->whereBetween('assign_time', [$startTime, $endTime]);
        }
    }

    /**
     * @notes 获取租户有效端口总数
     * @param int $tenantId 租户ID
     * @return int
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public static function getTenantValidPorts(int $tenantId): int
    {
        $result = self::where('tenant_id', $tenantId)
                      ->where('status', 1)
                      ->where('expire_time', '>', time())
                      ->sum('port_count');
        return (int)($result ?: 0);
    }

    /**
     * @notes 获取即将过期的端口数（7天内）
     * @param int $tenantId 租户ID
     * @return int
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public static function getTenantExpiringSoonPorts(int $tenantId): int
    {
        $sevenDaysLater = time() + (7 * 24 * 60 * 60);

        $result = self::where('tenant_id', $tenantId)
                      ->where('status', 1)
                      ->where('expire_time', '>', time())
                      ->where('expire_time', '<=', $sevenDaysLater)
                      ->sum('port_count');
        return (int)($result ?: 0);
    }

    /**
     * @notes 检查并更新过期套餐状态
     * @return int 更新的记录数
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public static function updateExpiredPackages(): int
    {
        return self::where('status', 1)
                   ->where('expire_time', '<=', time())
                   ->update(['status' => 0, 'update_time' => time()]);
    }

    /**
     * @notes 获取租户套餐按优先级排序（最早分配的优先）
     * @param int $tenantId 租户ID
     * @param bool $onlyValid 是否只获取有效套餐
     * @return array
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public static function getTenantPackagesByPriority(int $tenantId, bool $onlyValid = true): array
    {
        $query = self::where('tenant_id', $tenantId);

        if ($onlyValid) {
            $query->where('status', 1)
                  ->where('expire_time', '>', time());
        }

        return $query->field('id,port_count,assign_time,expire_time,status')
                     ->order('assign_time', 'asc') // 按分配时间升序，最早的优先
                     ->select()
                     ->toArray();
    }

    /**
     * @notes 计算租户端口分配详情（按套餐优先级）
     * @param int $tenantId 租户ID
     * @param int $usedPorts 已用端口数
     * @return array
     * @author 开发者
     * @date 2025/08/27 10:00
     */
    public static function calculatePortAllocationDetails(int $tenantId, int $usedPorts): array
    {
        $packages = self::getTenantPackagesByPriority($tenantId, true);
        $allocationDetails = [];
        $remainingUsed = $usedPorts;

        foreach ($packages as $package) {
            $packageUsed = min($remainingUsed, $package['port_count']);
            $packageFree = $package['port_count'] - $packageUsed;

            $allocationDetails[] = [
                'package_id' => $package['id'],
                'port_total' => $package['port_count'],
                'port_used' => $packageUsed,
                'port_free' => $packageFree,
                'assign_time' => $package['assign_time'],
                'expire_time' => $package['expire_time'],
                'is_fully_used' => $packageUsed >= $package['port_count']
            ];

            $remainingUsed -= $packageUsed;

            // 如果已用端口已经分配完，后续套餐都是空闲的
            if ($remainingUsed <= 0) {
                break;
            }
        }

        // 如果还有剩余的套餐没有处理，标记为完全空闲
        if ($remainingUsed <= 0) {
            $processedIds = array_column($allocationDetails, 'package_id');
            foreach ($packages as $package) {
                if (!in_array($package['id'], $processedIds)) {
                    $allocationDetails[] = [
                        'package_id' => $package['id'],
                        'port_total' => $package['port_count'],
                        'port_used' => 0,
                        'port_free' => $package['port_count'],
                        'assign_time' => $package['assign_time'],
                        'expire_time' => $package['expire_time'],
                        'is_fully_used' => false
                    ];
                }
            }
        }

        return $allocationDetails;
    }
}
