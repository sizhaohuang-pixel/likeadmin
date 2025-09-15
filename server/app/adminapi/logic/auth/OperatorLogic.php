<?php
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
 * 运营逻辑
 * Class OperatorLogic
 * @package app\adminapi\logic\auth
 */
class OperatorLogic extends BaseLogic
{
    /**
     * 运营角色ID
     */
    public static $operatorRoleId = 4;

    /**
     * @notes 添加运营
     * @param array $params
     * @param int $currentAdminId 当前操作的管理员ID
     * @return bool
     * @author 段誉
     * @date 2024/08/24
     */
    public static function add(array $params, int $currentAdminId = 0): bool
    {
        Db::startTrans();
        try {
            // 默认将添加者设置为新运营的上级
            $parentId = $currentAdminId;
            
            // 如果添加者是root用户，可以设置为顶级（parent_id = 0）
            $currentAdmin = Admin::findOrEmpty($currentAdminId);
            if ($currentAdmin->root == 1) {
                $parentId = 0; // root用户添加的运营可以是顶级
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
                'disable' => YesNoEnum::NO,
                'multipoint_login' => $params['multipoint_login'],
                'account_limit' => $params['account_limit'] ?? 0,
            ]);

            // 分配运营角色
            AdminRole::create([
                'admin_id' => $admin->id,
                'role_id' => self::$operatorRoleId,
            ]);

            // 清除权限缓存
            (new AdminAuthCache($admin->id))->clearAuthCache();

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 编辑运营
     * @param array $params
     * @param int $currentAdminId 当前操作的管理员ID
     * @return bool
     * @author 段誉
     * @date 2024/08/24
     */
    public static function edit(array $params, int $currentAdminId = 0): bool
    {
        Db::startTrans();
        try {
            // 权限验证
            if (!AdminHierarchyService::hasPermission($currentAdminId, (int)$params['id'])) {
                throw new \Exception('您没有权限编辑该运营');
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

            // 账号分配上限处理
            if (isset($params['account_limit'])) {
                $data['account_limit'] = $params['account_limit'];
            }

            // 头像处理
            if (isset($params['avatar'])) {
                $data['avatar'] = !empty($params['avatar']) ? FileService::setFileUrl($params['avatar']) : '';
            }

            // 密码处理
            if (!empty($params['password'])) {
                $passwordSalt = Config::get('project.unique_identification');
                $data['password'] = create_password($params['password'], $passwordSalt);
            }

            Admin::update($data);

            // 如果禁用了运营，清除其登录状态
            if ($params['disable'] == YesNoEnum::YES) {
                AdminSession::where('admin_id', $params['id'])->delete();
                AdminTokenCache::deleteAdminInfo($params['id']);
            }

            // 确保运营角色存在
            $hasOperatorRole = AdminRole::where('admin_id', $params['id'])
                ->where('role_id', self::$operatorRoleId)
                ->find();
            
            if (!$hasOperatorRole) {
                AdminRole::create([
                    'admin_id' => $params['id'],
                    'role_id' => self::$operatorRoleId,
                ]);
            }

            // 清除权限缓存
            (new AdminAuthCache($params['id']))->clearAuthCache();

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 删除运营
     * @param array $params
     * @param int $currentAdminId 当前操作的管理员ID
     * @return bool
     * @author 段誉
     * @date 2024/08/24
     */
    public static function delete(array $params, int $currentAdminId = 0): bool
    {
        Db::startTrans();
        try {
            // 权限验证
            if (!AdminHierarchyService::hasPermission($currentAdminId, (int)$params['id'])) {
                throw new \Exception('您没有权限删除该运营');
            }

            $operatorId = $params['id'];

            // 检查是否有下级管理员
            $hasSubordinates = Admin::where('parent_id', $operatorId)->count();
            if ($hasSubordinates > 0) {
                throw new \Exception('该运营下还有下级管理员，无法删除');
            }

            // 真实删除运营
            Admin::destroy($operatorId, true);

            // 删除角色关联
            AdminRole::where('admin_id', $operatorId)->delete(true);

            // 删除登录会话
            AdminSession::where('admin_id', $operatorId)->delete(true);

            // 清除缓存
            (new AdminTokenCache())->deleteAdminInfo($operatorId);
            (new AdminAuthCache($operatorId))->clearAuthCache();

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }

    /**
     * @notes 查看运营详情
     * @param $params
     * @param int $currentAdminId 当前操作的管理员ID
     * @return array
     * @author 段誉
     * @date 2024/08/24
     */
    public static function detail($params, int $currentAdminId = 0): array
    {
        // 权限验证
        if (!AdminHierarchyService::hasPermission($currentAdminId, (int)$params['id'])) {
            throw new \Exception('您没有权限查看该运营详情');
        }

        $admin = Admin::field([
            'id', 'account', 'name', 'disable', 'root',
            'multipoint_login', 'avatar', 'parent_id', 'account_limit'
        ])->append(['parent_name'])->findOrEmpty($params['id'])->toArray();

        // 检查是否为运营
        $hasOperatorRole = AdminRole::where('admin_id', $params['id'])
            ->where('role_id', self::$operatorRoleId)
            ->find();
        
        if (!$hasOperatorRole) {
            throw new \Exception("该管理员不是运营");
        }

        return $admin;
    }
}
