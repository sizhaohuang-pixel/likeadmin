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
use app\adminapi\lists\auth\AgentLists;
use app\adminapi\validate\auth\AgentValidate;
use app\adminapi\logic\auth\AgentLogic;
use app\common\service\PlatformAdminService;
use app\common\service\AdminHierarchyService;

/**
 * 代理商控制器
 * Class AgentController
 * @package app\adminapi\controller\auth
 */
class AgentController extends BaseAdminController
{

    /**
     * @notes 查看代理商列表
     * @return \think\response\Json
     * @author 段誉
     * @date 2021/12/29 9:55
     */
    public function lists()
    {
        return $this->dataLists(new AgentLists());
    }

    /**
     * @notes 添加代理商
     * @return \think\response\Json
     * @author 段誉
     * @date 2021/12/29 10:21
     */
    public function add()
    {
        try {
            // 验证平台管理员权限 - 只有平台管理员才能创建代理商
            PlatformAdminService::validatePlatformAdmin($this->adminId, '创建代理商');

            $params = (new AgentValidate())->post()->goCheck('add');
            $result = AgentLogic::add($params, $this->adminId);
            if (true === $result) {
                return $this->success('操作成功', [], 1, 1);
            }
            return $this->fail(AgentLogic::getError());
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * @notes 编辑代理商
     * @return \think\response\Json
     * @author 段誉
     * @date 2021/12/29 11:03
     */
    public function edit()
    {
        $params = (new AgentValidate())->post()->goCheck('edit');
        $result = AgentLogic::edit($params, $this->adminId);
        if (true === $result) {
            return $this->success('操作成功', [], 1, 1);
        }
        return $this->fail(AgentLogic::getError());
    }

    /**
     * @notes 删除代理商
     * @return \think\response\Json
     * @author 段誉
     * @date 2021/12/29 11:03
     */
    public function delete()
    {
        $params = (new AgentValidate())->post()->goCheck('delete');
        $result = AgentLogic::delete($params, $this->adminId);
        if (true === $result) {
            return $this->success('操作成功', [], 1, 1);
        }
        return $this->fail(AgentLogic::getError());
    }

    /**
     * @notes 查看代理商详情
     * @return \think\response\Json
     * @author 段誉
     * @date 2021/12/29 11:07
     */
    public function detail()
    {
        $params = (new AgentValidate())->goCheck('detail');
        try {
            $result = AgentLogic::detail($params, $this->adminId);
            return $this->data($result);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }
}
