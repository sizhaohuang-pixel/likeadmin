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

namespace app\common\model\auth;

use app\common\enum\YesNoEnum;
use app\common\model\BaseModel;
use app\common\model\dept\Dept;
use think\model\concern\SoftDelete;
use app\common\service\FileService;
use app\common\service\AdminOnlineStatusService;

class Admin extends BaseModel
{
    use SoftDelete;

    protected $deleteTime = 'delete_time';

    protected $append = [
        'role_id',
        'dept_id',
        'jobs_id',
        'parent_name',
        'online_status',
        'last_active_time',
        'online_status_text',
        'account_limit_text',
        'allocated_accounts_count',
    ];


    /**
     * @notes 关联角色id
     * @param $value
     * @param $data
     * @return array
     * @author 段誉
     * @date 2022/11/25 15:00
     */
    public function getRoleIdAttr($value, $data)
    {
        return AdminRole::where('admin_id', $data['id'])->column('role_id');
    }


    /**
     * @notes 关联部门id
     * @param $value
     * @param $data
     * @return array
     * @author 段誉
     * @date 2022/11/25 15:00
     */
    public function getDeptIdAttr($value, $data)
    {
        return AdminDept::where('admin_id', $data['id'])->column('dept_id');
    }


    /**
     * @notes 关联岗位id
     * @param $value
     * @param $data
     * @return array
     * @author 段誉
     * @date 2022/11/25 15:01\
     */
    public function getJobsIdAttr($value, $data)
    {
        return AdminJobs::where('admin_id', $data['id'])->column('jobs_id');
    }



    /**
     * @notes 获取禁用状态
     * @param $value
     * @param $data
     * @return string|string[]
     * @author 令狐冲
     * @date 2021/7/7 01:25
     */
    public function getDisableDescAttr($value, $data)
    {
        return YesNoEnum::getDisableDesc($data['disable']);
    }

    /**
     * @notes 最后登录时间获取器 - 格式化：年-月-日 时:分:秒
     * @param $value
     * @return string
     * @author Tab
     * @date 2021/7/13 11:35
     */
    public function getLoginTimeAttr($value)
    {
        return empty($value) ? '' : date('Y-m-d H:i:s', $value);
    }

    /**
     * @notes 头像获取器 - 头像路径添加域名
     * @param $value
     * @return string
     * @author Tab
     * @date 2021/7/13 11:35
     */
    public function getAvatarAttr($value)
    {
        return empty($value) ? FileService::getFileUrl(config('project.default_image.admin_avatar')) : FileService::getFileUrl(trim($value, '/'));
    }

    /**
     * @notes 获取上级姓名
     * @param $value
     * @param $data
     * @return string
     * @author 段誉
     * @date 2024/08/24
     */
    public function getParentNameAttr($value, $data)
    {
        if (empty($data['parent_id']) || $data['parent_id'] == 0) {
            return '顶级';
        }

        // 使用原生SQL查询，避免ORM可能的问题
        $result = \think\facade\Db::table('la_admin')
            ->where('id', $data['parent_id'])
            ->find();

        if ($result && isset($result['name'])) {
            return $result['name'];
        }

        return '未知';
    }

    /**
     * @notes 获取在线状态
     * @param $value
     * @param $data
     * @return bool
     * @author Claude
     * @date 2025/09/07
     */
    public function getOnlineStatusAttr($value, $data)
    {
        if (!isset($data['id'])) {
            return false;
        }
        
        return AdminOnlineStatusService::isOnline($data['id']);
    }

    /**
     * @notes 获取最后活跃时间
     * @param $value
     * @param $data
     * @return int|null
     * @author Claude
     * @date 2025/09/07
     */
    public function getLastActiveTimeAttr($value, $data)
    {
        if (!isset($data['id'])) {
            return null;
        }
        
        return AdminOnlineStatusService::getLastActiveTime($data['id']);
    }

    /**
     * @notes 获取在线状态文本
     * @param $value
     * @param $data
     * @return string
     * @author Claude
     * @date 2025/09/07
     */
    public function getOnlineStatusTextAttr($value, $data)
    {
        if (!isset($data['id'])) {
            return '未知';
        }
        
        $isOnline = AdminOnlineStatusService::isOnline($data['id']);
        $lastActiveTime = AdminOnlineStatusService::getLastActiveTime($data['id']);
        
        return AdminOnlineStatusService::getOnlineStatusText($isOnline, $lastActiveTime);
    }

    /**
     * @notes 获取账号分配上限文本
     * @param $value
     * @param $data
     * @return string
     * @author Claude
     * @date 2025/09/07
     */
    public function getAccountLimitTextAttr($value, $data)
    {
        if (!isset($data['account_limit'])) {
            return '0';
        }
        
        $limit = $data['account_limit'];
        if ($limit == -1) {
            return '无限制';
        } else if ($limit == 0) {
            return '禁止分配';
        } else {
            return strval($limit);
        }
    }

    /**
     * @notes 获取已分配账号数量
     * @param $value
     * @param $data
     * @return int
     * @author Claude
     * @date 2025/09/07
     */
    public function getAllocatedAccountsCountAttr($value, $data)
    {
        if (!isset($data['id'])) {
            return 0;
        }
        
        // 查询分配给此客服的账号数量
        return \think\facade\Db::table('la_alt_account')
            ->where('operator_id', $data['id'])
            ->whereNull('delete_time')
            ->count();
    }

    /**
     * @notes 检查是否可以分配账号
     * @param int $adminId 管理员ID
     * @param int $requestCount 请求分配的数量，默认为1
     * @return array ['can_assign' => bool, 'message' => string]
     * @author Claude
     * @date 2025/09/07
     */
    public static function canAssignAccounts($adminId, $requestCount = 1, $useLock = false)
    {
        $admin = self::find($adminId);
        if (!$admin) {
            return ['can_assign' => false, 'message' => '客服不存在'];
        }
        
        $limit = $admin['account_limit'];
        
        // 禁止分配
        if ($limit == 0) {
            return ['can_assign' => false, 'message' => '该客服禁止分配账号'];
        }
        
        // 无限制
        if ($limit == -1) {
            return ['can_assign' => true, 'message' => '可以分配'];
        }
        
        // 有限制的情况，检查当前已分配数量
        $query = \think\facade\Db::table('la_alt_account')
            ->where('operator_id', $adminId)
            ->whereNull('delete_time');
            
        // 如果需要锁定，使用悲观锁
        if ($useLock) {
            $query = $query->lock(true);
        }
            
        $currentCount = $query->count();
        
        if ($currentCount + $requestCount > $limit) {
            return ['can_assign' => false, 'message' => "超过分配上限，当前已分配{$currentCount}个，上限{$limit}个"];
        }
        
        return ['can_assign' => true, 'message' => '可以分配'];
    }

}