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

namespace app\adminapi\logic\auth;

use app\common\cache\AdminAuthCache;
use app\common\enum\YesNoEnum;
use app\common\logic\BaseLogic;
use app\common\model\auth\Admin;

use app\common\model\auth\AdminRole;
use app\common\model\auth\AdminSession;
use app\common\cache\AdminTokenCache;
use app\common\service\FileService;
use app\common\service\AdminHierarchyService;
use think\facade\Config;
use think\facade\Db;

/**
 * 代理商逻辑
 * Class AgentLogic
 * @package app\adminapi\logic\auth
 */
class AgentLogic extends BaseLogic
{
    /**
     * @notes 代理角色ID
     * @var int
     */
    private static $agentRoleId = 1;

    /**
     * @notes 添加代理商
     * @param array $params
     * @param int $currentAdminId 当前操作的管理员ID
     * @return bool
     * @author 段誉
     * @date 2021/12/29 10:23
     */
    public static function add(array $params, int $currentAdminId = 0): bool
    {
        Db::startTrans();
        try {
            // 默认将添加者设置为新代理商的上级
            $parentId = $currentAdminId;

            // 如果添加者是root用户，可以设置为顶级（parent_id = 0）
            $currentAdmin = Admin::findOrEmpty($currentAdminId);
            if ($currentAdmin->root == 1) {
                $parentId = 0; // root用户添加的代理商可以是顶级
            }

            $validateResult = AdminHierarchyService::validateParentRelation(0, $parentId, $currentAdminId);
            if ($validateResult !== true) {
                throw new \Exception($validateResult);
            }

            $passwordSalt = Config::get('project.unique_identification');
            $password = create_password($params['password'], $passwordSalt);
            $defaultAvatar = config('project.default_image.admin_avatar');
            $avatar = !empty($params['avatar']) ? FileService::setFileUrl($params['avatar']) : $defaultAvatar;

            $admin = Admin::create([
                'name' => $params['name'],
                'account' => $params['account'],
                'avatar' => $avatar,
                'password' => $password,
                'parent_id' => $parentId,
                'create_time' => time(),
                'disable' => $params['disable'] ?? 0,
                'multipoint_login' => $params['multipoint_login'] ?? 1,
            ]);

            // 自动分配代理角色
            self::insertRole($admin['id'], [self::$agentRoleId]);

            // 清除层级缓存
            AdminHierarchyService::clearCache();

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 编辑代理商
     * @param array $params
     * @param int $currentAdminId 当前操作的管理员ID
     * @return bool
     * @author 段誉
     * @date 2021/12/29 10:43
     */
    public static function edit(array $params, int $currentAdminId = 0): bool
    {
        Db::startTrans();
        try {
            // 权限验证
            if (!AdminHierarchyService::hasPermission($currentAdminId, (int)$params['id'])) {
                throw new \Exception('您没有权限编辑该代理商');
            }

            // 不允许修改上级关系，保持原有的层级结构

            // 基础信息
            $data = [
                'id' => $params['id'],
                'name' => $params['name'],
                'account' => $params['account'],
                'disable' => $params['disable'],
                'multipoint_login' => $params['multipoint_login']
            ];

            // 不修改上级关系

            // 头像
            $data['avatar'] = !empty($params['avatar']) ? FileService::setFileUrl($params['avatar']) : '';

            // 密码
            if (!empty($params['password'])) {
                $passwordSalt = Config::get('project.unique_identification');
                $data['password'] = create_password($params['password'], $passwordSalt);
            }

            // 禁用后设置token过期
            if ($params['disable'] == 1) {
                $tokenArr = AdminSession::where('admin_id', $params['id'])->select()->toArray();
                foreach ($tokenArr as $token) {
                    self::expireToken($token['token']);
                }
            }

            Admin::update($data);
            (new AdminAuthCache($params['id']))->clearAuthCache();

            // 删除旧的关联信息
            AdminRole::delByUserId($params['id']);

            // 重新分配代理角色（确保始终是代理商）
            self::insertRole($params['id'], [self::$agentRoleId]);

            // 清除层级缓存
            AdminHierarchyService::clearCache();

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 删除代理商
     * @param array $params
     * @param int $currentAdminId 当前操作的管理员ID
     * @return bool
     * @author 段誉
     * @date 2021/12/29 10:45
     */
    public static function delete(array $params, int $currentAdminId = 0): bool
    {
        Db::startTrans();
        try {
            // 权限验证
            if (!AdminHierarchyService::hasPermission($currentAdminId, (int)$params['id'])) {
                throw new \Exception('您没有权限删除该代理商');
            }

            $admin = Admin::findOrEmpty($params['id']);
            if ($admin->root == YesNoEnum::YES) {
                throw new \Exception("超级管理员不允许被删除");
            }

            // 检查是否为代理商
            $hasAgentRole = AdminRole::where('admin_id', $params['id'])
                ->where('role_id', self::$agentRoleId)
                ->find();
            if (!$hasAgentRole) {
                throw new \Exception("该管理员不是代理商，无法删除");
            }

            // 检查是否有下级，如果有下级则不能删除
            $subordinates = AdminHierarchyService::getSubordinateIds((int)$params['id']);
            if (!empty($subordinates)) {
                throw new \Exception("该代理商还有下级，无法删除");
            }

            // 使用真实删除而不是软删除
            $admin = Admin::find($params['id']);
            $admin->force()->delete();

            //设置token过期
            $tokenArr = AdminSession::where('admin_id', $params['id'])->select()->toArray();
            foreach ($tokenArr as $token) {
                self::expireToken($token['token']);
            }
            (new AdminAuthCache($params['id']))->clearAuthCache();

            // 删除旧的关联信息
            AdminRole::delByUserId($params['id']);

            // 清除层级缓存
            AdminHierarchyService::clearCache();

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 过期token
     * @param $token
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author 段誉
     * @date 2021/12/29 10:46
     */
    public static function expireToken($token): bool
    {
        $adminSession = AdminSession::where('token', '=', $token)
            ->with('admin')
            ->find();

        if (empty($adminSession)) {
            return false;
        }

        $time = time();
        $adminSession->expire_time = $time;
        $adminSession->update_time = $time;
        $adminSession->save();

        return (new AdminTokenCache())->deleteAdminInfo($token);
    }

    /**
     * @notes 查看代理商详情
     * @param $params
     * @param int $currentAdminId 当前操作的管理员ID
     * @return array
     * @author 段誉
     * @date 2021/12/29 11:07
     */
    public static function detail($params, int $currentAdminId = 0): array
    {
        // 权限验证
        if (!AdminHierarchyService::hasPermission($currentAdminId, (int)$params['id'])) {
            throw new \Exception('您没有权限查看该代理商详情');
        }

        $admin = Admin::field([
            'id', 'account', 'name', 'disable', 'root',
            'multipoint_login', 'avatar', 'parent_id'
        ])->append(['parent_name'])->findOrEmpty($params['id'])->toArray();

        // 检查是否为代理商
        $hasAgentRole = AdminRole::where('admin_id', $params['id'])
            ->where('role_id', self::$agentRoleId)
            ->find();

        if (!$hasAgentRole) {
            throw new \Exception("该管理员不是代理商");
        }

        return $admin;
    }

    /**
     * @notes 新增角色
     * @param $adminId
     * @param $roleIds
     * @throws \Exception
     * @author 段誉
     * @date 2022/11/25 14:23
     */
    public static function insertRole($adminId, $roleIds)
    {
        if (!empty($roleIds)) {
            // 角色
            $roleData = [];
            foreach ($roleIds as $roleId) {
                $roleData[] = [
                    'admin_id' => $adminId,
                    'role_id' => $roleId,
                ];
            }
            (new AdminRole())->saveAll($roleData);
        }
    }


}
