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


namespace app\adminapi\controller;


use app\adminapi\controller\BaseAdminController;
use app\adminapi\lists\AltAccountDeleteRecordLists;
use app\adminapi\logic\AltAccountDeleteRecordLogic;
use app\adminapi\validate\AltAccountDeleteRecordValidate;


/**
 * AltAccountDeleteRecord控制器
 * Class AltAccountDeleteRecordController
 * @package app\adminapi\controller
 */
class AltAccountDeleteRecordController extends BaseAdminController
{


    /**
     * @notes 获取删除记录列表
     * @return \think\response\Json
     * @author likeadmin
     * @date 2025/09/06 23:10
     */
    public function lists()
    {
        return $this->dataLists(new AltAccountDeleteRecordLists($this->adminId));
    }


}