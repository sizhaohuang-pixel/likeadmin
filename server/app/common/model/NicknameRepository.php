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

declare(strict_types=1);

namespace app\common\model;

use app\common\model\BaseModel;

/**
 * 昵称仓库模型
 * Class NicknameRepository
 * @package app\common\model
 */
class NicknameRepository extends BaseModel
{
    protected $name = 'nickname_repository';
    
    protected $deleteTime = 'delete_time';
    
    /**
     * 状态常量
     */
    const STATUS_AVAILABLE = 1; // 可用
    const STATUS_USED = 0;      // 已使用
    
    /**
     * 获取分组统计信息
     * @param int $tenantId 租户ID
     * @return array
     */
    public static function getGroupStats(int $tenantId): array
    {
        return self::where('tenant_id', $tenantId)
            ->field([
                'group_name',
                'COUNT(CASE WHEN nickname <> \'_placeholder_\' THEN 1 END) as total_count',
                'COUNT(CASE WHEN nickname <> \'_placeholder_\' AND status = ' . self::STATUS_AVAILABLE . ' THEN 1 END) as available_count',
                'MAX(create_time) as latest_create_time'
            ])
            ->group('group_name')
            ->order('latest_create_time', 'desc')
            ->select()
            ->toArray();
    }
    
    /**
     * 获取指定分组的昵称列表
     * @param int $tenantId 租户ID
     * @param string $groupName 分组名称
     * @param int $status 状态筛选，null为全部
     * @return \think\Collection
     */
    public static function getGroupNicknames(int $tenantId, string $groupName, ?int $status = null)
    {
        $query = self::where('tenant_id', $tenantId)
            ->where('group_name', $groupName)
            ->where('nickname', '<>', '_placeholder_');  // 排除占位符记录
            
        if ($status !== null) {
            $query->where('status', $status);
        }
        
        return $query->order('create_time', 'desc');
    }
    
    /**
     * 检查昵称是否已存在（全表范围内检查）
     * @param int $tenantId 租户ID
     * @param string $nickname 昵称
     * @return bool
     */
    public static function nicknameExists(int $tenantId, string $nickname): bool
    {
        return self::where('tenant_id', $tenantId)
            ->where('nickname', $nickname)
            ->count() > 0;
    }
    
    /**
     * 批量插入昵称
     * @param array $data 昵称数据
     * @return int 插入成功的数量
     */
    public static function batchInsert(array $data): int
    {
        if (empty($data)) {
            return 0;
        }
        
        $insertData = [];
        foreach ($data as $item) {
            $insertData[] = [
                'group_name' => $item['group_name'],
                'nickname' => $item['nickname'],
                'tenant_id' => $item['tenant_id'],
                'status' => self::STATUS_AVAILABLE,
                'create_time' => time(),
                'update_time' => time()
            ];
        }
        
        return self::insertAll($insertData) !== false ? count($insertData) : 0;
    }
    
    /**
     * 获取可用昵称数量
     * @param int $tenantId 租户ID
     * @param string $groupName 分组名称
     * @return int
     */
    public static function getAvailableCount(int $tenantId, string $groupName): int
    {
        return self::where('tenant_id', $tenantId)
            ->where('group_name', $groupName)
            ->where('status', self::STATUS_AVAILABLE)
            ->count();
    }
    
    /**
     * 标记昵称为已使用
     * @param int $tenantId 租户ID
     * @param string $groupName 分组名称
     * @param string $nickname 昵称
     * @return bool
     */
    public static function markAsUsed(int $tenantId, string $groupName, string $nickname): bool
    {
        return self::where('tenant_id', $tenantId)
            ->where('group_name', $groupName)
            ->where('nickname', $nickname)
            ->where('status', self::STATUS_AVAILABLE)
            ->update([
                'status' => self::STATUS_USED,
                'update_time' => time()
            ]) > 0;
    }
    
    /**
     * 获取一个可用昵称
     * @param int $tenantId 租户ID
     * @param string $groupName 分组名称
     * @return string|null
     */
    public static function getAvailableNickname(int $tenantId, string $groupName): ?string
    {
        $record = self::where('tenant_id', $tenantId)
            ->where('group_name', $groupName)
            ->where('status', self::STATUS_AVAILABLE)
            ->order('create_time', 'asc')
            ->find();
            
        if ($record) {
            // 标记为已使用
            $record->status = self::STATUS_USED;
            $record->update_time = time();
            $record->save();
            
            return $record->nickname;
        }
        
        return null;
    }
}