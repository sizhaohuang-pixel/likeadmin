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

namespace app\adminapi\logic;

use app\common\model\AltAccountGroup;
use app\common\model\AltAccount;
use app\common\logic\BaseLogic;
use think\facade\Db;

/**
 * AltAccountGroup逻辑
 * Class AltAccountGroupLogic
 * @package app\adminapi\logic
 */
class AltAccountGroupLogic extends BaseLogic
{

    /**
     * @notes 添加分组
     * @param array $params
     * @param int $currentAdminId 当前操作的管理员ID
     * @return bool
     * @author likeadmin
     * @date 2025/08/24 23:53
     */
    public static function add(array $params, int $currentAdminId = 0): bool
    {
        Db::startTrans();
        try {
            AltAccountGroup::create([
                'tenant_id' => $currentAdminId, // 自动设置tenant_id为当前用户ID
                'name' => $params['name'],
                'description' => $params['description'] ?? '',
            ]);

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }


    /**
     * @notes 编辑分组
     * @param array $params
     * @param int $currentAdminId 当前操作的管理员ID
     * @return bool
     * @author likeadmin
     * @date 2025/08/24 23:53
     */
    public static function edit(array $params, int $currentAdminId = 0): bool
    {
        Db::startTrans();
        try {
            // 权限验证：检查分组是否属于当前租户
            $group = AltAccountGroup::findOrEmpty($params['id']);
            if ($group->isEmpty()) {
                throw new \Exception('分组不存在');
            }
            
            if ($group->tenant_id != $currentAdminId) {
                throw new \Exception('您没有权限编辑该分组');
            }
            
            AltAccountGroup::where('id', $params['id'])->update([
                'name' => $params['name'],
                'description' => $params['description'] ?? '',
            ]);

            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }


    /**
     * @notes 删除分组
     * @param array $params
     * @param int $currentAdminId 当前操作的管理员ID
     * @return bool
     * @author likeadmin
     * @date 2025/08/24 23:53
     */
    public static function delete(array $params, int $currentAdminId = 0): bool
    {
        Db::startTrans();
        try {
            // 权限验证：检查分组是否属于当前租户
            $group = AltAccountGroup::findOrEmpty($params['id']);
            if ($group->isEmpty()) {
                self::setError('分组不存在');
                return false;
            }
            
            if ($group->tenant_id != $currentAdminId) {
                self::setError('您没有权限删除该分组');
                return false;
            }
            
            // 将该分组下的小号设置为未分组
            AltAccount::where('group_id', $params['id'])->update(['group_id' => null]);
            
            // 删除分组（物理删除）
            AltAccountGroup::destroy($params['id']);
            
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }


    /**
     * @notes 获取分组详情
     * @param $params
     * @param int $currentAdminId 当前操作的管理员ID
     * @return array
     * @author likeadmin
     * @date 2025/08/24 23:53
     */
    public static function detail($params, int $currentAdminId = 0): array
    {
        // 权限验证：检查分组是否属于当前租户
        $group = AltAccountGroup::findOrEmpty($params['id']);
        if ($group->isEmpty()) {
            throw new \Exception('分组不存在');
        }
        
        if ($group->tenant_id != $currentAdminId) {
            throw new \Exception('您没有权限查看该分组详情');
        }
        
        return $group->append(['alt_account_count'])->toArray();
    }


    /**
     * @notes 批量设置小号分组
     * @param array $params
     * @param int $currentAdminId 当前操作的管理员ID
     * @return bool
     * @author likeadmin
     * @date 2025/08/24 23:53
     */
    public static function batchSetGroup(array $params, int $currentAdminId = 0): bool
    {
        Db::startTrans();
        try {
            $altAccountIds = $params['alt_account_ids'];
            $groupId = $params['group_id'];
            
            // 如果设置分组ID不为0，验证分组是否属于当前租户
            if ($groupId > 0) {
                $group = AltAccountGroup::findOrEmpty($groupId);
                if ($group->isEmpty()) {
                    throw new \Exception('目标分组不存在');
                }
                
                if ($group->tenant_id != $currentAdminId) {
                    throw new \Exception('您没有权限将小号分配到该分组');
                }
            }
            
            // 验证所有小号是否属于当前租户
            foreach ($altAccountIds as $altAccountId) {
                $altAccount = AltAccount::findOrEmpty($altAccountId);
                if ($altAccount->isEmpty()) {
                    throw new \Exception("小号ID {$altAccountId} 不存在");
                }
                
                if ($altAccount->tenant_id != $currentAdminId) {
                    throw new \Exception("您没有权限操作小号ID {$altAccountId}");
                }
            }
            
            // 批量更新小号的分组
            $updateData = ['group_id' => $groupId > 0 ? $groupId : null];
            AltAccount::where('id', 'in', $altAccountIds)->update($updateData);
            
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            self::setError($e->getMessage());
            return false;
        }
    }


    /**
     * @notes 获取分组选项列表（用于下拉选择）
     * @param int $currentAdminId 当前操作的管理员ID
     * @return array
     * @author likeadmin
     * @date 2025/08/24 23:53
     */
    public static function getGroupOptions(int $currentAdminId = 0): array
    {
        try {
            $groups = AltAccountGroup::where('tenant_id', $currentAdminId)
                ->field(['id', 'name'])
                ->order('id', 'asc')
                ->select()
                ->toArray();
            
            // 添加"未分组"选项
            array_unshift($groups, ['id' => 0, 'name' => '未分组']);
            
            return $groups;
        } catch (\Exception $e) {
            self::setError($e->getMessage());
            return [];
        }
    }
}
