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

use app\common\model\auth\Admin;
use app\common\model\auth\AdminRole;

/**
 * 平台管理员权限控制服务
 * Class PlatformAdminService
 * @package app\common\service
 */
class PlatformAdminService
{
    /**
     * 平台管理员角色ID
     */
    const PLATFORM_ADMIN_ROLE_ID = 5;

    /**
     * @notes 检查是否为平台管理员
     * @param int $adminId 管理员ID
     * @return bool
     * @author 段誉
     * @date 2024/08/24
     */
    public static function isPlatformAdmin(int $adminId): bool
    {
        // root用户也被视为平台管理员
        $admin = Admin::findOrEmpty($adminId);
        if ($admin->isEmpty()) {
            return false;
        }
        
        if ($admin->root == 1) {
            return true;
        }
        
        // 检查是否具有平台管理员角色
        $hasRole = AdminRole::where('admin_id', $adminId)
            ->where('role_id', self::PLATFORM_ADMIN_ROLE_ID)
            ->find();
        
        return !empty($hasRole);
    }

    /**
     * @notes 验证平台管理员权限
     * @param int $adminId 管理员ID
     * @param string $operation 操作名称（用于错误提示）
     * @return bool
     * @throws \Exception
     * @author 段誉
     * @date 2024/08/24
     */
    public static function validatePlatformAdmin(int $adminId, string $operation = '此操作'): bool
    {
        if (!self::isPlatformAdmin($adminId)) {
            throw new \Exception($operation . '只能由平台管理员执行');
        }
        
        return true;
    }

    /**
     * @notes 获取平台管理员角色ID
     * @return int
     * @author 段誉
     * @date 2024/08/24
     */
    public static function getPlatformAdminRoleId(): int
    {
        return self::PLATFORM_ADMIN_ROLE_ID;
    }

    /**
     * @notes 检查管理员是否可以操作代理商管理
     * @param int $adminId 管理员ID
     * @return bool
     * @author 段誉
     * @date 2024/08/24
     */
    public static function canManageAgents(int $adminId): bool
    {
        return self::isPlatformAdmin($adminId);
    }

    /**
     * @notes 验证代理商管理权限
     * @param int $adminId 管理员ID
     * @return bool
     * @throws \Exception
     * @author 段誉
     * @date 2024/08/24
     */
    public static function validateAgentManagement(int $adminId): bool
    {
        return self::validatePlatformAdmin($adminId, '代理商管理');
    }
}
