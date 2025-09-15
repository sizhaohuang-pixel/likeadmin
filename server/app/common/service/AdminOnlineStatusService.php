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

namespace app\common\service;

use app\common\model\auth\AdminSession;

/**
 * 管理员在线状态服务类
 * Class AdminOnlineStatusService
 * @package app\common\service
 */
class AdminOnlineStatusService
{
    // 默认活跃时间阈值：10分钟
    const ACTIVE_TIME_THRESHOLD = 600;

    /**
     * @notes 判断单个管理员是否在线
     * @param int $adminId 管理员ID
     * @param int $threshold 活跃时间阈值（秒），默认10分钟
     * @return bool
     * @author Claude
     * @date 2025/09/07
     */
    public static function isOnline($adminId, $threshold = self::ACTIVE_TIME_THRESHOLD)
    {
        $currentTime = time();
        $minActiveTime = $currentTime - $threshold;

        // 查询该管理员是否有活跃的session
        $activeSession = AdminSession::where('admin_id', $adminId)
            ->where('expire_time', '>', $currentTime)  // session未过期
            ->where('update_time', '>=', $minActiveTime)  // 最近活跃
            ->find();

        return !empty($activeSession);
    }

    /**
     * @notes 批量获取多个管理员的在线状态
     * @param array $adminIds 管理员ID数组
     * @param int $threshold 活跃时间阈值（秒）
     * @return array ['admin_id' => bool, ...]
     * @author Claude
     * @date 2025/09/07
     */
    public static function getBatchOnlineStatus($adminIds, $threshold = self::ACTIVE_TIME_THRESHOLD)
    {
        if (empty($adminIds)) {
            return [];
        }

        $currentTime = time();
        $minActiveTime = $currentTime - $threshold;

        // 批量查询活跃的session
        $activeSessions = AdminSession::whereIn('admin_id', $adminIds)
            ->where('expire_time', '>', $currentTime)
            ->where('update_time', '>=', $minActiveTime)
            ->column('admin_id');

        // 构建结果数组
        $result = [];
        foreach ($adminIds as $adminId) {
            $result[$adminId] = in_array($adminId, $activeSessions);
        }

        return $result;
    }

    /**
     * @notes 获取管理员最后活跃时间
     * @param int $adminId 管理员ID
     * @return int|null 最后活跃时间戳，null表示从未活跃
     * @author Claude
     * @date 2025/09/07
     */
    public static function getLastActiveTime($adminId)
    {
        $session = AdminSession::where('admin_id', $adminId)
            ->order('update_time', 'desc')
            ->find();

        return $session ? $session->update_time : null;
    }

    /**
     * @notes 批量获取管理员最后活跃时间
     * @param array $adminIds 管理员ID数组
     * @return array ['admin_id' => timestamp, ...]
     * @author Claude
     * @date 2025/09/07
     */
    public static function getBatchLastActiveTime($adminIds)
    {
        if (empty($adminIds)) {
            return [];
        }

        // 查询每个管理员的最新session
        $sessions = AdminSession::whereIn('admin_id', $adminIds)
            ->field('admin_id, MAX(update_time) as last_active')
            ->group('admin_id')
            ->select()
            ->toArray();

        // 构建结果数组
        $result = [];
        foreach ($sessions as $session) {
            $result[$session['admin_id']] = $session['last_active'];
        }

        // 为没有session的管理员设置null
        foreach ($adminIds as $adminId) {
            if (!isset($result[$adminId])) {
                $result[$adminId] = null;
            }
        }

        return $result;
    }

    /**
     * @notes 格式化活跃时间为可读文本
     * @param int $timestamp 时间戳
     * @return string
     * @author Claude
     * @date 2025/09/07
     */
    public static function formatActiveTime($timestamp)
    {
        if (empty($timestamp)) {
            return '从未活跃';
        }

        $currentTime = time();
        $diff = $currentTime - $timestamp;

        if ($diff < 60) {
            return '刚刚活跃';
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return $minutes . '分钟前活跃';
        } elseif ($diff < 86400) {
            $hours = floor($diff / 3600);
            return $hours . '小时前活跃';
        } else {
            $days = floor($diff / 86400);
            return $days . '天前活跃';
        }
    }

    /**
     * @notes 获取在线状态文本
     * @param bool $isOnline 是否在线
     * @param int $lastActiveTime 最后活跃时间
     * @return string
     * @author Claude
     * @date 2025/09/07
     */
    public static function getOnlineStatusText($isOnline, $lastActiveTime = null)
    {
        if ($isOnline) {
            return '在线';
        } else {
            return self::formatActiveTime($lastActiveTime);
        }
    }

    /**
     * @notes 获取在线管理员数量统计
     * @param int $threshold 活跃时间阈值（秒）
     * @return int
     * @author Claude
     * @date 2025/09/07
     */
    public static function getOnlineCount($threshold = self::ACTIVE_TIME_THRESHOLD)
    {
        $currentTime = time();
        $minActiveTime = $currentTime - $threshold;

        return AdminSession::where('expire_time', '>', $currentTime)
            ->where('update_time', '>=', $minActiveTime)
            ->group('admin_id')
            ->count();
    }
}