<?php
declare (strict_types = 1);

namespace app\common\service;

use app\common\model\auth\Admin;
use app\common\model\auth\AdminRole;

/**
 * 租户权限控制服务
 * Class TenantAdminService
 * @package app\common\service
 */
class TenantAdminService
{
    /**
     * 租户角色ID
     */
    const TENANT_ROLE_ID = 2;

    /**
     * @notes 检查是否为租户
     * @param int $adminId 管理员ID
     * @return bool
     * @author 段誉
     * @date 2024/08/24
     */
    public static function isTenant(int $adminId): bool
    {
        // root用户也被视为租户（可以创建运营）
        $admin = Admin::findOrEmpty($adminId);
        if ($admin->isEmpty()) {
            return false;
        }
        
        if ($admin->root == 1) {
            return true;
        }
        
        // 检查是否具有租户角色
        $hasRole = AdminRole::where('admin_id', $adminId)
            ->where('role_id', self::TENANT_ROLE_ID)
            ->find();
        
        return !empty($hasRole);
    }

    /**
     * @notes 验证租户权限
     * @param int $adminId 管理员ID
     * @param string $operation 操作名称（用于错误提示）
     * @return bool
     * @throws \Exception
     * @author 段誉
     * @date 2024/08/24
     */
    public static function validateTenant(int $adminId, string $operation = '此操作'): bool
    {
        if (!self::isTenant($adminId)) {
            throw new \Exception($operation . '只能由租户执行');
        }
        
        return true;
    }

    /**
     * @notes 获取租户角色ID
     * @return int
     * @author 段誉
     * @date 2024/08/24
     */
    public static function getTenantRoleId(): int
    {
        return self::TENANT_ROLE_ID;
    }

    /**
     * @notes 检查管理员是否可以操作运营管理
     * @param int $adminId 管理员ID
     * @return bool
     * @author 段誉
     * @date 2024/08/24
     */
    public static function canManageOperators(int $adminId): bool
    {
        return self::isTenant($adminId);
    }
}
