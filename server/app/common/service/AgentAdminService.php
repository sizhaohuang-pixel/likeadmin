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
 * 代理商权限控制服务
 * Class AgentAdminService
 * @package app\common\service
 */
class AgentAdminService
{
    /**
     * 代理商角色ID
     */
    const AGENT_ROLE_ID = 1;

    /**
     * @notes 检查是否为代理商
     * @param int $adminId 管理员ID
     * @return bool
     * @author 段誉
     * @date 2024/08/24
     */
    public static function isAgent(int $adminId): bool
    {
        // root用户也被视为代理商（可以创建租户）
        $admin = Admin::findOrEmpty($adminId);
        if ($admin->isEmpty()) {
            return false;
        }
        
        if ($admin->root == 1) {
            return true;
        }
        
        // 检查是否具有代理商角色
        $hasRole = AdminRole::where('admin_id', $adminId)
            ->where('role_id', self::AGENT_ROLE_ID)
            ->find();
        
        return !empty($hasRole);
    }

    /**
     * @notes 验证代理商权限
     * @param int $adminId 管理员ID
     * @param string $operation 操作名称（用于错误提示）
     * @return bool
     * @throws \Exception
     * @author 段誉
     * @date 2024/08/24
     */
    public static function validateAgent(int $adminId, string $operation = '此操作'): bool
    {
        if (!self::isAgent($adminId)) {
            throw new \Exception($operation . '只能由代理商执行');
        }
        
        return true;
    }

    /**
     * @notes 获取代理商角色ID
     * @return int
     * @author 段誉
     * @date 2024/08/24
     */
    public static function getAgentRoleId(): int
    {
        return self::AGENT_ROLE_ID;
    }

    /**
     * @notes 检查管理员是否可以操作租户管理
     * @param int $adminId 管理员ID
     * @return bool
     * @author 段誉
     * @date 2024/08/24
     */
    public static function canManageTenants(int $adminId): bool
    {
        return self::isAgent($adminId);
    }

    /**
     * @notes 验证租户管理权限
     * @param int $adminId 管理员ID
     * @return bool
     * @throws \Exception
     * @author 段誉
     * @date 2024/08/24
     */
    public static function validateTenantManagement(int $adminId): bool
    {
        return self::validateAgent($adminId, '租户管理');
    }
}
