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

namespace app\adminapi\controller;

use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\AltAccountGroupLists;
use app\adminapi\logic\AltAccountGroupLogic;
use app\adminapi\validate\AltAccountGroupValidate;

/**
 * AltAccountGroup控制器
 * Class AltAccountGroupController
 * @package app\adminapi\controller
 */
class AltAccountGroupController extends BaseAdminController
{

    /**
     * @notes 获取分组列表
     * @return \think\response\Json
     * @author likeadmin
     * @date 2025/08/24 23:53
     */
    public function lists()
    {
        return $this->dataLists(new AltAccountGroupLists($this->adminId));
    }


    /**
     * @notes 添加分组
     * @return \think\response\Json
     * @author likeadmin
     * @date 2025/08/24 23:53
     */
    public function add()
    {
        $params = (new AltAccountGroupValidate())->post()->goCheck('add');
        $result = AltAccountGroupLogic::add($params, $this->adminId);
        if (true === $result) {
            return $this->success('添加成功', [], 1, 1);
        }
        return $this->fail(AltAccountGroupLogic::getError());
    }


    /**
     * @notes 编辑分组
     * @return \think\response\Json
     * @author likeadmin
     * @date 2025/08/24 23:53
     */
    public function edit()
    {
        $params = (new AltAccountGroupValidate())->post()->goCheck('edit');
        $result = AltAccountGroupLogic::edit($params, $this->adminId);
        if (true === $result) {
            return $this->success('编辑成功', [], 1, 1);
        }
        return $this->fail(AltAccountGroupLogic::getError());
    }


    /**
     * @notes 删除分组
     * @return \think\response\Json
     * @author likeadmin
     * @date 2025/08/24 23:53
     */
    public function delete()
    {
        $params = (new AltAccountGroupValidate())->post()->goCheck('delete');
        $result = AltAccountGroupLogic::delete($params, $this->adminId);
        if (true === $result) {
            return $this->success('删除成功', [], 1, 1);
        }
        return $this->fail(AltAccountGroupLogic::getError());
    }


    /**
     * @notes 获取分组详情
     * @return \think\response\Json
     * @author likeadmin
     * @date 2025/08/24 23:53
     */
    public function detail()
    {
        $params = (new AltAccountGroupValidate())->goCheck('detail');
        $result = AltAccountGroupLogic::detail($params, $this->adminId);
        return $this->data($result);
    }


    /**
     * @notes 获取分组选项列表（用于下拉选择）
     * @return \think\response\Json
     * @author likeadmin
     * @date 2025/08/24 23:53
     */
    public function getGroupOptions()
    {
        $result = AltAccountGroupLogic::getGroupOptions($this->adminId);
        return $this->data($result);
    }

}
