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

/**
 * 管理员层级权限控制服务
 * Class AdminHierarchyService
 * @package app\common\service
 */
class AdminHierarchyService
{

    /**
     * @notes 获取管理员的所有下级ID（包括下级的下级）
     * @param int $adminId 管理员ID
     * @param bool $includeself 是否包含自己
     * @return array
     * @author 段誉
     * @date 2024/08/24
     */
    public static function getSubordinateIds(int $adminId, bool $includeself = false): array
    {
        // 直接递归查询所有下级，不使用缓存
        $subordinateIds = self::getSubordinateIdsRecursive($adminId);

        // 确保返回数组
        if (!is_array($subordinateIds)) {
            $subordinateIds = [];
        }

        return $includeself ? array_merge([$adminId], $subordinateIds) : $subordinateIds;
    }

    /**
     * @notes 递归获取下级ID
     * @param int $adminId
     * @param array $visited 已访问的ID，防止循环引用
     * @return array
     * @author 段誉
     * @date 2024/08/24
     */
    private static function getSubordinateIdsRecursive(int $adminId, array $visited = []): array
    {
        // 防止循环引用
        if (in_array($adminId, $visited)) {
            return [];
        }
        
        $visited[] = $adminId;
        $result = [];
        
        // 查询直接下级（需要排除软删除的记录）
        $directSubordinates = Admin::where('parent_id', $adminId)
            ->whereNull('delete_time')
            ->column('id');
        
        foreach ($directSubordinates as $subordinateId) {
            $result[] = $subordinateId;
            // 递归查询下级的下级
            $subSubordinates = self::getSubordinateIdsRecursive($subordinateId, $visited);
            $result = array_merge($result, $subSubordinates);
        }
        
        return array_unique($result);
    }

    /**
     * @notes 检查管理员是否有权限操作目标管理员
     * @param int $operatorId 操作者ID
     * @param int $targetId 目标管理员ID
     * @return bool
     * @author 段誉
     * @date 2024/08/24
     */
    public static function hasPermission(int $operatorId, int $targetId): bool
    {
        // root用户可以操作所有人
        $operator = Admin::findOrEmpty($operatorId);
        if ($operator->isEmpty()) {
            return false;
        }
        
        if ($operator->root == 1) {
            return true;
        }
        
        // 不能操作自己
        if ($operatorId == $targetId) {
            return false;
        }
        
        // 检查目标是否是操作者的下级
        $subordinateIds = self::getSubordinateIds($operatorId);
        return in_array($targetId, $subordinateIds);
    }

    /**
     * @notes 获取管理员可以查看的管理员ID列表
     * @param int $adminId 管理员ID
     * @return array
     * @author 段誉
     * @date 2024/08/24
     */
    public static function getViewableAdminIds(int $adminId): array
    {
        $admin = Admin::findOrEmpty($adminId);
        if ($admin->isEmpty()) {
            return [];
        }
        
        // root用户可以查看所有人（需要排除软删除的记录）
        if ($admin->root == 1) {
            return Admin::whereNull('delete_time')->column('id');
        }
        
        // 普通管理员只能查看自己和下级
        return self::getSubordinateIds($adminId, true);
    }

    /**
     * @notes 获取可以作为某个管理员上级的管理员列表
     * @param int $adminId 管理员ID（0表示新增）
     * @param int $currentUserId 当前操作用户ID
     * @return array
     * @author 段誉
     * @date 2024/08/24
     */
    public static function getAvailableParents(int $adminId, int $currentUserId): array
    {
        $currentUser = Admin::findOrEmpty($currentUserId);
        if ($currentUser->isEmpty()) {
            return [];
        }
        
        // 获取当前用户可以查看的管理员
        $viewableIds = self::getViewableAdminIds($currentUserId);
        
        if (empty($viewableIds)) {
            return [];
        }
        
        $where = [
            ['id', 'in', $viewableIds]
        ];
        
        // 如果是编辑现有管理员，需要排除自己和自己的下级（防止循环引用）
        if ($adminId > 0) {
            $excludeIds = self::getSubordinateIds($adminId, true);
            $where[] = ['id', 'not in', $excludeIds];
        }
        
        return Admin::where($where)
            ->field('id,name,account,root')
            ->order('id', 'asc')
            ->select()
            ->toArray();
    }

    /**
     * @notes 验证上级关系是否合法
     * @param int $adminId 管理员ID
     * @param int $parentId 上级ID
     * @param int $currentUserId 当前操作用户ID
     * @return bool|string true表示合法，字符串表示错误信息
     * @author 段誉
     * @date 2024/08/24
     */
    public static function validateParentRelation(int $adminId, int $parentId, int $currentUserId)
    {
        // 上级ID为0表示顶级，总是合法的
        if ($parentId == 0) {
            return true;
        }
        
        // 检查上级是否存在
        $parent = Admin::findOrEmpty($parentId);
        if ($parent->isEmpty()) {
            return '指定的上级不存在';
        }
        
        // 不能将自己设置为自己的上级
        if ($adminId == $parentId) {
            return '不能将自己设置为自己的上级';
        }
        
        // 如果是编辑现有管理员，检查是否会造成循环引用
        if ($adminId > 0) {
            $subordinateIds = self::getSubordinateIds($adminId);
            if (in_array($parentId, $subordinateIds)) {
                return '不能将下级设置为自己的上级';
            }
        }
        
        // 检查当前用户是否有权限将目标设置为指定上级的下级
        // 如果当前用户就是要设置的上级，则允许
        if ($currentUserId != $parentId && !self::hasPermission($currentUserId, $parentId)) {
            return '您没有权限将管理员设置为该上级的下级';
        }
        
        return true;
    }


    /**
     * @notes 获取管理员的层级路径
     * @param int $adminId 管理员ID
     * @return array 从根到当前管理员的路径
     * @author 段誉
     * @date 2024/08/24
     */
    public static function getHierarchyPath(int $adminId): array
    {
        $path = [];
        $currentId = $adminId;
        $visited = [];
        
        while ($currentId > 0 && !in_array($currentId, $visited)) {
            $visited[] = $currentId;
            $admin = Admin::findOrEmpty($currentId);
            
            if ($admin->isEmpty()) {
                break;
            }
            
            array_unshift($path, [
                'id' => $admin->id,
                'name' => $admin->name,
                'account' => $admin->account,
                'root' => $admin->root
            ]);
            
            $currentId = $admin->parent_id;
        }
        
        return $path;
    }
}
