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

namespace app\adminapi\controller\auth;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\auth\TenantLists;
use app\adminapi\validate\auth\TenantValidate;
use app\adminapi\logic\auth\TenantLogic;
use app\common\service\AgentAdminService;
use app\common\service\PortStatisticsService;

/**
 * 租户控制器
 * Class TenantController
 * @package app\adminapi\controller\auth
 */
class TenantController extends BaseAdminController
{
    /**
     * @notes 租户列表
     * @return \think\response\Json
     * @author 段誉
     * @date 2024/08/24
     */
    public function lists()
    {
        return $this->dataLists(new TenantLists());
    }

    /**
     * @notes 添加租户
     * @return \think\response\Json
     * @author 段誉
     * @date 2024/08/24
     */
    public function add()
    {
        try {
            // 验证代理商权限 - 只有代理商才能创建租户
            AgentAdminService::validateAgent($this->adminId, '创建租户');
            
            $params = (new TenantValidate())->post()->goCheck('add');
            $result = TenantLogic::add($params, $this->adminId);
            if (true === $result) {
                return $this->success('操作成功', [], 1, 1);
            }
            return $this->fail(TenantLogic::getError());
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * @notes 编辑租户
     * @return \think\response\Json
     * @author 段誉
     * @date 2024/08/24
     */
    public function edit()
    {
        $params = (new TenantValidate())->post()->goCheck('edit');
        $result = TenantLogic::edit($params, $this->adminId);
        if (true === $result) {
            return $this->success('操作成功', [], 1, 1);
        }
        return $this->fail(TenantLogic::getError());
    }

    /**
     * @notes 删除租户
     * @return \think\response\Json
     * @author 段誉
     * @date 2024/08/24
     */
    public function delete()
    {
        $params = (new TenantValidate())->post()->goCheck('delete');
        $result = TenantLogic::delete($params, $this->adminId);
        if (true === $result) {
            return $this->success('操作成功', [], 1, 1);
        }
        return $this->fail(TenantLogic::getError());
    }

    /**
     * @notes 查看租户详情
     * @return \think\response\Json
     * @author 段誉
     * @date 2024/08/24
     */
    public function detail()
    {
        $params = (new TenantValidate())->goCheck('detail');
        try {
            $result = TenantLogic::detail($params, $this->adminId);
            return $this->data($result);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * @notes 获取当前租户的端口统计信息
     * @return \think\response\Json
     * @author 开发者
     * @date 2025/08/30
     */
    public function portStats()
    {
        try {
            // 检查当前用户是否为租户
            $tenantRoleId = TenantLogic::$tenantRoleId;
            $hasTenantRole = \app\common\model\auth\AdminRole::where('admin_id', $this->adminId)
                ->where('role_id', $tenantRoleId)
                ->find();

            if (!$hasTenantRole) {
                return $this->fail('当前用户不是租户');
            }

            // 获取租户端口统计信息
            $stats = PortStatisticsService::getTenantPortStats($this->adminId);
            
            // 获取最近过期时间
            $nearestExpireTime = \app\common\model\package\PackageAssignment::where('tenant_id', $this->adminId)
                ->where('status', 1)
                ->where('expire_time', '>', time())
                ->order('expire_time', 'asc')
                ->value('expire_time');
            
            $stats['nearest_expire_time'] = $nearestExpireTime ? date('Y-m-d H:i:s', $nearestExpireTime) : '无';

            return $this->data($stats);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }
}
